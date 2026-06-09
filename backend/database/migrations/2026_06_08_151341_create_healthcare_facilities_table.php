<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('healthcare_facilities', function (Blueprint $table) {
            $table->char('facility_id', 10)->primary();
            $table->string('facility_name', 255);
            $table->string('facility_name_en', 255)->nullable();
            $table->char('facility_type', 1); // H=Hospital, C=Clinic, P=Pharmacy
            $table->text('address')->nullable();
            $table->string('province', 100)->nullable();
            $table->string('district', 100)->nullable();
            $table->string('sub_district', 100)->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('website', 255)->nullable();
            $table->text('open_hours')->nullable();
            $table->char('status', 1)->default('1'); // 1=Active, 2=Inactive

            // FK
            $table->char('created_by', 9)->nullable();
            $table->foreign('created_by')
                ->references('user_id')
                ->on('users')
                ->nullOnDelete();

            // Timestamps
            $table->timestamp('created_at')->nullable();

            // FK
            $table->char('updated_by', 9)->nullable();
            $table->foreign('updated_by')
                ->references('user_id')
                ->on('users')
                ->nullOnDelete();

            // Timestamps
            $table->timestamp('updated_at')->nullable();

            // Index
            $table->index('status');
            $table->index('facility_type');
            $table->index('province');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('healthcare_facilities');
    }
};
