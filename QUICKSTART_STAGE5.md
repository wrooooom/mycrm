# 🚀 Stage 5 Quick Start Guide

Quick setup guide for Stage 5 integrations.

## Prerequisites

- Stage 4 completed (production-ready base system)
- PHP 8.0+
- MySQL 8.0+
- Access to integration providers (optional for testing)

## Installation

### 1. Run Migration

```bash
cd /home/engine/project
php scripts/apply_stage5_migration.php
```

Expected output:
```
✓ Stage 5 Migration completed successfully!
✓ 12 new tables created
✓ 8 integration settings initialized
```

### 2. Configure Environment

Copy the new environment variables to your `.env`:

```bash
# Add Stage 5 variables from .env.example
cat >> .env <<'EOF'

# ========================================
# STAGE 5: Integration Configurations
# ========================================

# SMS Integration (Start with test mode)
SMS_PROVIDER=smsru
SMS_API_KEY=
SMS_FROM_NUMBER=CRM
SMS_TEST_MODE=true

# Email Integration
MAIL_TEST_MODE=true
MAIL_QUEUE_ENABLED=true

# Payment Gateway
PAYMENT_PROVIDER=yandex
PAYMENT_TEST_MODE=true
YANDEX_KASSA_SHOP_ID=
YANDEX_KASSA_API_KEY=
YANDEX_KASSA_SECRET_KEY=

# Push Notifications
FCM_API_KEY=
FCM_SENDER_ID=
FCM_TEST_MODE=true

# Telegram Bot
TELEGRAM_BOT_TOKEN=
TELEGRAM_TEST_MODE=true

# ERP/1C Integration
ERP_TYPE=1c
ERP_API_URL=
ERP_API_KEY=
ERP_TEST_MODE=true

# GPS Tracking
GPS_TRACKING_ENABLED=false
GPS_UPDATE_INTERVAL=30

# Export
EXPORT_PATH=/var/www/html/exports
EXPORT_MAX_ROWS=10000
EOF
```

### 3. Create Exports Directory

```bash
mkdir -p exports
chmod 755 exports
```

### 4. Test in Test Mode

All integrations work in test mode without external API keys:

```bash
# Test SMS (in test mode, no real SMS sent)
curl -X POST http://localhost/api/sms.php?action=send \
  -H "Content-Type: application/json" \
  -H "Cookie: PHPSESSID=your_session" \
  -d '{"phone": "+79991234567", "message": "Test message"}'

# Test Email (in test mode, no real email sent)
curl -X POST http://localhost/api/email.php?action=send \
  -H "Content-Type: application/json" \
  -H "Cookie: PHPSESSID=your_session" \
  -d '{"to": "test@example.com", "subject": "Test", "body": "Test"}'

# Test Export
curl -X POST http://localhost/api/export.php?type=applications&format=csv \
  -H "Content-Type: application/json" \
  -H "Cookie: PHPSESSID=your_session" \
  -d '{}'
```

## Enabling Integrations

### SMS Integration (SMS.ru)

1. Register at https://sms.ru
2. Get API key from dashboard
3. Update `.env`:
   ```env
   SMS_PROVIDER=smsru
   SMS_API_KEY=your_actual_api_key
   SMS_FROM_NUMBER=YourCompany
   SMS_TEST_MODE=false
   ```
4. Test: Send a real SMS

### Email Integration (Gmail)

1. Enable 2FA on Google account
2. Generate App Password: https://myaccount.google.com/apppasswords
3. Update `.env`:
   ```env
   MAIL_HOST=smtp.gmail.com
   MAIL_PORT=587
   MAIL_USERNAME=your-email@gmail.com
   MAIL_PASSWORD=your-app-password
   MAIL_ENCRYPTION=tls
   MAIL_TEST_MODE=false
   ```
4. Test: Send a real email

### Payment Gateway (Yandex.Kassa)

