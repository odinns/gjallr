<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rescued_sites', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('import_run_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source_label')->nullable();
            $table->string('name')->nullable();
            $table->string('site_url')->nullable();
            $table->string('home_url')->nullable();
            $table->string('permalink_structure')->nullable();
            $table->string('active_theme')->nullable();
            $table->string('source_prefix')->nullable();
            $table->string('show_on_front')->default('posts');
            $table->unsignedBigInteger('page_on_front_source_id')->nullable();
            $table->unsignedBigInteger('page_for_posts_source_id')->nullable();
            $table->text('site_path')->nullable();
            $table->json('summary_json')->nullable();
            $table->timestamps();
        });

        Schema::create('content_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('rescued_site_id')->constrained()->cascadeOnDelete();
            $table->string('source_type');
            $table->unsignedBigInteger('original_source_id');
            $table->string('title')->nullable();
            $table->string('slug');
            $table->string('path')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('body_html')->nullable();
            $table->string('status')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->unsignedBigInteger('source_parent_id')->nullable();
            $table->integer('menu_order')->default(0);
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->boolean('is_posts_index')->default(false);
            $table->timestamps();

            $table->unique(['rescued_site_id', 'original_source_id']);
        });

        Schema::create('taxonomies', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('rescued_site_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('original_term_taxonomy_id');
            $table->unsignedBigInteger('original_term_id');
            $table->string('type');
            $table->string('name');
            $table->string('slug');
            $table->string('path');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('source_parent_term_id')->nullable();
            $table->timestamps();

            $table->unique(['rescued_site_id', 'original_term_taxonomy_id']);
        });

        Schema::create('content_taxonomy', function (Blueprint $table): void {
            $table->foreignId('content_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('taxonomy_id')->constrained()->cascadeOnDelete();
            $table->primary(['content_item_id', 'taxonomy_id']);
        });

        Schema::create('media_assets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('rescued_site_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('original_source_id');
            $table->foreignId('content_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('path')->nullable();
            $table->string('url')->nullable();
            $table->string('mime_type')->nullable();
            $table->string('alt_text')->nullable();
            $table->timestamps();

            $table->unique(['rescued_site_id', 'original_source_id']);
        });

        Schema::create('comments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('content_item_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('original_source_id');
            $table->unsignedBigInteger('source_parent_id')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('comments')->nullOnDelete();
            $table->string('author_name')->nullable();
            $table->string('author_email')->nullable();
            $table->text('body');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->unique(['content_item_id', 'original_source_id']);
        });

        Schema::create('navigation_menus', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('rescued_site_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('original_term_id');
            $table->string('name');
            $table->string('slug');
            $table->timestamps();

            $table->unique(['rescued_site_id', 'original_term_id']);
        });

        Schema::create('navigation_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('navigation_menu_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('original_source_id');
            $table->unsignedBigInteger('source_parent_id')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('navigation_items')->nullOnDelete();
            $table->foreignId('content_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('label');
            $table->string('url')->nullable();
            $table->integer('position')->default(0);
            $table->timestamps();

            $table->unique(['navigation_menu_id', 'original_source_id']);
        });

        Schema::create('redirect_rules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('rescued_site_id')->constrained()->cascadeOnDelete();
            $table->string('from_path')->unique();
            $table->string('to_path');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('redirect_rules');
        Schema::dropIfExists('navigation_items');
        Schema::dropIfExists('navigation_menus');
        Schema::dropIfExists('comments');
        Schema::dropIfExists('media_assets');
        Schema::dropIfExists('content_taxonomy');
        Schema::dropIfExists('taxonomies');
        Schema::dropIfExists('content_items');
        Schema::dropIfExists('rescued_sites');
    }
};
