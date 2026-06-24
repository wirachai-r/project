<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MainSymptom extends Model
{
    protected $primaryKey = 'symptom_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'symptom_id',
        'symptom_name',
        'symptom_name_en',
        'description',
        'symptom_image',
        'status',
        'symptom_category_id',
        'created_by',
        'updated_by',
    ];

    public function category()
    {
        return $this->belongsTo(SymptomCategory::class, 'symptom_category_id', 'symptom_category_id');
    }

    // Many-to-many กับ diagrams ผ่าน symptom_diagrams
    public function diagrams()
    {
        return $this->belongsToMany(
            Diagram::class,
            'symptom_diagrams',
            'symptom_id',
            'diagram_id',
            'symptom_id',
            'diagram_id'
        );
    }

    public function assessments()
    {
        return $this->hasMany(Assessment::class, 'symptom_id', 'symptom_id');
    }

    public function searchLogs()
    {
        return $this->hasMany(SymptomSearchLog::class, 'symptom_id', 'symptom_id');
    }
}
