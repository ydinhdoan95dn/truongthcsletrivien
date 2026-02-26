# ============================================================
# PowerShell Script: Tự động thêm Virtual Host
# ============================================================
# CÁCH CHẠY:
# 1. Click chuột phải vào file này
# 2. Chọn "Run with PowerShell"
# 3. Chọn "Yes" khi hỏi quyền Administrator
#
# HOẶC:
# 1. Mở PowerShell với quyền Administrator
# 2. Chạy: .\add-virtualhost.ps1
# ============================================================

Write-Host "============================================================" -ForegroundColor Cyan
Write-Host "  Tự động thêm Virtual Host cho truongbuithixuan.local" -ForegroundColor Cyan
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host ""

# Kiểm tra quyền Administrator
$isAdmin = ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)

if (-not $isAdmin) {
    Write-Host "❌ LỖI: Script này cần quyền Administrator!" -ForegroundColor Red
    Write-Host ""
    Write-Host "Vui lòng:" -ForegroundColor Yellow
    Write-Host "1. Click chuột phải vào PowerShell" -ForegroundColor Yellow
    Write-Host "2. Chọn 'Run as Administrator'" -ForegroundColor Yellow
    Write-Host "3. Chạy lại script này" -ForegroundColor Yellow
    Write-Host ""
    Pause
    Exit
}

Write-Host "✅ Đang chạy với quyền Administrator" -ForegroundColor Green
Write-Host ""

# Biến cấu hình
$domain = "truongbuithixuan.local"
$wwwDomain = "www.truongbuithixuan.local"
$projectPath = "C:/xampp/htdocs/truongbuithixuan"
$hostsFile = "C:\Windows\System32\drivers\etc\hosts"
$vhostsFile = "C:\xampp\apache\conf\extra\httpd-vhosts.conf"
$httpdConfFile = "C:\xampp\apache\conf\httpd.conf"

# ============================================================
# BƯỚC 1: Backup files
# ============================================================
Write-Host "📋 BƯỚC 1: Backup các file cấu hình..." -ForegroundColor Yellow

$backupFolder = "C:\xampp\htdocs\truongbuithixuan\config_backups"
if (-not (Test-Path $backupFolder)) {
    New-Item -ItemType Directory -Path $backupFolder | Out-Null
}

$timestamp = Get-Date -Format "yyyyMMdd_HHmmss"

# Backup hosts file
Copy-Item $hostsFile "$backupFolder\hosts_backup_$timestamp.txt"
Write-Host "  ✅ Đã backup: hosts file" -ForegroundColor Green

# Backup httpd-vhosts.conf
if (Test-Path $vhostsFile) {
    Copy-Item $vhostsFile "$backupFolder\httpd-vhosts_backup_$timestamp.conf"
    Write-Host "  ✅ Đã backup: httpd-vhosts.conf" -ForegroundColor Green
}

Write-Host ""

# ============================================================
# BƯỚC 2: Thêm domain vào hosts file
# ============================================================
Write-Host "📋 BƯỚC 2: Thêm domain vào hosts file..." -ForegroundColor Yellow

$hostsContent = Get-Content $hostsFile

# Kiểm tra đã tồn tại chưa
if ($hostsContent -match $domain) {
    Write-Host "  ⚠️  Domain đã tồn tại trong hosts file" -ForegroundColor Yellow
} else {
    $newHostsEntry = @"

# Virtual Hosts for truongbuithixuan
127.0.0.1    $domain
127.0.0.1    $wwwDomain
"@
    Add-Content -Path $hostsFile -Value $newHostsEntry
    Write-Host "  ✅ Đã thêm domain vào hosts file" -ForegroundColor Green
}

Write-Host ""

# ============================================================
# BƯỚC 3: Thêm Virtual Host vào httpd-vhosts.conf
# ============================================================
Write-Host "📋 BƯỚC 3: Thêm Virtual Host vào httpd-vhosts.conf..." -ForegroundColor Yellow

