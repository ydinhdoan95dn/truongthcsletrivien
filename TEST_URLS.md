# 🧪 TEST URLS - truongbuithixuan.local

**Ngày test:** 2026-02-10
**Domain:** http://truongbuithixuan.local

---

## 📋 DANH SÁCH URL CẦN TEST

### ✅ ROOT & LANDING

| # | Mô tả | URL | File thực tế | Status |
|---|-------|-----|--------------|--------|
| 1 | Root / Landing Page | http://truongbuithixuan.local/ | [index.php](c:\xampp\htdocs\truongbuithixuan\index.php) | ✅ File exists |
| 2 | Login Page | http://truongbuithixuan.local/login.php | [login.php](c:\xampp\htdocs\truongbuithixuan\login.php) | ✅ File exists |

---

### ✅ STUDENT PORTAL

| # | Mô tả | URL | File thực tế | Status |
|---|-------|-----|--------------|--------|
| 3 | Student Index | http://truongbuithixuan.local/student/ | [student/index.php](c:\xampp\htdocs\truongbuithixuan\student\index.php) | ✅ File exists |
| 4 | Student Dashboard | http://truongbuithixuan.local/student/dashboard.php | [student/dashboard.php](c:\xampp\htdocs\truongbuithixuan\student\dashboard.php) | ✅ File exists |
| 5 | **Xếp hạng lớp** | http://truongbuithixuan.local/student/thidua/xep_hang.php | [student/thidua/xep_hang.php](c:\xampp\htdocs\truongbuithixuan\student\thidua\xep_hang.php) | ✅ File exists |
| 6 | **Cờ đỏ chấm điểm** | http://truongbuithixuan.local/student/thidua/cham_diem.php | [student/thidua/cham_diem.php](c:\xampp\htdocs\truongbuithixuan\student\thidua\cham_diem.php) | ✅ File exists |

---

### ✅ ADMIN PANEL

| # | Mô tả | URL | File thực tế | Status |
|---|-------|-----|--------------|--------|
| 7 | Admin Index | http://truongbuithixuan.local/admin/ | [admin/index.php](c:\xampp\htdocs\truongbuithixuan\admin\index.php) | ✅ File exists |
| 8 | Admin Login | http://truongbuithixuan.local/admin/login.php | [admin/login.php](c:\xampp\htdocs\truongbuithixuan\admin\login.php) | ✅ File exists |
| 9 | Admin Dashboard | http://truongbuithixuan.local/admin/dashboard.php | [admin/dashboard.php](c:\xampp\htdocs\truongbuithixuan\admin\dashboard.php) | ⚠️ Cần kiểm tra |

---

### ✅ ADMIN - THI ĐUA MODULES

| # | Mô tả | URL | File thực tế | Status |
|---|-------|-----|--------------|--------|
| 10 | **Xếp hạng tuần** | http://truongbuithixuan.local/admin/thidua/xep_hang/tuan.php | [admin/thidua/xep_hang/tuan.php](c:\xampp\htdocs\truongbuithixuan\admin\thidua\xep_hang\tuan.php) | ✅ File exists |
| 11 | Duyệt điểm | http://truongbuithixuan.local/admin/thidua/duyet_diem/ | [admin/thidua/duyet_diem/index.php](c:\xampp\htdocs\truongbuithixuan\admin\thidua\duyet_diem\index.php) | ✅ File exists |
| 12 | Phân công chấm chéo | http://truongbuithixuan.local/admin/thidua/phan_cong_cham_diem/ | [admin/thidua/phan_cong_cham_diem/index.php](c:\xampp\htdocs\truongbuithixuan\admin\thidua\phan_cong_cham_diem\index.php) | ✅ File exists |
| 13 | Quản lý Cờ đỏ | http://truongbuithixuan.local/admin/thidua/hoc_sinh_co_do/ | [admin/thidua/hoc_sinh_co_do/index.php](c:\xampp\htdocs\truongbuithixuan\admin\thidua\hoc_sinh_co_do\index.php) | ✅ File exists |

---

## 🔧 .htaccess STATUS

| Thư mục | .htaccess | Trạng thái |
|---------|-----------|------------|
| / (root) | ✅ Có | Updated for truongbuithixuan.local |
| /admin | ✅ Có | Updated for truongbuithixuan.local |
| /student | ❌ Không | OK - Inherit từ root |
| /student/thidua | ❌ Không | OK - Inherit từ root |
| /admin/thidua | ❌ Không | OK - Inherit từ admin |

**Kết luận:** ✅ Không cần thêm .htaccess vào các thư mục con. Apache sẽ inherit từ parent.

---