1. Register at https://yookassa.ru
2. Get Shop ID and Secret Key
3. Update `.env`:
   ```env
   PAYMENT_PROVIDER=yandex
   YANDEX_KASSA_SHOP_ID=your_shop_id
   YANDEX_KASSA_API_KEY=your_api_key
   YANDEX_KASSA_SECRET_KEY=your_secret_key
   PAYMENT_TEST_MODE=false
   ```
4. Set webhook URL: `https://your-domain.com/api/payment-gateway.php?action=webhook`

### Push Notifications (Firebase)

1. Create project at https://console.firebase.google.com
2. Get Server Key from Project Settings > Cloud Messaging
3. Update `.env`:
   ```env
   FCM_API_KEY=your_server_key
   FCM_SENDER_ID=your_sender_id
   FCM_TEST_MODE=false
   ```
4. Register device tokens from mobile app

### Telegram Bot

1. Create bot with @BotFather
2. Get bot token
3. Update `.env`:
   ```env
   TELEGRAM_BOT_TOKEN=your_bot_token
   TELEGRAM_TEST_MODE=false
   ```
4. Set webhook:
   ```bash
   curl -X POST "https://api.telegram.org/bot<TOKEN>/setWebhook" \
     -d "url=https://your-domain.com/api/telegram-webhook.php"
   ```

## Verification

### Check Tables

```bash
mysql -u crm_user -p crm_proftransfer -e "
  SELECT TABLE_NAME, TABLE_ROWS 
  FROM information_schema.TABLES 
  WHERE TABLE_SCHEMA = 'crm_proftransfer' 
  AND TABLE_NAME LIKE '%log' 
  OR TABLE_NAME LIKE '%token%' 
  OR TABLE_NAME = 'integration_settings';
"
```

Should show:
- sms_log
- email_log
- payment_transactions
- device_tokens
- push_notification_log
- erp_sync_log
- webhook_events
- telegram_users
- notification_queue
- export_jobs
- gps_tracking
- integration_settings (8 rows)

### Check Logs

```bash
# Check if integrations are being logged
tail -f logs/app-$(date +%Y-%m-%d).log | grep -i "sms\|email\|payment"
```

## Common Issues

### SMS not sending
- Check SMS_API_KEY is correct
- Verify phone number format: +79991234567
- Check balance on SMS provider
- Review sms_log table for errors

### Email not sending
- Verify SMTP credentials
- Check port 587 is not blocked
- For Gmail: use App Password
- Review email_log table

### Payment webhook not working
- Ensure webhook URL is accessible from internet
- Verify HTTPS is enabled
- Check webhook_events table
- Test signature verification

## Next Steps

1. **Read Full Documentation**
   - [INTEGRATIONS.md](INTEGRATIONS.md) - Comprehensive guide
   - [STAGE5_SUMMARY.md](STAGE5_SUMMARY.md) - Feature overview

2. **Configure Required Integrations**
   - Start with SMS and Email (most commonly used)
   - Add payment gateway if needed
   - Enable others as required

3. **Test Each Integration**
   - Start in test mode
   - Move to production gradually
   - Monitor logs

4. **Monitor Performance**
   - Check integration logs
   - Monitor API rate limits
   - Review error rates
   - Track costs (SMS, etc.)

## Production Checklist

- [ ] All required API keys configured
- [ ] Test mode disabled for active integrations
- [ ] Webhooks configured with HTTPS
- [ ] Integration logs monitored
- [ ] Error alerts set up
- [ ] Backup configured
- [ ] Documentation reviewed
- [ ] Team trained on new features

## Support

For issues or questions:

1. Check [INTEGRATIONS.md](INTEGRATIONS.md) troubleshooting section
2. Review integration provider documentation
3. Check application logs in `logs/` directory
4. Review integration-specific log tables

---

**🎉 Stage 5 Complete - Enterprise Integration Platform Ready!**

Version: 5.0.0 | Status: Production Ready
