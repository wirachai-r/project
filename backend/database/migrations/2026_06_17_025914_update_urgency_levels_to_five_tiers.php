<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // treatment_orders.urgency_type: R,O,Y,G → R,P,Y,G,W
        Schema::table('treatment_orders', function (Blueprint $table) {
            $table->char('urgency_type', 1)->comment('R=Red, P=Pink, Y=Yellow, G=Green, W=White')->change();
        });

        // diagnosis_rules.urgency_level
        Schema::table('diagnosis_rules', function (Blueprint $table) {
            $table->char('urgency_level', 1)->comment('R=Red, P=Pink, Y=Yellow, G=Green, W=White')->change();
        });

        // assessment_results.urgency_level
        Schema::table('assessment_results', function (Blueprint $table) {
            $table->char('urgency_level', 1)->comment('R=Red, P=Pink, Y=Yellow, G=Green, W=White')->change();
        });
    }

    public function down(): void
    {
        Schema::table('treatment_orders', function (Blueprint $table) {
            $table->char('urgency_type', 1)->comment('R=Red, O=Orange, Y=Yellow, G=Green')->change();
        });

        Schema::table('diagnosis_rules', function (Blueprint $table) {
            $table->char('urgency_level', 1)->comment('R=Red, O=Orange, Y=Yellow, G=Green')->change();
        });

        Schema::table('assessment_results', function (Blueprint $table) {
            $table->char('urgency_level', 1)->comment('R=Red, O=Orange, Y=Yellow, G=Green')->change();
        });
    }
};
