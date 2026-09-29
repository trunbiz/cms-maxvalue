<?php

function media_url(?string $path): string
{
    return app(\App\Services\MediaService::class)->url($path);
}
function post_url(\App\Models\Post $post): string
{
    return $post->type === 'chapter' ? url('/truyen/'.$post->series->slug.'/'.$post->slug) : url('/bai-viet/'.$post->slug);
}
function clean_html(?string $html): string
{
    return app(\App\Services\ContentService::class)->clean($html ?? '');
}
function content_html(?string $html): string
{
    return app(\App\Services\ContentService::class)->render($html ?? '');
}
