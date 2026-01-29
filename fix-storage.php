<?php
/**
 * Storage Fix Script untuk cPanel
 * Akses: https://demo-sapahc.ptsi.co.id/fix-storage.php
 * 
 * Script ini akan:
 * 1. Create storage symlink
 * 2. Copy file storage lama (jika ada)
 * 3. Set permissions yang benar
 * 
 * HAPUS FILE INI SETELAH SELESAI!
 */

define('BASE_PATH', '/home/demosapahcptsico');
define('APP_PATH', BASE_PATH . '/talent-ptsi');
define('PUBLIC_PATH', BASE_PATH . '/public_html');
define('BACKUP_PATH', BASE_PATH . '/talent-ptsi-backup-20260129');

header('Content-Type: text/plain; charset=utf-8');
echo "=================================\n";
echo "🔧 STORAGE FIX SCRIPT\n";
echo "=================================\n\n";

// Check if Laravel exists
if (!file_exists(APP_PATH . '/vendor/autoload.php')) {
    die("❌ Laravel not found at: " . APP_PATH . "\n");
}

// Load Laravel
require APP_PATH . '/vendor/autoload.php';
$app = require_once APP_PATH . '/bootstrap/app.php';

echo "✅ Laravel loaded\n\n";

// ============================================
// STEP 1: Create Storage Symlink
// ============================================
echo "=================================\n";
echo "STEP 1: Create Storage Symlink\n";
echo "=================================\n";

$storageLink = PUBLIC_PATH . '/storage';
$storagePath = APP_PATH . '/storage/app/public';

echo "Checking storage link...\n";
echo "Link: $storageLink\n";
echo "Target: $storagePath\n\n";

// Remove old link if exists
if (file_exists($storageLink) || is_link($storageLink)) {
    echo "📁 Old storage link exists, removing...\n";
    if (is_link($storageLink)) {
        unlink($storageLink);
        echo "   ✅ Old symlink removed\n";
    } else {
        echo "   ⚠️  storage/ is a directory, not a symlink!\n";
        echo "   Renaming to storage-old...\n";
        rename($storageLink, PUBLIC_PATH . '/storage-old');
        echo "   ✅ Renamed\n";
    }
}

// Create new symlink
echo "📦 Creating new storage symlink...\n";
if (symlink($storagePath, $storageLink)) {
    echo "   ✅ Storage symlink created successfully!\n";
} else {
    echo "   ❌ Failed to create symlink\n";
    echo "   Trying alternative method...\n";
    
    // Try using Laravel's command
    try {
        $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
        $status = $kernel->call('storage:link', ['--force' => true]);
        
        if ($status === 0) {
            echo "   ✅ Storage link created via artisan\n";
        } else {
            echo "   ⚠️  Artisan command returned: $status\n";
        }
    } catch (Exception $e) {
        echo "   ❌ Error: " . $e->getMessage() . "\n";
    }
}
echo "\n";

// ============================================
// STEP 2: Copy Files from Backup
// ============================================
echo "=================================\n";
echo "STEP 2: Copy Storage Files from Backup\n";
echo "=================================\n";

$backupStorage = BACKUP_PATH . '/storage/app/public';
$newStorage = APP_PATH . '/storage/app/public';

if (file_exists($backupStorage)) {
    echo "📁 Backup storage found!\n";
    echo "From: $backupStorage\n";
    echo "To: $newStorage\n\n";
    
    // Function to copy directory
    function copyStorageDir($src, $dst) {
        $copied = 0;
        $dir = opendir($src);
        
        while (false !== ($file = readdir($dir))) {
            if ($file != '.' && $file != '..') {
                $srcFile = $src . '/' . $file;
                $dstFile = $dst . '/' . $file;
                
                if (is_dir($srcFile)) {
                    if (!file_exists($dstFile)) {
                        mkdir($dstFile, 0775, true);
                    }
                    $copied += copyStorageDir($srcFile, $dstFile);
                } else {
                    if (!file_exists($dstFile)) {
                        copy($srcFile, $dstFile);
                        $copied++;
                    }
                }
            }
        }
        closedir($dir);
        return $copied;
    }
    
    $filesCopied = copyStorageDir($backupStorage, $newStorage);
    echo "   ✅ Copied $filesCopied files\n";
} else {
    echo "ℹ️  No backup storage found\n";
    echo "   Path: $backupStorage\n";
}
echo "\n";

// ============================================
// STEP 3: Set Permissions
// ============================================
echo "=================================\n";
echo "STEP 3: Set Storage Permissions\n";
echo "=================================\n";

function setPermissionsRecursive($path, $dirPerm = 0775, $filePerm = 0664) {
    if (is_dir($path)) {
        chmod($path, $dirPerm);
        $items = scandir($path);
        foreach ($items as $item) {
            if ($item != '.' && $item != '..') {
                setPermissionsRecursive($path . '/' . $item, $dirPerm, $filePerm);
            }
        }
    } else {
        chmod($path, $filePerm);
    }
}

echo "📦 Setting permissions for storage/app/public...\n";
setPermissionsRecursive($newStorage, 0775, 0664);
echo "   ✅ Permissions set (775 for dirs, 664 for files)\n\n";

// ============================================
// STEP 4: Verify
// ============================================
echo "=================================\n";
echo "VERIFICATION\n";
echo "=================================\n";

// Check symlink
if (is_link($storageLink)) {
    $target = readlink($storageLink);
    echo "✅ Storage symlink exists\n";
    echo "   Link: $storageLink\n";
    echo "   Points to: $target\n";
} else {
    echo "⚠️  Storage symlink not found!\n";
    echo "   Manual fix needed\n";
}
echo "\n";

// Check if storage/app/public exists and has files
if (is_dir($newStorage)) {
    $files = array_diff(scandir($newStorage), ['.', '..', '.gitignore']);
    $fileCount = count($files);
    echo "✅ Storage directory exists\n";
    echo "   Path: $newStorage\n";
    echo "   Files: $fileCount\n";
    
    if ($fileCount > 0) {
        echo "   Sample files:\n";
        $sample = array_slice($files, 0, 5);
        foreach ($sample as $file) {
            echo "   - $file\n";
        }
    }
} else {
    echo "⚠️  Storage directory not found!\n";
}
echo "\n";

// ============================================
// SUMMARY
// ============================================
echo "=================================\n";
echo "SUMMARY\n";
echo "=================================\n";
echo "✅ Storage fix completed!\n\n";
echo "Test upload file sekarang:\n";
echo "1. Login ke website\n";
echo "2. Upload gambar/dokumen\n";
echo "3. Cek apakah file bisa diakses\n\n";
echo "⚠️  PENTING: HAPUS FILE INI SETELAH SELESAI!\n";
echo "File: " . PUBLIC_PATH . "/fix-storage.php\n";
echo "=================================\n";
?>
