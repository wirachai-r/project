<?php

namespace App\Http\Requests\Concerns;

trait NormalizesTextInput
{
    protected function normalizeTextInput(array $fields): void
    {
        $normalized = [];

        foreach ($fields as $field) {
            if (!$this->exists($field) || !is_string($this->input($field))) {
                continue;
            }

            $value = preg_replace('/\s+/u', ' ', trim($this->input($field)));
            $normalized[$field] = $value === '' ? null : $value;
        }

        $this->merge($normalized);
    }
}
