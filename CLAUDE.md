# CLAUDE.md - Hệ thống Học tập & Thi đua Trực tuyến

**Trường THCS Lê Trí Viễn**
Phường Điện Bàn Bắc - TP Đà Nẵng
Giáo viên: Đoàn Thị Ngọc Lĩnh

---

## 📋 MỤC LỤC

1. [Tổng quan dự án](#1-tổng-quan-dự-án)
2. [Kiến trúc hệ thống](#2-kiến-trúc-hệ-thống)
3. [Database Schema](#3-database-schema)
4. [SQL Migration](#4-sql-migration)
5. [Phân quyền 4 cấp](#5-phân-quyền-4-cấp)
6. [Logic Chấm Chéo](#6-logic-chấm-chéo)
7. [Công thức tính điểm](#7-công-thức-tính-điểm)
8. [Luồng nghiệp vụ](#8-luồng-nghiệp-vụ)
9. [Cấu trúc Modules](#9-cấu-trúc-modules)
10. [API Endpoints](#10-api-endpoints)
11. [Quy tắc Code](#11-quy-tắc-code)
12. [Development Guide](#12-development-guide)
13. [Roadmap](#13-roadmap)

---

## 1. TỔNG QUAN DỰ ÁN

### 1.1 Thông tin cơ bản

```
Tên dự án: Hệ thống Học tập & Thi đua Trực tuyến THCS
Trường: THCS Lê Trí Viễn
Địa chỉ: Phường Điện Bàn Bắc, TP Đà Nẵng
Cấp học: THCS (Lớp 6, 7, 8, 9)
Tech stack: PHP 5.6+ (KHÔNG dùng ?? operator), MySQL 5.7+, Bootstrap 5, jQuery
Database: hoctaptructuyen ✅
BASE_URL: http://localhost/truongbuithixuan (dev)
```

### 1.2 Mô tả dự án

Hệ thống tích hợp 2 chức năng chính:

**A. Hệ thống Thi trực tuyến** (Đã có)
- Thi online với timer mỗi câu hỏi
- Xếp hạng học sinh cá nhân
- Quản lý đề thi, câu hỏi
- Hệ thống tuần học, học kỳ

**B. Hệ thống Thi đua lớp học** (Mới - Phase này)
- Chấm điểm thi đua lớp theo tiêu chí
- **Chấm chéo**: Học sinh Cờ đỏ lớp A chấm lớp B (Admin phân công)
- Phân quyền 4 cấp: Admin → Tổng phụ trách → Học sinh Cờ đỏ → Học sinh
- Xếp hạng lớp theo tuần/tháng/học kỳ
- Báo cáo và thống kê

### 1.3 Đặc điểm nổi bật

🎯 **CHẤM CHÉO (Cross-checking):**
- Mỗi sáng, học sinh Cờ đỏ được phân công chấm lớp khác (không phải lớp mình)
- Admin cài đặt trước: Lớp A chấm lớp B, Lớp B chấm lớp C,...
- Đảm bảo công bằng, khách quan trong thi đua

---

## 2. KIẾN TRÚC HỆ THỐNG

### 2.1 Tech Stack

```
Backend:
├── PHP 7.4+ (vanilla PHP)
├── PDO (database abstraction)
├── Session-based authentication
└── bcrypt password hashing

Frontend:
├── Bootstrap 5.3
├── jQuery 3.6+
├── Font Awesome 6
├── Chart.js (biểu đồ)
└── SweetAlert2 (alerts)

Database:
└── MySQL 5.7+ - Database: hoctaptructuyen

Server:
├── XAMPP (development)
└── Apache + MySQL
```

### 2.2 Cấu trúc thư mục

```
truongbuithixuan/
├── admin/
│   ├── thidua/                 # ✨ MODULE MỚI
│   │   ├── tieu-chi.php        # CRUD tiêu chí
│   │   ├── phan-cong-co-do.php # Phân công chấm chéo
│   │   ├── cham-diem.php       # Admin/TPT chấm điểm
│   │   ├── duyet-diem.php      # Admin duyệt
│   │   ├── xep-hang-tuan.php
│   │   ├── xep-hang-thang.php
│   │   ├── xep-hang-hoc-ky.php
│   │   ├── bao-cao.php
│   │   └── thong-ke.php
│   └── ...
│
├── student/
│   ├── thidua/                 # ✨ MODULE MỚI
│   │   ├── cham-diem.php       # Cờ đỏ chấm điểm
│   │   ├── xem-diem-lop.php    # Xem điểm lớp
│   │   └── xep-hang.php        # Xếp hạng
│   └── ...
│
├── includes/
│   ├── config.php
│   ├── thidua_helper.php       # ✨ MỚI
│   ├── permission_helper.php   # ✨ MỚI
│   └── ...
│
├── database/
│   ├── migration_thidua.sql    # ✨ MỚI - Migration SQL
│   └── seed_thcs.sql           # ✨ MỚI - Seed data
│
├── CLAUDE.md                   # File này
└── README.md
```

---

## 3. DATABASE SCHEMA

### 3.1 Database hiện có

**Database name**: `hoctaptructuyen`

**Các bảng đã có (tái sử dụng):**
```
✅ admins           - Admin/Giáo viên (cần thêm vai_tro)
✅ lop_hoc          - Lớp học (cần cập nhật THCS)
✅ hoc_sinh         - Học sinh (cần thêm la_co_do)
✅ mon_hoc          - Môn học (cần cập nhật THCS)
✅ tuan_hoc         - Tuần học (tái sử dụng)
✅ hoc_ky           - Học kỳ (tái sử dụng)
✅ de_thi, cau_hoi  - Hệ thống thi online
✅ bai_lam, chi_tiet_bai_lam
✅ log_hoat_dong
```

### 3.2 Bảng MỚI cần tạo

#### **A. tieu_chi_thi_dua**
```sql
Quản lý tiêu chí chấm điểm
- id, ma_tieu_chi, ten_tieu_chi
- diem_toi_da, trong_so (%)
- mo_ta, thu_tu, trang_thai

Dữ liệu mẫu: 5 tiêu chí
1. Học tập (40%)
2. Nề nếp (25%)
3. Vệ sinh (15%)
4. Hoạt động (15%)
5. Đoàn kết (5%)
```

#### **B. phan_cong_cham_diem** ⭐ QUAN TRỌNG
```sql
Quản lý phân công chấm chéo
- id
- hoc_sinh_id (FK -> hoc_sinh.id) - Cờ đỏ
- lop_duoc_cham_id (FK -> lop_hoc.id) - Lớp được phân công chấm
- ngay_phan_cong
- nguoi_phan_cong (admin_id)
- trang_thai (active/inactive)

Logic:
- Học sinh Cờ đỏ lớp A → Chấm lớp B
- Học sinh Cờ đỏ lớp B → Chấm lớp C
- Học sinh Cờ đỏ lớp C → Chấm lớp A (vòng tròn)
```

#### **C. diem_thi_dua_lop**
```sql
Lưu điểm thi đua của lớp
- lop_id, tieu_chi_id, tuan_id
- diem (0-10), diem_co_trong_so
- nguoi_cham (hoc_sinh_id Cờ đỏ)
- trang_thai (nhap, cho_tong_hop, cho_duyet, da_duyet, tu_choi)
- Workflow 3 bước: Cờ đỏ → TPT → Admin
```

#### **D. xep_hang_lop_tuan**
```sql
Xếp hạng lớp theo tuần
- lop_id, tuan_id
- tong_diem_co_trong_so (max 100)
- diem_hoc_tap, diem_ne_nep, diem_ve_sinh, diem_hoat_dong, diem_doan_ket
- thu_hang_toan_truong, thu_hang_cung_khoi
- xep_loai
```

#### **E. xep_hang_lop_thang**
```sql
Xếp hạng lớp theo tháng (tổng hợp từ tuần)
```

#### **F. xep_hang_lop_hoc_ky**
```sql
Xếp hạng lớp theo học kỳ (tổng hợp từ tháng)
- Thêm: danh_hieu, thuong
```

#### **G. bao_cao_lop**
```sql
Báo cáo của lớp (GVCN, học sinh)
- loai_bao_cao (tuan, thang, hoc_ky)
- tieu_de, noi_dung, diem_manh, ton_tai, giai_phap
- phan_hoi_admin
```

---

## 4. SQL MIGRATION

### 4.1 Bước 1: ALTER TABLE (Thêm cột vào bảng cũ)

```sql
-- =====================================================
-- MIGRATION STEP 1: ALTER EXISTING TABLES
-- =====================================================

-- 1.1 Bảng admins: Thêm vai trò
ALTER TABLE admins
  ADD COLUMN vai_tro ENUM('admin','tong_phu_trach','giao_vien')
    DEFAULT 'giao_vien'
    COMMENT 'Admin=Toàn quyền, TPT=Tổng phụ trách, GV=Giáo viên'
    AFTER email;

-- 1.2 Bảng hoc_sinh: Thêm cờ đỏ
ALTER TABLE hoc_sinh
  ADD COLUMN la_co_do TINYINT(1) DEFAULT 0
    COMMENT '1=Cờ đỏ (chấm điểm), 0=Thường'
    AFTER trang_thai,
  ADD COLUMN ngay_gan_co_do DATE DEFAULT NULL
    AFTER la_co_do,
  ADD COLUMN nguoi_gan INT(11)
    COMMENT 'admin_id người phân quyền'
    AFTER ngay_gan_co_do;

-- 1.3 Bảng lop_hoc: Thêm GVCN và sĩ số
ALTER TABLE lop_hoc
  ADD COLUMN khoi_label VARCHAR(10)
    COMMENT '6A1, 7A2, etc'
    AFTER khoi,
  ADD COLUMN gvcn_id INT(11)
    COMMENT 'Giáo viên chủ nhiệm'
    AFTER khoi_label,
  ADD COLUMN si_so INT(11) DEFAULT 0
    COMMENT 'Sĩ số lớp'
    AFTER gvcn_id;

-- Foreign key
ALTER TABLE lop_hoc
  ADD CONSTRAINT fk_lop_gvcn
    FOREIGN KEY (gvcn_id) REFERENCES admins(id) ON DELETE SET NULL;
```

### 4.2 Bước 2: CREATE NEW TABLES

```sql
-- =====================================================
-- MIGRATION STEP 2: CREATE NEW TABLES
-- =====================================================

-- 2.1 Bảng tiêu chí thi đua
CREATE TABLE tieu_chi_thi_dua (
  id INT(11) AUTO_INCREMENT PRIMARY KEY,
  ma_tieu_chi VARCHAR(20) NOT NULL UNIQUE
    COMMENT 'hoc_tap, ne_nep, ve_sinh, hoat_dong, doan_ket',
  ten_tieu_chi VARCHAR(100) NOT NULL,
  mo_ta TEXT,
  diem_toi_da DECIMAL(4,2) DEFAULT 10.00,
  trong_so INT(11) DEFAULT 20
    COMMENT 'Trọng số % - Tổng 100%',
  thu_tu INT(11) DEFAULT 0,
  trang_thai TINYINT(1) DEFAULT 1,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_trang_thai (trang_thai),
  INDEX idx_thu_tu (thu_tu)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Tiêu chí chấm điểm thi đua';

-- 2.2 Bảng phân công chấm điểm (CHẤM CHÉO) ⭐
CREATE TABLE phan_cong_cham_diem (
  id INT(11) AUTO_INCREMENT PRIMARY KEY,
  hoc_sinh_id INT(11) NOT NULL
    COMMENT 'ID học sinh Cờ đỏ',
  lop_duoc_cham_id INT(11) NOT NULL
    COMMENT 'ID lớp được phân công chấm',
  ngay_phan_cong DATE NOT NULL,
  nguoi_phan_cong INT(11)
    COMMENT 'admin_id người phân công',
  ghi_chu TEXT,
  trang_thai ENUM('active','inactive') DEFAULT 'active',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  FOREIGN KEY (hoc_sinh_id) REFERENCES hoc_sinh(id) ON DELETE CASCADE,
  FOREIGN KEY (lop_duoc_cham_id) REFERENCES lop_hoc(id) ON DELETE CASCADE,
  FOREIGN KEY (nguoi_phan_cong) REFERENCES admins(id) ON DELETE SET NULL,
  UNIQUE KEY unique_phan_cong (hoc_sinh_id, lop_duoc_cham_id),
  INDEX idx_hoc_sinh (hoc_sinh_id),
  INDEX idx_lop (lop_duoc_cham_id),
  INDEX idx_trang_thai (trang_thai)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Phân công học sinh Cờ đỏ chấm lớp nào';

-- 2.3 Bảng điểm thi đua lớp
CREATE TABLE diem_thi_dua_lop (
  id INT(11) AUTO_INCREMENT PRIMARY KEY,
  lop_id INT(11) NOT NULL
    COMMENT 'Lớp bị chấm điểm',
  tieu_chi_id INT(11) NOT NULL,
  tuan_id INT(11) NOT NULL,

  -- Điểm số
  diem DECIMAL(4,2) DEFAULT 0
    COMMENT 'Điểm thô (0-10)',
  diem_co_trong_so DECIMAL(6,2) DEFAULT 0
    COMMENT 'Điểm sau khi nhân trọng số',

  -- Người chấm
  nguoi_cham INT(11)
    COMMENT 'hoc_sinh_id (Cờ đỏ) hoặc admin_id',
  loai_nguoi_cham ENUM('hoc_sinh','admin','tong_phu_trach') DEFAULT 'hoc_sinh',
  ghi_chu TEXT,
  cham_luc DATETIME,

  -- Workflow 3 bước
  trang_thai ENUM('nhap','cho_tong_hop','cho_duyet','da_duyet','tu_choi')
    DEFAULT 'nhap',

  -- Bước 1: Cờ đỏ chấm
  gui_tong_hop_luc DATETIME,

  -- Bước 2: TPT tổng hợp
  tong_hop_boi INT(11),
  tong_hop_luc DATETIME,
  ghi_chu_tong_hop TEXT,

  -- Bước 3: Admin duyệt
  duyet_boi INT(11),
  duyet_luc DATETIME,
  ly_do_tu_choi TEXT,

  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  FOREIGN KEY (lop_id) REFERENCES lop_hoc(id) ON DELETE CASCADE,
  FOREIGN KEY (tieu_chi_id) REFERENCES tieu_chi_thi_dua(id) ON DELETE CASCADE,
  FOREIGN KEY (tuan_id) REFERENCES tuan_hoc(id) ON DELETE CASCADE,
  UNIQUE KEY unique_diem (lop_id, tieu_chi_id, tuan_id),
  INDEX idx_trang_thai (trang_thai),
  INDEX idx_tuan (tuan_id),
  INDEX idx_lop (lop_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Điểm thi đua của lớp theo tiêu chí';

-- 2.4 Xếp hạng lớp theo tuần
CREATE TABLE xep_hang_lop_tuan (
  id INT(11) AUTO_INCREMENT PRIMARY KEY,
  lop_id INT(11) NOT NULL,
  tuan_id INT(11) NOT NULL,

  tong_diem_tho DECIMAL(6,2) DEFAULT 0,
  tong_diem_co_trong_so DECIMAL(6,2) DEFAULT 0
    COMMENT 'Tổng điểm (max 100)',

  -- Chi tiết từng tiêu chí (có trọng số)
  diem_hoc_tap DECIMAL(6,2) DEFAULT 0,
  diem_ne_nep DECIMAL(6,2) DEFAULT 0,
  diem_ve_sinh DECIMAL(6,2) DEFAULT 0,
  diem_hoat_dong DECIMAL(6,2) DEFAULT 0,
  diem_doan_ket DECIMAL(6,2) DEFAULT 0,

  thu_hang_toan_truong INT(11),
  thu_hang_cung_khoi INT(11),

  xep_loai ENUM('xuat_sac','tot','kha','trung_binh','can_co_gang')
    COMMENT 'XS>=90, T>=80, K>=70, TB>=50',

  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  FOREIGN KEY (lop_id) REFERENCES lop_hoc(id) ON DELETE CASCADE,
  FOREIGN KEY (tuan_id) REFERENCES tuan_hoc(id) ON DELETE CASCADE,
  UNIQUE KEY unique_xh (lop_id, tuan_id),
  INDEX idx_thu_hang (thu_hang_toan_truong),
  INDEX idx_tong_diem (tong_diem_co_trong_so)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.5 Xếp hạng lớp theo tháng
CREATE TABLE xep_hang_lop_thang (
  id INT(11) AUTO_INCREMENT PRIMARY KEY,
  lop_id INT(11) NOT NULL,
  nam INT(11) NOT NULL,
  thang INT(11) NOT NULL,
  hoc_ky_id INT(11),

  so_tuan INT(11) DEFAULT 0,
  tong_diem_co_trong_so DECIMAL(7,2) DEFAULT 0,
  diem_trung_binh DECIMAL(5,2) DEFAULT 0,

  diem_hoc_tap DECIMAL(6,2) DEFAULT 0,
  diem_ne_nep DECIMAL(6,2) DEFAULT 0,
  diem_ve_sinh DECIMAL(6,2) DEFAULT 0,
  diem_hoat_dong DECIMAL(6,2) DEFAULT 0,
  diem_doan_ket DECIMAL(6,2) DEFAULT 0,

  thu_hang_toan_truong INT(11),
  thu_hang_cung_khoi INT(11),
  xep_loai ENUM('xuat_sac','tot','kha','trung_binh','can_co_gang'),

  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  FOREIGN KEY (lop_id) REFERENCES lop_hoc(id) ON DELETE CASCADE,
  FOREIGN KEY (hoc_ky_id) REFERENCES hoc_ky(id) ON DELETE SET NULL,
  UNIQUE KEY unique_xh (lop_id, nam, thang),
  INDEX idx_thang (nam, thang)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.6 Xếp hạng lớp theo học kỳ
CREATE TABLE xep_hang_lop_hoc_ky (
  id INT(11) AUTO_INCREMENT PRIMARY KEY,
  lop_id INT(11) NOT NULL,
  hoc_ky_id INT(11) NOT NULL,

  so_tuan INT(11) DEFAULT 0,
  so_thang INT(11) DEFAULT 0,
  tong_diem_co_trong_so DECIMAL(8,2) DEFAULT 0,
  diem_trung_binh DECIMAL(5,2) DEFAULT 0,

  diem_hoc_tap DECIMAL(7,2) DEFAULT 0,
  diem_ne_nep DECIMAL(7,2) DEFAULT 0,
  diem_ve_sinh DECIMAL(7,2) DEFAULT 0,
  diem_hoat_dong DECIMAL(7,2) DEFAULT 0,
  diem_doan_ket DECIMAL(7,2) DEFAULT 0,

  thu_hang_toan_truong INT(11),
  thu_hang_cung_khoi INT(11),
  xep_loai ENUM('xuat_sac','tot','kha','trung_binh','can_co_gang'),

  danh_hieu VARCHAR(100),
  thuong DECIMAL(10,2) DEFAULT 0,

  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  FOREIGN KEY (lop_id) REFERENCES lop_hoc(id) ON DELETE CASCADE,
  FOREIGN KEY (hoc_ky_id) REFERENCES hoc_ky(id) ON DELETE CASCADE,
  UNIQUE KEY unique_xh (lop_id, hoc_ky_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.7 Báo cáo lớp
CREATE TABLE bao_cao_lop (
  id INT(11) AUTO_INCREMENT PRIMARY KEY,
  lop_id INT(11) NOT NULL,
  tuan_id INT(11) NOT NULL,
  loai_bao_cao ENUM('tuan','thang','hoc_ky') DEFAULT 'tuan',

  tieu_de VARCHAR(255),
  noi_dung TEXT,
  diem_manh TEXT,
  ton_tai TEXT,
  giai_phap TEXT,

  nguoi_tao INT(11),
  loai_nguoi_tao ENUM('admin','gvcn','hoc_sinh') DEFAULT 'gvcn',
  trang_thai ENUM('nhap','da_gui','da_phan_hoi') DEFAULT 'nhap',

  phan_hoi_admin TEXT,
  admin_phan_hoi INT(11),
  phan_hoi_luc DATETIME,

  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  FOREIGN KEY (lop_id) REFERENCES lop_hoc(id) ON DELETE CASCADE,
  FOREIGN KEY (tuan_id) REFERENCES tuan_hoc(id) ON DELETE CASCADE,
  INDEX idx_trang_thai (trang_thai),
  INDEX idx_loai (loai_bao_cao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 4.3 Bước 3: SEED DATA

```sql
-- =====================================================
-- MIGRATION STEP 3: SEED DATA
-- =====================================================

-- 3.1 Thêm tiêu chí thi đua
INSERT INTO tieu_chi_thi_dua
  (ma_tieu_chi, ten_tieu_chi, mo_ta, diem_toi_da, trong_so, thu_tu)
VALUES
  ('hoc_tap', 'Học tập',
   'Kết quả học tập, thi cử, HSG, học sinh tiến bộ, tỷ lệ lên lớp',
   10.00, 40, 1),
  ('ne_nep', 'Nề nếp',
   'Kỷ luật, đi học đúng giờ, trang phục đồng phục, nội quy lớp, vi phạm',
   10.00, 25, 2),
  ('ve_sinh', 'Vệ sinh',
   'Vệ sinh lớp học, khu vực phụ trách, vệ sinh cá nhân',
   10.00, 15, 3),
  ('hoat_dong', 'Hoạt động',
   'Đoàn, Đội, hoạt động ngoại khóa, văn nghệ, thể thao, phong trào',
   10.00, 15, 4),
  ('doan_ket', 'Đoàn kết',
   'Tinh thần đoàn kết lớp, giúp đỡ bạn bè, không có mâu thuẫn nội bộ',
   10.00, 5, 5);

-- 3.2 Cập nhật vai trò admin
UPDATE admins SET vai_tro = 'admin' WHERE username = 'admin' LIMIT 1;

-- 3.3 Cập nhật thông tin trường
UPDATE cau_hinh
  SET gia_tri = 'Trường THCS Lê Trí Viễn'
  WHERE ma_cau_hinh = 'site_name';

UPDATE cau_hinh
  SET gia_tri = 'Phường Điện Bàn Bắc - TP Đà Nẵng'
  WHERE ma_cau_hinh = 'school_address';
```

---

## 5. PHÂN QUYỀN 4 CẤP

### 5.1 Cấp 1: ADMIN

**Vai trò**: `admins.vai_tro = 'admin'`

**Quyền hạn:**
```
✅ Toàn quyền hệ thống
✅ Phân quyền Cờ đỏ, Tổng phụ trách
✅ Phân công chấm chéo (Lớp A chấm lớp B)
✅ Chấm điểm (tất cả lớp)
✅ Duyệt điểm cuối cùng (QUYỀN DUY NHẤT)
✅ Quản lý tiêu chí, tuần, học kỳ
✅ Xem tất cả báo cáo, thống kê
✅ Xuất Excel/PDF
```

### 5.2 Cấp 2: TỔNG PHỤ TRÁCH

**Vai trò**: `admins.vai_tro = 'tong_phu_trach'`

**Quyền hạn:**
```
✅ Xem tất cả điểm các lớp
✅ Tổng hợp điểm từ Cờ đỏ
✅ Chỉnh sửa điểm (trước khi gửi duyệt)
✅ Gửi điểm lên Admin để duyệt
✅ Xem thống kê, báo cáo
✅ Nhắc nhở Cờ đỏ chưa chấm
❌ KHÔNG duyệt cuối (cần Admin)
```

### 5.3 Cấp 3: HỌC SINH CỜ ĐỎ ⭐

**Đặc điểm**: `hoc_sinh.la_co_do = 1`

**Quyền hạn:**
```
✅ Chấm điểm cho LỚP ĐƯỢC PHÂN CÔNG (không phải lớp mình!)
✅ Xem trong bảng phan_cong_cham_diem → lop_duoc_cham_id
✅ Chấm điểm theo tuần
✅ Lưu tạm / Gửi tổng hợp
✅ Xem lịch sử điểm đã chấm
❌ KHÔNG chấm lớp mình
❌ KHÔNG chỉnh sửa sau khi gửi
```

### 5.4 Cấp 4: HỌC SINH THƯỜNG

**Đặc điểm**: `hoc_sinh.la_co_do = 0`

**Quyền hạn:**
```
✅ Xem điểm thi đua của lớp mình
✅ Xem xếp hạng lớp
✅ Xem báo cáo lớp
✅ Thi online
❌ KHÔNG chấm điểm
```

---

## 6. LOGIC CHẤM CHÉO (Cross-Checking) ⭐

### 6.1 Nguyên tắc

```
Học sinh Cờ đỏ KHÔNG chấm lớp mình
→ Chấm lớp khác (Admin phân công trước)
→ Đảm bảo công bằng, khách quan
```

### 6.2 Ví dụ phân công

**Trường có 16 lớp THCS:**

```
┌──────────────────────────────────────────────────────┐
│  PHÂN CÔNG CHẤM CHÉO KHỐI 6 (4 lớp)                 │
└──────────────────────────────────────────────────────┘

Admin phân công:
├─ Cờ đỏ lớp 6A1 (HS: Nguyễn Văn A) → Chấm lớp 6A2
├─ Cờ đỏ lớp 6A2 (HS: Trần Thị B)   → Chấm lớp 6A3
├─ Cờ đỏ lớp 6A3 (HS: Lê Văn C)     → Chấm lớp 6A4
└─ Cờ đỏ lớp 6A4 (HS: Phạm Thị D)   → Chấm lớp 6A1

Vòng tròn: 6A1 ← 6A4 ← 6A3 ← 6A2 ← 6A1

Tương tự cho khối 7, 8, 9
```

### 6.3 Bảng phan_cong_cham_diem

```sql
-- Ví dụ data
INSERT INTO phan_cong_cham_diem
  (hoc_sinh_id, lop_duoc_cham_id, ngay_phan_cong, nguoi_phan_cong, trang_thai)
VALUES
  -- Khối 6
  (101, 2, '2026-01-01', 1, 'active'), -- HS 101 (lớp 6A1) chấm lớp 6A2
  (102, 3, '2026-01-01', 1, 'active'), -- HS 102 (lớp 6A2) chấm lớp 6A3
  (103, 4, '2026-01-01', 1, 'active'), -- HS 103 (lớp 6A3) chấm lớp 6A4
  (104, 1, '2026-01-01', 1, 'active'); -- HS 104 (lớp 6A4) chấm lớp 6A1
```

### 6.4 Check quyền chấm

```php
function getLopDuocCham($hoc_sinh_id) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("
        SELECT pc.lop_duoc_cham_id, lh.ten_lop, lh.khoi_label
        FROM phan_cong_cham_diem pc
        JOIN lop_hoc lh ON pc.lop_duoc_cham_id = lh.id
        WHERE pc.hoc_sinh_id = ?
          AND pc.trang_thai = 'active'
    ");
    $stmt->execute([$hoc_sinh_id]);
    return $stmt->fetchAll();
}

function canChamLop($hoc_sinh_id, $lop_id) {
    $cac_lop = getLopDuocCham($hoc_sinh_id);
    foreach ($cac_lop as $lop) {
        if ($lop['lop_duoc_cham_id'] == $lop_id) {
            return true;
        }
    }
    return false;
}
```

---

## 7. CÔNG THỨC TÍNH ĐIỂM

### 7.1 Điểm có trọng số

```php
/**
 * Công thức:
 * Điểm có trọng số = (Điểm thô / 10) × Trọng số
 */
function tinhDiemCoTrongSo($diem_tho, $trong_so) {
    return round(($diem_tho / 10) * $trong_so, 2);
}

// Ví dụ:
// Học tập: 8.5/10, trọng số 40%
// → (8.5/10) × 40 = 34 điểm
```

### 7.2 Tổng điểm tuần

```php
/**
 * Tổng điểm tuần = SUM(điểm có trọng số các tiêu chí)
 * Max = 100 điểm
 */
function tinhTongDiemTuan($lop_id, $tuan_id) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("
        SELECT SUM(diem_co_trong_so) as tong
        FROM diem_thi_dua_lop
        WHERE lop_id = ? AND tuan_id = ?
          AND trang_thai = 'da_duyet'
    ");
    $stmt->execute([$lop_id, $tuan_id]);
    $result = $stmt->fetch();
    return $result['tong'] ?? 0;
}

// Ví dụ tuần 15:
// Học tập: 34 (8.5 × 40%)
// Nề nếp: 22.5 (9.0 × 25%)
// Vệ sinh: 12 (8.0 × 15%)
// Hoạt động: 14.25 (9.5 × 15%)
// Đoàn kết: 4.5 (9.0 × 5%)
// → Tổng = 87.25/100
```

### 7.3 Xếp loại

```php
function xepLoai($diem_100) {
    if ($diem_100 >= 90) return 'xuat_sac';      // >= 90
    if ($diem_100 >= 80) return 'tot';           // >= 80
    if ($diem_100 >= 70) return 'kha';           // >= 70
    if ($diem_100 >= 50) return 'trung_binh';    // >= 50
    return 'can_co_gang';                         // < 50
}
```

### 7.4 Xếp hạng

```php
function xepHangLop($tuan_id = null, $khoi = null) {
    $conn = getDBConnection();

    $where = ['1=1'];
    $params = [];

    if ($tuan_id) {
        $where[] = "tuan_id = ?";
        $params[] = $tuan_id;
    }

    if ($khoi) {
        $where[] = "lh.khoi = ?";
        $params[] = $khoi;
    }

    $sql = "
        SELECT
            xh.*,
            lh.ten_lop,
            lh.khoi,
            lh.khoi_label
        FROM xep_hang_lop_tuan xh
        JOIN lop_hoc lh ON xh.lop_id = lh.id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY xh.tong_diem_co_trong_so DESC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}
```

---

## 8. LUỒNG NGHIỆP VỤ

### 8.1 Workflow chấm điểm tuần

```
┌─────────────────────────────────────────────────────────┐
│  BƯỚC 1: HỌC SINH CỜ ĐỎ CHẤM ĐIỂM (Chấm chéo)         │
└─────────────────────────────────────────────────────────┘

Thứ 2 - Thứ 5

1. HS Cờ đỏ login → student/thidua/cham-diem.php
2. Hệ thống check:
   - La_co_do = 1?
   - Lấy lớp được phân công: getLopDuocCham()
3. Hiển thị form chấm điểm cho LỚP ĐƯỢC PHÂN CÔNG:
   Ví dụ: HS lớp 6A1 → Chấm điểm cho lớp 6A2
4. Nhập điểm 5 tiêu chí (0-10)
5. [Lưu tạm] hoặc [Gửi]
6. INSERT diem_thi_dua_lop
   - lop_id = 2 (lớp 6A2 - lớp được chấm)
   - nguoi_cham = hoc_sinh_id (Cờ đỏ lớp 6A1)
   - trang_thai = 'cho_tong_hop'

┌─────────────────────────────────────────────────────────┐
│  BƯỚC 2: TỔNG PHỤ TRÁCH TỔNG HỢP                       │
└─────────────────────────────────────────────────────────┘

Thứ 6

1. TPT login → admin/thidua/cham-diem.php
2. Xem bảng tổng hợp 16 lớp
3. Check: 14/16 lớp đã được chấm
4. Sửa điểm nếu cần
5. [Gửi Admin duyệt]
   - trang_thai = 'cho_duyet'

┌─────────────────────────────────────────────────────────┐
│  BƯỚC 3: ADMIN DUYỆT                                    │
└─────────────────────────────────────────────────────────┘

Thứ 7

1. Admin login → admin/thidua/duyet-diem.php
2. [Duyệt tất cả] hoặc [Từ chối]
3. Nếu duyệt:
   - trang_thai = 'da_duyet'
   - Trigger: tinhTongDiemTuan()
   - Trigger: xepHangLop()
4. Lưu vào xep_hang_lop_tuan

┌─────────────────────────────────────────────────────────┐
│  BƯỚC 4: CÔNG BỐ                                        │
└─────────────────────────────────────────────────────────┘

Chủ nhật

1. Học sinh xem kết quả
2. Dashboard hiển thị:
   ╔═══════════════════════════╗
   ║  LỚP 6A1 - TUẦN 15       ║
   ║  🏆 Hạng: 2/16           ║
   ║  📊 Điểm: 87.25/100      ║
   ║  ⭐ Xuất sắc             ║
   ╚═══════════════════════════╝
```

---

## 9. CẤU TRÚC MODULES

### 9.1 Helper Functions

**File: includes/thidua_helper.php**

```php
<?php
// Tính điểm
function tinhDiemCoTrongSo($diem_tho, $trong_so);
function tinhTongDiemTuan($lop_id, $tuan_id);
function tinhDiemThang($lop_id, $thang, $nam);
function xepLoai($diem_100);

// Xếp hạng
function xepHangLop($tuan_id, $khoi);
function capNhatThuHang($tuan_id);

// Phân công chấm chéo
function getLopDuocCham($hoc_sinh_id);
function canChamLop($hoc_sinh_id, $lop_id);
```

**File: includes/permission_helper.php**

```php
<?php
// Check role
function isAdmin($user_id);
function isTongPhuTrach($user_id);
function isHocSinhCoDo($hoc_sinh_id);
function canChamLop($hoc_sinh_id, $lop_id);

// Middleware
function requireAdmin();
function requireTongPhuTrach();
function requireHocSinhCoDo();
```

---

## 10. API ENDPOINTS

### POST /api/thidua/cham-diem.php

**Request:**
```json
{
  "lop_id": 2,
  "tuan_id": 15,
  "diem": {
    "hoc_tap": 8.5,
    "ne_nep": 9.0,
    "ve_sinh": 8.0,
    "hoat_dong": 9.5,
    "doan_ket": 9.0
  },
  "ghi_chu": "Lớp 6A2 học tốt tuần này",
  "action": "gui"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Gửi điểm thành công",
  "data": {
    "trang_thai": "cho_tong_hop",
    "tong_diem": 87.25
  }
}
```

---

## 11. QUY TẮC CODE

### 11.1 Security

```php
// ✅ Prepared Statements
$stmt = $conn->prepare("SELECT * FROM hoc_sinh WHERE id = ?");
$stmt->execute([$id]);

// ✅ Sanitize
$ho_ten = sanitize($_POST['ho_ten']);

// ✅ Validate
if (!canChamLop($student_id, $lop_id)) {
    die('Không có quyền chấm lớp này');
}
```

---

## 12. DEVELOPMENT GUIDE

### 12.1 Setup

```bash
# 1. Import database
mysql -uroot hoctaptructuyen < database/migration_thidua.sql

# 2. Cấu hình
# Check config.php: DB_NAME = 'hoctaptructuyen'

# 3. Test
http://localhost/truongbuithixuan/test_db.php
```

---

## 13. ROADMAP

### Phase 1: Database Setup ✅
- [x] Tạo migration SQL
- [x] ALTER TABLE
- [x] CREATE TABLE
- [x] Seed data

### Phase 2: Helper Functions ✅
- [x] thidua_helper.php
- [x] permission_helper.php
- [x] Logic chấm chéo

### Phase 3: Admin Panel ✅
- [x] Phân công Cờ đỏ
- [x] Phân công chấm chéo
- [x] Chấm điểm / Duyệt điểm
- [x] Xếp hạng

### Phase 4: Student Panel ✅
- [x] Cờ đỏ chấm điểm (lớp được phân công)
- [x] Xem điểm lớp
- [x] Xếp hạng

### Phase 5: UI Redesign (Indigo-Violet) ✅
- [x] Chuyển color scheme: coral/teal (#FF6B6B/#4ECDC4) -> Indigo/Violet (#4F46E5/#7C3AED)
- [x] Chuyển font: Quicksand -> Inter
- [x] Tích hợp menu Thi đua vào admin sidebar + student nav
- [x] Mobile tab bar: 4 tabs -> 5 tabs (thêm Thi đua)
- [x] Cập nhật tất cả student pages (desktop + mobile)
- [x] Admin UI giữ nguyên (chỉ thêm menu Thi đua)

### Phase 6: Testing & Deploy 📋
- [ ] Test workflow
- [ ] Import dữ liệu thật
- [ ] Deploy

---

## 14. UI THEME

### Color Scheme (Indigo-Violet)
```
Primary:    #4F46E5 (Indigo)
Primary Dark: #4338CA
Primary Light: #818CF8
Secondary:  #0D9488 (Teal)
Violet:     #7C3AED
Gradient:   #4F46E5 -> #7C3AED
Font:       Inter (Google Fonts)
```

### Files quan trong (Student UI)
```
assets/css/style.css         - CSS variables (:root), dung chung
includes/header.php          - theme-color, Google Fonts
student/dashboard.php        - Desktop main (~76KB, inline styles)
student/exam.php             - Desktop exam (2 layouts: chon de + lam bai)
student/ranking.php          - Desktop ranking
student/ranking-week.php     - Desktop ranking tuan
student/result.php           - Desktop ket qua
student/mobile/header.php    - Mobile shared CSS vars + font
student/mobile/index.php     - Mobile home
student/mobile/exams.php     - Mobile exams list
student/mobile/exam.php      - Mobile exam (standalone HTML)
student/mobile/documents.php - Mobile docs
student/mobile/document-view.php - Mobile doc viewer (standalone HTML)
student/mobile/profile.php   - Mobile profile
student/mobile/ranking.php   - Mobile ranking
student/mobile/history.php   - Mobile history
student/mobile/result.php    - Mobile result
```

### Mobile Tab Bar (5 tabs)
```
Trang chu | Lam bai | Thi dua | Tai lieu | Toi
```

---

**Updated**: 2026-02-15
**Database**: hoctaptructuyen ✅
**Logic**: Chấm chéo (Cross-checking) ⭐
**PHP**: 5.6+ (KHÔNG dùng ?? null coalescing operator)

---

