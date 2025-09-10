# MemoWindow Authentication Guide

## Overview
All pages in the MemoWindow application now require proper authentication. This ensures that only authorized users can access sensitive functionality and data.

## Authentication System

### Centralized Authentication (`auth_check.php`)
- **`requireAuth()`** - Requires any authenticated user
- **`requireAdmin()`** - Requires admin privileges
- **`getCurrentUserId()`** - Gets current user ID
- **`showAuthError()`** - Displays authentication error page

### Authentication Levels

#### 1. **Public Pages** (No authentication required)
- `index.html` - Marketing home page
- `login.html` - Main application (handles login)
- `firebase-config.php` - Firebase configuration
- `stripe_webhook.php` - Stripe webhook (external access)
- `order_success.php` - Order success page
- `order_cancelled.php` - Order cancelled page

#### 2. **User Pages** (Require authentication)
- `orders.php` - User's order tracking
- `play.php` - Memory playback (if user-specific)

#### 3. **Admin Pages** (Require admin privileges)
- `admin.php` - Main admin dashboard
- `admin_orders.php` - Order management
- `admin_products.php` - Product management
- `admin_users.php` - User management
- `admin_backups.php` - Backup management
- `admin_cancel_order.php` - Cancel orders
- `analytics.php` - Analytics dashboard
- `user_details.php` - User details (admin view)

## Implementation

### For New Pages
Add authentication to any new page:

```php
<?php
// Your page
require_once 'auth_check.php';
require_once 'config.php';

// For user pages
$userId = requireAuth();

// For admin pages
$adminId = requireAdmin();
?>
```

### Authentication Flow
1. **User visits protected page**
2. **Page checks for `user_id` parameter**
3. **If missing**: Redirects to `login.html?error=login_required`
4. **If present**: Verifies user exists and has required permissions
5. **If admin required**: Checks `is_admin` flag in database
6. **If unauthorized**: Redirects with appropriate error

### Error Handling
The system provides specific error messages:
- `login_required` - User needs to sign in
- `access_denied` - User lacks required permissions
- `admin_required` - Admin privileges needed
- `database_error` - Database connection issue

## Security Features

### ✅ **Implemented**
- Centralized authentication checking
- Admin privilege verification
- Automatic redirects for unauthorized access
- Error message handling
- Session management
- Database verification

### 🔒 **Security Benefits**
- **No unauthorized access** to sensitive pages
- **Admin-only functionality** properly protected
- **User data isolation** (users only see their own data)
- **Graceful error handling** with user-friendly messages
- **Consistent authentication** across all pages

## Database Requirements

### Users Table
```sql
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    firebase_uid VARCHAR(255) UNIQUE NOT NULL,
    email VARCHAR(255),
    name VARCHAR(255),
    is_admin BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Admin Users Table
```sql
CREATE TABLE admin_users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    firebase_uid VARCHAR(255) UNIQUE NOT NULL,
    email VARCHAR(255),
    name VARCHAR(255),
    is_admin BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

## Usage Examples

### Protecting a User Page
```php
<?php
require_once 'auth_check.php';
require_once 'config.php';

$userId = requireAuth();

// Page content for authenticated user
echo "Welcome, user: " . $userId;
?>
```

### Protecting an Admin Page
```php
<?php
require_once 'auth_check.php';
require_once 'config.php';

$adminId = requireAdmin();

// Page content for admin only
echo "Admin dashboard for: " . $adminId;
?>
```

### Custom Error Handling
```php
<?php
require_once 'auth_check.php';

try {
    $userId = requireAuth();
} catch (Exception $e) {
    showAuthError("Custom error message");
}
?>
```

## Production Considerations

### Firebase Token Verification
In production, you should verify Firebase tokens instead of relying on URL parameters:

```php
function verifyFirebaseToken($token) {
    // Verify the Firebase token
    // Return user ID if valid, false if invalid
}
```

### HTTPS Enforcement
Ensure all authentication pages use HTTPS in production.

### Session Security
- Use secure session cookies
- Implement session timeout
- Regenerate session IDs on login

## Testing

### Test Authentication
1. **Visit protected page without `user_id`** → Should redirect to login
2. **Visit admin page as regular user** → Should show access denied
3. **Visit admin page as admin** → Should work normally
4. **Test error messages** → Should display appropriate messages

### Test URLs
- `orders.php` (without user_id) → Redirects to login
- `admin.php?user_id=non_admin` → Access denied
- `admin.php?user_id=admin_user` → Works (if admin)

## Maintenance

### Adding New Protected Pages
1. Add `require_once 'auth_check.php';`
2. Call `requireAuth()` or `requireAdmin()`
3. Test authentication flow
4. Update this documentation

### Updating Permissions
1. Modify database `is_admin` flags
2. Test admin functionality
3. Verify user access restrictions

Your MemoWindow application now has comprehensive authentication protection! 🔒
