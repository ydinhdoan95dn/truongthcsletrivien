<?php
/**
 * Trang test Document Viewer
 * Kiểm tra xem file Google Drive có nhúng được không
 */
require_once 'includes/config.php';

$conn = getDBConnection();
$stmt = $conn->query("SELECT * FROM tai_lieu WHERE google_drive_id IS NOT NULL ORDER BY thu_tu");
$documents = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Document Viewer</title>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Quicksand', sans-serif;
            background: #f0f4f8;
            padding: 20px;
        }
        h1 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }
        .doc-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        .doc-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .doc-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
        }
        .doc-info {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 15px;
        }
        .doc-btn {
            display: inline-block;
            padding: 10px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-family: inherit;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            margin-right: 10px;
            margin-bottom: 10px;
        }
        .doc-btn:hover {
            transform: scale(1.02);
        }
        .doc-btn.preview {
            background: linear-gradient(135deg, #4ECDC4 0%, #44A8B3 100%);
        }
        .viewer-container {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.9);
            z-index: 1000;
            flex-direction: column;
        }
        .viewer-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 25px;
            background: #1a1a2e;
            color: white;
        }
        .viewer-title {
            font-weight: 700;
        }
        .close-btn {
            padding: 10px 20px;
            background: #EF4444;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 700;
        }
        .viewer-body {
            flex: 1;
            padding: 20px;
        }
        .viewer-body iframe {
            width: 100%;
            height: 100%;
            border: none;
            border-radius: 12px;
            background: white;
        }
        .status {
            padding: 10px;
            text-align: center;
            font-weight: 600;
        }
        .status.success { background: #10B981; color: white; }
        .status.error { background: #EF4444; color: white; }
    </style>
</head>
<body>
    <h1>Test Document Viewer - Google Drive</h1>

    <div class="status success">
        Đã tìm thấy <?php echo count($documents); ?> tài liệu từ Google Drive
    </div>

    <div class="doc-list">
        <?php foreach ($documents as $doc): ?>
            <?php
            $icon = '';
            switch ($doc['loai_file']) {
                case 'pdf': $icon = '📄'; break;
                case 'word': $icon = '📝'; break;
                case 'ppt': $icon = '📊'; break;
                default: $icon = '📁';
            }
            ?>
            <div class="doc-card">
                <div class="doc-title"><?php echo $icon; ?> <?php echo htmlspecialchars($doc['tieu_de']); ?></div>
                <div class="doc-info">
                    <strong>Loại:</strong> <?php echo strtoupper($doc['loai_file']); ?><br>
                    <strong>Drive ID:</strong> <?php echo $doc['google_drive_id']; ?>
                </div>
                <button class="doc-btn preview" onclick="openViewer('<?php echo $doc['google_drive_id']; ?>', '<?php echo addslashes($doc['tieu_de']); ?>')">
                    👁️ Xem trực tiếp
                </button>
                <a class="doc-btn" href="https://drive.google.com/file/d/<?php echo $doc['google_drive_id']; ?>/view" target="_blank">
                    🔗 Mở Drive
                </a>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="viewer-container" id="viewerContainer">
        <div class="viewer-header">
            <div class="viewer-title" id="viewerTitle">Tài liệu</div>
            <button class="close-btn" onclick="closeViewer()">✕ Đóng</button>
        </div>
        <div class="viewer-body">
            <iframe id="viewerIframe" src=""></iframe>
        </div>
    </div>

    <script>
        function openViewer(driveId, title) {
            var container = document.getElementById('viewerContainer');
            var iframe = document.getElementById('viewerIframe');
            var titleEl = document.getElementById('viewerTitle');

            // URL preview của Google Drive
            var previewUrl = 'https://drive.google.com/file/d/' + driveId + '/preview';

            titleEl.textContent = title;
            iframe.src = previewUrl;
            container.style.display = 'flex';
        }

        function closeViewer() {
            var container = document.getElementById('viewerContainer');
            var iframe = document.getElementById('viewerIframe');

            container.style.display = 'none';
            iframe.src = '';
        }

        // Đóng khi nhấn ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeViewer();
        });
    </script>
</body>
</html>
