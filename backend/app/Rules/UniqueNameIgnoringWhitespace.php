<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

class UniqueNameIgnoringWhitespace implements ValidationRule
{
    private mixed $ignoreId = null;

    private string $ignoreColumn = 'id';

    private ?Closure $scope = null;

    public function __construct(
        private readonly string $table,
        private readonly string $column,
    ) {}

    public function ignore(mixed $id, string $column = 'id'): self
    {
        $this->ignoreId = $id;
        $this->ignoreColumn = $column;

        return $this;
    }

    public function where(Closure $scope): self
    {
        $this->scope = $scope;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        $query = DB::table($this->table)->select($this->column);

        if ($this->ignoreId !== null) {
            $query->where($this->ignoreColumn, '!=', $this->ignoreId);
        }

        if ($this->scope !== null) {
            ($this->scope)($query);
        }

        $normalizedValue = $this->normalize($value);

        foreach ($query->cursor() as $record) {
            if ($this->normalize((string) $record->{$this->column}) === $normalizedValue) {
                $fail('มีชื่อนี้อยู่แล้ว');

                return;
            }
        }
    }

    private function normalize(string $value): string
    {
        return mb_strtolower(preg_replace('/\s+/u', '', trim($value)) ?? $value);
    }
}
