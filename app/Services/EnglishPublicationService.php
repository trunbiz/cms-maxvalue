<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Post;
use App\Models\Series;
use App\Models\Setting;
use App\Models\Tag;
use App\Models\User;
use Database\Seeders\PublicationPagesSeeder;
use Illuminate\Support\Facades\DB;

class EnglishPublicationService
{
    public function prepare(): array
    {
        $result = DB::transaction(function () {
            $changed = 0;
            $oldChapter = str_repeat('<p>Nắng sớm len qua ô cửa nhỏ, đánh thức những ký ức còn ngủ quên. Tôi bước ra con đường quen thuộc, nghe tiếng lá xào xạc và nghĩ về cuộc hành trình phía trước.</p><p>Ở cuối con đường, một người bạn đang đợi. Chúng tôi cùng đi, mang theo câu chuyện chưa kể và niềm tin vào những điều tốt đẹp.</p>', 8);
            $oldArticle = '<p>Mỗi cuốn sách là một lời mời bước vào thế giới mới. Hãy dành một chút thời gian mỗi ngày để đọc và khám phá.</p>';
            $articleTitles = ['Đọc chậm để hiểu mình' => 'Reading Slowly to Know Yourself', 'Một cuốn sách cho ngày mưa' => 'A Book for a Rainy Day', 'Những câu chuyện bên hiên' => 'Stories on the Porch', 'Tìm bình yên trong trang sách' => 'Finding Calm in a Book', 'Thói quen đọc mỗi ngày' => 'A Daily Reading Habit'];
            foreach (Post::select(['id', 'title', 'type', 'series_id', 'chapter_number', 'slug'])->with('content')->where(function ($q) use ($articleTitles) {
                $q->whereIn('title', array_keys($articleTitles))->orWhere('title', 'like', 'Chương %: Một ngày mới');
            })->get() as $post) {
                $expected = $post->type === 'chapter' ? $oldChapter : $oldArticle;
                if ($post->content?->content !== $expected) {
                    continue;
                }
                $post->update(['title' => $post->type === 'chapter' ? 'Chapter '.$post->chapter_number.': A New Day' : $articleTitles[$post->title], 'excerpt' => 'Sample content for learning the editor. Replace it with your own work before publishing.', 'status' => 'draft', 'is_demo' => true]);
                $post->content()->update(['content' => '<p>This is an unpublished sample. Use the editor to replace it with your own original article or chapter before publishing.</p>']);
                $changed++;
            }
            $stories = ['Miền gió kể chuyện' => 'Where the Wind Tells Stories', 'Những mùa hoa cũ' => 'Seasons of Yesterday', 'Bên kia đồi xanh' => 'Beyond the Green Hills', 'Người giữ ánh trăng' => 'The Keeper of Moonlight', 'Đường về bình yên' => 'The Road Back to Peace'];
            foreach (Series::select(['id', 'title', 'description', 'status'])->whereIn('title', array_keys($stories))->get() as $series) {
                if ($series->description !== 'Một hành trình qua những miền ký ức, nơi mỗi trang sách mở ra một góc nhìn mới.') {
                    continue;
                }
                $data = ['title' => $stories[$series->title], 'description' => 'An unpublished sample story. Replace these sample chapters with your own original work.', 'is_demo' => true];
                if (! $series->chapters()->where('is_demo', false)->exists()) {
                    $data['status'] = 'draft';
                }
                $series->update($data);
            }
            foreach (['Văn học' => 'Literature', 'Kỳ ảo' => 'Fantasy', 'Đời sống' => 'Everyday Life'] as $old => $new) {
                Category::where('name', $old)->update(['name' => $new]);
            }
            foreach (['Phiêu lưu' => 'Adventure', 'Gia đình' => 'Family', 'Tình bạn' => 'Friendship', 'Trưởng thành' => 'Growing Up', 'Bí ẩn' => 'Mystery', 'Thiên nhiên' => 'Nature', 'Việt Nam' => 'Vietnam', 'Cổ tích' => 'Fairy Tales', 'Tâm lý' => 'Psychology', 'Khám phá' => 'Discovery'] as $old => $new) {
                Tag::where('name', $old)->update(['name' => $new]);
            }
            User::where('name', 'Quản trị viên')->where('username', 'admin')->update(['name' => 'Administrator']);
            Setting::where('key', 'site_name')->where('value', 'Góc đọc')->update(['value' => 'Reading Corner']);
            Setting::where('key', 'seo_description')->where('value', 'Một góc yên tĩnh cho những câu chuyện hay.')->update(['value' => 'Thoughtful articles, reading notes, and stories for curious readers.']);
            // Only replace untouched seed pages. Authored pages are never overwritten.
            foreach (['gioi-thieu' => 'about', 'lien-he' => 'contact'] as $legacy => $slug) {
                $page = Page::where('slug', $legacy)->where('content', '<p>Chào mừng bạn đến với Góc đọc, nơi lưu giữ những câu chuyện hay.</p>')->first();
                if ($page && ! Page::where('slug', $slug)->exists()) {
                    $page->update(['slug' => $slug, 'title' => PublisherService::PAGES[$slug], 'status' => 'draft', 'content' => '']);
                }
            }
            foreach (['Menu chính' => 'Main navigation', 'Menu chân trang' => 'Footer navigation'] as $old => $new) {
                Menu::where('name', $old)->update(['name' => $new]);
            }
            foreach (['Trang chủ' => 'Home', 'Văn học' => 'Literature', 'Giới thiệu' => 'About', 'Liên hệ' => 'Contact'] as $old => $new) {
                MenuItem::where('label', $old)->update(['label' => $new]);
            }
            app(PublicationPagesSeeder::class)->run();

            return ['sample_posts_archived' => $changed, 'policy_pages' => Page::whereIn('slug', array_keys(PublisherService::PAGES))->count()];
        });
        app(CacheInvalidator::class)->invalidate();

        return $result;
    }
}
