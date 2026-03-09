-- =====================================================
-- MIGRATION: Thêm cột file_path, file_name vào bảng tai_lieu
-- Hỗ trợ upload file trực tiếp (Word, Excel, PDF,...)
-- =====================================================

ALTER TABLE tai_lieu
  ADD COLUMN file_path VARCHAR(500) DEFAULT NULL
    COMMENT 'Đường dẫn file upload (uploads/documents/...)'
    AFTER noi_dung,
  ADD COLUMN file_name VARCHAR(255) DEFAULT NULL
    COMMENT 'Tên file gốc khi upload'
    AFTER file_path;
