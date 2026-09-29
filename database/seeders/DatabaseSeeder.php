<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Menu;
use App\Models\Page;
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
        User::create(['name' => 'Quản trị viên', 'username' => 'admin', 'password' => 'admin123', 'role_id' => $role->id]);
        foreach (['Văn học', 'Kỳ ảo', 'Đời sống'] as $name) {
            Category::create(['name' => $name, 'slug' => Str::slug($name)]);
        }
        foreach (['Phiêu lưu', 'Gia đình', 'Tình bạn', 'Trưởng thành', 'Bí ẩn', 'Thiên nhiên', 'Việt Nam', 'Cổ tích', 'Tâm lý', 'Khám phá'] as $name) {
            Tag::create(['name' => $name, 'slug' => Str::slug($name)]);
        }
        foreach (['Miền gió kể chuyện', 'Những mùa hoa cũ', 'Bên kia đồi xanh', 'Người giữ ánh trăng', 'Đường về bình yên'] as $i => $title) {
            $series = Series::create(['title' => $title, 'slug' => Str::slug($title), 'description' => 'Một hành trình qua những miền ký ức, nơi mỗi trang sách mở ra một góc nhìn mới.', 'category_id' => $i % 3 + 1, 'status' => 'published']);
            $series->tags()->attach([$i + 1, $i + 2]);
            for ($n = 1; $n <= 30; $n++) {
                $post = Post::create(['title' => 'Chương '.$n.': Một ngày mới', 'slug' => $series->slug.'-chuong-'.$n, 'type' => 'chapter', 'series_id' => $series->id, 'chapter_number' => $n, 'category_id' => $series->category_id, 'status' => 'published', 'published_at' => now()]);
                $post->content()->create(['content' => str_repeat('<p>Nắng sớm len qua ô cửa nhỏ, đánh thức những ký ức còn ngủ quên. Tôi bước ra con đường quen thuộc, nghe tiếng lá xào xạc và nghĩ về cuộc hành trình phía trước.</p><p>Ở cuối con đường, một người bạn đang đợi. Chúng tôi cùng đi, mang theo câu chuyện chưa kể và niềm tin vào những điều tốt đẹp.</p>', 8)]);
                $post->tags()->attach([$i + 1, $i + 2]);
            }
        }
        foreach (['Đọc chậm để hiểu mình', 'Một cuốn sách cho ngày mưa', 'Những câu chuyện bên hiên', 'Tìm bình yên trong trang sách', 'Thói quen đọc mỗi ngày'] as $i => $title) {
            $post = Post::create(['title' => $title, 'slug' => Str::slug($title), 'excerpt' => 'Dành một khoảng lặng cho những câu chuyện và cảm nhận của riêng bạn.', 'type' => 'normal', 'category_id' => $i % 3 + 1, 'status' => 'published', 'published_at' => now()]);
            $post->content()->create(['content' => '<p>Mỗi cuốn sách là một lời mời bước vào thế giới mới. Hãy dành một chút thời gian mỗi ngày để đọc và khám phá.</p>']);
            $post->tags()->attach([$i + 1]);
        }
        foreach (['Giới thiệu', 'Liên hệ'] as $title) {
            Page::create(['title' => $title, 'slug' => Str::slug($title), 'content' => '<p>Chào mừng bạn đến với Góc đọc, nơi lưu giữ những câu chuyện hay.</p>']);
        }
        $main = Menu::create(['name' => 'Menu chính', 'slug' => 'main']);
        $main->items()->create(['label' => 'Trang chủ', 'type' => 'url', 'url' => '/']);
        $main->items()->create(['label' => 'Văn học', 'type' => 'category', 'target_id' => 1, 'sort_order' => 1]);
        $footer = Menu::create(['name' => 'Menu chân trang', 'slug' => 'footer']);
        $footer->items()->create(['label' => 'Giới thiệu', 'type' => 'page', 'target_id' => 1]);
        foreach (['site_name' => 'Góc đọc', 'seo_description' => 'Một góc yên tĩnh cho những câu chuyện hay.', 'ads_txt' => '', 'head_html' => ''] as $key => $value) {
            Setting::create(compact('key', 'value'));
        }
    }
}
