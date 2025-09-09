# Environment Variables Setup Guide

## Overview
All sensitive configuration (API keys, database credentials, etc.) is now stored in environment variables using a `.env` file for security.

## Setup Instructions

### 1. Copy the Example File
```bash
cp .env.example .env
```

### 2. Edit the .env File
Open `.env` and fill in your actual values:

```env
# Database Configuration
DB_HOST=localhost
DB_NAME=memowindow
DB_USER=root
DB_PASS=your_database_password

# Stripe Configuration
STRIPE_PUBLISHABLE_KEY=pk_test_your_stripe_publishable_key_here
STRIPE_SECRET_KEY=sk_test_your_stripe_secret_key_here
STRIPE_WEBHOOK_SECRET=whsec_your_webhook_secret_here

# Printful Configuration
PRINTFUL_API_KEY=your_printful_api_key_here
PRINTFUL_STORE_ID=12587389

# Firebase Configuration
FIREBASE_API_KEY=your_firebase_api_key_here
FIREBASE_AUTH_DOMAIN=your_project.firebaseapp.com
FIREBASE_PROJECT_ID=your_project_id
FIREBASE_STORAGE_BUCKET=your_project.appspot.com
FIREBASE_MESSAGING_SENDER_ID=your_sender_id
FIREBASE_APP_ID=your_app_id
```

### 3. Security Notes
- ✅ `.env` file is already in `.gitignore` - it won't be committed to Git
- ✅ Only `.env.example` (with placeholder values) is tracked in Git
- ✅ All API keys are now loaded from environment variables
- ✅ Fallback values are provided if environment variables are missing

## Production Deployment

### For Production:
1. Create a `.env` file on your production server
2. Fill in your **live/production** API keys (not test keys)
3. Ensure the `.env` file has proper permissions (600 or 644)
4. Never commit the production `.env` file to version control

### Environment Variables Used:
- `DB_HOST` - Database host
- `DB_NAME` - Database name
- `DB_USER` - Database username
- `DB_PASS` - Database password
- `STRIPE_PUBLISHABLE_KEY` - Stripe publishable key
- `STRIPE_SECRET_KEY` - Stripe secret key
- `STRIPE_WEBHOOK_SECRET` - Stripe webhook secret
- `PRINTFUL_API_KEY` - Printful API key
- `PRINTFUL_STORE_ID` - Printful store ID
- `FIREBASE_API_KEY` - Firebase API key
- `FIREBASE_AUTH_DOMAIN` - Firebase auth domain
- `FIREBASE_PROJECT_ID` - Firebase project ID
- `FIREBASE_STORAGE_BUCKET` - Firebase storage bucket
- `FIREBASE_MESSAGING_SENDER_ID` - Firebase messaging sender ID
- `FIREBASE_APP_ID` - Firebase app ID

## Benefits
- 🔒 **Security**: API keys are not exposed in code
- 🔄 **Flexibility**: Easy to switch between development/production
- 📝 **Maintainability**: Centralized configuration
- 🚀 **Deployment**: Safe to commit code to Git repositories
