# 🌐 CẤU HÌNH DOMAIN ẢO - truongbuithixuan.local

**Trường THCS Lê Trí Viễn**
**Hệ thống Học tập & Thi đua Trực tuyến**

---

## ✅ ĐÃ CẤU HÌNH HOÀN TẤT

### 📋 LOGIC ROUTING

```
http://truongbuithixuan.local/
    ↓
C:\xampp\htdocs\truongbuithixuan\index.php
    ↓
    ├─ Nếu isStudentLoggedIn() → redirect('student/dashboard.php')
    ├─ Nếu isAdminLoggedIn()   → redirect('admin/dashboard.php')
    └─ Chưa login              → Hiển thị Landing Page
```

---

## 📁 CẤU HÌNH CHI TIẾT

### 1. Virtual Host Apache

**File:** `C:\xampp\apache\conf\extra\httpd-vhosts.conf`

```apache
# Virtual host cho truongbuithixuan.local
<VirtualHost *:80>
    ServerName truongbuithixuan.local
    ServerAlias www.truongbuithixuan.local
    DocumentRoot "C:/xampp/htdocs/truongbuithixuan"

    <Directory "C:/xampp/htdocs/truongbuithixuan">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog "logs/truongbuithixuan.local-error.log"
    CustomLog "logs/truongbuithixuan.local-access.log" common
</VirtualHost>
```

**Đặc điểm:**
- ✅ DocumentRoot trỏ đến thư mục GỐC (không phải /student)
- ✅ index.php gốc sẽ xử lý routing
- ✅ Không cần Alias vì tất cả file đều trong DocumentRoot

---

### 2. Hosts File

**File:** `C:\Windows\System32\drivers\etc\hosts`

```
127.0.0.1 truongbuithixuan.local
```

---

### 3. .htaccess

**File:** `C:\xampp\htdocs\truongbuithixuan\.htaccess`

```apache
# Local Virtual Host (truongbuithixuan.local)
RewriteCond %{HTTP_HOST} truongbuithixuan\.local$ [NC]
RewriteRule .* - [E=BASE:/]

# Mac dinh RewriteBase
RewriteBase /
```

**Đặc điểm:**
- ✅ RewriteBase = `/` cho truongbuithixuan.local
- ✅ Tương tự Production (không có subfolder)
- ✅ Khác với localhost (có /truongbuithixuan/)

---

### 4. Config.php

**File:** `includes/config.php`

```php
// Local Virtual Host
$localDomains = array(
    'truongbuithixuan.local',
    'www.truongbuithixuan.local'
);

if ($isLocalVirtualHost) {
    define('BASE_URL', 'http://truongbuithixuan.local');
}
```

**Đặc điểm:**
- ✅ Tự động detect domain ảo
- ✅ BASE_URL không có subfolder
- ✅ Giống Production

---

## 🚀 CÁCH SỬ DỤNG

### Bước 1: Restart Apache

**Mở XAMPP Control Panel:**
1. Click **[Stop]** bên cạnh Apache
2. Đợi 2-3 giây
3. Click **[Start]**

---

### Bước 2: Truy cập

Mở trình duyệt và vào:

```
http://truongbuithixuan.local/
```

**Kết quả mong đợi:**

#### ✅ Chưa đăng nhập
→ Hiển thị **Landing Page** (trang chủ đẹp với sidebar, danh sách tài liệu, top học sinh)

#### ✅ Đã đăng nhập Student
→ Tự động redirect về `http://truongbuithixuan.local/student/dashboard.php`

#### ✅ Đã đăng nhập Admin
→ Tự động redirect về `http://truongbuithixuan.local/admin/dashboard.php`

---

## 🌐 CẤU TRÚC URL

| URL | File thực tế | Mô tả |
|-----|--------------|-------|
| http://truongbuithixuan.local/ | index.php | Landing Page / Router |
| http://truongbuithixuan.local/login.php | login.php | Trang login chung |
| http://truongbuithixuan.local/student/dashboard.php | student/dashboard.php | Dashboard học sinh |
| http://truongbuithixuan.local/student/thidua/xep_hang.php | student/thidua/xep_hang.php | Xếp hạng lớp |
| http://truongbuithixuan.local/student/thidua/cham_diem.php | student/thidua/cham_diem.php | Cờ đỏ chấm điểm |
| http://truongbuithixuan.local/admin/dashboard.php | admin/dashboard.php | Dashboard admin |
| http://truongbuithixuan.local/admin/thidua/xep_hang/tuan.php | admin/thidua/xep_hang/tuan.php | Admin xem xếp hạng |
| http://truongbuithixuan.local/admin/thidua/duyet_diem/ | admin/thidua/duyet_diem/index.php | Admin duyệt điểm |

