<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            MainSymptomSeeder::class,
            BodyAreaGroupSeeder::class,
            DiagramSeeder::class,
            SymptomDiagramSeeder::class,
            ComprehensiveDiseaseSeeder::class,
            Diagram1FeverSeeder::class,
            Diagram5FatigueSeeder::class,
            FollowUpQuestionTemplateSeeder::class,
        ]);
    }
}
