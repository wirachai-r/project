<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Disease extends Model
{
    protected $primaryKey = 'disease_id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'disease_id',
        'disease_name',
        'disease_name_en',
        'description',

        // เพิ่มฟิลด์ใหม่เข้าตระกูล Fillable ตรงนี้ครับ
        'cause',
        'symptom_description',
        'complications',
        'diagnosis',
        'medical_treatment',
        'self_care',
        'when_to_see_doctor',
        'prevention',
        'recommendations',
        'disease_image',
        'reference', // ฟิลด์ reference ที่เพิ่มเข้ามา
        'view_count',
        'references',

        'status',
        'disease_category_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = ['references' => 'array'];

    public function category()
    {
        return $this->belongsTo(DiseaseCategory::class, 'disease_category_id', 'disease_category_id');
    }

    public function treatmentOrders()
    {
        return $this->hasMany(TreatmentOrder::class, 'disease_id', 'disease_id');
    }

    public function symptoms()
    {
        return $this->belongsToMany(
            MainSymptom::class,
            'disease_symptoms',
            'disease_id',
            'symptom_id',
            'disease_id',
            'symptom_id'
        )->withPivot([
            'assessment_weight',
            'is_key_symptom',
            'absence_penalty',
            'question_text',
            'evidence_source',
        ])->withTimestamps();
    }
}
