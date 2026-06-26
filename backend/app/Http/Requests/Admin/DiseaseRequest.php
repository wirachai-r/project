<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class DiseaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'disease_name'        => 'required|string|max:150',
            'disease_name_en'     => 'nullable|string|max:150',
            'description'         => 'nullable|string',

            // เพิ่มการตรวจสอบฟิลด์ใหม่ตรงนี้ครับ
            'cause'               => 'nullable|string',
            'symptom_description' => 'nullable|string',
            'prevention'          => 'nullable|string',
            'reference'           => 'nullable|string',

            'disease_image'       => 'nullable|string|max:255',
            'status'              => 'nullable|in:1,2',
            'disease_category_id' => 'required|exists:disease_categories,disease_category_id',
        ];
    }

    public function messages(): array
    {
        return [
            'disease_name.required'        => 'กรุณากรอกชื่อโรค',
            'disease_name.max'             => 'ชื่อโรคต้องมีความยาวไม่เกิน 150 ตัวอักษร',
            'disease_name_en.max'          => 'ชื่อโรคภาษาอังกฤษต้องมีความยาวไม่เกิน 150 ตัวอักษร',
            'disease_category_id.required' => 'กรุณาเลือกหมวดหมู่',
            'disease_category_id.exists'   => 'ไม่พบหมวดหมู่ที่เลือก',

            // หากต้องการเพิ่ม Custom Message สำหรับฟิลด์ที่เพิ่มเข้ามา (ถ้ามี)
            // เช่น 'cause.string' => 'ข้อมูลสาเหตุต้องเป็นข้อความ',
        ];
    }
}
