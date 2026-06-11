<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Diagram extends Model
{
    protected $primaryKey = 'diagram_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'diagram_id',
        'diagram_name',
        'diagram_name_en',
        'description',
        'status',
        'symptom_id',
        'entry_box_id',
        'created_by',
        'updated_by',
    ];

    public function symptom()
    {
        return $this->belongsTo(MainSymptom::class, 'symptom_id', 'symptom_id');
    }

    public function entryBox()
    {
        return $this->belongsTo(QuestionBox::class, 'entry_box_id', 'box_id');
    }

    public function questionBoxes()
    {
        return $this->hasMany(QuestionBox::class, 'diagram_id', 'diagram_id');
    }
}
