<?php

namespace App\Services;

class SlugService
{
    public function unique(string $model, string $base, ?int $id = null): string
    {
        $slug = $base;
        $number = 2;
        while ($model::where('slug', $slug)->when($id, fn ($q) => $q->where('id', '!=', $id))->exists()) {
            $suffix = '-'.$number++;
            $slug = rtrim(substr($base, 0, 255 - strlen($suffix)), '-').$suffix;
        }

        return $slug;
    }
}
