<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('healthcare_facilities', function (Blueprint $table) {
            $table->string('facility_id', 20)->change();

            $table->string('moph_code_9_new', 9)->nullable()->unique();
            $table->string('moph_code_9', 9)->nullable()->index();
            $table->string('moph_code_5', 5)->nullable()->index();
            $table->string('license_code_11', 11)->nullable()->index();
            $table->string('organization_type', 150)->nullable();
            $table->string('service_type', 150)->nullable();
            $table->string('affiliation', 150)->nullable();
            $table->string('department', 150)->nullable();
            $table->string('hospital_level', 100)->nullable();
            $table->string('network_type', 100)->nullable();
            $table->unsignedInteger('actual_beds')->nullable();
            $table->string('source_status', 100)->nullable();
            $table->string('service_area', 20)->nullable();
            $table->string('province_code', 2)->nullable()->index();
            $table->string('district_code', 4)->nullable();
            $table->string('sub_district_code', 6)->nullable();
            $table->string('village', 20)->nullable();
            $table->string('parent_facility', 255)->nullable();
            $table->date('established_date')->nullable();
            $table->date('closed_date')->nullable();
            $table->timestamp('source_updated_at')->nullable();
            $table->string('data_source', 50)->nullable()->default('moph');

            $table->index(
                ['status', 'latitude', 'longitude'],
                'idx_facilities_nearby'
            );
        });
    }

    public function down(): void
    {
        Schema::table('healthcare_facilities', function (Blueprint $table) {
            $table->dropIndex('idx_facilities_nearby');
            $table->dropUnique(['moph_code_9_new']);
            $table->dropIndex(['moph_code_9']);
            $table->dropIndex(['moph_code_5']);
            $table->dropIndex(['license_code_11']);
            $table->dropIndex(['province_code']);
            $table->dropColumn([
                'moph_code_9_new',
                'moph_code_9',
                'moph_code_5',
                'license_code_11',
                'organization_type',
                'service_type',
                'affiliation',
                'department',
                'hospital_level',
                'network_type',
                'actual_beds',
                'source_status',
                'service_area',
                'province_code',
                'district_code',
                'sub_district_code',
                'village',
                'parent_facility',
                'established_date',
                'closed_date',
                'source_updated_at',
                'data_source',
            ]);

            $table->char('facility_id', 10)->change();
        });
    }
};
