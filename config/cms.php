<?php

use App\Models\Category;
use App\Models\Menu;
use App\Models\Page;
use App\Models\Post;
use App\Models\Role;
use App\Models\Series;
use App\Models\Tag;
use App\Models\User;

return ['resources' => [
    'users' => ['model' => User::class, 'label' => 'Người dùng', 'fields' => ['name' => 'Tên', 'username' => 'Tên đăng nhập', 'password' => 'Mật khẩu', 'role_id' => 'Quyền']],
    'roles' => ['model' => Role::class, 'label' => 'Phân quyền', 'fields' => ['name' => 'Tên quyền', 'modules' => 'Module']],
    'categories' => ['model' => Category::class, 'label' => 'Danh mục', 'fields' => ['name' => 'Tên danh mục', 'slug' => 'Đường dẫn', 'description' => 'Mô tả', 'seo_title' => 'Tiêu đề SEO', 'seo_keywords' => 'Từ khóa SEO', 'seo_description' => 'Mô tả SEO']],
    'tags' => ['model' => Tag::class, 'label' => 'Thẻ', 'fields' => ['name' => 'Tên thẻ', 'slug' => 'Đường dẫn']],
    'pages' => ['model' => Page::class, 'label' => 'Trang nội dung', 'fields' => ['title' => 'Tiêu đề', 'slug' => 'Đường dẫn', 'content' => 'Nội dung', 'seo_title' => 'Tiêu đề SEO', 'seo_description' => 'Mô tả SEO']],
    'series' => ['model' => Series::class, 'label' => 'Truyện', 'fields' => ['title' => 'Tên truyện', 'slug' => 'Đường dẫn', 'description' => 'Mô tả', 'image' => 'Ảnh bìa', 'category_id' => 'Danh mục', 'tags' => 'Thẻ', 'status' => 'Trạng thái', 'seo_title' => 'Tiêu đề SEO', 'seo_keywords' => 'Từ khóa SEO', 'seo_description' => 'Mô tả SEO']],
    'posts' => ['model' => Post::class, 'label' => 'Bài viết', 'fields' => ['title' => 'Tiêu đề', 'slug' => 'Đường dẫn', 'type' => 'Loại bài', 'series_id' => 'Truyện', 'chapter_number' => 'Số chương', 'excerpt' => 'Mô tả', 'content' => 'Nội dung', 'image' => 'Ảnh đại diện', 'category_id' => 'Danh mục', 'tags' => 'Thẻ', 'status' => 'Trạng thái', 'published_at' => 'Ngày xuất bản', 'seo_title' => 'Tiêu đề SEO', 'seo_description' => 'Mô tả SEO']],
    'menus' => ['model' => Menu::class, 'label' => 'Menu', 'fields' => ['name' => 'Tên menu', 'slug' => 'Vị trí (main / footer)']],
]];
