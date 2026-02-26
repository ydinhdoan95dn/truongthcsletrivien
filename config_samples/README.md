# 🌐 Virtual Host Configuration Files

Thư mục này chứa các file mẫu để tạo Virtual Host cho XAMPP.

---

## 📋 CHỌN MỘT TRONG HAI CÁCH:

### ⚡ CÁCH 1: TỰ ĐỘNG (Khuyến nghị - Nhanh nhất)

**File:** `add-virtualhost.ps1`

**Các bước:**
1. Click chuột phải vào file `add-virtualhost.ps1`
2. Chọn **"Run with PowerShell"**
3. Chọn **"Yes"** khi hỏi quyền Administrator
4. Đợi script chạy xong
5. Mở XAMPP → Stop và Start lại Apache
6. Truy cập: http://truongbuithixuan.local

**✅ Ưu điểm:**
- Tự động backup files cũ
- Tự động thêm vào hosts file
- Tự động thêm Virtual Host
- Tự động flush DNS
- Nhanh, chính xác, ít lỗi

---

### 🔧 CÁCH 2: THỦ CÔNG (Nếu Cách 1 không được)

**File:** Xem `HUONG_DAN_TAO_VIRTUAL_HOST.md` (ở thư mục gốc)

**Các bước:**
1. Đọc hướng dẫn chi tiết trong `HUONG_DAN_TAO_VIRTUAL_HOST.md`
2. Copy nội dung từ `httpd-vhosts-sample.conf`
3. Paste vào `C:\xampp\apache\conf\extra\httpd-vhosts.conf`
4. Copy nội dung từ `hosts-sample.txt`
5. Paste vào `C:\Windows\System32\drivers\etc\hosts`
6. Restart Apache
7. Truy cập: http://truongbuithixuan.local

**✅ Ưu điểm:**
- Hiểu rõ từng bước làm gì
- Tự chỉnh sửa nếu cần

---

## 📁 Danh sách files trong thư mục này:

| File | Mô tả |
|------|-------|
| `README.md` | File này - Hướng dẫn chung |
| `add-virtualhost.ps1` | Script PowerShell tự động thêm Virtual Host |
| `httpd-vhosts-sample.conf` | Mẫu cấu hình Apache Virtual Host |
| `hosts-sample.txt` | Mẫu file Windows hosts |

---

## ⚠️ LƯU Ý QUAN TRỌNG:

1. **Backup trước khi làm:**
   - Script tự động backup (Cách 1)
   - Nếu làm thủ công (Cách 2), hãy backup 2 files:
     - `C:\xampp\apache\conf\extra\httpd-vhosts.conf`
     - `C:\Windows\System32\drivers\etc\hosts`

2. **Cần quyền Administrator:**
   - Chỉnh sửa file hosts cần quyền Admin
   - Script PowerShell cần chạy với quyền Admin

3. **Sau khi config xong:**
   - Phải Restart Apache
   - Flush DNS: `ipconfig /flushdns`
   - Ping test: `ping truongbuithixuan.local`

---

## 🔍 Kiểm tra sau khi hoàn thành:

### Test 1: Ping domain
```bash
ping truongbuithixuan.local
```
**Kết quả mong đợi:** Reply from 127.0.0.1

### Test 2: Truy cập website
Mở trình duyệt:
- ✅ http://truongbuithixuan.local
- ✅ http://www.truongbuithixuan.local
- ✅ http://localhost (vẫn hoạt động)

### Test 3: Kiểm tra Apache
XAMPP Control Panel → Apache phải màu **xanh lá**

---

## 🆘 Nếu gặp lỗi:

### Apache không start được
- Mở Apache error log: XAMPP Control Panel → Logs → Apache (error.log)
- Kiểm tra lỗi cú pháp trong httpd-vhosts.conf

### Domain không hoạt động
- Kiểm tra file hosts đã thêm domain chưa
- Flush DNS: `ipconfig /flushdns`
- Ping để test: `ping truongbuithixuan.local`

### Lỗi 403 Forbidden
- Kiểm tra quyền trong `<Directory>`:
  ```apache
  Require all granted
  ```

---

## 📞 Thông tin dự án:

- **Tên dự án:** Trường THCS Lê Trí Viễn
- **Domain local:** truongbuithixuan.local
- **Thư mục:** C:\xampp\htdocs\truongbuithixuan
- **Database:** hoctaptructuyen

---

**Chúc bạn thành công! 🎉**
