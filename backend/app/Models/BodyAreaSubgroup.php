<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BodyAreaSubgroup extends Model
{
    protected $fillable = [
        'body_area_group_id', 'name', 'name_en', 'description', 'image_path',
        'display_order', 'status',
    ];

    protected $casts = ['display_order' => 'integer'];

    public function group()
    {
        return $this->belongsTo(BodyAreaGroup::class, 'body_area_group_id');
    }

    public function symptoms()
    {
        return $this->belongsToMany(MainSymptom::class, 'body_area_subgroup_symptoms', 'body_area_subgroup_id', 'symptom_id')
            ->withPivot('display_order')
            ->orderBy('body_area_subgroup_symptoms.display_order')
            ->orderBy('main_symptoms.symptom_name');
    }
}