---

## 🎯 SO SÁNH CẤU HÌNH

| Aspect | localhost | truongbuithixuan.local |
|--------|-----------|------------------------|
| **URL** | http://localhost/truongbuithixuan/ | http://truongbuithixuan.local/ |
| **DocumentRoot** | C:/xampp/htdocs | C:/xampp/htdocs/truongbuithixuan |
| **BASE_URL** | http://localhost/truongbuithixuan | http://truongbuithixuan.local |
| **RewriteBase** | /truongbuithixuan/ | / |
| **Landing Page** | localhost/truongbuithixuan/index.php | truongbuithixuan.local/ |
| **Student** | localhost/truongbuithixuan/student/ | truongbuithixuan.local/student/ |
| **Admin** | localhost/truongbuithixuan/admin/ | truongbuithixuan.local/admin/ |

---

## 🔍 TROUBLESHOOTING

### 1. Lỗi 404 "Object not found"

**Nguyên nhân:** Apache chưa restart hoặc cấu hình sai

**Giải pháp:**
```cmd
# Test cú pháp
C:\xampp\apache\bin\httpd.exe -t
→ Phải hiện: Syntax OK

# Restart Apache qua XAMPP Control Panel
```

### 2. Vẫn redirect về localhost/truongbuithixuan

**Nguyên nhân:** Cache trình duyệt hoặc BASE_URL sai

**Giải pháp:**
1. Xóa cache: `Ctrl + Shift + Del`
2. Hard refresh: `Ctrl + F5`
3. Check `config.php` → BASE_URL phải là `http://truongbuithixuan.local`

### 3. CSS/JS không load

**Nguyên nhân:** Đường dẫn asset sai

**Giải pháp:**
- Trong code, luôn dùng:
  ```php
  <link href="<?php echo BASE_URL; ?>/assets/css/style.css">
  ```

### 4. includes/config.php not found

**Nguyên nhân:** Đường dẫn relative sai

**Giải pháp:**
- Từ root: `require_once 'includes/config.php';`
- Từ student: `require_once '../includes/config.php';`
- Từ admin: `require_once '../includes/config.php';`

---

## ✨ LỢI ÍCH

### ✅ Giống Production
- URL sạch, không có subfolder
- BASE_URL không có /truongbuithixuan
- Test giống môi trường thật

### ✅ Dễ nhớ
- Chỉ cần: `truongbuithixuan.local`
- Không cần nhớ: `localhost/truongbuithixuan`

### ✅ Routing thông minh
- index.php tự động phân biệt Student/Admin
- Không cần login 2 lần

### ✅ Phát triển nhanh
- Không cần sửa code khi deploy
- .htaccess tự động detect môi trường

---

## 📝 CHECKLIST

- [x] Cấu hình httpd-vhosts.conf
- [x] DocumentRoot = C:/xampp/htdocs/truongbuithixuan
- [x] Thêm domain vào hosts file
- [x] Cập nhật .htaccess
- [x] Test Apache syntax (Syntax OK)
- [ ] **Restart Apache qua XAMPP Control Panel**
- [ ] **Test http://truongbuithixuan.local/**
- [ ] **Test login Student → redirect student/dashboard.php**
- [ ] **Test login Admin → redirect admin/dashboard.php**

---

## 🎓 VỊ TRÍ FILE QUAN TRỌNG

```
truongbuithixuan/
├── index.php                              ← Router chính (lines 14-20)
├── login.php                              ← Login chung
├── .htaccess                              ← Rewrite rules (updated)
├── includes/
│   └── config.php                         ← BASE_URL auto-detect
├── student/
│   ├── dashboard.php
│   └── thidua/
│       ├── xep_hang.php
│       └── cham_diem.php
└── admin/
    ├── dashboard.php
    └── thidua/
        ├── xep_hang/tuan.php
        └── duyet_diem/index.php
```

---

**Cập nhật:** 2026-02-10 21:15
**Status:** ✅ Cấu hình hoàn tất, chờ restart Apache

---

**Sau khi restart Apache, truy cập:**
```
http://truongbuithixuan.local/
```

Nếu thấy Landing Page → **Thành công!** 🎉

---

**🎓 Trường THCS Lê Trí Viễn**
**Hệ thống Học tập & Thi đua Trực tuyến**
