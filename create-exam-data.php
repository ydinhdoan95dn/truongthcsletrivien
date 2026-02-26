<?php
/**
 * Script tạo dữ liệu đề thi Toán lớp 3 và lớp 4
 * Mỗi lớp 10 bộ đề, mỗi đề 10 câu hỏi
 */
require_once 'includes/config.php';

header('Content-Type: text/html; charset=utf-8');

$conn = getDBConnection();

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Tạo đề thi</title>";
echo "<style>
body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
h1, h2, h3 { color: #333; }
.success { color: green; }
.exam-box { background: white; padding: 15px; margin: 10px 0; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
table { border-collapse: collapse; width: 100%; margin: 10px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background: #4CAF50; color: white; }
</style></head><body>";

echo "<h1>Tạo Dữ liệu Đề thi Toán Lớp 3 và Lớp 4</h1>";

// ============================================================
// DỮ LIỆU ĐỀ THI LỚP 3 - TOÁN
// Kiến thức: Phép cộng/trừ trong phạm vi 10000, nhân/chia trong bảng
// Đo lường: cm, m, km, kg, g, lít, giờ phút
// Hình học: Góc vuông, chu vi hình chữ nhật, hình vuông
// ============================================================

$exams_lop3 = array(
    // ĐỀ 1: Phép cộng trừ trong phạm vi 1000
    array(
        'ten_de' => 'Ôn tập Phép cộng, trừ trong phạm vi 1000',
        'mo_ta' => 'Luyện tập phép cộng và trừ các số trong phạm vi 1000',
        'cau_hoi' => array(
            array('noi_dung' => '234 + 156 = ?', 'a' => '380', 'b' => '390', 'c' => '400', 'd' => '410', 'dung' => 'B', 'giai_thich' => '234 + 156 = 390'),
            array('noi_dung' => '500 - 237 = ?', 'a' => '263', 'b' => '273', 'c' => '283', 'd' => '253', 'dung' => 'A', 'giai_thich' => '500 - 237 = 263'),
            array('noi_dung' => '428 + 372 = ?', 'a' => '790', 'b' => '800', 'c' => '810', 'd' => '700', 'dung' => 'B', 'giai_thich' => '428 + 372 = 800'),
            array('noi_dung' => '1000 - 456 = ?', 'a' => '544', 'b' => '554', 'c' => '534', 'd' => '564', 'dung' => 'A', 'giai_thich' => '1000 - 456 = 544'),
            array('noi_dung' => '375 + 425 = ?', 'a' => '700', 'b' => '800', 'c' => '900', 'd' => '750', 'dung' => 'B', 'giai_thich' => '375 + 425 = 800'),
            array('noi_dung' => '836 - 248 = ?', 'a' => '588', 'b' => '598', 'c' => '578', 'd' => '568', 'dung' => 'A', 'giai_thich' => '836 - 248 = 588'),
            array('noi_dung' => '567 + 333 = ?', 'a' => '800', 'b' => '890', 'c' => '900', 'd' => '910', 'dung' => 'C', 'giai_thich' => '567 + 333 = 900'),
            array('noi_dung' => '702 - 358 = ?', 'a' => '344', 'b' => '354', 'c' => '334', 'd' => '364', 'dung' => 'A', 'giai_thich' => '702 - 358 = 344'),
            array('noi_dung' => '189 + 211 = ?', 'a' => '390', 'b' => '400', 'c' => '410', 'd' => '380', 'dung' => 'B', 'giai_thich' => '189 + 211 = 400'),
            array('noi_dung' => '645 - 287 = ?', 'a' => '358', 'b' => '368', 'c' => '348', 'd' => '378', 'dung' => 'A', 'giai_thich' => '645 - 287 = 358')
        )
    ),

    // ĐỀ 2: Bảng nhân 2, 3, 4, 5
    array(
        'ten_de' => 'Ôn tập Bảng nhân 2, 3, 4, 5',
        'mo_ta' => 'Luyện tập bảng nhân từ 2 đến 5',
        'cau_hoi' => array(
            array('noi_dung' => '7 x 2 = ?', 'a' => '12', 'b' => '14', 'c' => '16', 'd' => '18', 'dung' => 'B', 'giai_thich' => '7 x 2 = 14'),
            array('noi_dung' => '8 x 3 = ?', 'a' => '21', 'b' => '24', 'c' => '27', 'd' => '30', 'dung' => 'B', 'giai_thich' => '8 x 3 = 24'),
            array('noi_dung' => '6 x 4 = ?', 'a' => '20', 'b' => '22', 'c' => '24', 'd' => '26', 'dung' => 'C', 'giai_thich' => '6 x 4 = 24'),
            array('noi_dung' => '9 x 5 = ?', 'a' => '40', 'b' => '45', 'c' => '50', 'd' => '55', 'dung' => 'B', 'giai_thich' => '9 x 5 = 45'),
            array('noi_dung' => '5 x 3 = ?', 'a' => '12', 'b' => '15', 'c' => '18', 'd' => '21', 'dung' => 'B', 'giai_thich' => '5 x 3 = 15'),
            array('noi_dung' => '4 x 4 = ?', 'a' => '12', 'b' => '14', 'c' => '16', 'd' => '18', 'dung' => 'C', 'giai_thich' => '4 x 4 = 16'),
            array('noi_dung' => '7 x 5 = ?', 'a' => '30', 'b' => '35', 'c' => '40', 'd' => '45', 'dung' => 'B', 'giai_thich' => '7 x 5 = 35'),
            array('noi_dung' => '9 x 2 = ?', 'a' => '16', 'b' => '18', 'c' => '20', 'd' => '22', 'dung' => 'B', 'giai_thich' => '9 x 2 = 18'),
            array('noi_dung' => '6 x 3 = ?', 'a' => '15', 'b' => '18', 'c' => '21', 'd' => '24', 'dung' => 'B', 'giai_thich' => '6 x 3 = 18'),
            array('noi_dung' => '8 x 4 = ?', 'a' => '28', 'b' => '32', 'c' => '36', 'd' => '40', 'dung' => 'B', 'giai_thich' => '8 x 4 = 32')
        )
    ),

    // ĐỀ 3: Bảng nhân 6, 7, 8, 9
    array(
        'ten_de' => 'Ôn tập Bảng nhân 6, 7, 8, 9',
        'mo_ta' => 'Luyện tập bảng nhân từ 6 đến 9',
        'cau_hoi' => array(
            array('noi_dung' => '7 x 6 = ?', 'a' => '36', 'b' => '42', 'c' => '48', 'd' => '54', 'dung' => 'B', 'giai_thich' => '7 x 6 = 42'),
            array('noi_dung' => '8 x 7 = ?', 'a' => '49', 'b' => '54', 'c' => '56', 'd' => '63', 'dung' => 'C', 'giai_thich' => '8 x 7 = 56'),
            array('noi_dung' => '6 x 8 = ?', 'a' => '42', 'b' => '48', 'c' => '54', 'd' => '56', 'dung' => 'B', 'giai_thich' => '6 x 8 = 48'),
            array('noi_dung' => '9 x 9 = ?', 'a' => '72', 'b' => '81', 'c' => '90', 'd' => '99', 'dung' => 'B', 'giai_thich' => '9 x 9 = 81'),
            array('noi_dung' => '5 x 6 = ?', 'a' => '25', 'b' => '30', 'c' => '35', 'd' => '40', 'dung' => 'B', 'giai_thich' => '5 x 6 = 30'),
            array('noi_dung' => '4 x 7 = ?', 'a' => '21', 'b' => '24', 'c' => '28', 'd' => '32', 'dung' => 'C', 'giai_thich' => '4 x 7 = 28'),
            array('noi_dung' => '7 x 8 = ?', 'a' => '48', 'b' => '54', 'c' => '56', 'd' => '63', 'dung' => 'C', 'giai_thich' => '7 x 8 = 56'),
            array('noi_dung' => '9 x 6 = ?', 'a' => '48', 'b' => '54', 'c' => '60', 'd' => '66', 'dung' => 'B', 'giai_thich' => '9 x 6 = 54'),
            array('noi_dung' => '6 x 7 = ?', 'a' => '36', 'b' => '42', 'c' => '48', 'd' => '54', 'dung' => 'B', 'giai_thich' => '6 x 7 = 42'),
            array('noi_dung' => '8 x 9 = ?', 'a' => '63', 'b' => '72', 'c' => '81', 'd' => '90', 'dung' => 'B', 'giai_thich' => '8 x 9 = 72')
        )
    ),

    // ĐỀ 4: Bảng chia
    array(
        'ten_de' => 'Ôn tập Bảng chia',
        'mo_ta' => 'Luyện tập phép chia trong bảng',
        'cau_hoi' => array(
            array('noi_dung' => '18 : 2 = ?', 'a' => '7', 'b' => '8', 'c' => '9', 'd' => '10', 'dung' => 'C', 'giai_thich' => '18 : 2 = 9'),
            array('noi_dung' => '24 : 3 = ?', 'a' => '6', 'b' => '7', 'c' => '8', 'd' => '9', 'dung' => 'C', 'giai_thich' => '24 : 3 = 8'),
            array('noi_dung' => '36 : 4 = ?', 'a' => '7', 'b' => '8', 'c' => '9', 'd' => '10', 'dung' => 'C', 'giai_thich' => '36 : 4 = 9'),
            array('noi_dung' => '45 : 5 = ?', 'a' => '7', 'b' => '8', 'c' => '9', 'd' => '10', 'dung' => 'C', 'giai_thich' => '45 : 5 = 9'),
            array('noi_dung' => '42 : 6 = ?', 'a' => '6', 'b' => '7', 'c' => '8', 'd' => '9', 'dung' => 'B', 'giai_thich' => '42 : 6 = 7'),
            array('noi_dung' => '56 : 7 = ?', 'a' => '6', 'b' => '7', 'c' => '8', 'd' => '9', 'dung' => 'C', 'giai_thich' => '56 : 7 = 8'),
            array('noi_dung' => '64 : 8 = ?', 'a' => '6', 'b' => '7', 'c' => '8', 'd' => '9', 'dung' => 'C', 'giai_thich' => '64 : 8 = 8'),
            array('noi_dung' => '81 : 9 = ?', 'a' => '7', 'b' => '8', 'c' => '9', 'd' => '10', 'dung' => 'C', 'giai_thich' => '81 : 9 = 9'),
            array('noi_dung' => '35 : 5 = ?', 'a' => '5', 'b' => '6', 'c' => '7', 'd' => '8', 'dung' => 'C', 'giai_thich' => '35 : 5 = 7'),
            array('noi_dung' => '72 : 8 = ?', 'a' => '7', 'b' => '8', 'c' => '9', 'd' => '10', 'dung' => 'C', 'giai_thich' => '72 : 8 = 9')
        )
    ),

    // ĐỀ 5: Đơn vị đo độ dài
    array(
        'ten_de' => 'Ôn tập Đơn vị đo độ dài',
        'mo_ta' => 'Luyện tập đổi đơn vị và tính toán với đơn vị đo độ dài',
        'cau_hoi' => array(
            array('noi_dung' => '1m = ? cm', 'a' => '10', 'b' => '100', 'c' => '1000', 'd' => '50', 'dung' => 'B', 'giai_thich' => '1m = 100cm'),
            array('noi_dung' => '5m 30cm = ? cm', 'a' => '530', 'b' => '503', 'c' => '53', 'd' => '5030', 'dung' => 'A', 'giai_thich' => '5m 30cm = 500cm + 30cm = 530cm'),
            array('noi_dung' => '1km = ? m', 'a' => '10', 'b' => '100', 'c' => '1000', 'd' => '10000', 'dung' => 'C', 'giai_thich' => '1km = 1000m'),
            array('noi_dung' => '3m 5cm = ? cm', 'a' => '35', 'b' => '305', 'c' => '350', 'd' => '3005', 'dung' => 'B', 'giai_thich' => '3m 5cm = 300cm + 5cm = 305cm'),
            array('noi_dung' => '250cm = ? m ? cm', 'a' => '2m 5cm', 'b' => '2m 50cm', 'c' => '25m 0cm', 'd' => '2m 500cm', 'dung' => 'B', 'giai_thich' => '250cm = 200cm + 50cm = 2m 50cm'),
            array('noi_dung' => '4km 500m = ? m', 'a' => '4500', 'b' => '4050', 'c' => '45000', 'd' => '450', 'dung' => 'A', 'giai_thich' => '4km 500m = 4000m + 500m = 4500m'),
            array('noi_dung' => '2m + 75cm = ? cm', 'a' => '275', 'b' => '2075', 'c' => '207', 'd' => '257', 'dung' => 'A', 'giai_thich' => '2m + 75cm = 200cm + 75cm = 275cm'),
            array('noi_dung' => '800cm = ? m', 'a' => '8', 'b' => '80', 'c' => '800', 'd' => '0.8', 'dung' => 'A', 'giai_thich' => '800cm = 800 : 100 = 8m'),
            array('noi_dung' => '6m - 150cm = ? cm', 'a' => '450', 'b' => '550', 'c' => '500', 'd' => '600', 'dung' => 'A', 'giai_thich' => '6m - 150cm = 600cm - 150cm = 450cm'),
            array('noi_dung' => '1m 25cm + 75cm = ?', 'a' => '1m 100cm', 'b' => '2m', 'c' => '2m 25cm', 'd' => '1m 75cm', 'dung' => 'B', 'giai_thich' => '1m 25cm + 75cm = 125cm + 75cm = 200cm = 2m')
        )
    ),

    // ĐỀ 6: Đơn vị đo khối lượng
    array(
        'ten_de' => 'Ôn tập Đơn vị đo khối lượng',
        'mo_ta' => 'Luyện tập đổi đơn vị và tính toán với đơn vị đo khối lượng',
        'cau_hoi' => array(
            array('noi_dung' => '1kg = ? g', 'a' => '10', 'b' => '100', 'c' => '1000', 'd' => '10000', 'dung' => 'C', 'giai_thich' => '1kg = 1000g'),
            array('noi_dung' => '3kg 500g = ? g', 'a' => '350', 'b' => '3500', 'c' => '35000', 'd' => '305', 'dung' => 'B', 'giai_thich' => '3kg 500g = 3000g + 500g = 3500g'),
            array('noi_dung' => '2500g = ? kg ? g', 'a' => '2kg 5g', 'b' => '2kg 500g', 'c' => '25kg 0g', 'd' => '250kg 0g', 'dung' => 'B', 'giai_thich' => '2500g = 2000g + 500g = 2kg 500g'),
            array('noi_dung' => '5kg - 800g = ? g', 'a' => '4200', 'b' => '4800', 'c' => '5800', 'd' => '4020', 'dung' => 'A', 'giai_thich' => '5kg - 800g = 5000g - 800g = 4200g'),
            array('noi_dung' => '1kg 250g + 750g = ?', 'a' => '1kg 1000g', 'b' => '2kg', 'c' => '2kg 250g', 'd' => '1kg 750g', 'dung' => 'B', 'giai_thich' => '1kg 250g + 750g = 1250g + 750g = 2000g = 2kg'),
            array('noi_dung' => '4000g = ? kg', 'a' => '4', 'b' => '40', 'c' => '400', 'd' => '0.4', 'dung' => 'A', 'giai_thich' => '4000g = 4000 : 1000 = 4kg'),
            array('noi_dung' => '6kg 200g = ? g', 'a' => '620', 'b' => '6200', 'c' => '62000', 'd' => '602', 'dung' => 'B', 'giai_thich' => '6kg 200g = 6000g + 200g = 6200g'),
            array('noi_dung' => '3kg + 1500g = ? g', 'a' => '3150', 'b' => '4500', 'c' => '31500', 'd' => '1800', 'dung' => 'B', 'giai_thich' => '3kg + 1500g = 3000g + 1500g = 4500g'),
            array('noi_dung' => '7500g = ? kg ? g', 'a' => '7kg 5g', 'b' => '7kg 500g', 'c' => '75kg 0g', 'd' => '750kg 0g', 'dung' => 'B', 'giai_thich' => '7500g = 7000g + 500g = 7kg 500g'),
            array('noi_dung' => '2kg 400g - 600g = ?', 'a' => '1kg 800g', 'b' => '2kg 200g', 'c' => '1kg 600g', 'd' => '2kg 800g', 'dung' => 'A', 'giai_thich' => '2kg 400g - 600g = 2400g - 600g = 1800g = 1kg 800g')
        )
    ),

    // ĐỀ 7: Chu vi hình chữ nhật, hình vuông
    array(
        'ten_de' => 'Chu vi hình chữ nhật, hình vuông',
        'mo_ta' => 'Luyện tập tính chu vi hình chữ nhật và hình vuông',
        'cau_hoi' => array(
            array('noi_dung' => 'Hình vuông có cạnh 5cm. Chu vi hình vuông là bao nhiêu?', 'a' => '10cm', 'b' => '15cm', 'c' => '20cm', 'd' => '25cm', 'dung' => 'C', 'giai_thich' => 'Chu vi hình vuông = 4 x cạnh = 4 x 5 = 20cm'),
            array('noi_dung' => 'Hình chữ nhật có chiều dài 8cm, chiều rộng 4cm. Chu vi là?', 'a' => '12cm', 'b' => '24cm', 'c' => '32cm', 'd' => '20cm', 'dung' => 'B', 'giai_thich' => 'Chu vi HCN = (dài + rộng) x 2 = (8 + 4) x 2 = 24cm'),
            array('noi_dung' => 'Hình vuông có chu vi 36cm. Cạnh hình vuông là?', 'a' => '6cm', 'b' => '8cm', 'c' => '9cm', 'd' => '12cm', 'dung' => 'C', 'giai_thich' => 'Cạnh = Chu vi : 4 = 36 : 4 = 9cm'),
            array('noi_dung' => 'Hình chữ nhật có chiều dài 10cm, chiều rộng 6cm. Chu vi là?', 'a' => '16cm', 'b' => '32cm', 'c' => '60cm', 'd' => '26cm', 'dung' => 'B', 'giai_thich' => 'Chu vi HCN = (10 + 6) x 2 = 32cm'),
            array('noi_dung' => 'Hình vuông có cạnh 7cm. Chu vi là bao nhiêu?', 'a' => '14cm', 'b' => '21cm', 'c' => '28cm', 'd' => '49cm', 'dung' => 'C', 'giai_thich' => 'Chu vi = 4 x 7 = 28cm'),
            array('noi_dung' => 'Hình chữ nhật có chu vi 30cm, chiều dài 10cm. Chiều rộng là?', 'a' => '5cm', 'b' => '10cm', 'c' => '15cm', 'd' => '20cm', 'dung' => 'A', 'giai_thich' => 'Nửa chu vi = 30 : 2 = 15cm. Chiều rộng = 15 - 10 = 5cm'),
            array('noi_dung' => 'Hình vuông có cạnh 12cm. Chu vi là?', 'a' => '24cm', 'b' => '36cm', 'c' => '48cm', 'd' => '144cm', 'dung' => 'C', 'giai_thich' => 'Chu vi = 4 x 12 = 48cm'),
            array('noi_dung' => 'Hình chữ nhật có chiều dài gấp đôi chiều rộng, chiều rộng 5cm. Chu vi là?', 'a' => '15cm', 'b' => '20cm', 'c' => '25cm', 'd' => '30cm', 'dung' => 'D', 'giai_thich' => 'Dài = 5 x 2 = 10cm. Chu vi = (10 + 5) x 2 = 30cm'),
            array('noi_dung' => 'Một sân trường hình vuông có cạnh 50m. Chu vi sân trường là?', 'a' => '100m', 'b' => '150m', 'c' => '200m', 'd' => '250m', 'dung' => 'C', 'giai_thich' => 'Chu vi = 4 x 50 = 200m'),
            array('noi_dung' => 'Hình chữ nhật có chiều dài 15cm, chiều rộng 8cm. Chu vi là?', 'a' => '23cm', 'b' => '46cm', 'c' => '120cm', 'd' => '38cm', 'dung' => 'B', 'giai_thich' => 'Chu vi = (15 + 8) x 2 = 46cm')
        )
    ),

    // ĐỀ 8: Xem giờ và tính thời gian
    array(
        'ten_de' => 'Xem giờ và tính thời gian',
        'mo_ta' => 'Luyện tập xem đồng hồ và tính thời gian',
        'cau_hoi' => array(
            array('noi_dung' => '1 giờ = ? phút', 'a' => '30', 'b' => '45', 'c' => '60', 'd' => '100', 'dung' => 'C', 'giai_thich' => '1 giờ = 60 phút'),
            array('noi_dung' => '2 giờ 30 phút = ? phút', 'a' => '120', 'b' => '130', 'c' => '150', 'd' => '230', 'dung' => 'C', 'giai_thich' => '2 giờ 30 phút = 120 phút + 30 phút = 150 phút'),
            array('noi_dung' => '90 phút = ? giờ ? phút', 'a' => '1 giờ 20 phút', 'b' => '1 giờ 30 phút', 'c' => '1 giờ 50 phút', 'd' => '2 giờ', 'dung' => 'B', 'giai_thich' => '90 phút = 60 phút + 30 phút = 1 giờ 30 phút'),
            array('noi_dung' => 'Bạn An bắt đầu học lúc 7 giờ, học trong 45 phút. Bạn An kết thúc lúc mấy giờ?', 'a' => '7 giờ 30 phút', 'b' => '7 giờ 45 phút', 'c' => '8 giờ', 'd' => '8 giờ 15 phút', 'dung' => 'B', 'giai_thich' => '7 giờ + 45 phút = 7 giờ 45 phút'),
            array('noi_dung' => '1 ngày = ? giờ', 'a' => '12', 'b' => '20', 'c' => '24', 'd' => '48', 'dung' => 'C', 'giai_thich' => '1 ngày = 24 giờ'),
            array('noi_dung' => 'Từ 8 giờ đến 10 giờ 30 phút là bao lâu?', 'a' => '2 giờ', 'b' => '2 giờ 30 phút', 'c' => '3 giờ', 'd' => '1 giờ 30 phút', 'dung' => 'B', 'giai_thich' => 'Từ 8 giờ đến 10 giờ 30 phút = 2 giờ 30 phút'),
            array('noi_dung' => '3 giờ = ? phút', 'a' => '120', 'b' => '150', 'c' => '180', 'd' => '200', 'dung' => 'C', 'giai_thich' => '3 giờ = 3 x 60 = 180 phút'),
            array('noi_dung' => 'Bạn Lan đi ngủ lúc 21 giờ, ngủ 9 tiếng. Bạn Lan thức dậy lúc mấy giờ?', 'a' => '5 giờ', 'b' => '6 giờ', 'c' => '7 giờ', 'd' => '8 giờ', 'dung' => 'B', 'giai_thich' => '21 giờ + 9 tiếng = 30 giờ = 6 giờ sáng hôm sau'),
            array('noi_dung' => '75 phút = ? giờ ? phút', 'a' => '1 giờ 5 phút', 'b' => '1 giờ 15 phút', 'c' => '1 giờ 25 phút', 'd' => '1 giờ 35 phút', 'dung' => 'B', 'giai_thich' => '75 phút = 60 phút + 15 phút = 1 giờ 15 phút'),
            array('noi_dung' => '1 tuần = ? ngày', 'a' => '5', 'b' => '6', 'c' => '7', 'd' => '10', 'dung' => 'C', 'giai_thich' => '1 tuần = 7 ngày')
        )
    ),

    // ĐỀ 9: Phép nhân, chia ngoài bảng
    array(
        'ten_de' => 'Phép nhân, chia ngoài bảng',
        'mo_ta' => 'Luyện tập nhân chia với số có 2 chữ số',
        'cau_hoi' => array(
            array('noi_dung' => '12 x 3 = ?', 'a' => '33', 'b' => '36', 'c' => '39', 'd' => '42', 'dung' => 'B', 'giai_thich' => '12 x 3 = 36'),
            array('noi_dung' => '24 x 4 = ?', 'a' => '86', 'b' => '92', 'c' => '96', 'd' => '104', 'dung' => 'C', 'giai_thich' => '24 x 4 = 96'),
            array('noi_dung' => '48 : 4 = ?', 'a' => '10', 'b' => '11', 'c' => '12', 'd' => '14', 'dung' => 'C', 'giai_thich' => '48 : 4 = 12'),
            array('noi_dung' => '15 x 6 = ?', 'a' => '80', 'b' => '85', 'c' => '90', 'd' => '95', 'dung' => 'C', 'giai_thich' => '15 x 6 = 90'),
            array('noi_dung' => '84 : 7 = ?', 'a' => '11', 'b' => '12', 'c' => '13', 'd' => '14', 'dung' => 'B', 'giai_thich' => '84 : 7 = 12'),
            array('noi_dung' => '25 x 4 = ?', 'a' => '90', 'b' => '95', 'c' => '100', 'd' => '105', 'dung' => 'C', 'giai_thich' => '25 x 4 = 100'),
            array('noi_dung' => '96 : 8 = ?', 'a' => '11', 'b' => '12', 'c' => '13', 'd' => '14', 'dung' => 'B', 'giai_thich' => '96 : 8 = 12'),
            array('noi_dung' => '18 x 5 = ?', 'a' => '80', 'b' => '85', 'c' => '90', 'd' => '95', 'dung' => 'C', 'giai_thich' => '18 x 5 = 90'),
            array('noi_dung' => '63 : 3 = ?', 'a' => '19', 'b' => '20', 'c' => '21', 'd' => '22', 'dung' => 'C', 'giai_thich' => '63 : 3 = 21'),
            array('noi_dung' => '32 x 3 = ?', 'a' => '92', 'b' => '94', 'c' => '96', 'd' => '98', 'dung' => 'C', 'giai_thich' => '32 x 3 = 96')
        )
    ),

    // ĐỀ 10: Giải toán có lời văn
    array(
        'ten_de' => 'Giải toán có lời văn lớp 3',
        'mo_ta' => 'Luyện tập giải các bài toán có lời văn',
        'cau_hoi' => array(
            array('noi_dung' => 'Mẹ mua 5 túi cam, mỗi túi có 8 quả. Hỏi mẹ mua tất cả bao nhiêu quả cam?', 'a' => '35 quả', 'b' => '40 quả', 'c' => '45 quả', 'd' => '50 quả', 'dung' => 'B', 'giai_thich' => 'Số quả cam = 5 x 8 = 40 quả'),
            array('noi_dung' => 'Lớp 3A có 32 học sinh, lớp 3B có ít hơn lớp 3A 5 học sinh. Hỏi lớp 3B có bao nhiêu học sinh?', 'a' => '27', 'b' => '28', 'c' => '37', 'd' => '35', 'dung' => 'A', 'giai_thich' => 'Lớp 3B có: 32 - 5 = 27 học sinh'),
            array('noi_dung' => 'Có 36 cái kẹo chia đều cho 4 bạn. Mỗi bạn được mấy cái kẹo?', 'a' => '7', 'b' => '8', 'c' => '9', 'd' => '10', 'dung' => 'C', 'giai_thich' => 'Mỗi bạn được: 36 : 4 = 9 cái kẹo'),
            array('noi_dung' => 'Bạn An có 45 viên bi, bạn Bình có nhiều hơn bạn An 18 viên. Hỏi bạn Bình có bao nhiêu viên bi?', 'a' => '27', 'b' => '53', 'c' => '63', 'd' => '73', 'dung' => 'C', 'giai_thich' => 'Bạn Bình có: 45 + 18 = 63 viên bi'),
            array('noi_dung' => 'Một cửa hàng có 84 kg gạo, đã bán 1/3 số gạo. Hỏi cửa hàng còn lại bao nhiêu kg gạo?', 'a' => '28 kg', 'b' => '56 kg', 'c' => '42 kg', 'd' => '54 kg', 'dung' => 'B', 'giai_thich' => 'Số gạo đã bán: 84 : 3 = 28 kg. Còn lại: 84 - 28 = 56 kg'),
            array('noi_dung' => 'Mỗi hộp có 6 cái bánh. Hỏi 9 hộp có bao nhiêu cái bánh?', 'a' => '45', 'b' => '48', 'c' => '54', 'd' => '56', 'dung' => 'C', 'giai_thich' => 'Số cái bánh = 6 x 9 = 54 cái'),
            array('noi_dung' => 'Bố năm nay 42 tuổi, bố hơn con 28 tuổi. Hỏi con năm nay bao nhiêu tuổi?', 'a' => '12', 'b' => '14', 'c' => '16', 'd' => '70', 'dung' => 'B', 'giai_thich' => 'Tuổi con = 42 - 28 = 14 tuổi'),
            array('noi_dung' => 'Một đoàn tàu có 8 toa, mỗi toa chở 125 hành khách. Hỏi đoàn tàu chở được bao nhiêu hành khách?', 'a' => '900', 'b' => '950', 'c' => '1000', 'd' => '1050', 'dung' => 'C', 'giai_thich' => 'Số hành khách = 8 x 125 = 1000 người'),
            array('noi_dung' => 'Có 72 quyển vở xếp đều vào 6 ngăn. Mỗi ngăn có bao nhiêu quyển vở?', 'a' => '10', 'b' => '11', 'c' => '12', 'd' => '13', 'dung' => 'C', 'giai_thich' => 'Mỗi ngăn có: 72 : 6 = 12 quyển vở'),
            array('noi_dung' => 'Năm nay em 8 tuổi, chị hơn em 5 tuổi. Hỏi 3 năm nữa chị bao nhiêu tuổi?', 'a' => '13', 'b' => '14', 'c' => '15', 'd' => '16', 'dung' => 'D', 'giai_thich' => 'Tuổi chị năm nay = 8 + 5 = 13 tuổi. 3 năm nữa chị: 13 + 3 = 16 tuổi')
        )
    )
);

// ============================================================
// DỮ LIỆU ĐỀ THI LỚP 4 - TOÁN
// Kiến thức: Số tự nhiên đến hàng triệu, phân số, số thập phân đơn giản
// Phép tính: Nhân chia với số có nhiều chữ số
// Hình học: Diện tích hình chữ nhật, hình vuông, góc nhọn, góc tù
// ============================================================

$exams_lop4 = array(
    // ĐỀ 1: Đọc viết số có nhiều chữ số
    array(
        'ten_de' => 'Đọc viết số có nhiều chữ số',
        'mo_ta' => 'Luyện tập đọc, viết và so sánh các số tự nhiên lớn',
        'cau_hoi' => array(
            array('noi_dung' => 'Số "Năm triệu ba trăm hai mươi nghìn bốn trăm linh năm" viết là:', 'a' => '5 320 450', 'b' => '5 320 405', 'c' => '5 302 405', 'd' => '53 204 05', 'dung' => 'B', 'giai_thich' => 'Năm triệu = 5 000 000, ba trăm hai mươi nghìn = 320 000, bốn trăm linh năm = 405'),
            array('noi_dung' => 'Số 7 025 136 đọc là:', 'a' => 'Bảy triệu hai mươi lăm nghìn một trăm ba mươi sáu', 'b' => 'Bảy triệu không trăm hai mươi lăm nghìn một trăm ba mươi sáu', 'c' => 'Bảy triệu hai trăm năm nghìn một trăm ba sáu', 'd' => 'Bảy mươi triệu hai mươi lăm nghìn một trăm ba mươi sáu', 'dung' => 'A', 'giai_thich' => '7 025 136 = 7 triệu, 25 nghìn, 136'),
            array('noi_dung' => 'Giá trị của chữ số 5 trong số 3 586 247 là:', 'a' => '5 000', 'b' => '50 000', 'c' => '500 000', 'd' => '5 000 000', 'dung' => 'C', 'giai_thich' => 'Chữ số 5 ở hàng trăm nghìn nên có giá trị 500 000'),
            array('noi_dung' => 'Số lớn nhất trong các số sau: 9 876 543; 9 867 543; 9 876 453; 9 876 534 là:', 'a' => '9 876 543', 'b' => '9 867 543', 'c' => '9 876 453', 'd' => '9 876 534', 'dung' => 'A', 'giai_thich' => 'So sánh từ hàng cao nhất, 9 876 543 là số lớn nhất'),
            array('noi_dung' => 'Số liền sau của số 2 999 999 là:', 'a' => '2 999 998', 'b' => '3 000 000', 'c' => '29 999 910', 'd' => '2 999 000', 'dung' => 'B', 'giai_thich' => 'Số liền sau = 2 999 999 + 1 = 3 000 000'),
            array('noi_dung' => 'Số liền trước của số 5 000 000 là:', 'a' => '4 999 999', 'b' => '5 000 001', 'c' => '4 999 990', 'd' => '4 999 000', 'dung' => 'A', 'giai_thich' => 'Số liền trước = 5 000 000 - 1 = 4 999 999'),
            array('noi_dung' => 'Số tròn triệu lớn nhất có 7 chữ số là:', 'a' => '1 000 000', 'b' => '9 000 000', 'c' => '9 999 999', 'd' => '10 000 000', 'dung' => 'B', 'giai_thich' => 'Số tròn triệu có 7 chữ số lớn nhất là 9 000 000'),
            array('noi_dung' => 'Sắp xếp các số sau theo thứ tự từ bé đến lớn: 5 678 123; 5 687 123; 5 678 213; 5 678 132', 'a' => '5 678 123; 5 678 132; 5 678 213; 5 687 123', 'b' => '5 678 132; 5 678 123; 5 678 213; 5 687 123', 'c' => '5 687 123; 5 678 213; 5 678 132; 5 678 123', 'd' => '5 678 123; 5 678 213; 5 678 132; 5 687 123', 'dung' => 'A', 'giai_thich' => 'So sánh từng hàng: 5 678 123 < 5 678 132 < 5 678 213 < 5 687 123'),
            array('noi_dung' => 'Trong số 4 567 891, chữ số 6 thuộc hàng nào?', 'a' => 'Hàng nghìn', 'b' => 'Hàng chục nghìn', 'c' => 'Hàng trăm nghìn', 'd' => 'Hàng triệu', 'dung' => 'B', 'giai_thich' => 'Từ phải sang: 1-đơn vị, 9-chục, 8-trăm, 7-nghìn, 6-chục nghìn'),
            array('noi_dung' => 'Làm tròn số 3 456 789 đến hàng trăm nghìn được số:', 'a' => '3 400 000', 'b' => '3 500 000', 'c' => '3 450 000', 'd' => '3 000 000', 'dung' => 'B', 'giai_thich' => 'Chữ số hàng chục nghìn là 5, nên làm tròn lên: 3 500 000')
        )
    ),

    // ĐỀ 2: Phép cộng trừ số có nhiều chữ số
    array(
        'ten_de' => 'Phép cộng trừ số có nhiều chữ số',
        'mo_ta' => 'Luyện tập cộng trừ các số có đến 6 chữ số',
        'cau_hoi' => array(
            array('noi_dung' => '234 567 + 145 433 = ?', 'a' => '370 000', 'b' => '380 000', 'c' => '379 000', 'd' => '390 000', 'dung' => 'B', 'giai_thich' => '234 567 + 145 433 = 380 000'),
            array('noi_dung' => '500 000 - 123 456 = ?', 'a' => '376 544', 'b' => '376 554', 'c' => '377 544', 'd' => '386 544', 'dung' => 'A', 'giai_thich' => '500 000 - 123 456 = 376 544'),
            array('noi_dung' => '678 912 + 321 088 = ?', 'a' => '999 000', 'b' => '1 000 000', 'c' => '990 000', 'd' => '1 100 000', 'dung' => 'B', 'giai_thich' => '678 912 + 321 088 = 1 000 000'),
            array('noi_dung' => '1 000 000 - 456 789 = ?', 'a' => '543 211', 'b' => '544 211', 'c' => '543 111', 'd' => '553 211', 'dung' => 'A', 'giai_thich' => '1 000 000 - 456 789 = 543 211'),
            array('noi_dung' => '159 753 + 246 802 = ?', 'a' => '406 555', 'b' => '405 555', 'c' => '406 655', 'd' => '416 555', 'dung' => 'A', 'giai_thich' => '159 753 + 246 802 = 406 555'),
            array('noi_dung' => '876 543 - 234 567 = ?', 'a' => '641 976', 'b' => '642 976', 'c' => '641 986', 'd' => '651 976', 'dung' => 'A', 'giai_thich' => '876 543 - 234 567 = 641 976'),
            array('noi_dung' => '425 000 + 375 000 = ?', 'a' => '700 000', 'b' => '800 000', 'c' => '750 000', 'd' => '850 000', 'dung' => 'B', 'giai_thich' => '425 000 + 375 000 = 800 000'),
            array('noi_dung' => '900 000 - 567 890 = ?', 'a' => '332 110', 'b' => '333 110', 'c' => '332 100', 'd' => '342 110', 'dung' => 'A', 'giai_thich' => '900 000 - 567 890 = 332 110'),
            array('noi_dung' => '246 135 + 753 865 = ?', 'a' => '999 000', 'b' => '1 000 000', 'c' => '990 000', 'd' => '1 010 000', 'dung' => 'B', 'giai_thich' => '246 135 + 753 865 = 1 000 000'),
            array('noi_dung' => '750 000 - 249 999 = ?', 'a' => '500 001', 'b' => '500 000', 'c' => '499 999', 'd' => '510 001', 'dung' => 'A', 'giai_thich' => '750 000 - 249 999 = 500 001')
        )
    ),

    // ĐỀ 3: Phép nhân với số có một chữ số
    array(
        'ten_de' => 'Phép nhân với số có một chữ số',
        'mo_ta' => 'Luyện tập nhân số có nhiều chữ số với số có một chữ số',
        'cau_hoi' => array(
            array('noi_dung' => '1 234 x 5 = ?', 'a' => '6 170', 'b' => '6 070', 'c' => '6 270', 'd' => '6 160', 'dung' => 'A', 'giai_thich' => '1 234 x 5 = 6 170'),
            array('noi_dung' => '2 456 x 3 = ?', 'a' => '7 368', 'b' => '7 268', 'c' => '7 468', 'd' => '7 358', 'dung' => 'A', 'giai_thich' => '2 456 x 3 = 7 368'),
            array('noi_dung' => '12 345 x 4 = ?', 'a' => '49 380', 'b' => '49 280', 'c' => '49 480', 'd' => '48 380', 'dung' => 'A', 'giai_thich' => '12 345 x 4 = 49 380'),
            array('noi_dung' => '3 025 x 8 = ?', 'a' => '24 100', 'b' => '24 200', 'c' => '24 300', 'd' => '24 000', 'dung' => 'B', 'giai_thich' => '3 025 x 8 = 24 200'),
            array('noi_dung' => '15 000 x 6 = ?', 'a' => '80 000', 'b' => '85 000', 'c' => '90 000', 'd' => '95 000', 'dung' => 'C', 'giai_thich' => '15 000 x 6 = 90 000'),
            array('noi_dung' => '4 567 x 7 = ?', 'a' => '31 869', 'b' => '31 969', 'c' => '31 769', 'd' => '32 069', 'dung' => 'B', 'giai_thich' => '4 567 x 7 = 31 969'),
            array('noi_dung' => '20 005 x 4 = ?', 'a' => '80 020', 'b' => '80 200', 'c' => '80 002', 'd' => '80 000', 'dung' => 'A', 'giai_thich' => '20 005 x 4 = 80 020'),
            array('noi_dung' => '8 125 x 8 = ?', 'a' => '64 000', 'b' => '65 000', 'c' => '66 000', 'd' => '63 000', 'dung' => 'B', 'giai_thich' => '8 125 x 8 = 65 000'),
            array('noi_dung' => '6 789 x 2 = ?', 'a' => '13 578', 'b' => '13 478', 'c' => '13 678', 'd' => '13 568', 'dung' => 'A', 'giai_thich' => '6 789 x 2 = 13 578'),
            array('noi_dung' => '11 111 x 9 = ?', 'a' => '99 999', 'b' => '99 000', 'c' => '100 000', 'd' => '98 999', 'dung' => 'A', 'giai_thich' => '11 111 x 9 = 99 999')
        )
    ),

    // ĐỀ 4: Phép chia cho số có một chữ số
    array(
        'ten_de' => 'Phép chia cho số có một chữ số',
        'mo_ta' => 'Luyện tập chia số có nhiều chữ số cho số có một chữ số',
        'cau_hoi' => array(
            array('noi_dung' => '8 424 : 4 = ?', 'a' => '2 016', 'b' => '2 106', 'c' => '2 116', 'd' => '2 006', 'dung' => 'B', 'giai_thich' => '8 424 : 4 = 2 106'),
            array('noi_dung' => '15 036 : 3 = ?', 'a' => '5 012', 'b' => '5 102', 'c' => '5 120', 'd' => '5 002', 'dung' => 'A', 'giai_thich' => '15 036 : 3 = 5 012'),
            array('noi_dung' => '42 000 : 6 = ?', 'a' => '6 000', 'b' => '7 000', 'c' => '8 000', 'd' => '9 000', 'dung' => 'B', 'giai_thich' => '42 000 : 6 = 7 000'),
            array('noi_dung' => '56 784 : 8 = ?', 'a' => '7 098', 'b' => '7 108', 'c' => '7 008', 'd' => '6 098', 'dung' => 'A', 'giai_thich' => '56 784 : 8 = 7 098'),
            array('noi_dung' => '24 500 : 5 = ?', 'a' => '4 800', 'b' => '4 900', 'c' => '5 000', 'd' => '4 700', 'dung' => 'B', 'giai_thich' => '24 500 : 5 = 4 900'),
            array('noi_dung' => '63 063 : 7 = ?', 'a' => '9 009', 'b' => '9 019', 'c' => '9 109', 'd' => '8 009', 'dung' => 'A', 'giai_thich' => '63 063 : 7 = 9 009'),
            array('noi_dung' => '81 000 : 9 = ?', 'a' => '8 000', 'b' => '9 000', 'c' => '10 000', 'd' => '7 000', 'dung' => 'B', 'giai_thich' => '81 000 : 9 = 9 000'),
            array('noi_dung' => '12 024 : 2 = ?', 'a' => '6 012', 'b' => '6 102', 'c' => '6 120', 'd' => '6 002', 'dung' => 'A', 'giai_thich' => '12 024 : 2 = 6 012'),
            array('noi_dung' => '36 036 : 6 = ?', 'a' => '6 006', 'b' => '6 016', 'c' => '6 106', 'd' => '5 006', 'dung' => 'A', 'giai_thich' => '36 036 : 6 = 6 006'),
            array('noi_dung' => '40 040 : 8 = ?', 'a' => '5 005', 'b' => '5 050', 'c' => '5 500', 'd' => '4 005', 'dung' => 'A', 'giai_thich' => '40 040 : 8 = 5 005')
        )
    ),

    // ĐỀ 5: Phân số - Nhận biết và so sánh
    array(
        'ten_de' => 'Phân số - Nhận biết và so sánh',
        'mo_ta' => 'Luyện tập nhận biết, đọc viết và so sánh phân số',
        'cau_hoi' => array(
            array('noi_dung' => 'Trong phân số 3/4, số 3 gọi là gì?', 'a' => 'Mẫu số', 'b' => 'Tử số', 'c' => 'Số chia', 'd' => 'Thương', 'dung' => 'B', 'giai_thich' => 'Trong phân số, số ở trên gọi là tử số'),
            array('noi_dung' => 'Phân số nào sau đây lớn hơn 1?', 'a' => '2/3', 'b' => '4/5', 'c' => '7/6', 'd' => '5/8', 'dung' => 'C', 'giai_thich' => 'Phân số có tử số lớn hơn mẫu số thì lớn hơn 1. 7/6 có 7 > 6'),
            array('noi_dung' => 'Phân số nào sau đây bằng 1/2?', 'a' => '2/3', 'b' => '3/6', 'c' => '4/6', 'd' => '5/8', 'dung' => 'B', 'giai_thich' => '3/6 = 1/2 (chia cả tử và mẫu cho 3)'),
            array('noi_dung' => 'So sánh: 2/5 ... 3/5', 'a' => '>', 'b' => '<', 'c' => '=', 'd' => 'Không so sánh được', 'dung' => 'B', 'giai_thich' => 'Cùng mẫu số, tử số nào lớn hơn thì phân số đó lớn hơn. 2 < 3 nên 2/5 < 3/5'),
            array('noi_dung' => 'Phân số 6/8 rút gọn được phân số nào?', 'a' => '2/4', 'b' => '3/4', 'c' => '1/2', 'd' => '3/8', 'dung' => 'B', 'giai_thich' => '6/8 = 6:2 / 8:2 = 3/4'),
            array('noi_dung' => 'So sánh: 3/4 ... 2/3', 'a' => '>', 'b' => '<', 'c' => '=', 'd' => 'Không so sánh được', 'dung' => 'A', 'giai_thich' => 'Quy đồng: 3/4 = 9/12, 2/3 = 8/12. Vì 9 > 8 nên 3/4 > 2/3'),
            array('noi_dung' => 'Phân số nào sau đây bằng 1?', 'a' => '3/4', 'b' => '5/5', 'c' => '6/7', 'd' => '8/9', 'dung' => 'B', 'giai_thich' => 'Phân số có tử số bằng mẫu số thì bằng 1. 5/5 = 1'),
            array('noi_dung' => 'Quy đồng mẫu số 1/2 và 1/3, mẫu số chung nhỏ nhất là:', 'a' => '5', 'b' => '6', 'c' => '12', 'd' => '3', 'dung' => 'B', 'giai_thich' => 'BCNN của 2 và 3 là 6'),
            array('noi_dung' => 'Phân số nào sau đây nhỏ hơn 1/2?', 'a' => '3/5', 'b' => '4/7', 'c' => '2/5', 'd' => '5/9', 'dung' => 'C', 'giai_thich' => '1/2 = 5/10, 2/5 = 4/10. Vì 4 < 5 nên 2/5 < 1/2'),
            array('noi_dung' => 'Viết phân số: "Ba phần năm" là:', 'a' => '5/3', 'b' => '3/5', 'c' => '3/15', 'd' => '15/3', 'dung' => 'B', 'giai_thich' => 'Ba phần năm = 3/5')
        )
    ),

    // ĐỀ 6: Phép cộng trừ phân số
    array(
        'ten_de' => 'Phép cộng trừ phân số',
        'mo_ta' => 'Luyện tập cộng trừ phân số cùng mẫu và khác mẫu',
        'cau_hoi' => array(
            array('noi_dung' => '2/7 + 3/7 = ?', 'a' => '5/7', 'b' => '5/14', 'c' => '6/7', 'd' => '1/7', 'dung' => 'A', 'giai_thich' => 'Cùng mẫu: 2/7 + 3/7 = (2+3)/7 = 5/7'),
            array('noi_dung' => '5/8 - 2/8 = ?', 'a' => '3/8', 'b' => '3/16', 'c' => '7/8', 'd' => '3/0', 'dung' => 'A', 'giai_thich' => 'Cùng mẫu: 5/8 - 2/8 = (5-2)/8 = 3/8'),
            array('noi_dung' => '1/2 + 1/4 = ?', 'a' => '2/6', 'b' => '3/4', 'c' => '1/3', 'd' => '2/4', 'dung' => 'B', 'giai_thich' => '1/2 = 2/4. Vậy 2/4 + 1/4 = 3/4'),
            array('noi_dung' => '3/4 - 1/2 = ?', 'a' => '2/2', 'b' => '1/4', 'c' => '1/2', 'd' => '2/4', 'dung' => 'B', 'giai_thich' => '1/2 = 2/4. Vậy 3/4 - 2/4 = 1/4'),
            array('noi_dung' => '1/3 + 1/6 = ?', 'a' => '2/9', 'b' => '1/2', 'c' => '2/6', 'd' => '3/6', 'dung' => 'B', 'giai_thich' => '1/3 = 2/6. Vậy 2/6 + 1/6 = 3/6 = 1/2'),
            array('noi_dung' => '5/6 - 1/3 = ?', 'a' => '4/3', 'b' => '1/2', 'c' => '2/3', 'd' => '4/6', 'dung' => 'B', 'giai_thich' => '1/3 = 2/6. Vậy 5/6 - 2/6 = 3/6 = 1/2'),
            array('noi_dung' => '2/5 + 1/5 + 1/5 = ?', 'a' => '3/5', 'b' => '4/5', 'c' => '4/15', 'd' => '1', 'dung' => 'B', 'giai_thich' => '2/5 + 1/5 + 1/5 = (2+1+1)/5 = 4/5'),
            array('noi_dung' => '1 - 3/8 = ?', 'a' => '5/8', 'b' => '4/8', 'c' => '3/8', 'd' => '2/8', 'dung' => 'A', 'giai_thich' => '1 = 8/8. Vậy 8/8 - 3/8 = 5/8'),
            array('noi_dung' => '1/4 + 2/4 + 1/4 = ?', 'a' => '3/4', 'b' => '1', 'c' => '4/12', 'd' => '4/4', 'dung' => 'B', 'giai_thich' => '1/4 + 2/4 + 1/4 = 4/4 = 1'),
            array('noi_dung' => '7/10 - 2/5 = ?', 'a' => '5/5', 'b' => '3/10', 'c' => '5/10', 'd' => '1/2', 'dung' => 'B', 'giai_thich' => '2/5 = 4/10. Vậy 7/10 - 4/10 = 3/10')
        )
    ),

    // ĐỀ 7: Diện tích hình chữ nhật, hình vuông
    array(
        'ten_de' => 'Diện tích hình chữ nhật, hình vuông',
        'mo_ta' => 'Luyện tập tính diện tích hình chữ nhật và hình vuông',
        'cau_hoi' => array(
            array('noi_dung' => 'Hình vuông có cạnh 6cm. Diện tích hình vuông là:', 'a' => '24 cm²', 'b' => '36 cm²', 'c' => '12 cm²', 'd' => '30 cm²', 'dung' => 'B', 'giai_thich' => 'Diện tích hình vuông = cạnh x cạnh = 6 x 6 = 36 cm²'),
            array('noi_dung' => 'Hình chữ nhật có chiều dài 8cm, chiều rộng 5cm. Diện tích là:', 'a' => '13 cm²', 'b' => '26 cm²', 'c' => '40 cm²', 'd' => '35 cm²', 'dung' => 'C', 'giai_thich' => 'Diện tích HCN = dài x rộng = 8 x 5 = 40 cm²'),
            array('noi_dung' => 'Hình vuông có diện tích 49 cm². Cạnh hình vuông là:', 'a' => '6 cm', 'b' => '7 cm', 'c' => '8 cm', 'd' => '9 cm', 'dung' => 'B', 'giai_thich' => 'Cạnh = √49 = 7 cm (vì 7 x 7 = 49)'),
            array('noi_dung' => 'Một mảnh vườn hình chữ nhật có chiều dài 25m, chiều rộng 12m. Diện tích mảnh vườn là:', 'a' => '74 m²', 'b' => '300 m²', 'c' => '37 m²', 'd' => '250 m²', 'dung' => 'B', 'giai_thich' => 'Diện tích = 25 x 12 = 300 m²'),
            array('noi_dung' => 'Hình vuông có chu vi 32cm. Diện tích hình vuông là:', 'a' => '64 cm²', 'b' => '128 cm²', 'c' => '256 cm²', 'd' => '48 cm²', 'dung' => 'A', 'giai_thich' => 'Cạnh = 32 : 4 = 8 cm. Diện tích = 8 x 8 = 64 cm²'),
            array('noi_dung' => 'Hình chữ nhật có diện tích 72 cm², chiều dài 9cm. Chiều rộng là:', 'a' => '6 cm', 'b' => '7 cm', 'c' => '8 cm', 'd' => '9 cm', 'dung' => 'C', 'giai_thich' => 'Chiều rộng = 72 : 9 = 8 cm'),
            array('noi_dung' => '1 m² = ? cm²', 'a' => '100', 'b' => '1 000', 'c' => '10 000', 'd' => '100 000', 'dung' => 'C', 'giai_thich' => '1 m² = 100 cm x 100 cm = 10 000 cm²'),
            array('noi_dung' => 'Một sân trường hình vuông cạnh 40m. Diện tích sân trường là:', 'a' => '160 m²', 'b' => '1 600 m²', 'c' => '800 m²', 'd' => '400 m²', 'dung' => 'B', 'giai_thich' => 'Diện tích = 40 x 40 = 1 600 m²'),
            array('noi_dung' => 'Hình chữ nhật có chiều dài 15cm, chiều rộng bằng 2/3 chiều dài. Diện tích là:', 'a' => '100 cm²', 'b' => '120 cm²', 'c' => '150 cm²', 'd' => '180 cm²', 'dung' => 'C', 'giai_thich' => 'Rộng = 15 x 2/3 = 10 cm. Diện tích = 15 x 10 = 150 cm²'),
            array('noi_dung' => '50 000 cm² = ? m²', 'a' => '5 m²', 'b' => '50 m²', 'c' => '500 m²', 'd' => '0,5 m²', 'dung' => 'A', 'giai_thich' => '50 000 cm² = 50 000 : 10 000 = 5 m²')
        )
    ),

    // ĐỀ 8: Góc nhọn, góc vuông, góc tù, góc bẹt
    array(
        'ten_de' => 'Góc nhọn, góc vuông, góc tù, góc bẹt',
        'mo_ta' => 'Luyện tập nhận biết và đo các loại góc',
        'cau_hoi' => array(
            array('noi_dung' => 'Góc vuông có số đo là:', 'a' => '45°', 'b' => '90°', 'c' => '180°', 'd' => '360°', 'dung' => 'B', 'giai_thich' => 'Góc vuông có số đo 90°'),
            array('noi_dung' => 'Góc có số đo 60° là góc gì?', 'a' => 'Góc vuông', 'b' => 'Góc nhọn', 'c' => 'Góc tù', 'd' => 'Góc bẹt', 'dung' => 'B', 'giai_thich' => 'Góc nhọn có số đo lớn hơn 0° và nhỏ hơn 90°'),
            array('noi_dung' => 'Góc có số đo 120° là góc gì?', 'a' => 'Góc vuông', 'b' => 'Góc nhọn', 'c' => 'Góc tù', 'd' => 'Góc bẹt', 'dung' => 'C', 'giai_thich' => 'Góc tù có số đo lớn hơn 90° và nhỏ hơn 180°'),
            array('noi_dung' => 'Góc bẹt có số đo là:', 'a' => '90°', 'b' => '120°', 'c' => '180°', 'd' => '360°', 'dung' => 'C', 'giai_thich' => 'Góc bẹt có số đo 180°'),
            array('noi_dung' => 'Góc có số đo 35° là góc gì?', 'a' => 'Góc vuông', 'b' => 'Góc nhọn', 'c' => 'Góc tù', 'd' => 'Góc bẹt', 'dung' => 'B', 'giai_thich' => '35° < 90° nên là góc nhọn'),
            array('noi_dung' => 'Góc có số đo 150° là góc gì?', 'a' => 'Góc vuông', 'b' => 'Góc nhọn', 'c' => 'Góc tù', 'd' => 'Góc bẹt', 'dung' => 'C', 'giai_thich' => '90° < 150° < 180° nên là góc tù'),
            array('noi_dung' => 'Trong hình chữ nhật có bao nhiêu góc vuông?', 'a' => '1', 'b' => '2', 'c' => '3', 'd' => '4', 'dung' => 'D', 'giai_thich' => 'Hình chữ nhật có 4 góc vuông'),
            array('noi_dung' => 'Hai góc nhọn cộng lại có thể bằng:', 'a' => 'Góc nhọn', 'b' => 'Góc vuông', 'c' => 'Góc tù', 'd' => 'Tất cả các đáp án trên', 'dung' => 'D', 'giai_thich' => 'Ví dụ: 30°+40°=70° (nhọn), 45°+45°=90° (vuông), 60°+70°=130° (tù)'),
            array('noi_dung' => 'Góc nào sau đây là góc tù?', 'a' => '75°', 'b' => '90°', 'c' => '100°', 'd' => '180°', 'dung' => 'C', 'giai_thich' => '100° nằm giữa 90° và 180° nên là góc tù'),
            array('noi_dung' => 'Kim giờ và kim phút tạo thành góc vuông khi đồng hồ chỉ mấy giờ?', 'a' => '12 giờ', 'b' => '3 giờ', 'c' => '6 giờ', 'd' => '9 giờ', 'dung' => 'B', 'giai_thich' => 'Lúc 3 giờ (hoặc 9 giờ), kim giờ và kim phút tạo góc vuông 90°')
        )
    ),

    // ĐỀ 9: Nhân chia với số có hai, ba chữ số
    array(
        'ten_de' => 'Nhân chia với số có hai, ba chữ số',
        'mo_ta' => 'Luyện tập nhân chia với số có hai, ba chữ số',
        'cau_hoi' => array(
            array('noi_dung' => '234 x 12 = ?', 'a' => '2 708', 'b' => '2 808', 'c' => '2 908', 'd' => '2 608', 'dung' => 'B', 'giai_thich' => '234 x 12 = 234 x 10 + 234 x 2 = 2340 + 468 = 2 808'),
            array('noi_dung' => '576 : 12 = ?', 'a' => '46', 'b' => '47', 'c' => '48', 'd' => '49', 'dung' => 'C', 'giai_thich' => '576 : 12 = 48'),
            array('noi_dung' => '125 x 24 = ?', 'a' => '2 500', 'b' => '2 800', 'c' => '3 000', 'd' => '3 200', 'dung' => 'C', 'giai_thich' => '125 x 24 = 125 x 8 x 3 = 1000 x 3 = 3 000'),
            array('noi_dung' => '1 456 : 16 = ?', 'a' => '89', 'b' => '90', 'c' => '91', 'd' => '92', 'dung' => 'C', 'giai_thich' => '1 456 : 16 = 91'),
            array('noi_dung' => '315 x 25 = ?', 'a' => '7 575', 'b' => '7 675', 'c' => '7 775', 'd' => '7 875', 'dung' => 'D', 'giai_thich' => '315 x 25 = 315 x 100 : 4 = 31500 : 4 = 7 875'),
            array('noi_dung' => '2 850 : 15 = ?', 'a' => '180', 'b' => '185', 'c' => '190', 'd' => '195', 'dung' => 'C', 'giai_thich' => '2 850 : 15 = 190'),
            array('noi_dung' => '108 x 36 = ?', 'a' => '3 688', 'b' => '3 788', 'c' => '3 888', 'd' => '3 988', 'dung' => 'C', 'giai_thich' => '108 x 36 = 3 888'),
            array('noi_dung' => '4 368 : 21 = ?', 'a' => '204', 'b' => '206', 'c' => '208', 'd' => '210', 'dung' => 'C', 'giai_thich' => '4 368 : 21 = 208'),
            array('noi_dung' => '256 x 15 = ?', 'a' => '3 740', 'b' => '3 840', 'c' => '3 940', 'd' => '4 040', 'dung' => 'B', 'giai_thich' => '256 x 15 = 256 x 10 + 256 x 5 = 2560 + 1280 = 3 840'),
            array('noi_dung' => '5 184 : 24 = ?', 'a' => '214', 'b' => '215', 'c' => '216', 'd' => '217', 'dung' => 'C', 'giai_thich' => '5 184 : 24 = 216')
        )
    ),

    // ĐỀ 10: Giải toán có lời văn lớp 4
    array(
        'ten_de' => 'Giải toán có lời văn lớp 4',
        'mo_ta' => 'Luyện tập giải các bài toán có lời văn nâng cao',
        'cau_hoi' => array(
            array('noi_dung' => 'Một cửa hàng có 2 450 kg gạo, đã bán 3/5 số gạo. Hỏi cửa hàng còn lại bao nhiêu kg gạo?', 'a' => '980 kg', 'b' => '1 470 kg', 'c' => '1 000 kg', 'd' => '1 200 kg', 'dung' => 'A', 'giai_thich' => 'Số gạo đã bán: 2 450 x 3/5 = 1 470 kg. Còn lại: 2 450 - 1 470 = 980 kg'),
            array('noi_dung' => 'Một mảnh vườn hình chữ nhật có chiều dài 45m, chiều rộng bằng 2/3 chiều dài. Tính diện tích mảnh vườn?', 'a' => '1 250 m²', 'b' => '1 350 m²', 'c' => '1 450 m²', 'd' => '1 550 m²', 'dung' => 'B', 'giai_thich' => 'Rộng = 45 x 2/3 = 30m. Diện tích = 45 x 30 = 1 350 m²'),
            array('noi_dung' => 'Một ô tô đi được 156 km trong 3 giờ. Hỏi với vận tốc đó, trong 5 giờ ô tô đi được bao nhiêu km?', 'a' => '240 km', 'b' => '260 km', 'c' => '280 km', 'd' => '300 km', 'dung' => 'B', 'giai_thich' => 'Vận tốc = 156 : 3 = 52 km/h. Quãng đường = 52 x 5 = 260 km'),
            array('noi_dung' => 'Hai kho có 5 600 kg thóc. Kho thứ nhất có nhiều hơn kho thứ hai 400 kg. Hỏi kho thứ nhất có bao nhiêu kg thóc?', 'a' => '2 600 kg', 'b' => '2 800 kg', 'c' => '3 000 kg', 'd' => '3 200 kg', 'dung' => 'C', 'giai_thich' => 'Hai lần kho 2 = 5 600 - 400 = 5 200 kg. Kho 2 = 2 600 kg. Kho 1 = 2 600 + 400 = 3 000 kg'),
            array('noi_dung' => 'Một người thợ làm 8 sản phẩm trong 2 giờ. Hỏi trong 5 giờ người thợ đó làm được bao nhiêu sản phẩm?', 'a' => '16', 'b' => '18', 'c' => '20', 'd' => '22', 'dung' => 'C', 'giai_thich' => 'Mỗi giờ làm: 8 : 2 = 4 sản phẩm. Trong 5 giờ: 4 x 5 = 20 sản phẩm'),
            array('noi_dung' => 'Chu vi hình chữ nhật là 84cm, chiều dài hơn chiều rộng 12cm. Tính diện tích hình chữ nhật?', 'a' => '405 cm²', 'b' => '432 cm²', 'c' => '420 cm²', 'd' => '450 cm²', 'dung' => 'A', 'giai_thich' => 'Nửa chu vi = 42cm. Rộng = (42-12):2 = 15cm. Dài = 27cm. S = 27 x 15 = 405 cm²'),
            array('noi_dung' => 'Một lớp có 36 học sinh, số học sinh nữ bằng 4/5 số học sinh nam. Hỏi lớp có bao nhiêu học sinh nam?', 'a' => '16', 'b' => '18', 'c' => '20', 'd' => '22', 'dung' => 'C', 'giai_thich' => 'Nữ = 4 phần, Nam = 5 phần. Tổng = 9 phần = 36 HS. Nam = 5 x 4 = 20 HS'),
            array('noi_dung' => 'Mua 15 quyển vở hết 67 500 đồng. Hỏi mua 24 quyển vở như thế hết bao nhiêu tiền?', 'a' => '98 000 đồng', 'b' => '102 000 đồng', 'c' => '108 000 đồng', 'd' => '112 000 đồng', 'dung' => 'C', 'giai_thich' => 'Giá 1 quyển = 67 500 : 15 = 4 500 đồng. 24 quyển = 4 500 x 24 = 108 000 đồng'),
            array('noi_dung' => 'Một hình vuông có chu vi bằng chu vi hình chữ nhật có chiều dài 18cm, chiều rộng 12cm. Tính diện tích hình vuông?', 'a' => '196 cm²', 'b' => '225 cm²', 'c' => '256 cm²', 'd' => '289 cm²', 'dung' => 'B', 'giai_thich' => 'Chu vi HCN = (18+12) x 2 = 60cm. Cạnh HV = 60 : 4 = 15cm. S = 15 x 15 = 225 cm²'),
            array('noi_dung' => 'Hiện nay tổng số tuổi của hai mẹ con là 52 tuổi. Biết tuổi con bằng 3/10 tuổi mẹ. Hỏi mẹ bao nhiêu tuổi?', 'a' => '36 tuổi', 'b' => '38 tuổi', 'c' => '40 tuổi', 'd' => '42 tuổi', 'dung' => 'C', 'giai_thich' => 'Mẹ = 10 phần, Con = 3 phần. Tổng = 13 phần = 52 tuổi. Mẹ = 10 x 4 = 40 tuổi')
        )
    )
);

// ============================================================
// THỰC HIỆN INSERT VÀO DATABASE
// ============================================================

$mon_hoc_id = 1; // Toán
$lop3_id = 3;
$lop4_id = 4;

// Xóa dữ liệu cũ (theo thứ tự đúng do khóa ngoại)
echo "<h2>Bước 1: Xóa dữ liệu cũ...</h2>";
$conn->exec("DELETE FROM chi_tiet_bai_lam");
$conn->exec("DELETE FROM bai_lam");
$conn->exec("DELETE FROM cau_hoi");
$conn->exec("DELETE FROM de_thi");
echo "<p class='success'>Đã xóa dữ liệu cũ.</p>";

// Insert đề thi lớp 3
echo "<h2>Bước 2: Tạo 10 đề thi Toán lớp 3...</h2>";
$de_count = 0;
$cau_count = 0;

foreach ($exams_lop3 as $exam) {
    // Insert đề thi
    $stmt = $conn->prepare("INSERT INTO de_thi (ten_de, mo_ta, mon_hoc_id, lop_id, so_cau, thoi_gian_cau, trang_thai, created_at) VALUES (?, ?, ?, ?, 10, 20, 1, NOW())");
    $stmt->execute([$exam['ten_de'], $exam['mo_ta'], $mon_hoc_id, $lop3_id]);
    $de_thi_id = $conn->lastInsertId();
    $de_count++;

    echo "<div class='exam-box'>";
    echo "<strong>Đề $de_count:</strong> " . htmlspecialchars($exam['ten_de'], ENT_QUOTES, 'UTF-8');

    // Insert câu hỏi
    $thu_tu = 0;
    foreach ($exam['cau_hoi'] as $cau) {
        $thu_tu++;
        $stmt = $conn->prepare("INSERT INTO cau_hoi (de_thi_id, noi_dung, dap_an_a, dap_an_b, dap_an_c, dap_an_d, dap_an_dung, giai_thich, thu_tu, trang_thai, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())");
        $stmt->execute([
            $de_thi_id,
            $cau['noi_dung'],
            $cau['a'],
            $cau['b'],
            $cau['c'],
            $cau['d'],
            $cau['dung'],
            $cau['giai_thich'],
            $thu_tu
        ]);
        $cau_count++;
    }
    echo " - <span class='success'>10 câu hỏi</span></div>";
}

echo "<p class='success'>Đã tạo $de_count đề thi lớp 3 với $cau_count câu hỏi.</p>";

// Insert đề thi lớp 4
echo "<h2>Bước 3: Tạo 10 đề thi Toán lớp 4...</h2>";
$de_count = 0;
$cau_count = 0;

foreach ($exams_lop4 as $exam) {
    // Insert đề thi
    $stmt = $conn->prepare("INSERT INTO de_thi (ten_de, mo_ta, mon_hoc_id, lop_id, so_cau, thoi_gian_cau, trang_thai, created_at) VALUES (?, ?, ?, ?, 10, 25, 1, NOW())");
    $stmt->execute([$exam['ten_de'], $exam['mo_ta'], $mon_hoc_id, $lop4_id]);
    $de_thi_id = $conn->lastInsertId();
    $de_count++;

    echo "<div class='exam-box'>";
    echo "<strong>Đề $de_count:</strong> " . htmlspecialchars($exam['ten_de'], ENT_QUOTES, 'UTF-8');

    // Insert câu hỏi
    $thu_tu = 0;
    foreach ($exam['cau_hoi'] as $cau) {
        $thu_tu++;
        $stmt = $conn->prepare("INSERT INTO cau_hoi (de_thi_id, noi_dung, dap_an_a, dap_an_b, dap_an_c, dap_an_d, dap_an_dung, giai_thich, thu_tu, trang_thai, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())");
        $stmt->execute([
            $de_thi_id,
            $cau['noi_dung'],
            $cau['a'],
            $cau['b'],
            $cau['c'],
            $cau['d'],
            $cau['dung'],
            $cau['giai_thich'],
            $thu_tu
        ]);
        $cau_count++;
    }
    echo " - <span class='success'>10 câu hỏi</span></div>";
}

echo "<p class='success'>Đã tạo $de_count đề thi lớp 4 với $cau_count câu hỏi.</p>";

// Thống kê
echo "<h2>Bước 4: Thống kê kết quả...</h2>";
$stmt = $conn->query("SELECT lh.ten_lop, COUNT(dt.id) as so_de, SUM(dt.so_cau) as tong_cau
                      FROM de_thi dt
                      JOIN lop_hoc lh ON dt.lop_id = lh.id
                      GROUP BY dt.lop_id");
$stats = $stmt->fetchAll();

echo "<table>";
echo "<tr><th>Lớp</th><th>Số đề thi</th><th>Tổng số câu hỏi</th></tr>";
foreach ($stats as $row) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['ten_lop'], ENT_QUOTES, 'UTF-8') . "</td>";
    echo "<td>" . $row['so_de'] . "</td>";
    echo "<td>" . $row['tong_cau'] . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h2 class='success'>Hoàn thành! Đã tạo 20 đề thi với 200 câu hỏi.</h2>";
echo "<p><a href='index.php'>=> Về trang chủ</a></p>";
echo "<p><a href='student/dashboard.php'>=> Dashboard học sinh (cần đăng nhập)</a></p>";
echo "</body></html>";
?>
