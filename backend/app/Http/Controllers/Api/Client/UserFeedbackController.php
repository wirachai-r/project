<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Client\StoreUserFeedbackRequest;
use App\Models\Article;
use App\Models\Assessment;
use App\Models\Disease;
use App\Models\FirstAid;
use App\Models\MainSymptom;
use App\Models\UserFeedback;
use App\Support\ImageStorage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UserFeedbackController extends Controller
{
    private const TARGET_MODELS = [
        'article' => Article::class,
        'disease' => Disease::class,
        'symptom' => MainSymptom::class,
        'first_aid' => FirstAid::class,
        'assessment' => Assessment::class,
    ];

    public function index(Request $request): JsonResponse
    {
        return response()->json(['data' => UserFeedback::query()
            ->where('user_id', $request->user()->user_id)
            ->latest()
            ->limit(50)
            ->get()]);
    }

    public function store(StoreUserFeedbackRequest $request): JsonResponse
    {
        $validated = $request->validated();
        if ($validated['feedback_type'] !== 'general') {
            $this->findTarget($validated['target_type'], $validated['target_id'], $request);
        }

        $attachments = collect($request->file('attachments', []))->map(fn ($image) => $image->storeAs(
            'feedbacks',
            Str::uuid().'.'.strtolower($image->extension() ?: 'jpg'),
            ImageStorage::diskName(),
        ))->values()->all();

        $feedback = UserFeedback::create([
            ...$validated,
            'attachments' => $attachments,
            'user_id' => $request->user()->user_id,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'ส่งข้อมูลเรียบร้อยแล้ว ขอบคุณที่ช่วยปรับปรุงระบบ',
            'data' => $feedback,
        ], 201);
    }

    public function attachment(Request $request, UserFeedback $feedback, int $index)
    {
        abort_unless($feedback->user_id === $request->user()->user_id, 403);
        $path = data_get($feedback->attachments, $index);
        abort_unless(is_string($path) && ImageStorage::disk()->exists($path), 404);

        return ImageStorage::disk()->response($path);
    }

    private function findTarget(string $type, string $id, Request $request): Model
    {
        $model = self::TARGET_MODELS[$type]::query()->findOrFail($id);
        if ($model instanceof Assessment) {
            abort_unless($model->user_id === $request->user()->user_id, 403);
        }

        return $model;
    }
}
