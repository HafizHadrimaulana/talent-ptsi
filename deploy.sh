#!/bin/bash
# Deploy script untuk cPanel
# Simpan di: /home/demosapahcptsico/deploy.sh
# Chmod: chmod +x /home/demosapahcptsico/deploy.sh

set -e

REPO_PATH="/home/demosapahcptsico/talent-ptsi"
WEB_PATH="/home/demosapahcptsico/public_html"

echo "🚀 Starting deployment..."
echo "📅 $(date)"

# Navigate to repository
cd $REPO_PATH

# Fetch and pull latest changes
echo "📥 Fetching latest changes from GitHub..."
git fetch origin production
git reset --hard origin/production

# Install/update Composer dependencies (production only)
if [ -f composer.json ]; then
    echo "📦 Installing Composer dependencies..."
    composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev
fi

# Clear caches
echo "🧹 Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Run migrations
echo "🗄️ Running database migrations..."
php artisan migrate --force

# Optimize for production
echo "⚡ Optimizing for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# Set permissions
echo "🔒 Setting permissions..."
chmod -R 775 storage bootstrap/cache
find storage -type f -exec chmod 664 {} \;
find bootstrap/cache -type f -exec chmod 664 {} \;

# Create storage link if not exists
if [ ! -L public/storage ]; then
    echo "🔗 Creating storage link..."
    php artisan storage:link
fi

echo ""
echo "✅ Deployment completed successfully!"
echo "🕐 Completed at: $(date)"
echo ""
