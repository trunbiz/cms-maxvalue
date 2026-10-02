# Cập nhật admin ngày 02/10/2026

- Featured image tải ngay qua MediaService lên MEDIA_DISK (cấu hình hiện tại là R2). Nhận URL để hiển thị; database chỉ lưu đường dẫn tương đối. Chặn lưu khi ảnh đang tải hoặc tải thất bại. Tệp đã tải chỉ được dùng trong phiên đã tải nó.
- Slug bài mới có dạng `ten-bai-viet/ten-nhan-vien`. Khi trùng, thêm số ngẫu nhiên trước phần tên nhân viên. Route frontend hỗ trợ slug có dấu `/`. Chương nhập mới cũng có tên người viết trong slug.
- Admin mặc định tiếng Việt; chọn English/Tiếng Việt ở thanh trên. Lựa chọn lưu theo phiên, chỉ dịch giao diện admin; tên người dùng, bài viết, danh mục và frontend giữ nguyên.
- Analyze chapters phân tích tại trình duyệt, xem trước nội dung có định dạng và ảnh, cảnh báo chương trùng trong bản thảo, thiếu nội dung và số không liên tiếp. Không gọi `/admin/import/preview`. Khi Save chapters mới gửi lên `/admin/import/save`; backend vẫn kiểm tra, làm sạch HTML và xử lý số chương đã tồn tại.
- Ghi created_by cho bài và bộ truyện mới. Super Admin và vai trò có tên Admin quản lý toàn bộ; các vai trò còn lại chỉ truy cập bài của mình. Áp dụng cho danh sách, sửa, cập nhật, preview, xóa và xóa hàng loạt. Nhân viên chỉ nhập vào bộ truyện của mình và không ghi đè chương của người khác.
- Trạng thái giao diện: Publish / Unpublish / Bin; giá trị database tương ứng published / draft / bin. Xóa bài chuyển sang Bin, giữ nội dung và ảnh. Khôi phục bằng sửa bài rồi chọn Publish hoặc Unpublish.
- Bộ lọc bài: trạng thái, series, danh mục, người tạo. Series có trạng thái, danh mục, người tạo và tìm kiếm.
- Bỏ AdSense Publisher ID, Additional ads.txt entries và toàn bộ Before requesting AdSense review khỏi giao diện Settings. Cấu hình AdSense đã có không bị xóa.

## Dữ liệu và kiểm tra

- Đã áp dụng migration thêm created_by trên database cấu hình hiện tại bằng migrate --force.
- Các bài/bộ truyện cũ chưa có người tạo vẫn giữ created_by = null, chỉ quản trị truy cập. Không tự suy đoán tác giả của dữ liệu cũ.
- Không sửa .env và không thêm biến môi trường.
- Toàn bộ 54 test PHP đạt (498 assertions); 5 test JavaScript đạt. Sau chỉnh sửa bản dịch cuối cùng, chạy lại 36 test liên quan và đều đạt.
- npm run build thành công. Vite vẫn cảnh báo kích thước chunk CKEditor.
- migrate:fresh --seed thành công trên SQLite riêng trong thư mục tạm; không reset database hiện tại.
- Kiểm tra upload dùng Storage::fake; chưa tải tệp kiểm tra lên R2 thực tế.

## File tạo mới

- app/Http/Middleware/AdminLocale.php
- app/Http/Requests/AdminLanguageRequest.php
- app/Services/PostSlugService.php
- database/migrations/2026_10_02_000001_add_created_by_to_content.php
- lang/vi.json
- resources/js/chapter-parser.js
- tests/Feature/AdminUpdatesTest.php
- ADMIN_UPDATES.md

## File chỉnh sửa

- lang/vi/validation.php
- app/Http/Controllers/Admin/ChapterImportController.php
- app/Http/Controllers/Admin/ResourceController.php
- app/Http/Controllers/Admin/UploadController.php
- app/Http/Controllers/Frontend/ReadingController.php
- app/Http/Requests/BrowseRequest.php
- app/Http/Requests/ChapterImportRequest.php
- app/Http/Requests/ResourceRequest.php
- app/Models/Post.php
- app/Models/Series.php
- app/Models/User.php
- app/Services/ChapterImportService.php
- app/Services/ResourceService.php
- resources/js/copy-link.js
- resources/js/menu.js
- resources/js/publishing.js
- resources/views/admin/chapter-preview.blade.php
- resources/views/admin/copy-link.blade.php
- resources/views/admin/dashboard.blade.php
- resources/views/admin/fields/choices.blade.php
- resources/views/admin/fields/image.blade.php
- resources/views/admin/fields/input.blade.php
- resources/views/admin/form.blade.php
- resources/views/admin/import-preview.blade.php
- resources/views/admin/index.blade.php
- resources/views/admin/layout.blade.php
- resources/views/admin/login.blade.php
- resources/views/admin/menu-editor.blade.php
- resources/views/admin/settings.blade.php
- routes/frontend.php
- routes/web.php
- tests/Feature/CmsTest.php
- tests/Js/publishing.test.mjs
- tests/TestCase.php

Assets trong public/build đã được tạo lại bằng Vite.
