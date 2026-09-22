<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReviewCommonAdaptiveRules extends Command
{
    protected $signature = 'adaptive:review-common-rules';

    protected $description = 'Attach authoritative public sources to supported adaptive routes for common symptoms';

    public function handle(): int
    {
        $groups = $this->reviewedRoutes();
        $updated = 0;
        $missing = [];

        DB::transaction(function () use ($groups, &$updated, &$missing): void {
            foreach ($groups as $initialName => $routes) {
                $initialId = DB::table('main_symptoms')->where('symptom_name', $initialName)->value('symptom_id');
                if (! $initialId) {
                    $missing[] = "initial: {$initialName}";

                    continue;
                }

                foreach ($routes as $targetName => $source) {
                    $targetId = DB::table('main_symptoms')->where('symptom_name', $targetName)->value('symptom_id');
                    if (! $targetId) {
                        $missing[] = "target: {$targetName}";

                        continue;
                    }

                    $questionIds = DB::table('adaptive_question_symptoms')
                        ->where('symptom_id', $targetId)
                        ->pluck('adaptive_question_id');

                    $count = DB::table('adaptive_question_rules')
                        ->where('initial_symptom_id', $initialId)
                        ->whereIn('adaptive_question_id', $questionIds)
                        ->update([
                            'evidence_source' => $source,
                            'evidence_status' => 'reviewed',
                            'reviewed_by' => null,
                            'reviewed_at' => now(),
                            'updated_at' => now(),
                        ]);

                    if ($count === 0) {
                        $missing[] = "route: {$initialName} -> {$targetName}";
                    }
                    $updated += $count;
                }
            }
        });

        $this->info("Reviewed {$updated} common-symptom adaptive routes.");
        if ($missing !== []) {
            $this->warn('Not found: '.implode(', ', $missing));
        }

        return self::SUCCESS;
    }

    /** @return array<string, array<string, string>> */
    private function reviewedRoutes(): array
    {
        $headacheSource = 'MedlinePlus, Brain aneurysm (headache with neck pain/stiffness, numbness or weakness, seizure, vision change, or loss of consciousness): https://medlineplus.gov/ency/article/001414.htm';
        $dizzinessSource = 'NHS, Dizziness (dizziness with headache, fainting/collapse, vision change, or numbness/weakness): https://www.nhs.uk/symptoms/dizziness/';
        $coughSource = 'NHS, Cough (cough with chest pain, difficulty breathing, or coughing up blood): https://www.nhs.uk/symptoms/cough/';
        $pneumoniaSource = 'NHS, Pneumonia (cough, shortness of breath, high temperature, and chest pain): https://www.nhs.uk/conditions/pneumonia/';
        $soreThroatSource = 'NHS, Laryngitis (sore throat with hoarse/lost voice or high temperature): https://www.nhs.uk/conditions/laryngitis/';
        $swollenGlandsSource = 'NHS, Swollen glands (sore throat or high temperature with neck/under-chin swelling): https://www.nhs.uk/symptoms/swollen-glands/';
        $abdominalSource = 'NHS, Stomach ache (abdominal pain with nausea, vomiting, recurrent pain, vomiting blood, chest pain, or collapse): https://www.nhs.uk/symptoms/stomach-ache/';
        $abdominalFeverSource = 'UCLH NHS, Abdominal pain discharge advice (abdominal pain with fever/shivering, vomiting blood, fainting or collapse): https://www.uclh.nhs.uk/patients-and-visitors/patient-information-pages/abdominal-pain';
        $breathlessnessSource = 'NHS, Shortness of breath (breathlessness with vomiting, coughing blood, palpitations, or persistent cough): https://www.nhs.uk/conditions/shortness-of-breath/';

        return [
            "\u{0E1B}\u{0E27}\u{0E14}\u{0E28}\u{0E35}\u{0E23}\u{0E29}\u{0E30}" => [
                "\u{0E1B}\u{0E27}\u{0E14}\u{0E15}\u{0E49}\u{0E19}\u{0E04}\u{0E2D}/\u{0E17}\u{0E49}\u{0E32}\u{0E22}\u{0E17}\u{0E2D}\u{0E22}" => $headacheSource,
                "\u{0E1A}\u{0E49}\u{0E32}\u{0E19}\u{0E2B}\u{0E21}\u{0E38}\u{0E19}" => $dizzinessSource,
                "\u{0E40}\u{0E27}\u{0E35}\u{0E22}\u{0E19}\u{0E28}\u{0E35}\u{0E23}\u{0E29}\u{0E30}/\u{0E27}\u{0E34}\u{0E07}\u{0E40}\u{0E27}\u{0E35}\u{0E22}\u{0E19}" => $dizzinessSource,
                "\u{0E2B}\u{0E19}\u{0E49}\u{0E32}\u{0E21}\u{0E37}\u{0E14}" => $dizzinessSource,
                "\u{0E0A}\u{0E32}" => $headacheSource,
                "\u{0E2A}\u{0E25}\u{0E1A}" => $headacheSource,
                "\u{0E2B}\u{0E21}\u{0E14}\u{0E2A}\u{0E15}\u{0E34}" => $headacheSource,
                "\u{0E40}\u{0E2B}\u{0E47}\u{0E19}\u{0E20}\u{0E32}\u{0E1E}\u{0E0B}\u{0E49}\u{0E2D}\u{0E19}" => $headacheSource,
                "\u{0E0A}\u{0E31}\u{0E01}" => $headacheSource,
                "\u{0E2D}\u{0E31}\u{0E21}\u{0E1E}\u{0E32}\u{0E15}" => $headacheSource,
            ],
            "\u{0E44}\u{0E2D}" => [
                "\u{0E44}\u{0E2D}\u{0E40}\u{0E1B}\u{0E47}\u{0E19}\u{0E40}\u{0E25}\u{0E37}\u{0E2D}\u{0E14}" => $coughSource,
                "\u{0E40}\u{0E08}\u{0E47}\u{0E1A}\u{0E2B}\u{0E19}\u{0E49}\u{0E32}\u{0E2D}\u{0E01}" => $coughSource,
                "\u{0E2B}\u{0E2D}\u{0E1A}" => $coughSource,
                "\u{0E44}\u{0E02}\u{0E49} + \u{0E40}\u{0E08}\u{0E47}\u{0E1A}\u{0E2B}\u{0E19}\u{0E49}\u{0E32}\u{0E2D}\u{0E01}" => $pneumoniaSource,
                "\u{0E44}\u{0E02}\u{0E49} + \u{0E2B}\u{0E2D}\u{0E1A}" => $pneumoniaSource,
            ],
            "\u{0E40}\u{0E08}\u{0E47}\u{0E1A}\u{0E04}\u{0E2D}" => [
                "\u{0E04}\u{0E2D}\u{0E40}\u{0E08}\u{0E47}\u{0E1A}" => $soreThroatSource,
                "\u{0E44}\u{0E02}\u{0E49} + \u{0E40}\u{0E08}\u{0E47}\u{0E1A}\u{0E04}\u{0E2D}" => $soreThroatSource,
                "\u{0E44}\u{0E21}\u{0E48}\u{0E21}\u{0E35}\u{0E40}\u{0E2A}\u{0E35}\u{0E22}\u{0E07} (\u{0E40}\u{0E2A}\u{0E35}\u{0E22}\u{0E07}\u{0E41}\u{0E2B}\u{0E1A})" => $soreThroatSource,
                "\u{0E40}\u{0E2A}\u{0E35}\u{0E22}\u{0E07}\u{0E41}\u{0E2B}\u{0E1A}" => $soreThroatSource,
                "\u{0E04}\u{0E32}\u{0E07}\u{0E1A}\u{0E27}\u{0E21}" => $swollenGlandsSource,
                "\u{0E44}\u{0E02}\u{0E49} + \u{0E04}\u{0E32}\u{0E07}\u{0E1A}\u{0E27}\u{0E21}/\u{0E04}\u{0E2D}\u{0E1A}\u{0E27}\u{0E21}" => $swollenGlandsSource,
            ],
            "\u{0E1B}\u{0E27}\u{0E14}\u{0E17}\u{0E49}\u{0E2D}\u{0E07}" => [
                "\u{0E04}\u{0E25}\u{0E37}\u{0E48}\u{0E19}\u{0E44}\u{0E2A}\u{0E49}" => $abdominalSource,
                "\u{0E2D}\u{0E32}\u{0E40}\u{0E08}\u{0E35}\u{0E22}\u{0E19}" => $abdominalSource,
                "\u{0E2D}\u{0E32}\u{0E40}\u{0E08}\u{0E35}\u{0E22}\u{0E19}\u{0E40}\u{0E1B}\u{0E47}\u{0E19}\u{0E40}\u{0E25}\u{0E37}\u{0E2D}\u{0E14}" => $abdominalSource,
                "\u{0E1B}\u{0E27}\u{0E14}\u{0E17}\u{0E49}\u{0E2D}\u{0E07}\u{0E41}\u{0E1A}\u{0E1A}\u{0E40}\u{0E1B}\u{0E47}\u{0E19}\u{0E46} \u{0E2B}\u{0E32}\u{0E22}\u{0E46}" => $abdominalSource,
                "\u{0E1B}\u{0E27}\u{0E14}\u{0E17}\u{0E49}\u{0E2D}\u{0E07} + \u{0E21}\u{0E35}\u{0E44}\u{0E02}\u{0E49}" => $abdominalFeverSource,
                "\u{0E40}\u{0E08}\u{0E47}\u{0E1A}\u{0E2B}\u{0E19}\u{0E49}\u{0E32}\u{0E2D}\u{0E01}" => $abdominalSource,
                "\u{0E40}\u{0E1B}\u{0E47}\u{0E19}\u{0E25}\u{0E21}" => $abdominalFeverSource,
            ],
            "\u{0E2B}\u{0E2D}\u{0E1A}" => [
                "\u{0E40}\u{0E2B}\u{0E19}\u{0E37}\u{0E48}\u{0E2D}\u{0E22}\u{0E07}\u{0E48}\u{0E32}\u{0E22}" => $pneumoniaSource,
                "\u{0E40}\u{0E08}\u{0E47}\u{0E1A}\u{0E2B}\u{0E19}\u{0E49}\u{0E32}\u{0E2D}\u{0E01}" => $pneumoniaSource,
                "\u{0E44}\u{0E2D}" => $pneumoniaSource,
                "\u{0E44}\u{0E2D}\u{0E40}\u{0E1B}\u{0E47}\u{0E19}\u{0E40}\u{0E25}\u{0E37}\u{0E2D}\u{0E14}" => $breathlessnessSource,
                "\u{0E43}\u{0E08}\u{0E2A}\u{0E31}\u{0E48}\u{0E19}" => $breathlessnessSource,
                "\u{0E44}\u{0E02}\u{0E49} + \u{0E40}\u{0E08}\u{0E47}\u{0E1A}\u{0E2B}\u{0E19}\u{0E49}\u{0E32}\u{0E2D}\u{0E01}" => $pneumoniaSource,
                "\u{0E44}\u{0E02}\u{0E49} + \u{0E2B}\u{0E2D}\u{0E1A}" => $pneumoniaSource,
                "\u{0E04}\u{0E25}\u{0E37}\u{0E48}\u{0E19}\u{0E44}\u{0E2A}\u{0E49}" => $breathlessnessSource,
                "\u{0E2D}\u{0E32}\u{0E40}\u{0E08}\u{0E35}\u{0E22}\u{0E19}" => $breathlessnessSource,
            ],
        ];
    }
}
