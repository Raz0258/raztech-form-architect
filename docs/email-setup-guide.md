# Email Setup Guide - SmartForms AI

SmartForms AI sends automated email responses to form submitters. This guide helps you configure reliable email delivery.

## Why SMTP Configuration?

WordPress default email (PHP `mail()` function) is unreliable:
- Often blocked by hosting providers
- Emails go to spam folders
- No delivery guarantees
- Works differently on each host

**SMTP (Simple Mail Transfer Protocol) solves this** by using professional email services.

## What You'll Need

- WordPress admin access
- 5-10 minutes
- Email service (Gmail, SendGrid, or your hosting provider)

## Recommended: WP Mail SMTP Plugin

**Best choice:** WP Mail SMTP by WPForms (5M+ active installs, free)

### Why This Plugin?

✅ Free and trusted (5M+ users)
✅ Easy setup wizard
✅ Supports all major email providers
✅ Built-in email testing
✅ No coding required

## Setup Instructions

### Step 1: Install WP Mail SMTP

1. Go to: **WordPress Admin → Plugins → Add New**
2. Search: "WP Mail SMTP"
3. Find: "WP Mail SMTP by WPForms"
4. Click: **Install Now**
5. Click: **Activate**

### Step 2: Choose Email Provider

After activation, you'll see a setup wizard. Choose your provider:

#### Option A: Gmail (Easiest)

**Best for:** Personal sites, testing, small businesses

**Steps:**
1. In WP Mail SMTP, select: **Gmail**
2. Follow the Google OAuth setup (plugin guides you)
3. Or use App Password (see below)

**Using Gmail App Password:**
1. Go to: https://myaccount.google.com/security
2. Enable: **2-Step Verification**
3. Go to: **App Passwords**
4. Create password for: **Mail → WordPress**
5. Copy the 16-character password
6. In WP Mail SMTP:
   - Select: **Other SMTP**
   - SMTP Host: `smtp.gmail.com`
   - SMTP Port: `587`
   - Encryption: **TLS**
   - Username: `your-email@gmail.com`
   - Password: [paste app password]

#### Option B: SendGrid (Professional)

**Best for:** Professional sites, high volume

**Free Tier:** 100 emails/day (sufficient for most forms)

**Steps:**
1. Sign up: https://sendgrid.com/free/
2. Create API Key:
   - SendGrid Dashboard → Settings → API Keys
   - Create API Key → Full Access
   - Copy the key (starts with "SG.")
3. In WP Mail SMTP:
   - Select: **SendGrid**
   - API Key: [paste your key]
   - Save Settings

#### Option C: Your Hosting Provider

**Best for:** If your host provides SMTP

**Steps:**
1. Contact your hosting support for SMTP details
2. They'll provide:
   - SMTP Host
   - SMTP Port
   - Username
   - Password
3. In WP Mail SMTP:
   - Select: **Other SMTP**
   - Enter the details provided
   - Save Settings

### Step 3: Configure From Email

1. **From Email:** `noreply@yourdomain.com` (or your Gmail)
2. **From Name:** `Your Business Name`
3. **Force From Email:** ✅ Yes
4. **Force From Name:** ✅ Yes

### Step 4: Test Email Delivery

1. In WP Mail SMTP, go to: **Email Test** tab
2. Send To: [your email address]
3. Click: **Send Email**
4. Check your inbox (should arrive within seconds)

**If test succeeds:** ✅ SMTP is configured correctly!

### Step 5: Configure SmartForms AI

1. Go to: **SmartForms AI → Settings**
2. **Auto-Response Settings:**
   - Enable auto-responses: ✅
   - From Name: Your Business Name
   - From Email: (same as WP Mail SMTP)
   - Reply-To Email: (your support email)
3. **Save Settings**

### Step 6: Test SmartForms AI Emails

1. Create a test form or use existing form
2. Publish on a page
3. Fill out the form with your email
4. Submit
5. Check your inbox for auto-response

**If email arrives:** ✅ Everything is working!

## Troubleshooting

### Emails Still Not Arriving

**Check Spam Folder:**
- First emails often go to spam
- Mark as "Not Spam" to train filters

**Verify SMTP Settings:**
- In WP Mail SMTP, check connection status
- Resend test email
- Check credentials are correct

**Check Error Logs:**
- WP Mail SMTP → Email Log
- Look for error messages

**Contact Support:**
- WP Mail SMTP has excellent support
- Your hosting provider can help with SMTP issues

### Gmail App Password Not Working

1. Verify 2-Step Verification is enabled
2. Create new App Password
3. Copy password without spaces
4. Use "Other SMTP" (not Gmail option)

### SendGrid Emails Going to Spam

1. Verify domain authentication in SendGrid
2. Add SPF and DKIM records (SendGrid guides you)
3. Takes 24-48 hours for DNS changes

## Best Practices

### Email Deliverability

✅ Use professional From Name
✅ Use real Reply-To address
✅ Don't send from "noreply@gmail.com"
✅ Keep email content professional
✅ Test regularly

### SMTP Provider Choice

**Gmail:** Great for <100 emails/day
**SendGrid:** Great for professional sites
**Your Host:** Works if they support SMTP

## FAQ

**Q: Do I need SMTP for SmartForms AI?**
A: Highly recommended. Default WordPress email is unreliable.

**Q: Will SMTP work for other plugins?**
A: Yes! Once configured, ALL WordPress emails use SMTP (WooCommerce, contact forms, etc.)

**Q: Is WP Mail SMTP free?**
A: Yes, free version is sufficient for most sites.

**Q: Can I use my business email?**
A: Yes! Configure your business email as SMTP sender.

**Q: What if I change hosting?**
A: SMTP configuration is site-specific. Reconfigure on new host.

## Support

**WP Mail SMTP Support:**
- https://wordpress.org/support/plugin/wp-mail-smtp/

**SmartForms AI Support:**
- Contact through plugin support forum

**Hosting Provider:**
- Contact for SMTP credentials and help

## Summary

1. ✅ Install WP Mail SMTP (free)
2. ✅ Choose provider (Gmail/SendGrid/Host)
3. ✅ Configure settings
4. ✅ Test email delivery
5. ✅ Configure SmartForms AI
6. ✅ Test auto-responses
7. ✅ Done! Reliable email delivery 🎉

**Setup Time:** 5-10 minutes
**Cost:** Free (Gmail/SendGrid free tiers)
**Benefit:** Professional, reliable email delivery
