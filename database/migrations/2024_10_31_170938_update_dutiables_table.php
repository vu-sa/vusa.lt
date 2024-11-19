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
        Schema::create('study_programs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->json('name');
            $table->string('degree');
            $table->unsignedInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::table('dutiables', function (Blueprint $table) {
            $table->string('additional_email')->nullable()->after('end_date');
            $table->string('additional_photo')->nullable()->after('additional_email');
            $table->json('description')->nullable()->after('additional_photo');
            $table->foreignUlid('study_program_id')->nullable()->after('end_date')->constrained();
            $table->boolean('use_original_duty_name')->default(false)->after('description');
        });

        $dutiables = \App\Models\Pivots\Dutiable::all();

        foreach ($dutiables as $dutiable) {
            
            $extra_attributes = json_decode($dutiable->extra_attributes);

            if (isset($extra_attributes?->study_program))
            {
                $studyProgram = \App\Models\StudyProgram::where('name->lt', $extra_attributes->study_program)->first();

                echo $studyProgram . '\n';
                echo $extra_attributes->study_program;

                if ($studyProgram) {
                    $dutiable->study_program_id = $studyProgram->id;
                    echo $studyProgram->id . ' old' . '\n';
                } else {
                    $studyProgram = new \App\Models\StudyProgram();
                    $studyProgram->setTranslation('name', 'lt', $extra_attributes->study_program);
                    $studyProgram->degree = 'BA';
                    $studyProgram->tenant_id = $dutiable->duty->institution->tenant_id;
                    $studyProgram->save();

                    echo $studyProgram->id . ' new' . '\n';

                    $dutiable->study_program_id = $studyProgram->id;
                }
            }

            if (isset($extra_attributes?->info_text)) {
                $dutiable->setTranslation('description', 'lt', $extra_attributes->info_text);
            }

            if (isset($extra_attributes?->en?->info_text)) {
                $dutiable->setTranslation('description', 'en', $extra_attributes->en->info_text);
            }

            if (isset($extra_attributes?->additional_photo)) {
                $dutiable->additional_photo = $extra_attributes->additional_photo;
            }

            if (isset($extra_attributes?->use_original_duty_name)) {
                $dutiable->use_original_duty_name = $extra_attributes->use_original_duty_name;
            }

            $dutiable->save();
        }

        Schema::table('dutiables', function (Blueprint $table) {
            $table->dropColumn('extra_attributes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
