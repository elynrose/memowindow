# CAPTCHA Setup Instructions

## Google reCAPTCHA v2 Configuration

The invitation form now includes Google reCAPTCHA v2 to prevent spam submissions.

### Current Configuration (Test Keys)

**Site Key (Frontend):** `6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI`
**Secret Key (Backend):** `6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe`

⚠️ **These are Google's test keys for development only!**

### Production Setup

1. **Register your site** at [Google reCAPTCHA Admin Console](https://www.google.com/recaptcha/admin)
2. **Create a new site** with reCAPTCHA v2 "I'm not a robot" Checkbox
3. **Add your domains** (e.g., `yourdomain.com`, `www.yourdomain.com`)
4. **Get your keys** and update the following files:

#### Files to Update:

1. **record_memory.php** (Line 450):
   ```html
   <div class="g-recaptcha" data-sitekey="YOUR_SITE_KEY_HERE"></div>
   ```

2. **upload_invitation_audio.php** (Line 66):
   ```php
   $captchaSecret = 'YOUR_SECRET_KEY_HERE';
   ```

### Features Implemented

✅ **CAPTCHA Validation**
- Client-side validation before form submission
- Server-side verification with Google's API
- Clear error messages for failed validation

✅ **Email Validation**
- For non-public invitations, email must match invited email
- Case-insensitive comparison
- Visual warning displayed to users
- Pre-filled email field for non-public invitations

✅ **Enhanced Security**
- Prevents automated spam submissions
- Ensures only invited users can submit (when not public)
- Comprehensive validation on both client and server

### Testing

The current test keys will always pass validation, making development easier. For production, replace with your actual keys from Google reCAPTCHA.
