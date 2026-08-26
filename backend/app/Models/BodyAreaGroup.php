<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BodyAreaGroup extends Model
{
    protected $fillable = [
        'name', 'name_en', 'description', 'image_path', 'display_order',
        'status', 'created_by', 'updated_by',
    ];

    protected $casts = ['display_order' => 'integer'];

    public function symptoms()
    {
        return $this->belongsToMany(MainSymptom::class, 'body_area_group_symptoms', 'body_area_group_id', 'symptom_id')
            ->withPivot('display_order')
            ->orderBy('body_area_group_symptoms.display_order')
            ->orderBy('main_symptoms.symptom_name');
    }
}
