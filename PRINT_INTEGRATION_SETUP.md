# 🖨️ Printful + Stripe Integration Setup Guide

## 🎯 Overview

MemoWindow now supports ordering physical prints through:
- **Stripe** for secure payment processing
- **Printful** for high-quality print fulfillment
- **Automatic workflow** from payment to print production

## 🔧 Setup Required

### 1. Stripe Setup
1. **Create Stripe account** at [stripe.com](https://stripe.com)
2. **Get API keys** from Stripe Dashboard → Developers → API Keys
3. **Update `config.php`**:
   ```php
   define('STRIPE_PUBLISHABLE_KEY', 'pk_test_your_actual_key');
   define('STRIPE_SECRET_KEY', 'sk_test_your_actual_key');
   ```

### 2. Printful Setup  
1. **Create Printful account** at [printful.com](https://printful.com)
2. **Get API key** from Printful Dashboard → Settings → API
3. **Update `config.php`**:
   ```php
   define('PRINTFUL_API_KEY', 'your_actual_printful_key');
   ```

### 3. Install Stripe PHP SDK
```bash
cd /Applications/XAMPP/xamppfiles/htdocs/memowindow
composer require stripe/stripe-php
```

### 4. Configure Webhook
1. **In Stripe Dashboard** → Developers → Webhooks
2. **Add endpoint**: `https://yourdomain.com/memowindow/stripe_webhook.php`
3. **Select events**: `checkout.session.completed`
4. **Copy webhook secret** and update `stripe_webhook.php`:
   ```php
   $endpoint_secret = 'whsec_your_actual_webhook_secret';
   ```

## 📦 Product Configuration

### Current Products (in `config.php`):
- **18" × 24" Memory Frame** - $25.00
- **12" × 16" Memory Frame** - $18.00  
- **16" × 20" Canvas Print** - $45.00

### To Add More Products:
1. **Get Printful product IDs** from their API or dashboard
2. **Add to `$PRINT_PRODUCTS` array** in `config.php`
3. **Set pricing** in cents (e.g., 2500 = $25.00)

## 🛒 How It Works

### User Experience:
1. **Create MemoryWave** → Upload voice, generate waveform
2. **See order options** → Products appear below results
3. **Click "Order Now"** → Redirects to Stripe checkout
4. **Complete payment** → Secure Stripe processing
5. **Order confirmed** → Automatic Printful order creation
6. **Receive print** → High-quality physical memory

### Technical Flow:
1. **User clicks order** → `create_checkout.php` creates Stripe session
2. **Payment completed** → Stripe webhook triggers `stripe_webhook.php`
3. **Order sent to Printful** → Automatic print production
4. **Database updated** → Order tracking record created
5. **User redirected** → Success page with order details

## 🗄️ Database Tables

### New `orders` Table:
- `id` - Auto-increment primary key
- `stripe_session_id` - Stripe payment session
- `user_id` - Firebase user ID
- `memory_id` - Associated MemoryWave
- `product_id` - Product type ordered
- `printful_order_id` - Printful order reference
- `customer_email` - Customer email
- `customer_name` - Customer name
- `amount_paid` - Payment amount in cents
- `status` - Order status
- `created_at` - Order timestamp

## 🔒 Security Features

### Payment Security:
- **Stripe handles all payments** - No card data on your server
- **Webhook verification** - Ensures legitimate payment notifications
- **User authentication** - Only signed-in users can order

### Order Security:
- **User-specific orders** - Users only see their own orders
- **Payment verification** - Orders only created after successful payment
- **Printful integration** - Secure API communication

## 🎨 UI Integration

### Order Interface:
- **Appears after MemoryWave creation** - Seamless workflow
- **Product cards** - Visual product selection
- **Responsive design** - Works on all devices
- **Clear pricing** - Transparent cost display

## 🚀 Next Steps

1. **Update configuration** with your Stripe and Printful keys
2. **Install Composer dependencies** for Stripe PHP
3. **Configure webhook** in Stripe dashboard
4. **Test with Stripe test mode** before going live
5. **Customize products** in `config.php` as needed

**MemoWindow is now ready for print commerce integration!** 🖨️💕
