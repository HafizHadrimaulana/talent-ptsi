#!/bin/bash
# Generate Deploy Token Script
# Run: bash generate-token.sh

echo "================================="
echo "🔐 DEPLOY TOKEN GENERATOR"
echo "================================="
echo ""

# Generate random token
TOKEN=$(openssl rand -base64 48 | tr -d "=+/" | cut -c1-64)

echo "✅ Token berhasil di-generate!"
echo ""
echo "📋 Copy token ini:"
echo "-----------------------------------"
echo "$TOKEN"
echo "-----------------------------------"
echo ""
echo "📝 Next steps:"
echo "1. Copy token di atas"
echo "2. Edit deploy-auto.php:"
echo "   define('SECRET_TOKEN', '$TOKEN');"
echo ""
echo "3. Tambahkan ke GitHub Secrets:"
echo "   Name: DEPLOY_TOKEN"
echo "   Value: $TOKEN"
echo ""
echo "⚠️  SIMPAN TOKEN INI DI PASSWORD MANAGER!"
echo "================================="