if (-not (Test-Path $vhostsFile)) {
    Write-Host "  ❌ Không tìm thấy file httpd-vhosts.conf" -ForegroundColor Red
    Write-Host "     Path: $vhostsFile" -ForegroundColor Red
    Pause
    Exit
}

$vhostsContent = Get-Content $vhostsFile -Raw

# Kiểm tra đã tồn tại chưa
if ($vhostsContent -match $domain) {
    Write-Host "  ⚠️  Virtual Host đã tồn tại" -ForegroundColor Yellow
} else {
    $newVirtualHost = @"


##
## Virtual Host: truongbuithixuan.local
## Added by script at $(Get-Date)
##
<VirtualHost *:80>
    ServerName $domain
    ServerAlias $wwwDomain
    DocumentRoot "$projectPath"

    <Directory "$projectPath">
        Options Indexes FollowSymLinks Includes ExecCGI
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog "logs/truongbuithixuan-error.log"
    CustomLog "logs/truongbuithixuan-access.log" common
</VirtualHost>
"@
    Add-Content -Path $vhostsFile -Value $newVirtualHost
    Write-Host "  ✅ Đã thêm Virtual Host" -ForegroundColor Green
}

Write-Host ""

# ============================================================
# BƯỚC 4: Kiểm tra httpd.conf
# ============================================================
Write-Host "📋 BƯỚC 4: Kiểm tra httpd.conf..." -ForegroundColor Yellow

$httpdContent = Get-Content $httpdConfFile

$vhostsIncludeLine = $httpdContent | Select-String -Pattern "Include conf/extra/httpd-vhosts.conf" | Select-Object -First 1

if ($vhostsIncludeLine -match "^\s*#") {
    Write-Host "  ⚠️  Virtual hosts bị comment trong httpd.conf!" -ForegroundColor Yellow
    Write-Host "  📝 Cần bật dòng: Include conf/extra/httpd-vhosts.conf" -ForegroundColor Yellow

    # Uncomment the line
    $httpdContent = $httpdContent -replace "^\s*#\s*Include conf/extra/httpd-vhosts.conf", "Include conf/extra/httpd-vhosts.conf"
    Set-Content -Path $httpdConfFile -Value $httpdContent
    Write-Host "  ✅ Đã bật Virtual Hosts trong httpd.conf" -ForegroundColor Green
} else {
    Write-Host "  ✅ Virtual Hosts đã được bật" -ForegroundColor Green
}

Write-Host ""

# ============================================================
# BƯỚC 5: Flush DNS Cache
# ============================================================
Write-Host "📋 BƯỚC 5: Flush DNS Cache..." -ForegroundColor Yellow

ipconfig /flushdns | Out-Null
Write-Host "  ✅ Đã xóa DNS cache" -ForegroundColor Green

Write-Host ""

# ============================================================
# HOÀN THÀNH
# ============================================================
Write-Host "============================================================" -ForegroundColor Green
Write-Host "  ✅ HOÀN THÀNH!" -ForegroundColor Green
Write-Host "============================================================" -ForegroundColor Green
Write-Host ""
Write-Host "📌 CÁC BƯỚC TIẾP THEO:" -ForegroundColor Cyan
Write-Host ""
Write-Host "1. MỞ XAMPP Control Panel" -ForegroundColor Yellow
Write-Host "2. STOP Apache" -ForegroundColor Yellow
Write-Host "3. START lại Apache" -ForegroundColor Yellow
Write-Host ""
Write-Host "4. Mở trình duyệt và truy cập:" -ForegroundColor Yellow
Write-Host "   👉 http://$domain" -ForegroundColor Cyan
Write-Host "   👉 http://$wwwDomain" -ForegroundColor Cyan
Write-Host ""
Write-Host "📁 File backup được lưu tại:" -ForegroundColor Yellow
Write-Host "   $backupFolder" -ForegroundColor Gray
Write-Host ""
Write-Host "============================================================" -ForegroundColor Green
Write-Host ""

Pause
