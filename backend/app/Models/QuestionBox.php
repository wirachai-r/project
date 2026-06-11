<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuestionBox extends Model
{
    protected $primaryKey = 'box_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'box_id',
        'question_text',
        'question_text_en',
        'question_type',
        'status',
        'diagram_id',
        'created_by',
        'updated_by',
    ];

    public function diagram()
    {
        return $this->belongsTo(Diagram::class, 'diagram_id', 'diagram_id');
    }

    public function choices()
    {
        return $this->hasMany(AnswerChoice::class, 'box_id', 'box_id');
    }
}
