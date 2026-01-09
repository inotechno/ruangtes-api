#!/bin/bash

# Script to setup Docker permissions for user

set -e

echo "🔧 Setting up Docker permissions..."

# Check if running as root
if [ "$EUID" -eq 0 ]; then 
    echo "❌ Please run this script as regular user (not root)"
    echo "   The script will use sudo when needed"
    exit 1
fi

# Get current user
CURRENT_USER=$USER

echo "📝 Adding user '$CURRENT_USER' to docker group..."

# Add user to docker group
sudo usermod -aG docker $CURRENT_USER

echo "✅ User added to docker group"
echo ""
echo "⚠️  IMPORTANT: You need to logout and login again for changes to take effect"
echo "   Or run: newgrp docker"
echo ""
echo "After logout/login, verify with: docker ps"
