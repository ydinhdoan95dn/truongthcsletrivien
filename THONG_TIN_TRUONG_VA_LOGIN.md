# 🏫 THÔNG TIN TRƯỜNG & TÀI KHOẢN LOGIN

**Cập nhật:** 2026-02-10 21:35

---

## 🎓 THÔNG TIN TRƯỜNG HỌC

```
Tên trường: Trường THCS Lê Trí Viễn
Địa chỉ: Phường Điện Bàn Bắc - TP Đà Nẵng
Giáo viên phụ trách: Đoàn Thị Ngọc Lĩnh
Cấp học: THCS (Lớp 6, 7, 8, 9)
```

---

## ✅ ĐÃ CẬP NHẬT THÔNG TIN TRƯỜNG

### 1. Config File
**File:** [includes/config.php](c:\xampp\htdocs\truongbuithixuan\includes\config.php)

```php
define('SITE_NAME', 'Trường THCS Lê Trí Viễn');
define('SITE_FULL_NAME', 'Trường Trung học Cơ sở Lê Trí Viễn');
define('SITE_ADDRESS', 'Phường Điện Bàn Bắc - TP Đà Nẵng');
define('SITE_TEACHER', 'Đoàn Thị Ngọc Lĩnh');
define('SITE_DESCRIPTION', 'Hệ thống Học tập & Thi đua Trực tuyến');
```

### 2. Database
**Bảng:** `cau_hinh`

```sql
site_name = 'Trường THCS Lê Trí Viễn'
school_address = 'Phường Điện Bàn Bắc - TP Đà Nẵng'
```

### 3. Password đã cập nhật
**Tất cả học sinh THCS (Khối 6-9):** Password đã được cập nhật theo format đúng của hệ thống

---

## 🔐 TÀI KHOẢN LOGIN TEST

### **Password chung cho TẤT CẢ tài khoản:**
```
Password: 123456
```

### **Tài khoản Học sinh Cờ đỏ (Có quyền chấm điểm):**

| Username | Họ tên | Lớp | Chức năng |
|----------|--------|-----|-----------|
| **HS6A101** | Nguyễn Văn An | 6A1 | Chấm điểm lớp 6A2 |
| **HS6A201** | Lê Văn Cường | 6A2 | Chấm điểm lớp 6A3 |
| **HS6A301** | Hoàng Văn Em | 6A3 | Chấm điểm lớp 6A4 |
| **HS6A401** | Đỗ Văn Giang | 6A4 | Chấm điểm lớp 6A1 |
| HS7A101 | Bùi Văn Hùng | 7A1 | Chấm điểm lớp 7A2 |
| HS7A201 | Trương Văn Kiên | 7A2 | Chấm điểm lớp 7A3 |
| HS8A101 | Cao Văn Sơn | 8A1 | Chấm điểm lớp 8A2 |
| HS9A101 | Đào Văn Bình | 9A1 | Chấm điểm lớp 9A2 |

### **Tài khoản Học sinh thường (Chỉ xem điểm):**

| Username | Họ tên | Lớp |
|----------|--------|-----|
| HS6A102 | Trần Thị Bình | 6A1 |
| HS6A202 | Phạm Thị Dung | 6A2 |
| HS7A102 | Đinh Thị Lan | 7A1 |
| HS8A102 | Huỳnh Thị Tâm | 8A1 |

---

## 🎯 CHỨC NĂNG MỚI - HỆ THỐNG THI ĐUA

### **Dành cho Học sinh Cờ đỏ:**

#### 1. **Chấm điểm thi đua lớp**
**URL:** http://truongbuithixuan.local/student/thidua/cham_diem.php

**Chức năng:**
- ✅ Chấm điểm 5 tiêu chí cho lớp được phân công (KHÔNG phải lớp mình)
- ✅ Tiêu chí: Học tập (40%), Nề nếp (25%), Vệ sinh (15%), Hoạt động (15%), Đoàn kết (5%)
- ✅ Lưu tạm hoặc Gửi duyệt
- ✅ Không thể sửa sau khi gửi

