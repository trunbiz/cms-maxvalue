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
    'users' => ['model' => User::class, 'label' => 'Users', 'fields' => ['name' => 'Name', 'username' => 'Username', 'password' => 'Password', 'role_id' => 'Role']],
    'roles' => ['model' => Role::class, 'label' => 'Roles', 'fields' => ['name' => 'Role name', 'modules' => 'Module']],
    'categories' => ['model' => Category::class, 'label' => 'Categories', 'fields' => ['name' => 'Category name', 'slug' => 'Slug', 'description' => 'Description', 'seo_title' => 'SEO title', 'seo_keywords' => 'SEO keywords', 'seo_description' => 'SEO description']],
    'tags' => ['model' => Tag::class, 'label' => 'Tags', 'fields' => ['name' => 'Tag name', 'slug' => 'Slug']],
    'pages' => ['model' => Page::class, 'label' => 'Pages', 'fields' => ['title' => 'Title', 'slug' => 'Slug', 'content' => 'Content', 'status' => 'Status', 'seo_title' => 'SEO title', 'seo_description' => 'SEO description']],
    'series' => ['model' => Series::class, 'label' => 'Stories', 'fields' => ['title' => 'Story title', 'slug' => 'Slug', 'description' => 'Description', 'image' => 'Cover image', 'category_id' => 'Categories', 'tags' => 'Tags', 'status' => 'Status', 'seo_title' => 'SEO title', 'seo_keywords' => 'SEO keywords', 'seo_description' => 'SEO description']],
    'posts' => ['model' => Post::class, 'label' => 'Articles', 'fields' => ['title' => 'Title', 'slug' => 'Slug', 'type' => 'Content type', 'series_id' => 'Stories', 'excerpt' => 'Description', 'content' => 'Content', 'image' => 'Featured image', 'category_id' => 'Categories', 'tags' => 'Tags', 'status' => 'Status', 'published_at' => 'Publication date', 'seo_title' => 'SEO title', 'seo_description' => 'SEO description']],
    'menus' => ['model' => Menu::class, 'label' => 'Menu', 'fields' => ['name' => 'Name menu', 'slug' => 'Location (main / footer)']],
]];