## 🧪 HƯỚNG DẪN TEST

### Bước 1: Import dữ liệu mẫu

```bash
# Mở MySQL command line hoặc phpMyAdmin
mysql -uroot hoctaptructuyen < C:\xampp\htdocs\truongbuithixuan\database\seed_data_thidua.sql
```

**Dữ liệu được tạo:**
- ✅ 16 lớp THCS (Khối 6-9, mỗi khối 4 lớp)
- ✅ 32 học sinh (16 Cờ đỏ + 16 Thường)
- ✅ 16 phân công chấm chéo (cross-checking)
- ✅ 5 tiêu chí thi đua
- ✅ 1 tuần học mẫu (Tuần 20)
- ✅ Điểm mẫu cho test workflow

---

### Bước 2: Restart Apache

**XAMPP Control Panel:**
1. Stop Apache
2. Start Apache

---

### Bước 3: Test từng URL

#### 3.1 Test Root

```
✅ http://truongbuithixuan.local/
→ Phải hiển thị Landing Page (sidebar + tài liệu + top học sinh)
```

#### 3.2 Test Student Login

```
Username: HS6A101
Password: 123456
Role: Cờ đỏ lớp 6A1 (được phân công chấm lớp 6A2)

Sau khi login:
✅ http://truongbuithixuan.local/student/dashboard.php
→ Dashboard học sinh

✅ http://truongbuithixuan.local/student/thidua/cham_diem.php
→ Form chấm điểm (chỉ hiện lớp 6A2)

✅ http://truongbuithixuan.local/student/thidua/xep_hang.php
→ Xếp hạng lớp
```

#### 3.3 Test Admin Login

```
Username: admin (hoặc tài khoản admin hiện có)
Password: (mật khẩu admin)

Sau khi login:
✅ http://truongbuithixuan.local/admin/dashboard.php
→ Dashboard admin

✅ http://truongbuithixuan.local/admin/thidua/xep_hang/tuan.php
→ Xếp hạng tuần

✅ http://truongbuithixuan.local/admin/thidua/duyet_diem/
→ Duyệt điểm

✅ http://truongbuithixuan.local/admin/thidua/phan_cong_cham_diem/
→ Quản lý phân công

✅ http://truongbuithixuan.local/admin/thidua/hoc_sinh_co_do/
→ Quản lý Cờ đỏ
```

---

## 🎯 TEST SCENARIOS

### Scenario 1: Student Cờ đỏ chấm điểm

**Login:** HS6A101 / 123456

**Steps:**
1. Vào http://truongbuithixuan.local/student/thidua/cham_diem.php
2. Chọn lớp: 6A2 (lớp được phân công)
3. Chọn tuần: Tuần 20
4. Nhập điểm 5 tiêu chí:
   - Học tập: 8.5
   - Nề nếp: 9.0
   - Vệ sinh: 8.0
   - Hoạt động: 9.5
   - Đoàn kết: 9.0
5. Click **[Gửi duyệt]**

**Expected:**
- ✅ Thông báo: "Gửi điểm thành công!"
- ✅ Trạng thái: cho_duyet
- ✅ Không thể chỉnh sửa nữa

---

### Scenario 2: Admin duyệt điểm

**Login:** admin

**Steps:**
1. Vào http://truongbuithixuan.local/admin/thidua/duyet_diem/
2. Chọn tuần: Tuần 20
3. Click **[Duyệt tất cả]**

**Expected:**
- ✅ Thông báo: "Đã duyệt tất cả thành công! Xếp hạng đã được cập nhật."
- ✅ Auto-calculate xếp hạng
- ✅ Dữ liệu xuất hiện trong xep_hang_lop_tuan

---

### Scenario 3: Xem xếp hạng

**Login:** HS6A101 / 123456

**Steps:**
1. Vào http://truongbuithixuan.local/student/thidua/xep_hang.php
2. Chọn tuần: Tuần 20
3. Xem xếp hạng lớp mình

**Expected:**
- ✅ Hero card hiển thị: Hạng, Điểm, Xếp loại
- ✅ Chi tiết điểm 5 tiêu chí với progress bars
- ✅ Medal nếu Top 3

---

## 🐛 TROUBLESHOOTING

### Lỗi 404 "Object not found"

**Nguyên nhân:**
- Apache chưa restart
- Virtual host sai
- .htaccess sai

