<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdaptiveAssessmentResult extends Model
{
    protected $fillable = ['adaptive_assessment_id', 'disease_id', 'disease_name', 'match_percent', 'display_order'];

    public function disease()
    {
        return $this->belongsTo(Disease::class, 'disease_id', 'disease_id');
    }
}
