<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiagnosisRule extends Model
{
    protected $primaryKey = 'rule_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'rule_id',
        'disease_id',
        'choice_id',
        'score',
        'status',
        'created_by',
        'updated_by',
    ];

    public function disease()
    {
        return $this->belongsTo(Disease::class, 'disease_id', 'disease_id');
    }

    public function answerChoice()
    {
        return $this->belongsTo(AnswerChoice::class, 'choice_id', 'choice_id');
    }
}
