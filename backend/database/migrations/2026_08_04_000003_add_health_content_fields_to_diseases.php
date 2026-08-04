<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diseases', function (Blueprint $table) {
            $table->text('complications')->nullable()->after('symptom_description');
            $table->text('diagnosis')->nullable()->after('complications');
            $table->text('medical_treatment')->nullable()->after('diagnosis');
            $table->text('self_care')->nullable()->after('medical_treatment');
            $table->text('when_to_see_doctor')->nullable()->after('self_care');
            $table->text('recommendations')->nullable()->after('prevention');
        });
    }

    public function down(): void
    {
        Schema::table('diseases', function (Blueprint $table) {
            $table->dropColumn([
                'complications',
                'diagnosis',
                'medical_treatment',
                'self_care',
                'when_to_see_doctor',
                'recommendations',
            ]);
        });
    }
};
