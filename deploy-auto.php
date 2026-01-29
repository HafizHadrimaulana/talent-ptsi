<?php
/**
 * Auto Deploy Script untuk cPanel
 * Akses: https://demo-sapahc.ptsi.co.id/deploy-auto.php?token=YOUR_SECRET_TOKEN
 * 
 * SETUP:
 * 1. Ganti SECRET_TOKEN di bawah dengan random string
 * 2. Upload file ini ke public_html/
 * 3. Akses: https://demo-sapahc.ptsi.co.id/deploy-auto.php?token=YOUR_SECRET_TOKEN
 * 
 * SECURITY: File ini protected dengan token. Jangan share URL ke orang lain!
 */

// ============================================
// KONFIGURASI - GANTI INI!
// ============================================
define('SECRET_TOKEN', 'talent-ptsi-deploy-2026'); // Ganti dengan random string!
define('BASE_PATH', '/home/demosapahcptsico');
define('APP_PATH', BASE_PATH . '/talent-ptsi');
define('PUBLIC_PATH', BASE_PATH . '/public_html');

// ============================================
// SECURITY CHECK
// ============================================
if (!isset($_GET['token']) || $_GET['token'] !== SECRET_TOKEN) {
    http_response_code(403);
    die('❌ Access Denied! Invalid token.');
}

// ============================================
// START DEPLOYMENT
// ============================================
header('Content-Type: text/plain; charset=utf-8');
echo "=================================\n";
echo "🚀 AUTO DEPLOY - TALENT PTSI\n";
echo "=================================\n";
echo "Started at: " . date('Y-m-d H:i:s') . "\n\n";

$errors = [];
$warnings = [];

// Function to run command
function runCommand($cmd, $description) {
    global $errors, $warnings;
    echo "📦 $description\n";
    echo "   Command: $cmd\n";
    
    $output = [];
    $returnVar = 0;
    exec("cd " . APP_PATH . " && $cmd 2>&1", $output, $returnVar);
    
    if ($returnVar === 0) {
        echo "   ✅ Success\n";
        if (!empty($output)) {
            echo "   Output: " . implode("\n           ", array_slice($output, -3)) . "\n";
        }
    } else {
        echo "   ⚠️ Warning (exit code: $returnVar)\n";
        if (!empty($output)) {
            echo "   Output: " . implode("\n           ", array_slice($output, -5)) . "\n";
        }
        $warnings[] = $description;
    }
    echo "\n";
    
    return $returnVar === 0;
}

// Function to copy directory recursively
function copyDirectory($src, $dst) {
    if (!file_exists($src)) {
        return false;
    }
    
    if (!file_exists($dst)) {
        mkdir($dst, 0755, true);
    }
    
    $dir = opendir($src);
    while (false !== ($file = readdir($dir))) {
        if (($file != '.') && ($file != '..')) {
            if (is_dir($src . '/' . $file)) {
                copyDirectory($src . '/' . $file, $dst . '/' . $file);
            } else {
                copy($src . '/' . $file, $dst . '/' . $file);
            }
        }
    }
    closedir($dir);
    return true;
}

// ============================================
// STEP 1: GIT PULL
// ============================================
echo "=================================\n";
echo "STEP 1: Git Pull\n";
echo "=================================\n";
runCommand('git pull origin production', 'Pulling latest from production branch');

// ============================================
// STEP 2: COPY BUILD ASSETS
// ============================================
echo "=================================\n";
echo "STEP 2: Copy Build Assets\n";
echo "=================================\n";
$srcBuild = APP_PATH . '/public/build';
$dstBuild = PUBLIC_PATH . '/build';

echo "📦 Copying build assets\n";
echo "   From: $srcBuild\n";
echo "   To: $dstBuild\n";

// Backup old build
if (file_exists($dstBuild)) {
    $backupBuild = PUBLIC_PATH . '/build-backup-' . date('YmdHis');
    rename($dstBuild, $backupBuild);
    echo "   📁 Old build backed up to: build-backup-" . date('YmdHis') . "\n";
}

