# 🔄 KẾ HOẠCH MIGRATE SANG PROJECT MỚI: LETRIVIEN

**Từ:** `truongbuithixuan` (Database: `hoctaptructuyen`)
**Sang:** `letrivien` (Database: `letrivien`)

**Mục tiêu:** Source code sạch sẽ hơn, tổ chức tốt hơn, áp dụng đầy đủ hệ thống thi đua

---

## 📊 ĐÁNH GIÁ HIỆN TRẠNG

### ✅ Thư mục XOA_TAM có thể XÓA AN TOÀN
```bash
# Kiểm tra: KHÔNG có file .php nào reference đến xoa_tam
Kết quả: ✅ AN TOÀN để xóa

2 thư mục:
- xoa_tam/
- xoa_tam_2/
→ Chỉ là bản copy backup cũ, KHÔNG ảnh hưởng source chính
```

### 📁 So sánh 2 projects

| Tiêu chí | truongbuithixuan | letrivien |
|----------|------------------|-----------|
| **Database** | hoctaptructuyen | letrivien |
| **Trường** | Tiểu học Bùi Thị Xuân | THCS Lê Trí Viễn |
| **Thư mục rác** | xoa_tam, xoa_tam_2 | Không có |
| **Code mới** | ✅ Đã có (helper, migration) | ⏳ Chưa có |
| **CLAUDE.md** | ✅ Đầy đủ | ⏳ Chưa có |
| **Virtual Host** | truongbuithixuan.local | Chưa config |

---

## 🎯 KHUYẾN NGHỊ: MIGRATE SANG LETRIVIEN

**Lý do:**
1. ✅ Tên phù hợp: THCS Lê Trí Viễn
2. ✅ Database mới sạch sẽ
3. ✅ Không có thư mục rác
4. ✅ Dễ quản lý hơn
5. ✅ Tách biệt rõ ràng với project cũ

---

## 📋 PHƯƠNG ÁN THỰC HIỆN

### 🔵 PHƯƠNG ÁN 1: MIGRATE TOÀN BỘ (Khuyến nghị)

**Mô tả:** Copy tất cả code mới sang letrivien, config lại từ đầu

**Các bước:**

#### Bước 1: Backup
```bash
# Backup database letrivien hiện tại
mysqldump -u root -p letrivien > letrivien_backup_before_migrate.sql
```

#### Bước 2: Copy files code mới
```bash
FROM: truongbuithixuan/
  ✅ CLAUDE.md
  ✅ database/migration_thidua.sql
  ✅ includes/permission_helper.php
  ✅ includes/thidua_helper.php
  ✅ includes/config.php (đã update auto-detect)
  ✅ HUONG_DAN_TAO_VIRTUAL_HOST.md
  ✅ config_samples/*

TO: letrivien/
  → Cùng cấu trúc thư mục
```

#### Bước 3: Chỉnh sửa config
```php
// letrivien/includes/config.php

// Đổi tên database
define('DB_NAME', 'letrivien');  // ← QUAN TRỌNG

// Đổi tên trường
define('SITE_NAME', 'Trường THCS Lê Trí Viễn');

// Thêm virtual host domain
$localDomains = array(
    'letrivien.local',      // ← Domain mới
    'www.letrivien.local'
);
```

#### Bước 4: Chạy migration SQL
```bash
# Import vào database letrivien
mysql -u root -p letrivien < database/migration_thidua.sql
```

#### Bước 5: Tạo Virtual Host mới
```apache
# Thêm vào httpd-vhosts.conf
<VirtualHost *:80>
    ServerName letrivien.local
    DocumentRoot "C:/xampp/htdocs/letrivien"
    ...
</VirtualHost>
```

```
# Thêm vào hosts file
127.0.0.1    letrivien.local
127.0.0.1    www.letrivien.local
```

#### Bước 6: Test
```
✅ http://letrivien.local
✅ Database letrivien hoạt động
✅ Helper functions hoạt động
```

---

### 🟢 PHƯƠNG ÁN 2: GIỮ CẢ HAI (Tạm thời)

**Mô tả:** Giữ cả 2 project, sau này xóa truongbuithixuan

**Ưu điểm:**
- An toàn, có backup
- So sánh được khi phát triển

**Nhược điểm:**
- Tốn dung lượng
- Dễ nhầm lẫn khi code

---

## 🚀 HƯỚNG DẪN THỰC HIỆN MIGRATE (Chi tiết)

### 📦 Bước 1: Chuẩn bị files cần copy

Tôi sẽ tạo script tự động copy files:

```bash
# Sẽ tạo: migrate_to_letrivien.ps1
# Tự động:
# - Copy CLAUDE.md
# - Copy database/
# - Copy includes/permission_helper.php
# - Copy includes/thidua_helper.php
# - Update config.php với DB_NAME = 'letrivien'
# - Copy config_samples/
# - Copy HUONG_DAN_TAO_VIRTUAL_HOST.md
```

### 📦 Bước 2: Database Migration

