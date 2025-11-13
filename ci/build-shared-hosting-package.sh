#!/bin/bash

# Exit on error
set -e

echo "Starting local build of the Shared Hosting package..."

# Use version passed as argument instead of generating a timestamp
if [ -z "$1" ]; then
    echo "Usage: $0 <version>"
    exit 1
fi
VERSION="$1"

TEMP_DIR="/tmp/heinerenergie-build-$VERSION"
ZIP_NAME="heinerenergie-sammelbestellung-v${VERSION}.zip"

if [ -f "$ZIP_NAME" ]; then
    echo "Removing existing ZIP archive..."
    rm -f "$ZIP_NAME"
fi

if [ -d "$TEMP_DIR" ]; then
    echo "Removing existing temporary directory..."
    rm -rf "$TEMP_DIR"
fi

# Copy project files to temporary directory
echo "Copying project files to temporary directory..."
mkdir -p "$TEMP_DIR"
cp -r . "$TEMP_DIR"

# Switch to temporary directory
cd "$TEMP_DIR"

# Remove unnecessary files and directories in temporary directory
echo "Removing unnecessary files and directories..."
git add --all
git reset --hard HEAD
git clean -fxd
rm -rf "$TEMP_DIR/.git"
rm -rf "$TEMP_DIR/node_modules"
rm -rf "$TEMP_DIR/vendor"
rm -rf "$TEMP_DIR/tests"
rm -rf "$TEMP_DIR/.github"
rm -rf "$TEMP_DIR/storage/logs/"*
rm -rf "$TEMP_DIR/storage/framework/cache/"*
rm -rf "$TEMP_DIR/storage/framework/sessions/"*
rm -rf "$TEMP_DIR/storage/framework/views/"*
rm -f "$TEMP_DIR/.env"
rm -f "$TEMP_DIR/*.log"
rm -f "$TEMP_DIR/local-build.sh"

# Installing Composer dependencies...
echo "Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader --prefer-dist

# Installing NPM dependencies and building assets...
echo "Installing NPM dependencies and building assets..."
npm ci
npm run build

php artisan storage:link

composer dump-autoload

# Removing development files and directories...
echo "Removing development files and directories..."
rm -rf node_modules
rm -rf resources/css
rm -rf resources/img
rm -rf resources/js
rm -rf resources/sass
rm -rf storage/app/public
rm -rf storage/app/uploads
rm components.json
rm -rf ci/
rm devextreme.json
rm eslint.config.js
rm package.json
rm package-lock.json
rm phpstan.neon
rm phpunit.xml
rm rector.php
rm tsconfig.json
rm vite.config.js
rm tailwind.config.js
rm .editorconfig
rm .gitattributes
rm .gitignore
rm .prettierignore
rm .prettierrc
rm .styleci.yml
rm -rf .vscode


# Setting permissions...
echo "Setting permissions..."
chmod -R 775 storage bootstrap/cache

# Copying installation instructions...
echo "Copying installation instructions..."
cp INSTALLATION.md README.md

# Return to project directory
cd ..

# Creating ZIP archive... in $PWD
echo "Creating ZIP archive... in $PWD"
zip -r "$ZIP_NAME" "$TEMP_DIR"

# Removing temporary directory...
echo "Removing temporary directory..."
#rm -rf "$TEMP_DIR"

echo "Build completed successfully!"
echo "The package has been saved as $ZIP_NAME."