// Copy new build
if (copyDirectory($srcBuild, $dstBuild)) {
    echo "   ✅ Build assets copied successfully\n";
} else {
    echo "   ❌ Failed to copy build assets\n";
    $errors[] = "Copy build assets failed";
}
echo "\n";

// ============================================
// STEP 3: COPY STATIC ASSETS (if needed)
// ============================================
echo "=================================\n";
echo "STEP 3: Copy Static Assets\n";
echo "=================================\n";

$staticAssets = [
    'images' => APP_PATH . '/public/images',
    'storage' => APP_PATH . '/public/storage',
];

foreach ($staticAssets as $name => $src) {
    if (file_exists($src)) {
        $dst = PUBLIC_PATH . '/' . $name;
        echo "📦 Copying $name\n";
        
        if (file_exists($dst)) {
            echo "   ℹ️  Already exists, skipping\n";
        } else {
            if (copyDirectory($src, $dst)) {
                echo "   ✅ $name copied\n";
            } else {
                echo "   ⚠️ Failed to copy $name\n";
                $warnings[] = "Copy $name failed";
            }
        }
    } else {
        echo "📦 $name not found, skipping\n";
    }
    echo "\n";
}

// ============================================
// STEP 4: RUN ARTISAN COMMANDS
// ============================================
echo "=================================\n";
echo "STEP 4: Laravel Artisan Commands\n";
echo "=================================\n";

// Check if we can run artisan
if (file_exists(APP_PATH . '/artisan')) {
    runCommand('php artisan migrate --force', 'Running database migrations');
    runCommand('php artisan config:cache', 'Caching configuration');
    runCommand('php artisan route:cache', 'Caching routes');
    runCommand('php artisan view:cache', 'Caching views');
    runCommand('php artisan optimize', 'Optimizing application');
} else {
    echo "⚠️  Artisan not found, skipping Laravel commands\n\n";
    $warnings[] = "Artisan commands skipped";
}

// ============================================
// STEP 5: SET PERMISSIONS
// ============================================
echo "=================================\n";
echo "STEP 5: Set Permissions\n";
echo "=================================\n";

$permissionDirs = [
    APP_PATH . '/storage' => 0775,
    APP_PATH . '/bootstrap/cache' => 0775,
];

foreach ($permissionDirs as $dir => $perm) {
    if (file_exists($dir)) {
        if (chmod($dir, $perm)) {
            echo "✅ Set permissions for " . basename($dir) . "\n";
        } else {
            echo "⚠️  Could not set permissions for " . basename($dir) . "\n";
            $warnings[] = "Set permissions for " . basename($dir);
        }
    }
}
echo "\n";

// ============================================
// DEPLOYMENT SUMMARY
// ============================================
echo "=================================\n";
echo "DEPLOYMENT SUMMARY\n";
echo "=================================\n";
echo "Completed at: " . date('Y-m-d H:i:s') . "\n\n";

if (empty($errors)) {
    echo "✅ Deployment Successful!\n\n";
    
    if (!empty($warnings)) {
        echo "⚠️  Warnings (" . count($warnings) . "):\n";
        foreach ($warnings as $warning) {
            echo "   - $warning\n";
        }
        echo "\n";
    }
    
    echo "🎉 Website updated!\n";
    echo "🌐 Check: https://demo-sapahc.ptsi.co.id\n\n";
} else {
    echo "❌ Deployment Failed!\n\n";
    echo "Errors:\n";
    foreach ($errors as $error) {
        echo "   - $error\n";
    }
    echo "\n";
}

echo "=================================\n";
echo "⚠️  REMEMBER TO:\n";
echo "=================================\n";
echo "1. Clear browser cache (Ctrl+Shift+R)\n";
echo "2. Test the website thoroughly\n";
echo "3. Check error logs if issues occur\n";
echo "=================================\n";
?>
