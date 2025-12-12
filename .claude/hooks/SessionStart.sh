#!/bin/bash

# Session start hook to ensure pre-commit is installed
echo "Installing pre-commit hooks..."

# Check if pre-commit is available
if ! command -v pre-commit &> /dev/null; then
    echo "Error: pre-commit is not installed. Please install it with: pip install pre-commit"
    exit 1
fi

# Install pre-commit hooks
pre-commit install
pre-commit install --hook-type commit-msg

echo "Pre-commit hooks installed successfully!"
