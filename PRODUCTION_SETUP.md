# MemoWindow Production Setup Guide

## 🚀 Complete Setup Instructions

### 1. Database Setup

1. **Create MySQL Database:**
   ```sql
   CREATE DATABASE memowindow;
   CREATE USER 'memowindow_user'@'localhost' IDENTIFIED BY 'your_secure_password';
   GRANT ALL PRIVILEGES ON memowindow.* TO 'memowindow_user'@'localhost';
   FLUSH PRIVILEGES;
   ```

2. **Import Database Schema:**
   - Run the `setup_database.sql` file in your MySQL database
   - This creates all necessary tables and inserts default data

### 2. Configuration Files

1. **Database Configuration:**
   - Copy `config_production.php` to `config.php`
   - Update database credentials with your actual values

2. **Firebase Configuration:**
   - Copy `firebase-config_production.js` to `src/firebase-config.js`
   - Update with your production Firebase project details

3. **Environment Variables:**
   - Copy `.env.example` to `.env`
   - Fill in your actual Firebase configuration values

### 3. Firebase Setup

1. **Create Production Firebase Project:**
   - Go to [Firebase Console](https://console.firebase.google.com)
   - Create a new project for production
   - Enable Authentication (Google + Email/Password)
   - Enable Storage
   - Set up Security Rules

2. **Firebase Storage Rules:**
   ```javascript
   rules_version = '2';
   service firebase.storage {
     match /b/{bucket}/o {
       match /{allPaths=**} {
         allow read, write: if request.auth != null;
       }
     }
   }
   ```

3. **Firebase Authentication:**
   - Enable Google Sign-in
   - Enable Email/Password authentication
   - Add your domain to authorized domains

### 4. Stripe Setup

1. **Create Stripe Account:**
   - Go to [Stripe Dashboard](https://dashboard.stripe.com)
   - Get your live API keys
   - Set up webhook endpoint: `https://yourdomain.com/memowindow/stripe_webhook.php`

2. **Webhook Events to Listen For:**
   - `checkout.session.completed`
   - `payment_intent.succeeded`

### 5. Printful Setup

1. **Create Printful Account:**
   - Go to [Printful Dashboard](https://www.printful.com/dashboard)
   - Connect your store
   - Get your API key
   - Note your Store ID

2. **Product Setup:**
   - Create products in Printful
   - Note the variant IDs
   - Update the `print_products` table with your actual products

### 6. File Permissions

Set proper file permissions:
```bash
chmod 644 *.php
chmod 644 *.js
chmod 644 *.html
chmod 755 uploads/
```

### 7. SSL Certificate

Ensure your domain has a valid SSL certificate for:
- Firebase Authentication
- Stripe payments
- Secure file uploads

### 8. Testing

1. **Test Authentication:**
   - Try Google sign-in
   - Try email/password registration
   - Verify user data is saved

2. **Test File Upload:**
   - Upload an audio file
   - Verify it appears in Firebase Storage
   - Check database entry

3. **Test Voice Recording:**
   - Use the record button
   - Verify recording works
   - Check audio processing

4. **Test Orders:**
   - Create a test order
   - Verify Stripe payment
   - Check Printful integration

### 9. Admin Setup

1. **Set Admin User:**
   - Find your Firebase UID from the browser console
   - Update the `admin_users` table with your UID
   - Access admin panel at `/admin.php`

2. **Configure Products:**
   - Add your actual print products
   - Set correct prices and variant IDs
   - Test order flow

### 10. Security Checklist

- [ ] Database credentials are secure
- [ ] Firebase rules are properly configured
- [ ] Stripe webhook is verified
- [ ] SSL certificate is valid
- [ ] File permissions are correct
- [ ] Error reporting is disabled
- [ ] Sensitive files are not accessible

### 11. Performance Optimization

1. **Enable Gzip Compression**
2. **Set up CDN for static assets**
3. **Optimize images**
4. **Enable browser caching**

### 12. Monitoring

1. **Set up error logging**
2. **Monitor Stripe webhooks**
3. **Track Firebase usage**
4. **Monitor database performance**

## 🔧 Troubleshooting

### Common Issues:

1. **Database Connection Failed:**
   - Check database credentials
   - Verify database exists
   - Check user permissions

2. **Firebase Authentication Not Working:**
   - Check domain authorization
   - Verify API keys
   - Check browser console for errors

3. **File Upload Issues:**
   - Check Firebase Storage rules
   - Verify storage bucket exists
   - Check file permissions

4. **Payment Issues:**
   - Verify Stripe keys
   - Check webhook endpoint
   - Verify SSL certificate

## 📞 Support

If you encounter issues:
1. Check the browser console for errors
2. Check server error logs
3. Verify all configuration values
4. Test each component individually

## 🎉 Go Live!

Once everything is tested and working:
1. Update DNS to point to your server
2. Monitor for any issues
3. Set up backups
4. Enjoy your live MemoWindow application!
