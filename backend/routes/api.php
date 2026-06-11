<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Admin\SymptomCategoryController;
use App\Http\Controllers\Api\Admin\SymptomController;
use App\Http\Controllers\Api\Admin\DiagramController;
use App\Http\Controllers\Api\Admin\QuestionBoxController;
use App\Http\Controllers\Api\Admin\AnswerChoiceController;
use App\Http\Controllers\Api\Admin\DiseaseCategoryController;
use App\Http\Controllers\Api\Admin\DiseaseController;
use App\Http\Controllers\Api\Admin\TreatmentOrderController;
use App\Http\Controllers\Api\Admin\DiagnosisRuleController;
use App\Http\Controllers\Api\Admin\ArticleCategoryController;
use App\Http\Controllers\Api\Admin\ArticleController;
use App\Http\Controllers\Api\Admin\FirstAidCategoryController;
use App\Http\Controllers\Api\Admin\FirstAidController;
use App\Http\Controllers\Api\Admin\HealthcareFacilityController;

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login',    [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me',      [AuthController::class, 'me']);
    });
});

Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::apiResource('symptom-categories', SymptomCategoryController::class);
    Route::apiResource('symptoms', SymptomController::class);
    Route::apiResource('diagrams', DiagramController::class);
    Route::apiResource('diagrams.question-boxes', QuestionBoxController::class);
    Route::apiResource('question-boxes.answer-choices', AnswerChoiceController::class);
    Route::apiResource('disease-categories', DiseaseCategoryController::class);
    Route::apiResource('diseases', DiseaseController::class);
    Route::apiResource('diseases.treatment-orders', TreatmentOrderController::class);
    Route::apiResource('diagnosis-rules', DiagnosisRuleController::class);
    Route::apiResource('article-categories', ArticleCategoryController::class);
    Route::apiResource('articles', ArticleController::class);
    Route::apiResource('first-aid-categories', FirstAidCategoryController::class);
    Route::apiResource('first-aids', FirstAidController::class);
    Route::apiResource('healthcare-facilities', HealthcareFacilityController::class);
});
