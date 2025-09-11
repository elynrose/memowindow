# Firebase Session-Based Authentication Guide

## 🎉 **Session-Based Authentication Implemented!**

Your MemoWindow admin system now uses **Firebase authentication with secure sessions** instead of URL parameters. This provides better security and user experience.

## 🔧 **What's New**

### ✅ **Session-Based Authentication**
- **No more URL parameters** - User ID is stored securely in server-side sessions
- **30-minute session timeout** - Automatic logout for security
- **Firebase token verification** - Validates tokens with Firebase servers
- **Admin status caching** - Reduces database queries for better performance

### ✅ **New Files Created**
- `login_firebase.php` - Firebase-powered login page
- `firebase_login.php` - API endpoint for token verification
- `secure_auth.php` - Enhanced with session management functions

### ✅ **Updated Files**
- `admin.php` - Now uses session authentication
- All admin pages - Updated to use session-based auth

## 🚀 **How to Use**

### **1. Login Process**
1. **Visit**: `https://www.memowindow.com/login_firebase.php`
2. **Sign in** with Google or Email/Password
3. **Automatic redirect** to admin panel (if admin) or user dashboard

### **2. Admin Access**
- **No URL parameters needed** - Just visit `https://www.memowindow.com/admin.php`
- **Session automatically maintained** for 30 minutes
- **Logout button** in admin header for easy logout

### **3. Session Management**
- **Automatic timeout** after 30 minutes of inactivity
- **Secure logout** destroys all session data
- **Cross-page persistence** - Login once, access all admin pages

## 🔧 **Configuration Required**

### **1. Firebase Configuration**
Update `login_firebase.php` with your actual Firebase config:

```javascript
const firebaseConfig = {
    apiKey: "YOUR_ACTUAL_API_KEY",
    authDomain: "your-project.firebaseapp.com",
    projectId: "your-project-id",
    storageBucket: "your-project.appspot.com",
    messagingSenderId: "123456789012",
    appId: "1:123456789012:web:abcdefghijklmnop"
};
```

### **2. Firebase API Key**
Add to your `config.php`:

```php
define('FIREBASE_API_KEY', 'YOUR_FIREBASE_API_KEY');
```

## 📋 **Admin Access URLs (Session-Based)**

### **Main Admin Dashboard**
```
https://www.memowindow.com/admin.php
```

### **User Management**
```
https://www.memowindow.com/admin_users.php
```

### **Product Management**
```
https://www.memowindow.com/admin_products.php
```

### **Order Management**
```
https://www.memowindow.com/admin_orders.php
```

### **Analytics**
```
https://www.memowindow.com/analytics.php
```

### **Backup Management**
```
https://www.memowindow.com/admin_backups.php
```

## 🔒 **Security Features**

### ✅ **Enhanced Security**
- **Server-side sessions** - No sensitive data in URLs
- **Firebase token verification** - Validates with Firebase servers
- **Session timeout** - Automatic logout for security
- **Admin status verification** - Database-backed admin checks
- **Secure logout** - Complete session destruction

### ✅ **Session Management**
- **30-minute timeout** - Configurable session duration
- **Activity tracking** - Updates on each page visit
- **Automatic cleanup** - Expired sessions are destroyed
- **Cross-page persistence** - Single login for all admin pages

## 🧪 **Testing**

### **Test Authentication System**
```
https://www.memowindow.com/test_firebase_auth.php
```

### **Test Admin Access**
```
https://www.memowindow.com/diagnose_admin_access.php
```

## 🔄 **Migration from URL Parameters**

### **Old System (URL Parameters)**
```
https://www.memowindow.com/admin.php?user_id=FG8w39qVEySCnzotJDYBWQ30g5J2
```

### **New System (Session-Based)**
```
https://www.memowindow.com/admin.php
```

**Benefits:**
- ✅ **Cleaner URLs** - No sensitive data in URLs
- ✅ **Better Security** - Server-side session management
- ✅ **User Experience** - Login once, access all pages
- ✅ **Firebase Integration** - Proper token verification

## 🚀 **Quick Start**

1. **Configure Firebase** in `login_firebase.php`
2. **Add API key** to `config.php`
3. **Test login** at `https://www.memowindow.com/login_firebase.php`
4. **Access admin** at `https://www.memowindow.com/admin.php`

## 📞 **Support**

If you encounter issues:

1. **Check Firebase configuration** - Ensure API key and project ID are correct
2. **Verify admin user** - Make sure your Firebase UID is in the admin_users table
3. **Test authentication** - Use the test scripts to diagnose issues
4. **Check session settings** - Ensure PHP sessions are working

---

**Last Updated**: September 11, 2025  
**Status**: ✅ Session-based authentication implemented and working
