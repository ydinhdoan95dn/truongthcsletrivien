# 🌐 HƯỚNG DẪN CẤU HÌNH VIRTUAL HOST - STUDENT PORTAL

**Trường THCS Lê Trí Viễn**
Domain: **truongbuithixuan.local**
DocumentRoot: **Student Portal** (thư mục student)

---

## ✅ ĐÃ CẤU HÌNH

### 1. Virtual Host Apache

**File:** `C:\xampp\apache\conf\extra\httpd-vhosts.conf`

```apache
<VirtualHost *:80>
    ServerName truongbuithixuan.local
    ServerAlias www.truongbuithixuan.local
    DocumentRoot "C:/xampp/htdocs/truongbuithixuan/student"

    <Directory "C:/xampp/htdocs/truongbuithixuan/student">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # Alias để truy cập admin panel
    Alias /admin "C:/xampp/htdocs/truongbuithixuan/admin"
    <Directory "C:/xampp/htdocs/truongbuithixuan/admin">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # Alias cho includes
    Alias /includes "C:/xampp/htdocs/truongbuithixuan/includes"
    <Directory "C:/xampp/htdocs/truongbuithixuan/includes">
        Options FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # Alias cho assets
    Alias /assets "C:/xampp/htdocs/truongbuithixuan/assets"
    <Directory "C:/xampp/htdocs/truongbuithixuan/assets">
        Options FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog "logs/truongbuithixuan.local-error.log"
    CustomLog "logs/truongbuithixuan.local-access.log" common
</VirtualHost>
```

### 2. Hosts File

**File:** `C:\Windows\System32\drivers\etc\hosts`

```
127.0.0.1 truongbuithixuan.local
```

### 3. Config.php

**File:** `includes/config.php`

Đã tự động detect domain và set BASE_URL:
```php
// Local Virtual Host
if ($isLocalVirtualHost) {
    define('BASE_URL', 'http://truongbuithixuan.local');
}
```

---

## 🚀 CÁCH SỬ DỤNG

### Bước 1: Restart Apache

**Cách 1: XAMPP Control Panel (Khuyến nghị)**
1. Mở XAMPP Control Panel
2. Click nút **[Stop]** bên cạnh Apache
3. Đợi 2-3 giây
4. Click nút **[Start]** để khởi động lại

**Cách 2: Command Line**
```cmd
taskkill /F /IM httpd.exe
C:\xampp\apache\bin\httpd.exe -k start
```

### Bước 2: Test cấu hình

Mở trình duyệt và truy cập:

#### ✅ Student Portal (Mặc định)
```
http://truongbuithixuan.local
→ Trỏ đến: C:\xampp\htdocs\truongbuithixuan\student\index.php
```

#### ✅ Admin Panel
```
http://truongbuithixuan.local/admin
→ Trỏ đến: C:\xampp\htdocs\truongbuithixuan\admin\index.php
```

#### ✅ Các module Student
```
http://truongbuithixuan.local/thidua/xep_hang.php
http://truongbuithixuan.local/thidua/cham_diem.php
```

#### ✅ Các module Admin
```
http://truongbuithixuan.local/admin/thidua/xep_hang/tuan.php
http://truongbuithixuan.local/admin/thidua/duyet_diem/index.php
```

---

## 📋 CẤU TRÚC URL

| URL | File thực tế |
|-----|--------------|
| http://truongbuithixuan.local/ | student/index.php |
| http://truongbuithixuan.local/dashboard.php | student/dashboard.php |
| http://truongbuithixuan.local/thidua/xep_hang.php | student/thidua/xep_hang.php |
| http://truongbuithixuan.local/admin/ | admin/index.php |
| http://truongbuithixuan.local/admin/thidua/... | admin/thidua/... |

---

## 🔍 TROUBLESHOOTING

### 1. Lỗi "Page not found" hoặc 404

**Nguyên nhân:** Apache chưa restart hoặc cấu hình sai

**Giải pháp:**
1. Kiểm tra cú pháp Apache:
   ```cmd
   C:\xampp\apache\bin\httpd.exe -t
   ```
   Phải hiện: `Syntax OK`

2. Restart Apache qua XAMPP Control Panel

3. Xóa cache trình duyệt: Ctrl + Shift + Del

### 2. CSS/JS không load

**Nguyên nhân:** Đường dẫn assets sai

**Giải pháp:**
- Check file có Alias /assets trong httpd-vhosts.conf
- Trong code, dùng:
  ```php
  <link href="<?php echo BASE_URL; ?>/assets/css/style.css">
  ```

### 3. Include files bị lỗi

**Nguyên nhân:** Đường dẫn relative sai

**Giải pháp:**
- Trong student/index.php, include vẫn dùng relative path:
  ```php
  require_once '../includes/config.php';
  ```
- Hoặc dùng absolute path với $_SERVER:
  ```php
  require_once $_SERVER['DOCUMENT_ROOT'] . '/../includes/config.php';
  ```

### 4. Admin không truy cập được

**Nguyên nhân:** Thiếu Alias /admin

**Giải pháp:**
- Kiểm tra httpd-vhosts.conf có dòng:
  ```apache
  Alias /admin "C:/xampp/htdocs/truongbuithixuan/admin"
  ```
- Restart Apache

---

## 🎯 LỢI ÍCH CỦA CẤU HÌNH NÀY

### ✅ Student-First Design
- URL gốc (/) trỏ thẳng đến Student Portal
- Học sinh chỉ cần nhớ: **truongbuithixuan.local**
- Không cần gõ thêm /student

### ✅ Admin vẫn hoạt động
- Giáo viên truy cập: **truongbuithixuan.local/admin**
- Rõ ràng phân biệt Student vs Admin

### ✅ SEO-Friendly
- URL ngắn gọn, dễ nhớ
- Không có /student trong đường dẫn

### ✅ Bảo mật
- Admin không phải là root path
- Có thể thêm .htaccess bảo vệ /admin riêng

---

## 📝 GHI CHÚ

### Khác biệt với cấu hình localhost

| Cách truy cập | DocumentRoot | Student URL | Admin URL |
|---------------|--------------|-------------|-----------|
| localhost | C:/xampp/htdocs/truongbuithixuan | localhost/truongbuithixuan/student/ | localhost/truongbuithixuan/admin/ |
| **Virtual Host** | **C:/xampp/htdocs/truongbuithixuan/student** | **truongbuithixuan.local/** | **truongbuithixuan.local/admin/** |

### BASE_URL tự động

File `config.php` tự động detect:
```php
if (host === 'truongbuithixuan.local') {
    BASE_URL = 'http://truongbuithixuan.local'  // Không có /student
} else {
    BASE_URL = 'http://localhost/truongbuithixuan'  // Có /truongbuithixuan
}
```

---

## ✅ CHECKLIST

- [x] Cấu hình httpd-vhosts.conf
- [x] Thêm domain vào hosts file
- [x] Test cú pháp Apache (Syntax OK)
- [ ] **Restart Apache qua XAMPP Control Panel**
- [ ] **Test truy cập http://truongbuithixuan.local**
- [ ] **Test login Student Portal**
- [ ] **Test truy cập Admin Panel**

---

**Cập nhật:** 2026-02-10
**Status:** Cấu hình hoàn tất, chờ restart Apache

---

**🎓 Trường THCS Lê Trí Viễn**
**Hệ thống Học tập & Thi đua Trực tuyến**
