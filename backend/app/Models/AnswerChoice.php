<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnswerChoice extends Model
{
    protected $primaryKey = 'choice_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'choice_id',
        'choice_text',
        'choice_text_en',
        'order',
        'status',
        'box_id',
        'next_box_id',
        'created_by',
        'updated_by',
    ];

    public function nextBox()
    {
        return $this->belongsTo(QuestionBox::class, 'next_box_id', 'box_id');
    }
}
