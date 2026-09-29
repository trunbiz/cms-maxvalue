# Góc đọc — Laravel 10 CMS

Website đọc truyện tiếng Việt với CMS, phân quyền theo module, nhập nhiều chương, media local/Cloudflare R2 và cache Redis.

## Chạy ở máy hiện tại

- Website: http://127.0.0.1:8010
- Quản trị: http://127.0.0.1:8010/admin
- Tài khoản seed: **admin / admin123**. Đổi mật khẩu khi đưa lên môi trường thật.
- PHP 8.1 tại `C:\OpenServer\modules\php\PHP_8.1\php.exe`.
- Composer tại `C:\OpenServer\userdata\composer\composer.phar`.
- MySQL 8 và Redis phải đang chạy. Thông tin kết nối nằm trong `.env`, không đưa vào Git.

PowerShell:

```powershell
$env:Path = 'C:\OpenServer\modules\php\PHP_8.1;' + $env:Path
php C:\OpenServer\userdata\composer\composer.phar install
npm ci
php artisan migrate --seed
php artisan storage:link
npm run build
php artisan serve --host=127.0.0.1 --port=8010
```

Nếu dùng domain OpenServer, đặt document root vào `public/` và chỉnh `APP_URL` trong `.env` thành domain đó, rồi chạy `php artisan config:clear`.

Để tạo lại **toàn bộ dữ liệu mẫu** trong database phát triển:

```powershell
php artisan migrate:fresh --seed
```

Lệnh này xóa các bảng hiện có. Seeder tạo 1 Super Admin, 3 danh mục, 10 thẻ, 5 truyện × 30 chương, 5 bài viết, 2 trang và 2 menu.

## Chức năng

1. Database, factory, seeder; đăng nhập username; quyền `dashboard`, `users`, `roles`, `categories`, `tags`, `posts`, `pages`, `menus`, `settings`. Truyện và nhập chương thuộc module `posts`.
2. MediaService resize tối đa 1200px, chuyển WebP; đường dẫn tương đối; upload CKEditor; chuyển media sang R2 và kiểm tra kết nối.
3. CMS Bootstrap 5.3 responsive, dashboard, CRUD người dùng/quyền. Bảo vệ Super Admin, tài khoản cuối cùng và tài khoản đang đăng nhập.
4. CRUD danh mục, thẻ, trang; logo, favicon, ads.txt, HTML head riêng cho Super Admin; menu nhiều cấp bằng SortableJS.
5. CRUD truyện/bài/chương, xóa nhiều chương theo truyện; nhập HTML hoặc văn bản; xem trước; bỏ qua/ghi đè chương trùng; lưu transaction theo lô; bảo vệ ảnh dùng chung.
6. Frontend mobile, tìm kiếm FULLTEXT, sitemap, canonical/Open Graph/JSON-LD. Literata và Be Vietnam Pro tự host; ba giao diện đọc, cỡ chữ 16–24px, độ rộng dòng, phím mũi tên, tiến độ và lịch sử localStorage.
7. Response cache Redis trên các trang HTML; frontend không tạo session/cookie và không phụ thuộc tài khoản. Cloudflare purge qua queue, tối đa 30 URL/request. Redis INCR, chống lặp cùng thiết bị/IP trong 60 giây, ghi dồn SQL mỗi 10 phút với batch id chống ghi trùng khi gián đoạn.

## Nhập chương

Vào **Nhập nhiều chương**, dán nội dung dạng:

```text
Tên truyện mới
Mô tả truyện
CHAPTER 1 - Khởi đầu
Nội dung chương một.
Chapter 2 – Hành trình
Nội dung chương hai.
CHAPTER 3: Trở về
Nội dung chương ba.
```

Chọn truyện có sẵn nếu muốn nhập tiếp. Khi cập nhật mô tả truyện đã có, toàn bộ phần trước dòng CHAPTER đầu tiên được dùng làm mô tả. Bản xem trước có hiệu lực một giờ và chỉ tài khoản đã tạo mới có thể xác nhận. Giới hạn mỗi lần nhập: 2.000 chương, 5 triệu ký tự, ảnh 5MB/40 triệu pixel.

Ảnh trong nội dung được lưu dạng `/media/{đường-dẫn-tương-đối}` và chuyển thành URL bằng `media_url()` khi hiển thị. Ảnh từ nguồn ngoài bị loại khi lưu; dùng nút upload trong CKEditor để đưa ảnh qua MediaService.

## Cloudflare R2

Local sử dụng `MEDIA_DISK=public` và `CLOUDFLARE_PURGE_ENABLED=false`.

Production cần bucket, custom domain và các biến `R2_*` trong `.env.example`. Mọi cấu hình ứng dụng Cloudflare đi qua `config('cloudflare.*')`; disk R2 được cấu hình ở `config/filesystems.php`.

```bash
php artisan config:clear
php artisan r2:test
php artisan media:migrate-to-r2
```

Lệnh chuyển media bỏ qua file đích đã tồn tại và giữ nguyên file local. Sau khi kiểm tra thành công, đặt `MEDIA_DISK=r2`. Hiện `.env` chưa có thông tin R2, nên kết nối dịch vụ thật chưa được xác minh.

Muốn purge Cloudflare, điền Zone ID và API token, bật `CLOUDFLARE_PURGE_ENABLED=true`. Chạy worker:

```bash
php artisan queue:work redis --tries=3 --timeout=300
```

Các URL frontend đã truy cập được ghi nhận để xóa cả trang chi tiết, trang danh sách/phân trang và slug cũ khi nội dung thay đổi. Cloudflare tự chia lô 30 URL; lỗi được retry qua queue.

## Scheduler và cache

Chạy local:

```bash
php artisan schedule:work
```

Production chạy `php artisan schedule:run` mỗi phút qua cron/Task Scheduler. Tác vụ `views:flush` chạy mỗi 10 phút. Có thể chạy thủ công bằng `php artisan views:flush`.

`CACHE_PREFIX` và `REDIS_PREFIX` riêng giúp không đụng dữ liệu Redis của dự án khác. Debugbar chỉ bật ở local qua `DEBUGBAR_ENABLED=true`; frontend tắt thanh debug để giữ HTML giống nhau và không đưa thông tin debug vào cache. Kiểm tra số truy vấn frontend bằng Feature test.

## Kiểm thử

```bash
php artisan test
php vendor/bin/pint --test
npm run build
php artisan route:cache
php artisan route:clear
```

Feature test dùng SQLite in-memory, không đụng database `.env`. Test lượt xem dùng Redis thật với tiền tố UUID riêng, tự dọn key và sẽ báo skipped nếu Redis không có sẵn. Có test nhập 300 chương, transaction rollback, HTML/soft-break, trùng chương, phân quyền, media dùng chung, menu vòng lặp, cache, không có cookie frontend và phục hồi batch lượt xem.

PHP dependencies khóa theo PHP 8.1.1. npm audit hiện không có cảnh báo; Composer audit vẫn báo advisory của Laravel 10 theo phiên bản bắt buộc của dự án. Chưa kiểm tra trực quan qua Browser vì phiên làm việc không có trình duyệt khả dụng.

Danh sách file tạo/sửa: [docs/FILES_CHANGED.md](docs/FILES_CHANGED.md).
