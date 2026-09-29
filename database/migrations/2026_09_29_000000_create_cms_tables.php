<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->text('description')->nullable();
            $t->string('seo_title')->nullable();
            $t->text('seo_keywords')->nullable();
            $t->text('seo_description')->nullable();
            $t->timestamps();
        });
        Schema::create('tags', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->timestamps();
        });
        Schema::create('series', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->string('slug')->unique();
            $t->text('description')->nullable();
            $t->string('image')->nullable();
            $t->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $t->string('status')->default('draft')->index();
            $t->unsignedBigInteger('views')->default(0);
            $t->string('seo_title')->nullable();
            $t->text('seo_keywords')->nullable();
            $t->text('seo_description')->nullable();
            $t->timestamps();
            if (Schema::getConnection()->getDriverName() === 'mysql') {
                $t->fullText('title');
            }
        });
        Schema::create('posts', function (Blueprint $t) {
            $t->id();
            $t->string('type')->default('normal')->index();
            $t->foreignId('series_id')->nullable()->constrained('series')->cascadeOnDelete();
            $t->unsignedInteger('chapter_number')->nullable();
            $t->string('title');
            $t->string('slug')->unique();
            $t->text('excerpt')->nullable();
            $t->string('image')->nullable();
            $t->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $t->string('status')->default('draft');
            $t->unsignedBigInteger('views')->default(0);
            $t->timestamp('published_at')->nullable();
            $t->string('seo_title')->nullable();
            $t->text('seo_description')->nullable();
            $t->timestamps();
            $t->unique(['series_id', 'chapter_number']);
            $t->index(['status', 'published_at']);
            if (Schema::getConnection()->getDriverName() === 'mysql') {
                $t->fullText('title');
            }
        });
        Schema::create('post_contents', function (Blueprint $t) {
            $t->id();
            $t->foreignId('post_id')->unique()->constrained()->cascadeOnDelete();
            $t->longText('content');
            $t->timestamps();
        });
        foreach (['post', 'series'] as $entity) {
            Schema::create($entity.'_tag', function (Blueprint $t) use ($entity) {
                $t->foreignId($entity.'_id')->constrained($entity === 'post' ? 'posts' : 'series')->cascadeOnDelete();
                $t->foreignId('tag_id')->constrained()->cascadeOnDelete();
                $t->primary([$entity.'_id', 'tag_id']);
            });
        }
        Schema::create('pages', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->string('slug')->unique();
            $t->longText('content');
            $t->string('seo_title')->nullable();
            $t->text('seo_description')->nullable();
            $t->timestamps();
        });
        Schema::create('menus', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->timestamps();
        });
        Schema::create('menu_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('menu_id')->constrained()->cascadeOnDelete();
            $t->foreignId('parent_id')->nullable()->constrained('menu_items')->cascadeOnDelete();
            $t->string('label');
            $t->string('type');
            $t->unsignedBigInteger('target_id')->nullable();
            $t->string('url', 2048)->nullable();
            $t->unsignedInteger('sort_order')->default(0);
            $t->timestamps();
            $t->index(['menu_id', 'parent_id', 'sort_order']);
        });
        Schema::create('settings', function (Blueprint $t) {
            $t->id();
            $t->string('key')->unique();
            $t->longText('value')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['settings', 'menu_items', 'menus', 'pages', 'series_tag', 'post_tag', 'post_contents', 'posts', 'series', 'tags', 'categories'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