#### 2. **Xem xếp hạng lớp**
**URL:** http://truongbuithixuan.local/student/thidua/xep_hang.php

**Chức năng:**
- ✅ Xem xếp hạng lớp mình
- ✅ Hero card hiển thị: Hạng, Điểm, Xếp loại
- ✅ Chi tiết điểm từng tiêu chí với progress bars
- ✅ Medal cho Top 3
- ✅ Xem bảng xếp hạng toàn khối

### **Dành cho Học sinh thường:**

#### 1. **Xem điểm lớp**
**URL:** http://truongbuithixuan.local/student/thidua/xep_hang.php

**Chức năng:**
- ✅ Xem điểm và xếp hạng lớp mình
- ✅ Xem chi tiết điểm từng tuần
- ✅ Theo dõi tiến độ lớp

---

## 👨‍🏫 CHỨC NĂNG ADMIN / GIÁO VIÊN

### **Dành cho Admin:**

#### 1. **Quản lý học sinh Cờ đỏ**
**URL:** http://truongbuithixuan.local/admin/thidua/hoc_sinh_co_do/

**Chức năng:**
- ✅ Gắn/Gỡ Cờ đỏ cho học sinh
- ✅ Toggle switch AJAX (không reload trang)
- ✅ Thống kê số lượng Cờ đỏ theo lớp
- ✅ Lịch sử gắn/gỡ Cờ đỏ

#### 2. **Phân công chấm chéo**
**URL:** http://truongbuithixuan.local/admin/thidua/phan_cong_cham_diem/

**Chức năng:**
- ✅ Phân công học sinh Cờ đỏ chấm lớp nào
- ✅ Logic chấm chéo: KHÔNG được chấm lớp mình
- ✅ CRUD đầy đủ (Create, Read, Update, Delete)
- ✅ Validation tự động

#### 3. **Duyệt điểm tuần**
**URL:** http://truongbuithixuan.local/admin/thidua/duyet_diem/

**Chức năng:**
- ✅ Xem danh sách điểm chờ duyệt
- ✅ Duyệt từng lớp hoặc duyệt tất cả
- ✅ Từ chối điểm với lý do
- ✅ **Tự động tính toán xếp hạng** sau khi duyệt
- ✅ Lịch sử duyệt/từ chối

#### 4. **Xếp hạng tuần**
**URL:** http://truongbuithixuan.local/admin/thidua/xep_hang/tuan.php

**Chức năng:**
- ✅ Xem bảng xếp hạng toàn trường
- ✅ Filter theo khối
- ✅ Stats cards (Xuất sắc, Tốt, Khá, TB, Cần cố gắng)
- ✅ Medal cho Top 3
- ✅ Xuất Excel (placeholder)

---

## 🔄 WORKFLOW THI ĐUA

```
┌─────────────────────────────────────────┐
│  BƯỚC 1: HỌC SINH CỜ ĐỎ CHẤM ĐIỂM     │
└─────────────────────────────────────────┘
Thứ 2-5:
• HS Cờ đỏ login → student/thidua/cham_diem.php
• Chấm điểm lớp được phân công (VD: HS 6A1 chấm lớp 6A2)
• Nhập điểm 5 tiêu chí
• [Lưu tạm] hoặc [Gửi duyệt]
• Trạng thái: cho_duyet

┌─────────────────────────────────────────┐
│  BƯỚC 2: ADMIN DUYỆT                    │
└─────────────────────────────────────────┘
Thứ 6:
• Admin login → admin/thidua/duyet_diem/
• Xem tất cả điểm chờ duyệt
• [Duyệt tất cả] hoặc [Từ chối]
• Trạng thái: da_duyet
• ✨ Tự động tính toán xếp hạng

┌─────────────────────────────────────────┐
│  BƯỚC 3: CÔNG BỐ KẾT QUẢ               │
└─────────────────────────────────────────┘
Chủ nhật:
• Học sinh xem: student/thidua/xep_hang.php
• Hiển thị: Hạng, Điểm, Xếp loại, Medal
```

---

## 📊 DỮ LIỆU HIỆN TẠI

