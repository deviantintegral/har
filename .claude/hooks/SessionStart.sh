#!/bin/bash

# Only run in remote environments
if [ "$CLAUDE_CODE_REMOTE" != "true" ]; then
  exit 0
fi

# Session start hook to ensure pre-commit is installed
echo "Setting up pre-commit..."

# Check if pre-commit is available, install if not
if ! command -v pre-commit &> /dev/null; then
    echo "pre-commit is not installed. Installing pre-commit..."

    # Try to install pre-commit using pip
    if command -v pip3 &> /dev/null; then
        pip3 install pre-commit
    elif command -v pip &> /dev/null; then
        pip install pre-commit
    else
        echo "Error: pip is not installed. Cannot install pre-commit."
        exit 1
    fi

    # Verify installation
    if ! command -v pre-commit &> /dev/null; then
        echo "Error: Failed to install pre-commit."
        exit 1
    fi

    echo "pre-commit installed successfully!"
fi

# Install pre-commit hooks
pre-commit install
pre-commit install --hook-type commit-msg

echo "Pre-commit hooks installed successfully!"

# Install xdebug if not already present
echo "Checking for xdebug..."
if ! php -m | grep -q xdebug; then
    echo "xdebug is not installed. Installing xdebug..."

    # Try to install xdebug using apt
    if command -v apt-get &> /dev/null; then
        # Detect PHP version and install corresponding xdebug package
        PHP_VERSION=$(php -r 'echo PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION;')
        apt-get install -y php${PHP_VERSION}-xdebug
    else
        echo "Error: apt package manager is not available. Cannot install xdebug."
        exit 1
    fi

    # Verify installation
    if ! php -m | grep -q xdebug; then
        echo "Error: Failed to install xdebug."
        exit 1
    fi

    echo "xdebug installed successfully!"
else
    echo "xdebug is already available."
fi

composer install

# Install infection for mutation testing
echo "Checking for infection..."
if [ ! -f "infection.phar" ]; then
    echo "infection is not installed. Downloading infection..."

    # Download Infection PHAR and signature
    wget https://github.com/infection/infection/releases/download/0.31.9/infection.phar
    wget https://github.com/infection/infection/releases/download/0.31.9/infection.phar.asc

    # Validate Infection PHAR with GPG
    echo "Validating infection signature..."
    SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
    gpg --import "$SCRIPT_DIR/infection-public-key.asc"
    gpg --with-fingerprint --verify infection.phar.asc infection.phar

    if [ $? -ne 0 ]; then
        echo "Error: Failed to validate infection.phar signature."
        rm -f infection.phar infection.phar.asc
        exit 1
    fi

    chmod +x infection.phar
    echo "infection installed successfully!"
else
    echo "infection is already available."
fi
