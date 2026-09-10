#!/usr/bin/env bash
# À exécuter sur le VPS après scp de l'APK.
set -euo pipefail
cd /var/www/mami.ga
git pull origin feature/mami-taxi-v2-p2
mkdir -p public/apk
# Si l'APK a été uploadée dans /tmp :
if [[ -f /tmp/jb-games-1.0.5.apk ]]; then
  cp /tmp/jb-games-1.0.5.apk public/apk/jb-games-1.0.5.apk
fi
ln -sfn jb-games-1.0.5.apk public/apk/jb-games-latest.apk
chmod 644 public/apk/jb-games-1.0.5.apk || true
php artisan config:cache
php artisan route:cache
ls -lh public/apk/jb-games*
echo "URL: https://admin.mami.ga/apk/jb-games-1.0.5.apk"
