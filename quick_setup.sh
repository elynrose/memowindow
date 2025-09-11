#!/bin/bash

# Quick MemoWindow Directory Setup
# Simple script for quick directory creation and permission setting

echo "🚀 Quick MemoWindow Setup"
echo "========================="

# Create directories
mkdir -p uploads/qr audio_cache backups/{audio,generated-audio,qr-codes,waveforms} temp rate_limit_storage

# Set permissions
chmod 755 uploads uploads/qr audio_cache backups backups/audio backups/generated-audio backups/qr-codes backups/waveforms temp rate_limit_storage

echo "✅ Directories created and permissions set!"
echo "📁 Created: uploads, audio_cache, backups, temp, rate_limit_storage"
echo "🔐 Permissions: 755 (read/write/execute for owner, read/execute for group/others)"
echo ""
echo "🎉 Setup complete! MemoWindow is ready to use."
