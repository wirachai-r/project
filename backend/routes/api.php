<?php

use App\Http\Controllers\Api\Admin\AnswerChoiceController as AdminAnswerChoiceController;
use App\Http\Controllers\Api\Admin\ArticleCategoryController as AdminArticleCategoryController;
use App\Http\Controllers\Api\Admin\ArticleCommentController;
use App\Http\Controllers\Api\Admin\ArticleCommentReportController;
use App\Http\Controllers\Api\Admin\ArticleController as AdminArticleController;
use App\Http\Controllers\Api\Admin\DashboardController as AdminDashboardController;
// Client
use App\Http\Controllers\Api\Admin\DiagnosisRuleController as AdminDiagnosisRuleController;
use App\Http\Controllers\Api\Admin\BodyAreaGroupController as AdminBodyAreaGroupController;
use App\Http\Controllers\Api\Admin\DiagramController as AdminDiagramController;
use App\Http\Controllers\Api\Admin\DiseaseCategoryController as AdminDiseaseCategoryController;
use App\Http\Controllers\Api\Admin\DiseaseController as AdminDiseaseController;
use App\Http\Controllers\Api\Admin\FirstAidCategoryController as AdminFirstAidCategoryController;
use App\Http\Controllers\Api\Admin\FirstAidController as AdminFirstAidController;
use App\Http\Controllers\Api\Admin\HealthcareFacilityController as AdminHealthcareFacilityController;
use App\Http\Controllers\Api\Admin\NotificationController as AdminNotificationController;
use App\Http\Controllers\Api\Admin\QuestionBoxController as AdminQuestionBoxController;
use App\Http\Controllers\Api\Admin\SymptomCategoryController as AdminSymptomCategoryController;
use App\Http\Controllers\Api\Admin\SymptomController as AdminSymptomController;
// Admin
use App\Http\Controllers\Api\Admin\TreatmentOrderController as AdminTreatmentOrderController;
use App\Http\Controllers\Api\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\Admin\UserFeedbackController as AdminUserFeedbackController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Client\AccountActivityController as ClientAccountActivityController;
use App\Http\Controllers\Api\Client\ArticleController as ClientArticleController;
use App\Http\Controllers\Api\Client\AssessmentController as ClientAssessmentController;
use App\Http\Controllers\Api\Client\BodyAreaGroupController as ClientBodyAreaGroupController;
use App\Http\Controllers\Api\Client\BookmarkController as ClientBookmarkController;
use App\Http\Controllers\Api\Client\DailyHealthRecordController as ClientDailyHealthRecordController;
use App\Http\Controllers\Api\Client\DiseaseController as ClientDiseaseController;
use App\Http\Controllers\Api\Client\FirstAidController as ClientFirstAidController;
use App\Http\Controllers\Api\Client\HealthcareFacilityController as ClientHealthcareFacilityController;
use App\Http\Controllers\Api\Client\HealthDashboardController as ClientHealthDashboardController;
use App\Http\Controllers\Api\Client\HealthReminderController as ClientHealthReminderController;
use App\Http\Controllers\Api\Client\HealthReportController as ClientHealthReportController;
use App\Http\Controllers\Api\Client\NotificationController as ClientNotificationController;
use App\Http\Controllers\Api\Client\PrivacyController as ClientPrivacyController;
use App\Http\Controllers\Api\Client\ProfileController as ClientProfileController;
use App\Http\Controllers\Api\Client\SessionController as ClientSessionController;
use App\Http\Controllers\Api\Client\SymptomController as ClientSymptomController;
use App\Http\Controllers\Api\Client\SymptomFollowUpController as ClientSymptomFollowUpController;
use App\Http\Controllers\Api\Client\UnifiedSearchController as ClientUnifiedSearchController;
use App\Http\Controllers\Api\Client\UserFeedbackController as ClientUserFeedbackController;
use App\Http\Controllers\Api\ImageUploadController;
use App\Http\Controllers\Api\PasswordOtpController;
use App\Http\Controllers\Api\PublicMediaController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('google', [AuthController::class, 'googleLogin']);
    Route::post('forgot-password', [PasswordOtpController::class, 'request'])->middleware('throttle:5,1');
    Route::post('verify-password-otp', [PasswordOtpController::class, 'verify'])->middleware('throttle:10,1');
    Route::post('reset-password', [PasswordOtpController::class, 'reset'])->middleware('throttle:10,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });
});

