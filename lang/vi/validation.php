<?php

return [
    'required' => 'Vui lòng nhập :attribute.', 'required_if' => 'Vui lòng nhập :attribute khi :other là :value.',
    'string' => ':attribute phải là chuỗi ký tự.', 'integer' => ':attribute phải là số nguyên.',
    'numeric' => ':attribute phải là số.', 'boolean' => ':attribute không hợp lệ.',
    'array' => ':attribute phải là danh sách.', 'date' => ':attribute phải là ngày hợp lệ.',
    'email' => ':attribute phải là email hợp lệ.', 'url' => ':attribute phải là URL hợp lệ.',
    'unique' => ':attribute đã tồn tại.', 'exists' => ':attribute không tồn tại.',
    'in' => ':attribute không hợp lệ.', 'not_in' => ':attribute không hợp lệ.', 'regex' => ':attribute không đúng định dạng.',
    'alpha_dash' => ':attribute chỉ được chứa chữ, số, dấu gạch ngang và gạch dưới.',
    'image' => ':attribute phải là ảnh.', 'mimes' => ':attribute chỉ chấp nhận các định dạng: :values.',
    'max' => ['string' => ':attribute không được vượt quá :max ký tự.', 'file' => ':attribute không được vượt quá :max KB.', 'array' => ':attribute không được có quá :max phần tử.', 'numeric' => ':attribute không được lớn hơn :max.'],
    'min' => ['string' => ':attribute cần ít nhất :min ký tự.', 'numeric' => ':attribute phải từ :min trở lên.', 'array' => ':attribute cần ít nhất :min phần tử.', 'file' => ':attribute cần ít nhất :min KB.'],
    'prohibited' => 'Bạn không được thay đổi :attribute.', 'present' => 'Thiếu :attribute.',
    'distinct' => ':attribute bị trùng.', 'uuid' => ':attribute không hợp lệ.',
    'attributes' => ['name' => 'tên', 'title' => 'tiêu đề', 'username' => 'tên đăng nhập', 'password' => 'mật khẩu', 'slug' => 'đường dẫn', 'content' => 'nội dung', 'image' => 'ảnh', 'upload' => 'ảnh tải lên', 'role_id' => 'quyền', 'category_id' => 'danh mục', 'series_id' => 'truyện', 'chapter_number' => 'số chương', 'status' => 'trạng thái', 'head_html' => 'HTML trong thẻ head', 'site_name' => 'tên website', 'items' => 'các mục menu', 'token' => 'mã xác nhận'],
];
