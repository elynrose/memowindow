# MemoWindow Production Deployment Guide

## Domain Setup
Your production domain: **https://www.memorywindow.com**

## Webhook Configuration

### Stripe Webhook Setup
1. **Go to Stripe Dashboard**: https://dashboard.stripe.com/webhooks
2. **Create Webhook Endpoint**:
   - **URL**: `https://www.memorywindow.com/stripe_webhook.php`
   - **Events**: `checkout.session.completed`
3. **Copy the Webhook Secret** (starts with `whsec_...`)

### Update Production Config
Update your production `config.php` with:
```php
// Stripe Configuration
define('STRIPE_SECRET_KEY', 'sk_live_your_live_stripe_secret_key');
define('STRIPE_PUBLISHABLE_KEY', 'pk_live_your_live_stripe_publishable_key');
define('STRIPE_WEBHOOK_SECRET', 'whsec_your_actual_webhook_secret');

// Printful Configuration
define('PRINTFUL_API_KEY', 'your_printful_api_key');
define('PRINTFUL_STORE_ID', '12587389');

// Site Configuration
define('SITE_URL', 'https://www.memorywindow.com');
```

## File Upload Checklist

### Required Files to Upload:
- [ ] `index.html` - Main application
- [ ] `stripe_webhook.php` - Webhook handler
- [ ] `create_checkout.php` - Checkout creation
- [ ] `config.php` - Production configuration
- [ ] `admin_orders.php` - Order management
- [ ] `admin_products.php` - Product management
- [ ] `PriceManager.php` - Price handling
- [ ] `src/` directory - JavaScript files
- [ ] `dist/` directory - Firebase bundles
- [ ] `firebase-config.php` - Firebase configuration

### Database Setup:
- [ ] Create MySQL database
- [ ] Import database schema
- [ ] Update database credentials in config.php

## Testing Checklist

### Before Going Live:
- [ ] Test webhook endpoint: `https://www.memorywindow.com/stripe_webhook.php`
- [ ] Place a test order
- [ ] Verify order appears in database
- [ ] Verify order is sent to Printful
- [ ] Check admin orders page works
- [ ] Test product sync functionality

### Post-Deployment:
- [ ] Monitor webhook delivery in Stripe dashboard
- [ ] Check Printful dashboard for new orders
- [ ] Verify SSL certificate is working
- [ ] Test all payment flows

## Security Considerations

- [ ] Use HTTPS for all requests
- [ ] Keep API keys secure
- [ ] Regularly update dependencies
- [ ] Monitor for security vulnerabilities
- [ ] Set up proper file permissions

## Monitoring

- [ ] Set up error logging
- [ ] Monitor webhook delivery failures
- [ ] Track order processing times
- [ ] Monitor Printful API usage

## Support

If you encounter issues:
1. Check webhook logs in Stripe dashboard
2. Verify all configuration values
3. Test with a simple order first
4. Check server error logs