Route::get('media/{path}', [PublicMediaController::class, 'show'])
    ->where('path', '.*');

// --- Client (Public) ---
Route::get('symptom-categories', [ClientSymptomController::class, 'categories']);
Route::get('symptoms', [ClientSymptomController::class, 'index']);
Route::get('symptoms/{mainSymptom}', [ClientSymptomController::class, 'show']);
Route::get('body-area-groups', [ClientBodyAreaGroupController::class, 'index']);
Route::get('body-area-groups/{bodyAreaGroup}/symptoms', [ClientBodyAreaGroupController::class, 'symptoms']);

Route::get('disease-categories', [ClientDiseaseController::class, 'categories']);
Route::get('diseases', [ClientDiseaseController::class, 'index']);
Route::get('diseases/{disease}', [ClientDiseaseController::class, 'show']);

Route::get('article-categories', [ClientArticleController::class, 'categories']);
Route::get('articles', [ClientArticleController::class, 'index']);
Route::get('articles/{article}', [ClientArticleController::class, 'show']);
Route::get('articles/{article}/comments', [ClientArticleController::class, 'comments']);

Route::get('first-aid-categories', [ClientFirstAidController::class, 'categories']);
Route::get('first-aids', [ClientFirstAidController::class, 'index']);
Route::get('first-aids/offline', [ClientFirstAidController::class, 'offlineBundle']);
Route::get('first-aids/{firstAid}', [ClientFirstAidController::class, 'show']);

Route::get('healthcare-facilities', [ClientHealthcareFacilityController::class, 'index']);
Route::get('healthcare-facilities/{healthcareFacility}', [ClientHealthcareFacilityController::class, 'show']);
Route::get('search', ClientUnifiedSearchController::class);

// --- Client (Authenticated) ---
Route::middleware('auth:sanctum')->group(function () {
    Route::post('assessments/start', [ClientAssessmentController::class, 'start']);
    Route::post('assessments/{assessment}/answer', [ClientAssessmentController::class, 'answer']);
    Route::post('assessments/{assessment}/continue', [ClientAssessmentController::class, 'continueAssessment']);
    Route::get('assessments/{assessment}/result', [ClientAssessmentController::class, 'result']);
    Route::post('assessments/{assessment}/save', [ClientAssessmentController::class, 'save']);

    Route::get('profile', [ClientProfileController::class, 'show']);
    Route::put('profile', [ClientProfileController::class, 'update']);
    Route::put('profile/password', [ClientProfileController::class, 'changePassword']);
    Route::get('privacy/export', [ClientPrivacyController::class, 'export']);
    Route::delete('privacy/account', [ClientPrivacyController::class, 'destroy']);
    Route::get('sessions', [ClientSessionController::class, 'index']);
    Route::get('account-activities', [ClientAccountActivityController::class, 'index']);
    Route::delete('sessions/others', [ClientSessionController::class, 'destroyOthers']);
    Route::delete('sessions/{session}', [ClientSessionController::class, 'destroy'])->whereNumber('session');
    Route::apiResource('health-reminders', ClientHealthReminderController::class)
        ->parameters(['health-reminders' => 'reminder'])
        ->except(['show']);
    Route::get('health-report', [ClientHealthReportController::class, 'download']);
    Route::get('feedback', [ClientUserFeedbackController::class, 'index']);
    Route::post('feedback', [ClientUserFeedbackController::class, 'store']);

    Route::get('notifications', [ClientNotificationController::class, 'index']);
    Route::get('notifications/unread-count', [ClientNotificationController::class, 'unreadCount']);
    Route::patch('notifications/{notification}/read', [ClientNotificationController::class, 'markAsRead']);
    Route::patch('notifications/{notification}/dismiss', [ClientNotificationController::class, 'dismiss']);
    Route::patch('notifications/{notification}/restore', [ClientNotificationController::class, 'restore']);
    Route::post('notifications/read-all', [ClientNotificationController::class, 'markAllAsRead']);

    Route::get('bookmarks', [ClientBookmarkController::class, 'index']);
    Route::post('bookmarks', [ClientBookmarkController::class, 'store']);
    Route::delete('bookmarks/{bookmark}', [ClientBookmarkController::class, 'destroy']);
    Route::get('health-dashboard', [ClientHealthDashboardController::class, 'show']);
    Route::get('daily-health-records', [ClientDailyHealthRecordController::class, 'index']);
    Route::post('daily-health-records', [ClientDailyHealthRecordController::class, 'store']);
    Route::get('assessments/{assessment}/follow-ups', [ClientSymptomFollowUpController::class, 'index']);
    Route::post('assessments/{assessment}/follow-ups', [ClientSymptomFollowUpController::class, 'store']);
    Route::delete('follow-ups/{followUp}', [ClientSymptomFollowUpController::class, 'destroy']);

    Route::get('assessments', [ClientAssessmentController::class, 'history']);
    Route::get('assessments/{assessment}', [ClientAssessmentController::class, 'show']);

    Route::post('articles/{article}/view', [ClientArticleController::class, 'recordView']);
    Route::get('articles/{article}/engagement', [ClientArticleController::class, 'engagement']);
    Route::post('articles/{article}/like', [ClientArticleController::class, 'toggleLike']);
    Route::post('articles/{article}/comments', [ClientArticleController::class, 'storeComment']);
    Route::delete('article-comments/{comment}', [ClientArticleController::class, 'destroyComment']);
    Route::post('article-comments/{comment}/like', [ClientArticleController::class, 'toggleCommentLike']);
    Route::post('article-comments/{comment}/report', [ClientArticleController::class, 'reportComment']);

    Route::post('uploads/image', [ImageUploadController::class, 'upload']);
    Route::delete('uploads/image', [ImageUploadController::class, 'destroy']);
});

