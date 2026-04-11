#!/bin/bash
BRANCH=$(git branch --show-current)

if [ "$BRANCH" = "main" ]; then
    cp .env.production .env
    echo "✅ Switched to PRODUCTION env"
elif [ "$BRANCH" = "develop" ]; then
    cp .env.staging .env
    echo "✅ Switched to STAGING env"
else
    echo "⚠️  Branch '$BRANCH' tidak dikenali, env tidak diganti"
fi

php artisan config:clear