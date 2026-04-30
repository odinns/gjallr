<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_runs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('source_analysis_run_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source_label')->nullable();
            $table->string('status');
            $table->text('sql_dump_path')->nullable();
            $table->text('site_path')->nullable();
            $table->string('detected_prefix')->nullable();
            $table->json('summary_json')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_runs');
    }
};
