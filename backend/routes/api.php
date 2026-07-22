<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ImageUploadController;

// Client
use App\Http\Controllers\Api\Client\SymptomController as ClientSymptomController;
use App\Http\Controllers\Api\Client\DiseaseController as ClientDiseaseController;
use App\Http\Controllers\Api\Client\ArticleController as ClientArticleController;
use App\Http\Controllers\Api\Client\FirstAidController as ClientFirstAidController;
use App\Http\Controllers\Api\Client\HealthcareFacilityController as ClientHealthcareFacilityController;
use App\Http\Controllers\Api\Client\AssessmentController as ClientAssessmentController;
use App\Http\Controllers\Api\Client\ProfileController as ClientProfileController;
use App\Http\Controllers\Api\Client\NotificationController as ClientNotificationController;
use App\Http\Controllers\Api\Client\BookmarkController as ClientBookmarkController;

// Admin
use App\Http\Controllers\Api\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Api\Admin\SymptomCategoryController as AdminSymptomCategoryController;
use App\Http\Controllers\Api\Admin\SymptomController as AdminSymptomController;
use App\Http\Controllers\Api\Admin\DiagramController as AdminDiagramController;
use App\Http\Controllers\Api\Admin\QuestionBoxController as AdminQuestionBoxController;
use App\Http\Controllers\Api\Admin\AnswerChoiceController as AdminAnswerChoiceController;
use App\Http\Controllers\Api\Admin\DiseaseCategoryController as AdminDiseaseCategoryController;
use App\Http\Controllers\Api\Admin\DiseaseController as AdminDiseaseController;
use App\Http\Controllers\Api\Admin\TreatmentOrderController as AdminTreatmentOrderController;
use App\Http\Controllers\Api\Admin\DiagnosisRuleController as AdminDiagnosisRuleController;
use App\Http\Controllers\Api\Admin\ArticleCategoryController as AdminArticleCategoryController;
use App\Http\Controllers\Api\Admin\ArticleController as AdminArticleController;
use App\Http\Controllers\Api\Admin\FirstAidCategoryController as AdminFirstAidCategoryController;
use App\Http\Controllers\Api\Admin\FirstAidController as AdminFirstAidController;
use App\Http\Controllers\Api\Admin\HealthcareFacilityController as AdminHealthcareFacilityController;
use App\Http\Controllers\Api\Admin\NotificationController as AdminNotificationController;

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login',    [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me',      [AuthController::class, 'me']);
    });
});

// --- Client (Public) ---
Route::get('symptom-categories', [ClientSymptomController::class, 'categories']);
Route::get('symptoms', [ClientSymptomController::class, 'index']);
Route::get('symptoms/{mainSymptom}', [ClientSymptomController::class, 'show']);

Route::get('disease-categories', [ClientDiseaseController::class, 'categories']);
Route::get('diseases', [ClientDiseaseController::class, 'index']);
Route::get('diseases/{disease}', [ClientDiseaseController::class, 'show']);

Route::get('article-categories', [ClientArticleController::class, 'categories']);
Route::get('articles', [ClientArticleController::class, 'index']);
Route::get('articles/{article}', [ClientArticleController::class, 'show']);

Route::get('first-aid-categories', [ClientFirstAidController::class, 'categories']);
Route::get('first-aids', [ClientFirstAidController::class, 'index']);
Route::get('first-aids/{firstAid}', [ClientFirstAidController::class, 'show']);

Route::get('healthcare-facilities', [ClientHealthcareFacilityController::class, 'index']);
Route::get('healthcare-facilities/{healthcareFacility}', [ClientHealthcareFacilityController::class, 'show']);

// --- Client (Authenticated) ---
Route::middleware('auth:sanctum')->group(function () {
    Route::post('assessments/start', [ClientAssessmentController::class, 'start']);
    Route::post('assessments/{assessment}/answer', [ClientAssessmentController::class, 'answer']);
    Route::get('assessments/{assessment}/result', [ClientAssessmentController::class, 'result']);

    Route::get('profile', [ClientProfileController::class, 'show']);
    Route::put('profile', [ClientProfileController::class, 'update']);

    Route::get('notifications', [ClientNotificationController::class, 'index']);
    Route::get('notifications/unread-count', [ClientNotificationController::class, 'unreadCount']);
    Route::patch('notifications/{notification}/read', [ClientNotificationController::class, 'markAsRead']);
    Route::post('notifications/read-all', [ClientNotificationController::class, 'markAllAsRead']);

    Route::get('bookmarks', [ClientBookmarkController::class, 'index']);
    Route::post('bookmarks', [ClientBookmarkController::class, 'store']);
    Route::delete('bookmarks/{bookmark}', [ClientBookmarkController::class, 'destroy']);

    Route::get('assessments', [ClientAssessmentController::class, 'history']);
    Route::get('assessments/{assessment}', [ClientAssessmentController::class, 'show']);

    Route::post('articles/{article}/view', [ClientArticleController::class, 'recordView']);

    Route::post('uploads/image', [ImageUploadController::class, 'upload']);
    Route::delete('uploads/image', [ImageUploadController::class, 'destroy']);
});

// --- Admin ---
Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('users/stats', [AdminUserController::class, 'stats']);
    Route::apiResource('users', AdminUserController::class);
    Route::patch('users/{user}/ban',   [AdminUserController::class, 'ban']);
    Route::patch('users/{user}/unban', [AdminUserController::class, 'unban']);
    Route::get('dashboard/stats', [AdminDashboardController::class, 'stats']);

    Route::apiResource('symptom-categories', AdminSymptomCategoryController::class);
    Route::apiResource('symptoms', AdminSymptomController::class);
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
    Route::apiResource('notifications', AdminNotificationController::class);
});
