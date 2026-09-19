<?php

namespace Tests\Feature;

use App\Http\Requests\Admin\SymptomRequest;
use App\Rules\UniqueNameIgnoringWhitespace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class SymptomRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_symptom_name_is_unique_when_whitespace_is_ignored(): void
    {
        DB::table('symptom_categories')->insert([
            'symptom_category_id' => 'SC0001',
            'category_name' => 'ทั่วไป',
        ]);
        DB::table('main_symptoms')->insert([
            'symptom_id' => 'SYM0000001',
            'symptom_name' => 'ไอเป็นเลือด',
            'symptom_category_id' => 'SC0001',
        ]);

        $request = SymptomRequest::create('/', 'POST', [
            'symptom_name' => " ไอเป็น\t เลือด ",
        ]);

        $validator = Validator::make($request->all(), $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('symptom_name', $validator->errors()->toArray());
    }

    public function test_shared_name_rule_ignores_the_record_being_updated(): void
    {
        DB::table('symptom_categories')->insert([
            'symptom_category_id' => 'SC0001',
            'category_name' => 'ทั่วไป',
        ]);
        DB::table('main_symptoms')->insert([
            'symptom_id' => 'SYM0000001',
            'symptom_name' => 'ไอเป็นเลือด',
            'symptom_category_id' => 'SC0001',
        ]);

        $validator = Validator::make(
            ['name' => 'ไอ เป็น เลือด'],
            ['name' => [(new UniqueNameIgnoringWhitespace('main_symptoms', 'symptom_name'))->ignore('SYM0000001', 'symptom_id')]],
        );

        $this->assertFalse($validator->fails());
    }
}