// --- Admin ---
Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('article-comments', [ArticleCommentController::class, 'index']);
    Route::patch('article-comments/{comment}/visibility', [ArticleCommentController::class, 'updateVisibility']);
    Route::delete('article-comments/{comment}', [ArticleCommentController::class, 'destroy']);
    Route::get('feedback', [AdminUserFeedbackController::class, 'index']);
    Route::patch('feedback/{feedback}', [AdminUserFeedbackController::class, 'update']);
    Route::get('article-comment-reports', [ArticleCommentReportController::class, 'index']);
    Route::patch('article-comment-reports/{report}/resolve', [ArticleCommentReportController::class, 'resolve']);
    Route::get('users/stats', [AdminUserController::class, 'stats']);
    Route::apiResource('users', AdminUserController::class);
    Route::patch('users/{user}/ban', [AdminUserController::class, 'ban']);
    Route::patch('users/{user}/unban', [AdminUserController::class, 'unban']);

    Route::get('dashboard/stats', [AdminDashboardController::class, 'stats']);

    Route::apiResource('symptom-categories', AdminSymptomCategoryController::class);
    Route::apiResource('symptoms', AdminSymptomController::class);
    Route::patch('body-area-groups/reorder', [AdminBodyAreaGroupController::class, 'reorder']);
    Route::apiResource('body-area-groups', AdminBodyAreaGroupController::class);

    Route::apiResource('diagrams', AdminDiagramController::class);
    Route::apiResource('diagrams.question-boxes', AdminQuestionBoxController::class)
        ->scoped(['questionBox' => 'box_id']);
    Route::apiResource('question-boxes.answer-choices', AdminAnswerChoiceController::class)
        ->scoped(['answerChoice' => 'choice_id']);
    Route::apiResource('disease-categories', AdminDiseaseCategoryController::class);
    Route::apiResource('diseases', AdminDiseaseController::class);
    Route::apiResource('diseases.treatment-orders', AdminTreatmentOrderController::class)
        ->scoped(['treatmentOrder' => 'order_id']);
    Route::apiResource('diagnosis-rules', AdminDiagnosisRuleController::class);

    Route::apiResource('article-categories', AdminArticleCategoryController::class);
    Route::apiResource('articles', AdminArticleController::class);

    Route::apiResource('first-aid-categories', AdminFirstAidCategoryController::class);
    Route::apiResource('first-aids', AdminFirstAidController::class);

    Route::apiResource('healthcare-facilities', AdminHealthcareFacilityController::class);
    Route::patch('notifications/{notification}/mark-as-read', [AdminNotificationController::class, 'markAsRead']);
    Route::apiResource('notifications', AdminNotificationController::class);
});
