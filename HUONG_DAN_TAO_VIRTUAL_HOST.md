# 🌐 HƯỚNG DẪN TẠO VIRTUAL HOST CHO XAMPP

**Tên domain:** truongbuithixuan.local
**Thư mục:** C:\xampp\htdocs\truongbuithixuan

---

## 📋 BƯỚC 1: Cấu hình Apache Virtual Host

### 1.1 Mở file `httpd-vhosts.conf`
📁 Đường dẫn: `C:\xampp\apache\conf\extra\httpd-vhosts.conf`

**Cách mở:**
- Mở XAMPP Control Panel
- Click nút **[Config]** bên cạnh Apache
- Chọn **"Apache (httpd-vhosts.conf)"**

### 1.2 Thêm cấu hình Virtual Host

Kéo xuống cuối file và thêm đoạn sau:

```apache
##
## Virtual Host cho truongbuithixuan.local
##
<VirtualHost *:80>
    ServerName truongbuithixuan.local
    ServerAlias www.truongbuithixuan.local
    DocumentRoot "C:/xampp/htdocs/truongbuithixuan"

    <Directory "C:/xampp/htdocs/truongbuithixuan">
        Options Indexes FollowSymLinks Includes ExecCGI
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog "logs/truongbuithixuan-error.log"
    CustomLog "logs/truongbuithixuan-access.log" common
</VirtualHost>

##
## QUAN TRỌNG: Giữ lại localhost
##
<VirtualHost *:80>
    ServerName localhost
    DocumentRoot "C:/xampp/htdocs"

    <Directory "C:/xampp/htdocs">
        Options Indexes FollowSymLinks Includes ExecCGI
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

**⚠️ LƯU Ý:**
- Dùng dấu `/` (forward slash), không dùng `\` (backslash)
- Phải có cả VirtualHost cho `localhost` để giữ localhost hoạt động

### 1.3 Lưu file
- Nhấn **Ctrl + S** để lưu
- Đóng Notepad

---

## 📋 BƯỚC 2: Cấu hình Windows Hosts File

### 2.1 Mở file `hosts` với quyền Administrator

**Cách 1: Dùng Notepad**
1. Nhấn **Windows + R**
2. Gõ: `notepad C:\Windows\System32\drivers\etc\hosts`
3. Nếu không mở được, làm theo Cách 2

**Cách 2: Mở Notepad với quyền Admin**
1. Nhấn **Windows**, gõ "Notepad"
2. Click chuột phải → **Run as administrator**
3. Trong Notepad: File → Open
4. Dán đường dẫn: `C:\Windows\System32\drivers\etc\hosts`
5. Chọn "All Files (*.*)" ở dropdown bên dưới
6. Click Open

### 2.2 Thêm domain vào hosts

Kéo xuống cuối file, thêm dòng sau:

```
# Virtual Host cho Trường Bùi Thị Xuân
127.0.0.1    truongbuithixuan.local
127.0.0.1    www.truongbuithixuan.local
```

**File hosts sau khi chỉnh sẽ giống thế này:**
```
# Copyright (c) 1993-2009 Microsoft Corp.
#
# This is a sample HOSTS file used by Microsoft TCP/IP for Windows.
#
# localhost name resolution is handled within DNS itself.
#       127.0.0.1       localhost
#       ::1             localhost

127.0.0.1    localhost
127.0.0.1    truongbuithixuan.local
127.0.0.1    www.truongbuithixuan.local
```

### 2.3 Lưu file
- Nhấn **Ctrl + S**
- Đóng Notepad

---

## 📋 BƯỚC 3: Kiểm tra và Restart Apache

### 3.1 Kiểm tra cấu hình Apache
1. Mở XAMPP Control Panel
2. Click nút **[Config]** bên cạnh Apache
3. Chọn **"Apache (httpd.conf)"**
4. Tìm dòng sau (thường ở dòng 477-480):

```apache
# Virtual hosts
Include conf/extra/httpd-vhosts.conf
```

**⚠️ QUAN TRỌNG:** Nếu dòng này có dấu `#` ở đầu, hãy XÓA dấu `#` để bật Virtual Hosts:
```apache
# Sai (bị comment):
# Include conf/extra/httpd-vhosts.conf

# Đúng (đã bật):
Include conf/extra/httpd-vhosts.conf
```

5. Lưu file nếu có thay đổi

### 3.2 Restart Apache
1. Trong XAMPP Control Panel
2. Click **[Stop]** bên cạnh Apache
3. Đợi 2 giây
4. Click **[Start]** để khởi động lại

