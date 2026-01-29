<?php
// Debug script untuk cPanel
// Akses: https://demo-sapahc.ptsi.co.id/debug-cpanel.php
// HAPUS FILE INI SETELAH SELESAI DEBUG!

echo "<h1>🔍 Debug cPanel - Talent PTSI</h1>";
echo "<pre>";

// 1. Check PHP Version
echo "=================================\n";
echo "PHP VERSION\n";
echo "=================================\n";
echo "PHP Version: " . PHP_VERSION . "\n\n";

// 2. Check Laravel Path
echo "=================================\n";
echo "FILE STRUCTURE\n";
echo "=================================\n";
$basePath = __DIR__ . '/../talent-ptsi';
echo "Base Path: $basePath\n";
echo "Vendor exists: " . (file_exists("$basePath/vendor") ? "✅ YES" : "❌ NO") . "\n";
echo "Vendor/autoload.php: " . (file_exists("$basePath/vendor/autoload.php") ? "✅ YES" : "❌ NO") . "\n";
echo ".env exists: " . (file_exists("$basePath/.env") ? "✅ YES" : "❌ NO") . "\n";
echo "bootstrap/app.php: " . (file_exists("$basePath/bootstrap/app.php") ? "✅ YES" : "❌ NO") . "\n";
echo "public/build: " . (file_exists("$basePath/public/build") ? "✅ YES" : "❌ NO") . "\n\n";

// 3. Check Git Branch
echo "=================================\n";
echo "GIT INFO\n";
echo "=================================\n";
if (file_exists("$basePath/.git/HEAD")) {
    $head = trim(file_get_contents("$basePath/.git/HEAD"));
    echo "Current branch: $head\n\n";
} else {
    echo "❌ .git folder not found\n\n";
}

// 4. Check Permissions
echo "=================================\n";
echo "PERMISSIONS\n";
echo "=================================\n";
echo "storage/ readable: " . (is_readable("$basePath/storage") ? "✅ YES" : "❌ NO") . "\n";
echo "storage/ writable: " . (is_writable("$basePath/storage") ? "✅ YES" : "❌ NO") . "\n";
echo "bootstrap/cache readable: " . (is_readable("$basePath/bootstrap/cache") ? "✅ YES" : "❌ NO") . "\n";
echo "bootstrap/cache writable: " . (is_writable("$basePath/bootstrap/cache") ? "✅ YES" : "❌ NO") . "\n\n";

// 5. Try Loading Laravel
echo "=================================\n";
echo "LOADING LARAVEL\n";
echo "=================================\n";
try {
    if (!file_exists("$basePath/vendor/autoload.php")) {
        throw new Exception("❌ vendor/autoload.php NOT FOUND!\n   Path: $basePath/vendor/autoload.php\n   SOLUSI: Pull branch production di cPanel Git Version Control!");
    }
    
    require "$basePath/vendor/autoload.php";
    echo "✅ Autoloader loaded successfully\n";
    
    if (!file_exists("$basePath/bootstrap/app.php")) {
        throw new Exception("❌ bootstrap/app.php NOT FOUND!");
    }
    
    $app = require_once "$basePath/bootstrap/app.php";
    echo "✅ Laravel app bootstrapped successfully\n";
    
    if (!file_exists("$basePath/.env")) {
        throw new Exception("❌ .env file NOT FOUND!\n   SOLUSI: Copy .env dari backup (lihat DEPLOYMENT-GUIDE.md Step 4)");
    }
    echo "✅ .env file exists\n";
    
    echo "\n✅✅✅ ALL CHECKS PASSED! ✅✅✅\n";
    echo "Laravel siap digunakan.\n\n";
    echo "Next: Jalankan setup-cpanel.php\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n\n";
}

// 6. Check recent Laravel logs
echo "=================================\n";
echo "RECENT LARAVEL ERRORS\n";
echo "=================================\n";
$logFile = "$basePath/storage/logs/laravel.log";
if (file_exists($logFile)) {
    $logs = file_get_contents($logFile);
    $lines = explode("\n", $logs);
    $lastLines = array_slice($lines, -30); // Last 30 lines
    echo implode("\n", $lastLines);
} else {
    echo "No log file found yet.\n";
}

echo "</pre>";

echo "<h2>⚠️ PENTING: HAPUS FILE INI SETELAH DEBUG!</h2>";
echo "<p>File ini expose informasi sensitif. Delete via File Manager setelah selesai.</p>";
?>
