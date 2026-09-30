# Dự án: Web đọc truyện + CMS (Laravel 10 + Bootstrap 5)

## Công nghệ (BẮT BUỘC đúng phiên bản)
- Laravel 10, PHP 8.1 (KHÔNG dùng cú pháp/tính năng của PHP 8.2+)
- MySQL 8, Redis (cache, session, queue)
- Blade + Bootstrap 5.3 cài qua npm + Vite. Không Tailwind, không Livewire, không jQuery
- JS thuần (vanilla). Kéo thả dùng SortableJS (npm)
- Editor: CKEditor 5 classic cài qua npm (nếu phiên bản yêu cầu thì đặt licenseKey: 'GPL')
- Font tự host qua npm @fontsource (không gọi Google Fonts)
- Mọi package cài thêm phải chọn phiên bản tương thích Laravel 10 + PHP 8.1

## Cấu trúc
- Admin: prefix /admin, middleware auth + kiểm tra quyền module. Không có đăng ký công khai
- Frontend: controller riêng trong App\Http\Controllers\Frontend
- Validate bằng FormRequest, logic phức tạp tách ra Service
  (vd ChapterImportService, MediaService, CloudflareService)
- Tên bảng/biến/hàm bằng tiếng Anh; giao diện hiển thị tiếng Anh (theo yêu cầu cập nhật của chủ dự án)

## Lưu trữ file (Cloudflare R2)
- Mọi file upload (logo, favicon, ảnh bài viết, ảnh series, ảnh trong CKEditor)
  đều đi qua MediaService, lưu lên disk lấy từ MEDIA_DISK
  (local dùng 'public', production dùng 'r2')
- Database chỉ lưu đường dẫn tương đối (vd: series/2026/09/abc.webp),
  KHÔNG lưu URL đầy đủ
- URL ảnh luôn tạo qua helper media_url($path)
- Mọi cấu hình Cloudflare đọc qua config('cloudflare.*'), không gọi env() trực tiếp trong code

## Quy tắc hiệu năng
- HTML trang frontend giống nhau cho mọi người đọc, không có @auth trong Blade frontend
- Read counts are disabled: no browser tracking requests, Redis counters, or scheduled database writes.
- Không SELECT *, luôn eager loading, không N+1
- Nội dung bài viết nằm ở bảng riêng post_contents
- Settings, menu, danh mục cache bằng Cache::rememberForever, xóa cache khi admin cập nhật

## Thiết kế giao diện đọc
- Font đọc: Literata hoặc Noto Serif (hỗ trợ tiếng Việt). Font giao diện: Be Vietnam Pro
- Nội dung chương: cỡ chữ 19px, line-height 1.8, max-width 70ch, căn giữa,
  khoảng cách đoạn 1.2em
- Bảng màu định nghĩa bằng CSS variables:
  - Sáng: nền #FAF8F3, chữ #2B2B2B, màu chính #3D6B6F, viền #E6E1D6
  - Sepia: nền #F4ECD8, chữ #5B4636
  - Tối: nền #1C1E21, chữ #D4D4D4, màu chính #7FB3B8
- Tương phản chữ/nền đạt chuẩn WCAG AA

## Quy ước làm việc
- Mỗi giai đoạn xong phải chạy được `php artisan migrate:fresh --seed` và `npm run build`
- Viết Feature test cho các chức năng chính
- Khi thêm biến môi trường mới thì cập nhật .env.example kèm chú thích tiếng Việt
- Làm xong báo lại danh sách file đã tạo/sửa
