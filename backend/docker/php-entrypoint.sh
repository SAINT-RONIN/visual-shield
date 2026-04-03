#!/bin/sh
set -eu

APP_DIR="/app"
LOCK_DIR="$APP_DIR/.composer-install.lock"

should_install_composer() {
    if [ ! -f "$APP_DIR/vendor/autoload.php" ]; then
        return 0
    fi

    if [ -f "$APP_DIR/composer.lock" ] && [ "$APP_DIR/composer.lock" -nt "$APP_DIR/vendor/autoload.php" ]; then
        return 0
    fi

    if [ "$APP_DIR/composer.json" -nt "$APP_DIR/vendor/autoload.php" ]; then
        return 0
    fi

    return 1
}

cleanup_lock() {
    rmdir "$LOCK_DIR" 2>/dev/null || true
}

install_composer_dependencies() {
    if ! should_install_composer; then
        return 0
    fi

    echo "Installing Composer dependencies..."

    while ! mkdir "$LOCK_DIR" 2>/dev/null; do
        echo "Waiting for Composer install lock..."
        sleep 2

        if ! should_install_composer; then
            return 0
        fi
    done

    trap cleanup_lock EXIT INT TERM

    if should_install_composer; then
        composer install --working-dir="$APP_DIR" --no-interaction --prefer-dist
    fi

    cleanup_lock
    trap - EXIT INT TERM
}

install_composer_dependencies

exec "$@"
