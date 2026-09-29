<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Post;
use App\Models\Role;
use App\Models\Series;
use App\Models\Setting;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::create(['name' => 'Super Admin', 'modules' => array_keys(config('modules'))]);
        User::create(['name' => 'Administrator', 'username' => 'admin', 'password' => 'admin123', 'role_id' => $role->id]);
        foreach (['Literature', 'Fantasy', 'Everyday Life'] as $name) {
            Category::create(['name' => $name, 'slug' => Str::slug($name)]);
        }
        foreach (['Adventure', 'Family', 'Friendship', 'Growing Up', 'Mystery', 'Nature', 'Vietnam', 'Fairy Tales', 'Psychology', 'Discovery'] as $name) {
            Tag::create(['name' => $name, 'slug' => Str::slug($name)]);
        }
        foreach (['Where the Wind Tells Stories', 'Seasons of Yesterday', 'Beyond the Green Hills', 'The Keeper of Moonlight', 'The Road Back to Peace'] as $i => $title) {
            $series = Series::create(['title' => $title, 'slug' => Str::slug($title), 'description' => 'An unpublished sample story. Replace these sample chapters with your own original work.', 'category_id' => $i % 3 + 1, 'status' => 'draft', 'is_demo' => true]);
            $series->tags()->attach([$i + 1, $i + 2]);
            for ($n = 1; $n <= 30; $n++) {
                $post = Post::create(['title' => 'Chapter '.$n.': A New Day', 'slug' => $series->slug.'-chapter-'.$n, 'type' => 'chapter', 'series_id' => $series->id, 'chapter_number' => $n, 'category_id' => $series->category_id, 'status' => 'draft', 'is_demo' => true, 'published_at' => now()]);
                $post->content()->create(['content' => '<p>This is an unpublished sample. Use the editor to replace it with your own original chapter before publishing.</p>']);
                $post->tags()->attach([$i + 1, $i + 2]);
            }
        }
        foreach (['Reading Slowly to Know Yourself', 'A Book for a Rainy Day', 'Stories on the Porch', 'Finding Calm in a Book', 'A Daily Reading Habit'] as $i => $title) {
            $post = Post::create(['title' => $title, 'slug' => Str::slug($title), 'excerpt' => 'Sample content for learning the editor. Replace it with your own work before publishing.', 'type' => 'normal', 'category_id' => $i % 3 + 1, 'status' => 'draft', 'is_demo' => true, 'published_at' => now()]);
            $post->content()->create(['content' => '<p>This is an unpublished sample. Use the editor to replace it with your own original article before publishing.</p>']);
            $post->tags()->attach([$i + 1]);
        }
        foreach (['site_name' => 'Reading Corner', 'seo_description' => 'Thoughtful articles, reading notes, and stories for curious readers.', 'ads_txt' => '', 'head_html' => ''] as $key => $value) {
            Setting::create(compact('key', 'value'));
        }
        $this->call(PublicationPagesSeeder::class);
    }
}
