# 🔌 CRM.PROFTRANSFER - Integrations Guide

Comprehensive guide for configuring and using all integration features in Stage 5.

## 📋 Table of Contents

- [Overview](#overview)
- [SMS Integration](#sms-integration)
- [Email Integration](#email-integration)
- [Payment Gateway](#payment-gateway)
- [Push Notifications](#push-notifications)
- [Telegram Bot](#telegram-bot)
- [ERP/1C Integration](#erp1c-integration)
- [GPS Tracking](#gps-tracking)
- [Export Service](#export-service)
- [Security](#security)
- [Troubleshooting](#troubleshooting)

---

## Overview

Stage 5 introduces comprehensive integration support for external services and systems. All integrations support:

- ✅ Test mode for development
- ✅ Comprehensive logging
- ✅ Error handling and retries
- ✅ Webhook support (where applicable)
- ✅ Activity logging and auditing

### Quick Start

1. Run the Stage 5 migration:
   ```bash
   php scripts/apply_stage5_migration.php
   ```

2. Copy `.env.example` to `.env` and configure your integration settings

3. Enable integrations in the admin panel or database

---

## SMS Integration

Send SMS notifications to drivers, clients, and managers.

### Supported Providers

- **SMS.ru** (Russian provider, recommended for RU/BY)
- **Twilio** (International provider)

### Configuration

#### SMS.ru Setup

1. Register at https://sms.ru
2. Get your API key from dashboard
3. Configure `.env`:

```env
SMS_PROVIDER=smsru
SMS_API_KEY=your_api_key_here
SMS_FROM_NUMBER=YourCompany
SMS_TEST_MODE=false
```

#### Twilio Setup

1. Register at https://twilio.com
2. Get Account SID and Auth Token
3. Configure `.env`:

```env
SMS_PROVIDER=twilio
TWILIO_ACCOUNT_SID=your_account_sid
TWILIO_AUTH_TOKEN=your_auth_token
TWILIO_FROM_NUMBER=+1234567890
SMS_TEST_MODE=false
```

### Usage

#### API Endpoint

```http
POST /api/sms.php?action=send
Content-Type: application/json

{
  "phone": "+79991234567",
  "message": "Your order #123 has been assigned",
  "application_id": 123
}
```

#### PHP Code

```php
require_once 'includes/integrations/SmsProvider.php';

$smsProvider = SmsProvider::create();

$result = $smsProvider->send(
    '+79991234567',
    'Your order has been assigned',
    [
        'user_id' => $userId,
        'application_id' => $applicationId
    ]
);

if ($result['success']) {
    echo "SMS sent! Message ID: " . $result['message_id'];
}
```

### Use Cases

1. **New order assigned to driver**
2. **Order status changed**
3. **Payment confirmation**
4. **Urgent notifications**

### Logging

All SMS are logged in the `sms_log` table with:
- Delivery status
- Provider message ID
- Cost tracking
- Error messages

---

## Email Integration

Send HTML emails with templates and attachments.

### Configuration

```env
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@proftransfer.com
MAIL_FROM_NAME="CRM.PROFTRANSFER"
MAIL_TEST_MODE=false
```

### Gmail Setup

1. Enable 2FA on your Google account
2. Generate App Password: https://myaccount.google.com/apppasswords
3. Use the app password in `MAIL_PASSWORD`

### Usage

#### Send Simple Email

```http
POST /api/email.php?action=send
Content-Type: application/json

{
  "to": "user@example.com",
  "subject": "Order Confirmation",
  "body": "<h1>Your order is confirmed</h1>",
  "is_html": true
}
```

#### Send Template Email

```http
POST /api/email.php?action=send_template
Content-Type: application/json

{
  "to": "driver@example.com",
  "template": "application_assigned",
  "data": {
    "driver": {...},
    "application": {...}
  }
}
```

### Email Templates

Located in `templates/emails/`:

- `user_registration.php` - New user welcome email
- `password_reset.php` - Password reset link
- `application_assigned.php` - New order assigned
- `application_status_changed.php` - Status update (create this)
- `payment_notification.php` - Payment receipt (create this)
- `weekly_report.php` - Manager weekly report (create this)

### Creating Custom Templates

Create a new PHP file in `templates/emails/`:

```php
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        /* Your styles */
    </style>
</head>
<body>
    <h1>Hello <?= htmlspecialchars($user['name']) ?></h1>
    <p>Your custom content here</p>
</body>
</html>
```

---

## Payment Gateway

Accept payments via Yandex.Kassa or Stripe.

### Supported Providers

- **Yandex.Kassa (YooKassa)** - for Russian market
- **Stripe** - for international payments

### Yandex.Kassa Setup

1. Register at https://yookassa.ru
2. Get Shop ID and Secret Key
3. Configure `.env`:

```env
PAYMENT_PROVIDER=yandex
PAYMENT_TEST_MODE=true
YANDEX_KASSA_SHOP_ID=your_shop_id
YANDEX_KASSA_API_KEY=your_api_key
YANDEX_KASSA_SECRET_KEY=your_secret_key
```

4. Set up webhook:
   - URL: `https://your-domain.com/api/payment-gateway.php?action=webhook`
   - Events: `payment.succeeded`, `payment.canceled`

### Stripe Setup

1. Register at https://stripe.com
2. Get API keys from Dashboard
3. Configure `.env`:

```env
PAYMENT_PROVIDER=stripe
PAYMENT_TEST_MODE=true
STRIPE_API_KEY=sk_test_...
STRIPE_SECRET_KEY=your_webhook_secret
```

### Usage

#### Create Payment

```http
POST /api/payment-gateway.php?action=create
Content-Type: application/json

{
  "amount": 5000.00,
  "application_id": 123,
  "description": "Payment for order #APP-001",
  "customer_email": "customer@example.com",
  "return_url": "https://your-site.com/payment-success"
}
```

Response:
```json
{
  "success": true,
  "transaction_id": "2d8e87a8-...",
  "payment_link": "https://yookassa.ru/checkout/...",
  "status": "pending"
}
```

#### Check Payment Status

```http
GET /api/payment-gateway.php?action=status&transaction_id=2d8e87a8-...
```

#### Refund Payment

```http
POST /api/payment-gateway.php?action=refund
Content-Type: application/json

{
  "transaction_id": "2d8e87a8-...",
  "amount": 1000.00
}
```

### Webhook Handling

Webhooks are automatically processed and logged in `webhook_events` table.

Payment status updates are saved in `payment_transactions` table.

---

## Push Notifications

Send push notifications to mobile devices via Firebase Cloud Messaging (FCM).

### Firebase Setup

1. Create project at https://console.firebase.google.com
2. Get Server Key from Project Settings > Cloud Messaging
3. Configure `.env`:

```env
FCM_API_KEY=your_server_key
FCM_SENDER_ID=your_sender_id
FCM_TEST_MODE=false
```

### Usage

#### Register Device Token

From mobile app or web app:

```http
POST /api/push-notifications.php?action=register_token
Content-Type: application/json

{
  "token": "device_fcm_token_here",
  "device_type": "android",
  "device_name": "Samsung Galaxy S21"
}
```

#### Send Push Notification

```http
POST /api/push-notifications.php?action=send
Content-Type: application/json

{
  "user_id": 123,
  "title": "New Order",
  "body": "Order #APP-001 assigned to you",
  "data": {
    "type": "application",
    "application_id": 123,
    "action": "view"
  }
}
```

### PHP Usage

```php
require_once 'includes/integrations/PushNotification.php';

$push = new PushNotification();

$push->sendApplicationNotification($userId, $application);
$push->sendStatusChangeNotification($userId, $application, 'inwork');
$push->sendUrgentMessage($userId, 'Important update!');
```

---

## Telegram Bot

Integrate Telegram bot for managers and dispatchers.

### Setup

1. Create bot with [@BotFather](https://t.me/botfather)
2. Get bot token
3. Configure `.env`:

```env
TELEGRAM_BOT_TOKEN=1234567890:ABCdefGHIjklMNOpqrSTUvwxYZ
TELEGRAM_CHAT_ID=your_chat_id
TELEGRAM_TEST_MODE=false
```

4. Set webhook:

```bash
curl -X POST "https://api.telegram.org/bot<YOUR_BOT_TOKEN>/setWebhook" \
  -d "url=https://your-domain.com/api/telegram-webhook.php"
```

### Available Commands

- `/start` - Welcome message and help
- `/status` - Active applications count
- `/today` - Applications for today
- `/drivers` - Driver status summary
- `/earnings` - Revenue statistics
- `/alerts` - Urgent notifications

### Usage

Users must link their Telegram account:

```php
$bot = new TelegramBot();
$bot->linkUser($userId, $chatId, $username, $firstName, $lastName);
```

### Sending Messages

```php
$bot = new TelegramBot();
$bot->sendMessage($chatId, 'Your message here');
```

---

## ERP/1C Integration

Sync data with 1C or other ERP systems.

### Configuration

```env
ERP_TYPE=1c
ERP_API_URL=https://your-1c-server.com/api
ERP_API_KEY=your_api_key
ERP_TEST_MODE=true
ERP_AUTO_SYNC=false
ERP_SYNC_INTERVAL=3600
```

### Usage

#### Sync Single Entity

```http
POST /api/erp-sync.php
Content-Type: application/json

{
  "entity_type": "application",
  "entity_id": 123
}
```

#### Get Sync Status

```http
GET /api/erp-sync.php?action=status&entity_type=application&entity_id=123
```

### PHP Usage

```php
require_once 'includes/integrations/ErpSync.php';

$erpSync = new ErpSync();

// Sync application
$result = $erpSync->syncApplication($applicationId);

// Sync company
$result = $erpSync->syncCompany($companyId);

// Sync driver
$result = $erpSync->syncDriver($driverId);

// Sync payment
$result = $erpSync->syncPayment($paymentId);
```

### Sync Log

All sync operations are logged in `erp_sync_log` table with:
- Request/response data
- ERP entity IDs
- Error messages
- Sync timestamp

---

## GPS Tracking

Track driver locations in real-time.

### Configuration

```env
GPS_TRACKING_ENABLED=true
GPS_UPDATE_INTERVAL=30
GPS_STORE_HISTORY_DAYS=90
```

### Sending GPS Data

```http
POST /api/tracking.php?action=update_location
Content-Type: application/json

{
  "driver_id": 123,
  "application_id": 456,
  "latitude": 55.7558,
  "longitude": 37.6173,
  "accuracy": 10.5,
  "speed": 60.0,
  "heading": 180.0,
  "battery_level": 75
}
```

### Retrieving GPS History

```http
GET /api/tracking.php?action=history&driver_id=123&date=2024-01-15
```

### Database

GPS data is stored in `gps_tracking` table and automatically cleaned after configured retention period.

---

## Export Service

Export data in multiple formats: CSV, Excel, PDF, JSON.

### Configuration

```env
EXPORT_PATH=/var/www/html/exports
EXPORT_MAX_ROWS=10000
EXPORT_TTL=86400
```

### Usage

```http
POST /api/export.php?type=applications&format=csv
Content-Type: application/json

{
  "filters": {
    "status": "completed",
    "date_from": "2024-01-01",
    "date_to": "2024-01-31",
    "limit": 5000
  }
}
```

Response:
```json
{
  "success": true,
  "filename": "applications_2024-01-15_10-30-00.csv",
  "download_url": "/exports/applications_2024-01-15_10-30-00.csv",
  "rows": 1234
}
```

### Supported Export Types

- `applications` - Orders/applications
- `drivers` - Drivers list
- `vehicles` - Vehicles list
- `payments` - Payment transactions

### Formats

- **CSV** - UTF-8 with BOM, semicolon delimiter
- **Excel** - Same as CSV (basic implementation)
- **PDF** - HTML-based PDF export
- **JSON** - Pretty-printed JSON

---

## Security

### Webhook Signature Verification

All webhook endpoints verify signatures using HMAC-SHA256:

```php
$gateway = PaymentGateway::create();
if (!$gateway->verifyWebhook($payload, $signature)) {
    http_response_code(403);
    exit();
}
```

### API Key Storage

Store sensitive API keys in `.env` file. **Never commit `.env` to git!**

### Rate Limiting

All API endpoints are protected by rate limiting (configured in Stage 4).

### HTTPS Required

Always use HTTPS in production for:
- Webhooks
- API endpoints
- Payment links

---

## Troubleshooting

### SMS Not Sending

1. Check SMS provider credentials in `.env`
2. Verify phone number format (international format: +79991234567)
3. Check `sms_log` table for error messages
4. Enable test mode: `SMS_TEST_MODE=true`
5. Check SMS provider balance

### Email Not Sending

1. Verify SMTP credentials
2. Check if port 587 is not blocked by firewall
3. For Gmail: use App Password, not regular password
4. Check `email_log` table for errors
5. Enable test mode: `MAIL_TEST_MODE=true`

### Payment Webhook Not Working

1. Verify webhook URL is accessible from internet
2. Check webhook signature verification
3. Review `webhook_events` table
4. Check payment provider dashboard for webhook delivery status
5. Ensure HTTPS is configured

### Push Notifications Not Delivered

1. Verify FCM API key
2. Check device token is registered in `device_tokens`
3. Verify device token is still valid (not expired)
4. Check `push_notification_log` for errors
5. Test with FCM testing tool

### Telegram Bot Not Responding

1. Verify bot token is correct
2. Check webhook is set correctly
3. Use `getWebhookInfo` API to check webhook status
4. Review bot logs in `telegram_users` table
5. Test bot commands directly in Telegram

### ERP Sync Failing

1. Check ERP API URL is accessible
2. Verify API credentials
3. Review `erp_sync_log` for detailed errors
4. Enable test mode: `ERP_TEST_MODE=true`
5. Check ERP system logs

### Export File Not Generated

1. Verify `exports/` directory exists and is writable
2. Check export limits in `.env`
3. Review application logs for errors
4. Test with smaller dataset first

---

## Best Practices

### Development

- Always use test mode during development
- Test all integrations with sandbox/test accounts
- Review logs regularly
- Handle errors gracefully

### Production

- Disable test modes: `*_TEST_MODE=false`
- Monitor integration logs
- Set up alerts for failed transactions
- Regular backup of integration logs
- Keep API credentials secure
- Rotate API keys periodically

### Performance

- Use queue system for bulk notifications
- Implement retry logic for failed operations
- Clean old logs regularly
- Monitor API rate limits
- Cache integration responses where applicable

---

## Support

For integration issues:

1. Check logs in `logs/` directory
2. Review integration-specific log tables
3. Consult provider documentation
4. Contact provider support if needed

---

**© 2024 CRM.PROFTRANSFER - Stage 5 Integrations**
