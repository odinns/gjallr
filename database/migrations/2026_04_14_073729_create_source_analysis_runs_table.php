<?php

declare(strict_types=1);

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
        Schema::create('source_analysis_runs', function (Blueprint $table) {
            $table->id();
            $table->string('source_type');
            $table->string('source_label')->nullable();
            $table->text('sql_dump_path')->nullable();
            $table->text('site_path')->nullable();
            $table->string('detected_prefix')->nullable();
            $table->string('detected_version')->nullable();
            $table->string('detected_db_version')->nullable();
            $table->string('compatibility_band');
            $table->boolean('has_uploads')->default(false);
            $table->unsignedInteger('tables_count')->default(0);
            $table->unsignedInteger('plugins_count')->default(0);
            $table->unsignedInteger('themes_count')->default(0);
            $table->unsignedInteger('suspicious_findings_count')->default(0);
            $table->string('artifact_path');
            $table->json('summary_json')->nullable();
            $table->timestamp('analyzed_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('source_analysis_runs');
    }
};