**Nếu Apache không start được:**
- Có lỗi cú pháp trong file config
- Xem lỗi trong: XAMPP Control Panel → Logs → Apache (error.log)

---

## 📋 BƯỚC 4: Kiểm tra hoạt động

### 4.1 Kiểm tra DNS
Mở **Command Prompt** (Windows + R → gõ `cmd`):

```bash
ping truongbuithixuan.local
```

**Kết quả đúng:**
```
Pinging truongbuithixuan.local [127.0.0.1] with 32 bytes of data:
Reply from 127.0.0.1: bytes=32 time<1ms TTL=128
```

Nếu hiện `Ping request could not find host`, nghĩa là file hosts chưa đúng.

### 4.2 Truy cập website
Mở trình duyệt, truy cập:

✅ **http://truongbuithixuan.local**
✅ **http://www.truongbuithixuan.local**
✅ **http://localhost** (vẫn hoạt động bình thường)

**Kết quả mong đợi:**
- Trang chủ website Trường Bùi Thị Xuân hiển thị
- URL trên thanh địa chỉ là `truongbuithixuan.local`

---

## 🔧 XỬ LÝ LỖI

### Lỗi 1: Apache không start được sau khi config

**Nguyên nhân:** Lỗi cú pháp trong `httpd-vhosts.conf`

**Cách fix:**
1. Mở XAMPP Control Panel
2. Click **Logs** → **Apache (error.log)**
3. Xem dòng lỗi cuối cùng
4. Thường là:
   - Thiếu dấu `>` hoặc `<`
   - Path sai (dùng `\` thay vì `/`)
   - Thiếu VirtualHost cho localhost

### Lỗi 2: Truy cập truongbuithixuan.local bị lỗi 403 Forbidden

**Nguyên nhân:** Chưa cấp quyền truy cập thư mục

**Cách fix:** Kiểm tra lại phần `<Directory>` trong httpd-vhosts.conf:
```apache
<Directory "C:/xampp/htdocs/truongbuithixuan">
    Options Indexes FollowSymLinks Includes ExecCGI
    AllowOverride All
    Require all granted    # ← Phải có dòng này
</Directory>
```

### Lỗi 3: Truy cập truongbuithixuan.local nhưng vẫn vào localhost

**Nguyên nhân:** File hosts chưa đúng hoặc cần flush DNS

**Cách fix:**
```bash
# Flush DNS cache
ipconfig /flushdns

# Ping lại để kiểm tra
ping truongbuithixuan.local
```

### Lỗi 4: Không sửa được file hosts (Access Denied)

**Nguyên nhân:** Chưa mở Notepad với quyền Administrator

**Cách fix:** Xem lại BƯỚC 2.1 - Cách 2

---

## 📝 CHECKLIST HOÀN THÀNH

- [ ] Đã thêm Virtual Host vào `httpd-vhosts.conf`
- [ ] Đã thêm domain vào file `hosts`
- [ ] Đã kiểm tra `Include conf/extra/httpd-vhosts.conf` trong httpd.conf (không có dấu #)
- [ ] Đã restart Apache thành công
- [ ] Ping `truongbuithixuan.local` thành công (Reply from 127.0.0.1)
- [ ] Truy cập http://truongbuithixuan.local thành công
- [ ] Truy cập http://localhost vẫn hoạt động bình thường

---

## 🎯 KẾT QUẢ SAU KHI HOÀN THÀNH

✅ **Trước đây:**
```
http://localhost/truongbuithixuan/
```

✅ **Bây giờ:**
```
http://truongbuithixuan.local/
```

**Lợi ích:**
- URL ngắn gọn, dễ nhớ
- Giống domain thật khi deploy
- Dễ dàng test session/cookies
- Chuyên nghiệp hơn khi demo

---

## 📌 GHI CHÚ

### Cấu hình database trong config.php
Sau khi tạo virtual host, kiểm tra file `includes/config.php`:

```php
// URL base vẫn dùng localhost hoặc domain mới
define('BASE_URL', 'http://truongbuithixuan.local');

// Database không đổi
define('DB_NAME', 'hoctaptructuyen');
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### Tạo thêm virtual host khác
Nếu muốn tạo thêm domain khác (ví dụ: `chamdiemthidua.local`), lặp lại các bước trên với tên domain và thư mục khác.

---

**Chúc bạn thành công! 🎉**

Nếu gặp lỗi, hãy kiểm tra Apache error log:
- XAMPP Control Panel → Logs → Apache (error.log)
