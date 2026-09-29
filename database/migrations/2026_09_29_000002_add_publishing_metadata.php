<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->string('author_name')->nullable();
            $table->boolean('is_demo')->default(false)->index();
        });
        Schema::table('series', fn (Blueprint $table) => $table->boolean('is_demo')->default(false)->index());
        Schema::table('pages', fn (Blueprint $table) => $table->string('status')->default('published')->index());
    }

    public function down(): void
    {
        Schema::table('posts', fn (Blueprint $table) => $table->dropColumn(['author_name', 'is_demo']));
        Schema::table('series', fn (Blueprint $table) => $table->dropColumn('is_demo'));
        Schema::table('pages', fn (Blueprint $table) => $table->dropColumn('status'));
    }
};
