#!/bin/bash

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