**Giải pháp:**
```bash
# 1. Test Apache syntax
C:\xampp\apache\bin\httpd.exe -t
→ Phải: Syntax OK

# 2. Restart Apache

# 3. Check virtual host
tail -20 "C:\xampp\apache\conf\extra\httpd-vhosts.conf"
→ DocumentRoot phải: C:/xampp/htdocs/truongbuithixuan

# 4. Check hosts file
cat "C:\Windows\System32\drivers\etc\hosts" | grep truongbuithixuan
→ Phải có: 127.0.0.1 truongbuithixuan.local
```

---

### Lỗi "Permission denied" khi chấm điểm

**Nguyên nhân:**
- Học sinh không phải Cờ đỏ
- Chưa có phân công chấm chéo

**Giải pháp:**
```sql
-- Check Cờ đỏ
SELECT id, ho_ten, la_co_do FROM hoc_sinh WHERE ma_hs = 'HS6A101';
→ la_co_do phải = 1

-- Check phân công
SELECT * FROM phan_cong_cham_diem WHERE hoc_sinh_id = (SELECT id FROM hoc_sinh WHERE ma_hs = 'HS6A101');
→ Phải có record với lop_duoc_cham_id
```

---

### Lỗi "Chưa có dữ liệu xếp hạng"

**Nguyên nhân:**
- Admin chưa duyệt điểm
- Auto-calculate chưa chạy

**Giải pháp:**
1. Admin duyệt điểm tuần đó
2. Check bảng xep_hang_lop_tuan:
   ```sql
   SELECT * FROM xep_hang_lop_tuan WHERE tuan_id = 20;
   ```

---

## 📊 DATABASE CHECK

Sau khi import seed data, check:

```sql
-- 1. Tổng lớp THCS
SELECT COUNT(*) FROM lop_hoc WHERE khoi IN (6,7,8,9);
→ Phải: 16

-- 2. Tổng học sinh
SELECT COUNT(*) FROM hoc_sinh WHERE lop_id IN (SELECT id FROM lop_hoc WHERE khoi IN (6,7,8,9));
→ Phải: 32

-- 3. Cờ đỏ
SELECT COUNT(*) FROM hoc_sinh WHERE la_co_do = 1;
→ Phải: 16

-- 4. Phân công chấm chéo
SELECT COUNT(*) FROM phan_cong_cham_diem WHERE trang_thai = 'active';
→ Phải: 16

-- 5. Tiêu chí
SELECT COUNT(*) FROM tieu_chi_thi_dua;
→ Phải: 5

-- 6. Chi tiết phân công khối 6
SELECT
    hs.ma_hs,
    hs.ho_ten,
    lh1.khoi_label as lop_cua_hs,
    lh2.khoi_label as lop_duoc_cham
FROM phan_cong_cham_diem pc
JOIN hoc_sinh hs ON pc.hoc_sinh_id = hs.id
JOIN lop_hoc lh1 ON hs.lop_id = lh1.id
JOIN lop_hoc lh2 ON pc.lop_duoc_cham_id = lh2.id
WHERE lh1.khoi = 6;

→ Phải hiển thị:
HS6A101 (6A1) → chấm 6A2
HS6A201 (6A2) → chấm 6A3
HS6A301 (6A3) → chấm 6A4
HS6A401 (6A4) → chấm 6A1
```

---

## ✅ CHECKLIST

### Import Data
- [ ] Import seed_data_thidua.sql
- [ ] Check database: 16 lớp, 32 HS, 16 Cờ đỏ
- [ ] Check phân công chấm chéo

### Virtual Host
- [ ] Cấu hình httpd-vhosts.conf
- [ ] Cập nhật .htaccess root
- [ ] Cập nhật .htaccess admin
- [ ] Restart Apache
- [ ] Test syntax: `httpd.exe -t`

### Test URLs
- [ ] http://truongbuithixuan.local/ → Landing Page
- [ ] http://truongbuithixuan.local/student/thidua/cham_diem.php → Chấm điểm
- [ ] http://truongbuithixuan.local/student/thidua/xep_hang.php → Xếp hạng
- [ ] http://truongbuithixuan.local/admin/thidua/xep_hang/tuan.php → Admin xếp hạng
- [ ] http://truongbuithixuan.local/admin/thidua/duyet_diem/ → Duyệt điểm

### Test Workflow
- [ ] Login Cờ đỏ (HS6A101)
- [ ] Chấm điểm lớp 6A2
- [ ] Gửi duyệt
- [ ] Login Admin
- [ ] Duyệt tất cả điểm tuần 20
- [ ] Check auto-calculate xếp hạng
- [ ] Login Student xem xếp hạng

---

**Tất cả files đã sẵn sàng! Chỉ cần import database và test!** 🎉

---

**🎓 Trường THCS Lê Trí Viễn**
**Hệ thống Học tập & Thi đua Trực tuyến**
