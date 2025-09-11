#!/bin/bash

# MemoWindow Directory Setup Script
# Creates all required directories and sets appropriate permissions

echo "🔧 MEMOWINDOW DIRECTORY SETUP"
echo "============================="
echo ""

# Define required directories
DIRECTORIES=(
    "uploads"
    "uploads/qr"
    "audio_cache"
    "backups"
    "backups/audio"
    "backups/generated-audio"
    "backups/qr-codes"
    "backups/waveforms"
    "temp"
    "rate_limit_storage"
)

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Counters
created=0
existing=0
failed=0

echo "📁 Creating directories..."
echo ""

# Create directories
for dir in "${DIRECTORIES[@]}"; do
    if [ -d "$dir" ]; then
        echo -e "${BLUE}✅ Directory exists:${NC} $dir"
        existing=$((existing + 1))
    else
        echo -e "${YELLOW}🔨 Creating directory:${NC} $dir"
        if mkdir -p "$dir"; then
            echo -e "${GREEN}✅ Successfully created:${NC} $dir"
            created=$((created + 1))
        else
            echo -e "${RED}❌ Failed to create:${NC} $dir"
            failed=$((failed + 1))
        fi
    fi
done

echo ""
echo "📊 DIRECTORY CREATION RESULTS"
echo "============================="
echo -e "${GREEN}✅ Existing directories:${NC} $existing"
echo -e "${GREEN}✅ Created directories:${NC} $created"
echo -e "${RED}❌ Failed to create:${NC} $failed"

if [ $failed -eq 0 ]; then
    echo ""
    echo -e "${GREEN}🎉 All required directories are now present!${NC}"
else
    echo ""
    echo -e "${RED}⚠️  Some directories failed to create. Check permissions.${NC}"
fi

echo ""
echo "🔐 Setting directory permissions..."
echo ""

# Set permissions
permissions_set=0
permissions_failed=0

for dir in "${DIRECTORIES[@]}"; do
    if [ -d "$dir" ]; then
        echo -e "${YELLOW}🔧 Setting permissions for:${NC} $dir"
        if chmod 755 "$dir"; then
            echo -e "${GREEN}✅ Permissions set:${NC} $dir"
            permissions_set=$((permissions_set + 1))
        else
            echo -e "${RED}❌ Failed to set permissions:${NC} $dir"
            permissions_failed=$((permissions_failed + 1))
        fi
    fi
done

echo ""
echo "📊 PERMISSION RESULTS"
echo "====================="
echo -e "${GREEN}✅ Permissions set:${NC} $permissions_set"
echo -e "${RED}❌ Failed to set permissions:${NC} $permissions_failed"

if [ $permissions_failed -eq 0 ]; then
    echo ""
    echo -e "${GREEN}🎉 All directories have proper permissions!${NC}"
else
    echo ""
    echo -e "${RED}⚠️  Some directories failed to set permissions.${NC}"
    echo -e "${YELLOW}💡 You may need to run:${NC} sudo chmod 755 ${DIRECTORIES[*]}"
fi

echo ""
echo "🧪 Testing directory access..."
echo ""

# Test write access
writable=0
not_writable=0

for dir in "${DIRECTORIES[@]}"; do
    if [ -d "$dir" ]; then
        if [ -w "$dir" ]; then
            echo -e "${GREEN}✅ Writable:${NC} $dir"
            writable=$((writable + 1))
        else
            echo -e "${RED}❌ Not writable:${NC} $dir"
            not_writable=$((not_writable + 1))
        fi
    fi
done

echo ""
echo "📊 WRITE ACCESS RESULTS"
echo "======================="
echo -e "${GREEN}✅ Writable directories:${NC} $writable"
echo -e "${RED}❌ Not writable directories:${NC} $not_writable"

if [ $not_writable -eq 0 ]; then
    echo ""
    echo -e "${GREEN}🎉 All directories are writable!${NC}"
else
    echo ""
    echo -e "${RED}⚠️  Some directories are not writable.${NC}"
    echo -e "${YELLOW}💡 You may need to run:${NC} sudo chown -R \$(whoami):\$(whoami) ${DIRECTORIES[*]}"
fi

echo ""
echo "📋 FINAL SUMMARY"
echo "================"
echo -e "${BLUE}Total directories:${NC} ${#DIRECTORIES[@]}"
echo -e "${GREEN}Existing:${NC} $existing"
echo -e "${GREEN}Created:${NC} $created"
echo -e "${RED}Failed to create:${NC} $failed"
echo -e "${GREEN}Permissions set:${NC} $permissions_set"
echo -e "${RED}Permissions failed:${NC} $permissions_failed"
echo -e "${GREEN}Writable:${NC} $writable"
echo -e "${RED}Not writable:${NC} $not_writable"

if [ $failed -eq 0 ] && [ $permissions_failed -eq 0 ] && [ $not_writable -eq 0 ]; then
    echo ""
    echo -e "${GREEN}🎉 ALL SETUP COMPLETED SUCCESSFULLY!${NC}"
    echo -e "${GREEN}✅ MemoWindow directories are ready for production!${NC}"
    exit 0
else
    echo ""
    echo -e "${YELLOW}⚠️  Setup completed with some issues.${NC}"
    echo -e "${YELLOW}💡 Please review the errors above and fix them manually.${NC}"
    exit 1
fi