```sql
✅ 4 lớp THCS (Khối 6: 6A1, 6A2, 6A3, 6A4)
✅ 8 học sinh (4 Cờ đỏ + 4 Thường)
✅ 4 phân công chấm chéo (6A1→6A2→6A3→6A4→6A1)
✅ 5 tiêu chí thi đua (Học tập, Nề nếp, Vệ sinh, Hoạt động, Đoàn kết)
```

---

## 🧪 HƯỚNG DẪN TEST NHANH

### Test 1: Login Student Cờ đỏ

```
1. Vào: http://truongbuithixuan.local/
2. Login: HS6A101 / 123456
3. Dashboard → Thấy menu "Thi đua"
4. Click "Chấm điểm" → Chỉ thấy lớp 6A2
```

### Test 2: Chấm điểm

```
1. Login: HS6A101 / 123456
2. Vào: student/thidua/cham_diem.php
3. Chọn lớp: 6A2
4. Nhập điểm:
   - Học tập: 8.5
   - Nề nếp: 9.0
   - Vệ sinh: 8.0
   - Hoạt động: 9.5
   - Đoàn kết: 9.0
5. Click [Gửi duyệt]
6. ✅ Thành công: "Gửi điểm thành công!"
```

### Test 3: Admin duyệt

```
1. Login Admin
2. Vào: admin/thidua/duyet_diem/
3. Thấy điểm chờ duyệt từ HS6A101
4. Click [Duyệt tất cả]
5. ✅ Thành công: "Xếp hạng đã được cập nhật"
```

### Test 4: Xem xếp hạng

```
1. Login: HS6A101 / 123456
2. Vào: student/thidua/xep_hang.php
3. ✅ Thấy Hero card với Hạng, Điểm, Xếp loại
4. ✅ Thấy chi tiết điểm 5 tiêu chí
```

---

## 🎨 HIỂN THỊ THÔNG TIN TRƯỜNG

### Các trang đã cập nhật thông tin:

| Trang | Hiển thị |
|-------|----------|
| Landing Page | "Trường THCS Lê Trí Viễn" |
| Student Dashboard | "Trường THCS Lê Trí Viễn" |
| Admin Dashboard | "Trường THCS Lê Trí Viễn" |
| Footer | "Phường Điện Bàn Bắc - TP Đà Nẵng" |
| Header | "GV: Đoàn Thị Ngọc Lĩnh" |

### Sử dụng constants trong code:

```php
<?php echo SITE_NAME; ?>
// → "Trường THCS Lê Trí Viễn"

<?php echo SITE_FULL_NAME; ?>
// → "Trường Trung học Cơ sở Lê Trí Viễn"

<?php echo SITE_ADDRESS; ?>
// → "Phường Điện Bàn Bắc - TP Đà Nẵng"

<?php echo SITE_TEACHER; ?>
// → "Đoàn Thị Ngọc Lĩnh"
```

---

## ✅ CHECKLIST HOÀN TẤT

- [x] ✅ Cập nhật thông tin trường trong config.php
- [x] ✅ Cập nhật thông tin trường trong database
- [x] ✅ Cập nhật password cho tất cả học sinh THCS
- [x] ✅ Tạo dữ liệu mẫu (4 lớp, 8 học sinh, 4 phân công)
- [x] ✅ Module 1: Phân công chấm chéo
- [x] ✅ Module 2: Gán học sinh Cờ đỏ
- [x] ✅ Module 3: Duyệt điểm tuần
- [x] ✅ Module 4: Tổng kết & Xếp hạng
- [x] ✅ Virtual host: truongbuithixuan.local
- [x] ✅ Migration & Seed data

---

**TẤT CẢ ĐÃ SẴN SÀNG!** 🎉

**Password chung:** 123456
**School:** Trường THCS Lê Trí Viễn - Phường Điện Bàn Bắc - TP Đà Nẵng
**Teacher:** Đoàn Thị Ngọc Lĩnh

---

**🎓 Trường THCS Lê Trí Viễn**
**Hệ thống Học tập & Thi đua Trực tuyến**
