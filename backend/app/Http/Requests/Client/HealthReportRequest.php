<?php

namespace App\Http\Requests\Client;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class HealthReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from' => ['required', 'date_format:Y-m-d', 'before_or_equal:to'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from', 'before_or_equal:today'],
            'include_assessments' => ['required', 'boolean'],
            'include_follow_ups' => ['required', 'boolean'],
            'include_daily_records' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['include_assessments', 'include_follow_ups', 'include_daily_records'] as $field) {
            if ($this->has($field)) {
                $this->merge([$field => filter_var($this->input($field), FILTER_VALIDATE_BOOLEAN)]);
            }
        }
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if (! $this->boolean('include_assessments')
                && ! $this->boolean('include_follow_ups')
                && ! $this->boolean('include_daily_records')) {
                $validator->errors()->add('report', 'กรุณาเลือกข้อมูลอย่างน้อยหนึ่งประเภท');
            }

            if (! $validator->errors()->hasAny(['from', 'to'])
                && $this->date('from')->diffInDays($this->date('to')) > 366) {
                $validator->errors()->add('to', 'ช่วงวันที่ต้องไม่เกินหนึ่งปี');
            }
        }];
    }
}
