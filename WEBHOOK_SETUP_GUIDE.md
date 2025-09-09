# Stripe Webhook Setup Guide for MemoWindow

## Overview
This guide will help you set up the Stripe webhook to automatically send orders to Printful when customers complete their payments.

## Step 1: Create Webhook Endpoint in Stripe Dashboard

### For Development/Testing:
1. Go to: https://dashboard.stripe.com/test/webhooks
2. Click **"Add endpoint"**
3. **Endpoint URL**: `http://localhost/memowindow/stripe_webhook.php`
4. **Events to send**: Select `checkout.session.completed`
5. Click **"Add endpoint"**

### For Production:
1. Go to: https://dashboard.stripe.com/webhooks
2. Click **"Add endpoint"**
3. **Endpoint URL**: `https://www.memorywindow.com/stripe_webhook.php`
4. **Events to send**: Select `checkout.session.completed`
5. Click **"Add endpoint"**

## Step 2: Get the Webhook Secret

After creating the endpoint:
1. Click on your newly created webhook
2. In the **"Signing secret"** section, click **"Reveal"**
3. Copy the secret (starts with `whsec_...`)

## Step 3: Update Configuration

### For Development:
Update `config.php` line 11:
```php
define('STRIPE_WEBHOOK_SECRET', 'whsec_your_actual_webhook_secret_here');
```

### For Production:
Update your production config file with the production webhook secret.

## Step 4: Test the Webhook

### Using Stripe CLI (Recommended):
```bash
# Install Stripe CLI
# Then forward events to your local server
stripe listen --forward-to localhost/memowindow/stripe_webhook.php
```

### Manual Testing:
1. Place a test order in your app
2. Complete the payment
3. Check your database for the order
4. Check Printful dashboard for the new order

## Step 5: Verify Integration

After setup, when a customer places an order:

1. **Order Created**: Order is saved to your database with `status = 'pending'`
2. **Payment Completed**: Stripe sends webhook to `stripe_webhook.php`
3. **Printful Order**: Webhook automatically creates order in Printful
4. **Database Updated**: Order status changes to `processing` and gets Printful order ID

## Troubleshooting

### Webhook Not Working:
1. Check webhook secret is correct
2. Verify endpoint URL is accessible
3. Check Stripe dashboard for webhook delivery attempts
4. Look at webhook logs for error messages

### Orders Not Going to Printful:
1. Verify Printful API key is correct
2. Check that variant IDs match your synced products
3. Ensure image URLs are accessible
4. Check webhook logs for Printful API errors

### Common Issues:
- **"Invalid signature"**: Wrong webhook secret
- **"This item is discontinued"**: Wrong variant ID
- **"Item can't be submitted without print files"**: Image URL not accessible

## Security Notes

- Never commit webhook secrets to version control
- Use environment variables in production
- Regularly rotate your API keys
- Monitor webhook delivery in Stripe dashboard

## Production Checklist

- [ ] Webhook endpoint created in Stripe dashboard
- [ ] Webhook secret updated in config
- [ ] Endpoint URL `https://www.memorywindow.com/stripe_webhook.php` is publicly accessible
- [ ] SSL certificate is valid
- [ ] Test order placed and processed successfully
- [ ] Printful order created automatically
- [ ] Database updated with Printful order ID

## Support

If you encounter issues:
1. Check the webhook logs in Stripe dashboard
2. Verify all configuration values
3. Test with a simple order first
4. Check Printful API documentation for any changes