**Option A: Migrate từ hoctaptructuyen sang letrivien**
```sql
-- Dump database cũ
mysqldump -u root -p hoctaptructuyen > hoctaptructuyen_export.sql

-- Import vào letrivien
mysql -u root -p letrivien < hoctaptructuyen_export.sql

-- Chạy migration thi đua
mysql -u root -p letrivien < database/migration_thidua.sql
```

**Option B: Database letrivien đã có data, chỉ thêm tables mới**
```sql
-- Chỉ chạy migration thi đua
mysql -u root -p letrivien < database/migration_thidua.sql
```

### 📦 Bước 3: Cập nhật config

```php
// letrivien/includes/config.php

// 1. Đổi database name
define('DB_NAME', 'letrivien');

// 2. Đổi tên trường
define('SITE_NAME', 'Trường THCS Lê Trí Viễn');
define('SITE_DESCRIPTION', 'Hệ thống quản lý học tập & thi đua');

// 3. Thêm virtual host
$localDomains = array(
    'letrivien.local',
    'www.letrivien.local'
);
```

### 📦 Bước 4: Include helpers

```php
// Thêm vào các file admin/student cần dùng:

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/permission_helper.php';
require_once __DIR__ . '/../includes/thidua_helper.php';
```

### 📦 Bước 5: Tạo Virtual Host

**Cách nhanh:**
```powershell
# Chạy script (sẽ tạo sau)
.\migrate_to_letrivien.ps1
```

**Hoặc thủ công:**
Theo hướng dẫn trong `HUONG_DAN_TAO_VIRTUAL_HOST.md`, thay:
- truongbuithixuan → letrivien
- truongbuithixuan.local → letrivien.local

---

## 🗑️ XÓA THỦ MỤC RÁC

### Sau khi migrate xong và test OK:

```bash
# Xóa thư mục rác trong truongbuithixuan (nếu giữ project cũ)
cd c:\xampp\htdocs\truongbuithixuan
rm -rf xoa_tam
rm -rf xoa_tam_2

# HOẶC xóa toàn bộ project cũ (sau khi migrate xong)
cd c:\xampp\htdocs
rm -rf truongbuithixuan
```

**⚠️ CHỈ XÓA SAU KHI:**
1. ✅ Đã migrate xong sang letrivien
2. ✅ Đã test letrivien hoạt động OK
3. ✅ Đã backup database
4. ✅ Đã backup code cũ (nếu cần)

---

## 📝 CHECKLIST MIGRATE

### Trước khi migrate:
- [ ] Backup database hoctaptructuyen
- [ ] Backup database letrivien (nếu có data)
- [ ] Backup source code truongbuithixuan

### Trong quá trình migrate:
- [ ] Copy CLAUDE.md
- [ ] Copy database/migration_thidua.sql
- [ ] Copy includes/permission_helper.php
- [ ] Copy includes/thidua_helper.php
- [ ] Update includes/config.php (DB_NAME, SITE_NAME, localDomains)
- [ ] Copy config_samples/
- [ ] Copy HUONG_DAN_TAO_VIRTUAL_HOST.md
- [ ] Chạy migration SQL vào database letrivien
- [ ] Tạo Virtual Host letrivien.local
- [ ] Test database connection
- [ ] Test helper functions

### Sau khi migrate:
- [ ] Test http://letrivien.local hoạt động
- [ ] Test đăng nhập admin
- [ ] Test đăng nhập học sinh
- [ ] Test các chức năng cũ
- [ ] Xóa thư mục xoa_tam, xoa_tam_2 (nếu giữ project cũ)
- [ ] Hoặc xóa toàn bộ truongbuithixuan (nếu không cần)

---

## 🤔 BẠN MUỐN GÌ?

### 🔵 Option 1: Tôi tự động migrate cho bạn
Tôi sẽ:
1. Tạo script PowerShell tự động
2. Copy tất cả files cần thiết
3. Update config tự động
4. Tạo Virtual Host
5. Hướng dẫn chạy migration SQL

**→ Nhanh, ít lỗi, tự động**

### 🟢 Option 2: Bạn tự migrate theo hướng dẫn
Bạn:
1. Làm theo file này từng bước
2. Copy files thủ công
3. Chỉnh config thủ công
4. Chạy SQL thủ công

**→ Hiểu rõ từng bước, chủ động**

### 🟡 Option 3: Giữ nguyên truongbuithixuan, bỏ qua letrivien
Vẫn:
1. Dùng truongbuithixuan
2. Database hoctaptructuyen
3. Xóa xoa_tam, xoa_tam_2
4. Tiếp tục code Admin Panel

**→ Ít thay đổi, nhanh nhất**

---

## 💡 KHUYẾN NGHỊ CỦA TÔI

**→ Chọn Option 1: Migrate tự động sang letrivien**

**Lý do:**
1. Tên project phù hợp với trường thực tế
2. Database mới sạch sẽ
3. Không có thư mục rác
4. Dễ quản lý lâu dài
5. Tôi sẽ tự động hóa toàn bộ, bạn chỉ cần chạy script

**Thời gian:** ~10 phút (bao gồm backup, migrate, test)

---

**BẠN CHỌN OPTION NÀO?** 🚀
