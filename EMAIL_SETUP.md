# Email Notification System Setup

## Overview
The MemoWindow email notification system sends automated emails for:
- Payment confirmations
- Subscription confirmations and cancellations
- Order confirmations

## Configuration

### 1. Environment Variables
Add these variables to your environment configuration:

```bash
# Email Configuration
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=your_email@gmail.com
SMTP_PASSWORD=your_app_password_here
FROM_EMAIL=noreply@memowindow.com
FROM_NAME=MemoWindow
SITE_URL=https://memowindow.com
```

### 2. Gmail Setup (Recommended)
1. Enable 2-Factor Authentication on your Gmail account
2. Generate an App Password:
   - Go to Google Account settings
   - Security → 2-Step Verification → App passwords
   - Generate a password for "Mail"
   - Use this password in `SMTP_PASSWORD`

### 3. Alternative SMTP Providers
You can use other SMTP providers by changing the configuration:

**SendGrid:**
```bash
SMTP_HOST=smtp.sendgrid.net
SMTP_PORT=587
SMTP_USERNAME=apikey
SMTP_PASSWORD=your_sendgrid_api_key
```

**Mailgun:**
```bash
SMTP_HOST=smtp.mailgun.org
SMTP_PORT=587
SMTP_USERNAME=your_mailgun_username
SMTP_PASSWORD=your_mailgun_password
```

## Testing

### 1. Test the Email System
Visit: `http://localhost:8000/test_email_notifications.php`

This will test all email types:
- Payment confirmation
- Subscription confirmation
- Subscription cancellation
- Order confirmation

### 2. Check Email Logs
The system logs email sending attempts to your server's error log:
- Success: `✅ Email sent successfully to: user@example.com`
- Failure: `❌ Failed to send email to: user@example.com`

## Email Templates

### Payment Confirmation
- Sent when a payment is processed
- Includes amount, transaction ID, payment method
- Links to orders page

### Subscription Confirmation
- Sent when a subscription is created/updated
- Includes plan details, billing cycle, next billing date
- Links to subscription management

### Subscription Cancellation
- Sent when a subscription is cancelled
- Includes access end date
- Links to reactivation

### Order Confirmation
- Sent when an order is placed
- Includes order details, product info
- Links to order tracking

## Troubleshooting

### Common Issues

1. **Emails not sending**
   - Check SMTP credentials
   - Verify server can send emails
   - Check firewall settings

2. **Authentication errors**
   - Use App Password for Gmail
   - Enable "Less secure app access" (not recommended)

3. **Emails going to spam**
   - Set up SPF, DKIM, DMARC records
   - Use a dedicated email domain
   - Avoid spam trigger words

### Server Requirements
- PHP `mail()` function enabled
- SMTP access from server
- Proper DNS records for email domain

## Security Notes
- Never commit email credentials to version control
- Use environment variables for sensitive data
- Consider using a dedicated email service for production
- Implement rate limiting for email sending
