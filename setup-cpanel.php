<?php
// Setup script untuk first-time deployment
// Akses: https://yourdomain.com/setup.php
// HAPUS FILE INI SETELAH SETUP SELESAI!

define('LARAVEL_START', microtime(true));

// Load Laravel
require __DIR__.'/../talent-ptsi/vendor/autoload.php';
$app = require_once __DIR__.'/../talent-ptsi/bootstrap/app.php';

// Set to command line mode
$_SERVER['argv'] = ['artisan'];
$_SERVER['argc'] = 1;

echo "<pre>";
echo "=================================\n";
echo "LARAVEL SETUP SCRIPT\n";
echo "=================================\n\n";

// Check if .env exists
if (!file_exists(__DIR__.'/../talent-ptsi/.env')) {
    echo "❌ ERROR: .env file not found!\n";
    echo "Please copy .env from backup folder\n";
    exit;
}

echo "✓ .env file exists\n\n";

// Run commands
$commands = [
    'key:generate' => 'Generating application key...',
    'storage:link' => 'Creating storage link...',
    'migrate --force' => 'Running database migrations...',
    'config:cache' => 'Caching configuration...',
    'route:cache' => 'Caching routes...',
    'view:cache' => 'Caching views...',
    'optimize' => 'Optimizing application...',
];

foreach ($commands as $command => $message) {
    echo "📦 $message\n";
    
    try {
        $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
        $status = $kernel->call($command);
        
        if ($status === 0) {
            echo "   ✅ Success\n\n";
        } else {
            echo "   ⚠️  Warning (exit code: $status)\n\n";
        }
    } catch (Exception $e) {
        echo "   ❌ Error: " . $e->getMessage() . "\n\n";
    }
}

echo "=================================\n";
echo "SETUP COMPLETED!\n";
echo "=================================\n\n";
echo "⚠️  IMPORTANT: DELETE THIS FILE NOW!\n";
echo "Run: rm " . __FILE__ . "\n";
echo "</pre>";
