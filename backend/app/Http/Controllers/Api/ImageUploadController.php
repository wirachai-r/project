<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

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
        $image = $manager->read($request->file('image')->getRealPath());

        $maxWidth = $folder === 'profiles' ? 500 : 1200;
        $image->scaleDown(width: $maxWidth);

        $filename = $folder === 'profiles'
            ? $folder.'/'.$user->getKey().'/'.Str::uuid().'.webp'
            : $folder.'/'.Str::uuid().'.webp';
        $encoded = $image->toWebp(quality: 80);

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('public');
        $disk->put($filename, (string) $encoded);

        $relativeUrl = '/storage/'.$filename;

        return response()->json([
            'url' => rtrim($request->getSchemeAndHttpHost(), '/').$relativeUrl,
            'relative_url' => $relativeUrl,
            'path' => $filename,
        ], 201);
    }

    private function ownsProfileImage(User $user, string $path): bool
    {
        if (Str::startsWith($path, 'profiles/'.$user->getKey().'/')) {
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
        $disk = Storage::disk('public');

        abort_unless($disk->exists($request->path), 404, 'ไม่พบไฟล์ที่ต้องการลบ');

        $disk->delete($request->path);

        return response()->json(['message' => 'ลบรูปภาพสำเร็จ']);
    }
}
