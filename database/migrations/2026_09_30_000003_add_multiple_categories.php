<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['post' => 'posts', 'series' => 'series'] as $entity => $source) {
            Schema::create('category_'.$entity, function (Blueprint $table) use ($entity, $source) {
                $table->foreignId('category_id')->constrained()->cascadeOnDelete();
                $table->foreignId($entity.'_id')->constrained($source)->cascadeOnDelete();
                $table->primary(['category_id', $entity.'_id']);
                $table->index($entity.'_id');
            });
            DB::table($source)->select(['id', 'category_id'])->whereNotNull('category_id')->chunkById(500, function ($rows) use ($entity) {
                DB::table('category_'.$entity)->insert($rows->map(fn ($row) => ['category_id' => $row->category_id, $entity.'_id' => $row->id])->all());
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('category_post');
        Schema::dropIfExists('category_series');
    }
};
