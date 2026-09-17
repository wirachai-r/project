<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ImageStorage;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Exceptions\DecoderException;
use Intervention\Image\ImageManager;
use Throwable;

/**
 * @tags ImageUploadController
 */
class ImageUploadController extends Controller
{
    private const ALLOWED_FOLDERS = ['diseases', 'articles', 'first_aids', 'notifications', 'profiles'];

    private const ADMIN_ONLY_FOLDERS = ['diseases', 'articles', 'first_aids', 'notifications'];

    // รูปใหม่ใช้ {folder}/{uuid}.webp และยังยอมรับโครงสร้างปี/เดือนเดิมตอนลบไฟล์เก่า
    // กัน path traversal (../) และ path ที่ไม่ได้มาจากระบบ
    private const PATH_PATTERN = '/^[a-z_]+\/(?:[A-Za-z0-9\-]+\/)?(?:\d{4}\/\d{2}\/)?[a-f0-9\-]+\.webp$/';

    public function upload(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
            'folder' => 'nullable|string|in:'.implode(',', self::ALLOWED_FOLDERS),
        ]);

        $folder = $request->folder ?? 'profiles';
        $user = $request->user();

        // User ทั่วไปอัปโหลดได้แค่ profiles ของตัวเอง
        if ($user->role !== 'Admin' && in_array($folder, self::ADMIN_ONLY_FOLDERS)) {
            abort(403, 'ไม่มีสิทธิ์อัปโหลดรูปภาพประเภทนี้');
        }

        $manager = new ImageManager(new Driver);
        try {
            $image = $manager->read($request->file('image')->getRealPath());
        } catch (DecoderException $exception) {
            report($exception);

            return response()->json([
                'message' => 'ไม่สามารถอ่านรูปภาพนี้ได้ กรุณาเลือกรูป JPG, PNG หรือ WebP ไฟล์อื่น',
            ], 422);
        }

        try {
            $maxWidth = $folder === 'profiles' ? 500 : 1200;
            $image->scaleDown(width: $maxWidth);
            $encoded = $image->toWebp(quality: 80);
        } catch (Throwable $exception) {
            report($exception);
            Log::error('Image processing failure', [
                'folder' => $folder,
                'mime_type' => $request->file('image')->getMimeType(),
                'exception' => $exception::class,
            ]);

            return response()->json([
                'message' => 'เซิร์ฟเวอร์ไม่สามารถแปลงรูปภาพได้ กรุณาตรวจสอบการรองรับ WebP',
            ], 503);
        }

        $filename = $folder === 'profiles'
            ? $folder.'/'.$user->getKey().'-'.Str::uuid().'.webp'
            : $folder.'/'.Str::uuid().'.webp';

        /** @var FilesystemAdapter $disk */
        $disk = ImageStorage::disk();
        try {
            $stored = $disk->put($filename, (string) $encoded);
        } catch (Throwable $exception) {
            report($exception);
            Log::error('Image upload storage failure', [
                'disk' => ImageStorage::diskName(),
                'folder' => $folder,
                'exception' => $exception::class,
            ]);

            return response()->json([
                'message' => 'ไม่สามารถบันทึกรูปภาพไปยังพื้นที่จัดเก็บได้ กรุณาตรวจสอบการตั้งค่า Storage',
            ], 503);
        }

        if (! $stored) {
            Log::error('Image upload storage returned false', [
                'disk' => ImageStorage::diskName(),
                'folder' => $folder,
            ]);

            return response()->json([
                'message' => 'ไม่สามารถบันทึกรูปภาพไปยังพื้นที่จัดเก็บได้ กรุณาตรวจสอบการตั้งค่า Storage',
            ], 503);
        }

        try {
            $url = $disk->url($filename);

            return response()->json([
                'url' => $url,
                'relative_url' => '/storage/'.$filename,
                'path' => $filename,
            ], 201);
        } catch (Throwable $exception) {
            // A malformed cloud-disk URL must not leave an orphaned upload or
            // escape Laravel's JSON API error handling as an opaque HTTP 500.
            $disk->delete($filename);
            report($exception);
            Log::error('Image upload URL generation failure', [
                'disk' => ImageStorage::diskName(),
                'folder' => $folder,
                'exception' => $exception::class,
            ]);

            return response()->json([
                'message' => 'ไม่สามารถสร้าง URL ของรูปภาพได้ กรุณาตรวจสอบการตั้งค่า Storage',
            ], 503);
        }
    }

    private function ownsProfileImage(User $user, string $path): bool
    {
        if (Str::startsWith($path, [
            'profiles/'.$user->getKey().'-',
            'profiles/'.$user->getKey().'/',
        ])) {
            return true;
        }

        $storedPath = (string) $user->profile_image;

        if (filter_var($storedPath, FILTER_VALIDATE_URL)) {
            $storedPath = (string) parse_url($storedPath, PHP_URL_PATH);
        }

        return ltrim(Str::after($storedPath, '/storage/'), '/') === $path;
    }

    public function destroy(Request $request)
    {
        $request->validate([
            'path' => ['required', 'string', 'regex:'.self::PATH_PATTERN],
        ]);

        $allowedPrefixes = array_map(fn ($f) => $f.'/', self::ALLOWED_FOLDERS);
        abort_unless(Str::startsWith($request->path, $allowedPrefixes), 422);

        $user = $request->user();
        $folder = explode('/', $request->path)[0];

        if ($user->role !== 'Admin' && $folder === 'profiles') {
            abort_unless($this->ownsProfileImage($user, $request->path), 403);
        }

        if ($user->role !== 'Admin' && in_array($folder, self::ADMIN_ONLY_FOLDERS)) {
            abort(403, 'ไม่มีสิทธิ์ลบรูปภาพประเภทนี้');
        }

        /** @var FilesystemAdapter $disk */
        $disk = ImageStorage::disk();

        abort_unless($disk->exists($request->path), 404, 'ไม่พบไฟล์ที่ต้องการลบ');

        $disk->delete($request->path);

        return response()->json(['message' => 'ลบรูปภาพสำเร็จ']);
    }
}
