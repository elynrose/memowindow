#!/bin/bash

# Setup webhook tunnel for Stripe webhook testing
echo "🔧 Setting up Stripe webhook tunnel..."
echo "======================================"

# Check if ngrok is installed
if ! command -v ngrok &> /dev/null; then
    echo "❌ ngrok is not installed"
    echo "📦 Installing ngrok..."
    
    # Check if brew is available
    if command -v brew &> /dev/null; then
        brew install ngrok
    else
        echo "❌ Homebrew not found. Please install ngrok manually:"
        echo "   Visit: https://ngrok.com/download"
        exit 1
    fi
fi

echo "✅ ngrok is installed"

# Check if ngrok is authenticated
if ! ngrok config check &> /dev/null; then
    echo "⚠️  ngrok is not authenticated"
    echo "🔑 Please authenticate ngrok:"
    echo "   1. Sign up at https://ngrok.com"
    echo "   2. Get your authtoken from https://dashboard.ngrok.com/get-started/your-authtoken"
    echo "   3. Run: ngrok config add-authtoken YOUR_TOKEN"
    echo ""
    read -p "Press Enter after you've authenticated ngrok..."
fi

echo "🚀 Starting ngrok tunnel..."
echo "📡 This will create a public URL for your local webhook"
echo ""

# Start ngrok tunnel
ngrok http 80 --log=stdout
