<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DiseaseRequest;
use App\Http\Resources\Admin\DiseaseResource;
use App\Models\Disease;
use App\Support\AdminTableQuery;
use App\Support\ContentImageStorage;
use App\Support\NotificationContent;
use Illuminate\Http\Request;

/**
 * @tags Admin DiseaseController
 */
class DiseaseController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min(max($request->integer('per_page', 20), 1), 100);

        $diseases = Disease::query()
            ->with('category')
            ->withCount('symptoms')
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->disease_category_id, fn ($q) => $q->where('disease_category_id', $request->disease_category_id))
            ->when($request->filled('disease_category_ids'), fn ($q) => $q->whereIn(
                'disease_category_id',
                array_filter((array) $request->input('disease_category_ids')),
            ))
            ->tap(fn ($q) => AdminTableQuery::fuzzySearch($q, $request->search, 'disease_id', ['disease_name', 'disease_name_en']))
            ->when(
                in_array($request->sort_by, ['id', 'name', 'updated_at']),
                function ($q) use ($request) {
                    $direction = $request->sort_direction === 'asc' ? 'asc' : 'desc';

                    if ($request->sort_by === 'id') {
                        $q->orderBy('disease_id', $direction);
                    } elseif ($request->sort_by === 'name') {
                        $q->orderBy('disease_name', $direction);
                    } elseif ($request->sort_by === 'updated_at') {
                        $q->orderBy('updated_at', $direction);
                    }
                },
                fn ($q) => $q->orderBy('disease_id', 'desc')
            )
            ->orderBy('disease_id', 'desc')
            ->paginate($perPage);

        return DiseaseResource::collection($diseases);
    }

    public function store(DiseaseRequest $request)
    {
        $disease = Disease::create([
            'disease_id' => $this->generateId(),
            'disease_name' => $request->disease_name,
            'disease_name_en' => $request->disease_name_en,
            'description' => NotificationContent::normalizeImageUrls($request->description),
            'cause' => NotificationContent::normalizeImageUrls($request->cause),
            'symptom_description' => NotificationContent::normalizeImageUrls($request->symptom_description),
            'complications' => NotificationContent::normalizeImageUrls($request->complications),
            'diagnosis' => NotificationContent::normalizeImageUrls($request->diagnosis),
            'medical_treatment' => NotificationContent::normalizeImageUrls($request->medical_treatment),
            'self_care' => NotificationContent::normalizeImageUrls($request->self_care),
            'when_to_see_doctor' => NotificationContent::normalizeImageUrls($request->when_to_see_doctor),
            'prevention' => NotificationContent::normalizeImageUrls($request->prevention),
            'recommendations' => NotificationContent::normalizeImageUrls($request->recommendations),
            'disease_image' => $request->disease_image,
            'status' => $request->status ?? '1',
            'disease_category_id' => $request->disease_category_id,
            'references' => $request->input('references', []),
            'created_by' => $request->user()->user_id,
            'updated_by' => $request->user()->user_id,
        ]);

        $disease->symptoms()->sync($this->symptomSyncPayload($request));

        return new DiseaseResource($disease->load(['category', 'symptoms.category'])->loadCount('symptoms'));
    }

    public function show(Disease $disease)
    {
        return new DiseaseResource($disease->load(['category', 'treatmentOrders', 'symptoms.category'])->loadCount('symptoms'));
    }

    public function update(DiseaseRequest $request, Disease $disease)
    {
        $imageFields = [
            'disease_image',
            'description',
            'cause',
            'symptom_description',
            'complications',
            'diagnosis',
            'medical_treatment',
            'self_care',
            'when_to_see_doctor',
            'prevention',
            'recommendations',
        ];
        $oldImages = array_map(fn (string $field) => $disease->{$field}, $imageFields);

        $disease->update([
            'disease_name' => $request->has('disease_name') ? $request->disease_name : $disease->disease_name,
            'disease_name_en' => $request->has('disease_name_en') ? $request->disease_name_en : $disease->disease_name_en,
            'description' => $request->has('description') ? NotificationContent::normalizeImageUrls($request->description) : $disease->description,
            'cause' => $request->has('cause') ? NotificationContent::normalizeImageUrls($request->cause) : $disease->cause,
            'symptom_description' => $request->has('symptom_description') ? NotificationContent::normalizeImageUrls($request->symptom_description) : $disease->symptom_description,
            'complications' => $request->has('complications') ? NotificationContent::normalizeImageUrls($request->complications) : $disease->complications,
            'diagnosis' => $request->has('diagnosis') ? NotificationContent::normalizeImageUrls($request->diagnosis) : $disease->diagnosis,
            'medical_treatment' => $request->has('medical_treatment') ? NotificationContent::normalizeImageUrls($request->medical_treatment) : $disease->medical_treatment,
            'self_care' => $request->has('self_care') ? NotificationContent::normalizeImageUrls($request->self_care) : $disease->self_care,
            'when_to_see_doctor' => $request->has('when_to_see_doctor') ? NotificationContent::normalizeImageUrls($request->when_to_see_doctor) : $disease->when_to_see_doctor,
            'prevention' => $request->has('prevention') ? NotificationContent::normalizeImageUrls($request->prevention) : $disease->prevention,
            'recommendations' => $request->has('recommendations') ? NotificationContent::normalizeImageUrls($request->recommendations) : $disease->recommendations,
            'disease_image' => $request->has('disease_image') ? $request->disease_image : $disease->disease_image,
            'status' => $request->status ?? $disease->status,
            'disease_category_id' => $request->has('disease_category_id') ? $request->disease_category_id : $disease->disease_category_id,
            'references' => $request->has('references') ? $request->input('references') : $disease->references,
            'updated_by' => $request->user()->user_id,
        ]);

        if ($request->hasAny(['symptom_ids', 'symptom_assessments'])) {
            $disease->symptoms()->sync($this->symptomSyncPayload($request, $disease));
        }

        ContentImageStorage::deleteRemoved(
            $oldImages,
            array_map(fn (string $field) => $disease->{$field}, $imageFields),
        );

        return new DiseaseResource($disease->load(['category', 'symptoms.category'])->loadCount('symptoms'));
    }

    public function destroy(Disease $disease)
    {
        if ($disease->treatmentOrders()->exists()) {
            return response()->json([
                'message' => 'ไม่สามารถลบได้ เนื่องจากมีคำสั่งการรักษาของโรคนี้อยู่',
            ], 422);
        }

        $images = ContentImageStorage::paths([
            $disease->disease_image,
            $disease->description,
            $disease->cause,
            $disease->symptom_description,
            $disease->complications,
            $disease->diagnosis,
            $disease->medical_treatment,
            $disease->self_care,
            $disease->when_to_see_doctor,
            $disease->prevention,
            $disease->recommendations,
        ]);
        $disease->delete();
        ContentImageStorage::delete($images);

        return response()->json(['message' => 'ลบโรคสำเร็จ']);
    }

    private function generateId(): string
    {
        $last = Disease::max('disease_id');
        $next = $last ? (int) $last + 1 : 1;

        return str_pad($next, 10, '0', STR_PAD_LEFT);
    }

    /**
     * Build pivot data without discarding assessment metadata when the
     * existing admin form submits only symptom_ids.
     */
    private function symptomSyncPayload(DiseaseRequest $request, ?Disease $disease = null): array
    {
        $configured = collect($request->input('symptom_assessments', []))
            ->keyBy('symptom_id');
        $symptomIds = $request->has('symptom_ids')
            ? $request->input('symptom_ids', [])
            : $configured->keys()->all();

        $existing = $disease
            ? $disease->symptoms()->get()->keyBy('symptom_id')
            : collect();

        return collect($symptomIds)->mapWithKeys(function (string $symptomId) use ($configured, $existing) {
            $current = $existing->get($symptomId)?->pivot;
            $input = $configured->get($symptomId, []);

            return [$symptomId => [
                'assessment_weight' => $input['assessment_weight'] ?? $current?->assessment_weight ?? 1,
                'is_key_symptom' => $input['is_key_symptom'] ?? $current?->is_key_symptom ?? false,
                'absence_penalty' => $input['absence_penalty'] ?? $current?->absence_penalty ?? 0,
                'question_text' => $input['question_text'] ?? $current?->question_text,
                'evidence_source' => $input['evidence_source'] ?? $current?->evidence_source,
            ]];
        })->all();
    }
}
