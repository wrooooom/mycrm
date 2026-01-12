# 🚀 CRM.PROFTRANSFER - Production-Ready Transportation Management System

[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-blue)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0%2B-orange)](https://www.mysql.com/)
[![License](https://img.shields.io/badge/License-Proprietary-red)](LICENSE)
[![Status](https://img.shields.io/badge/Status-Enterprise%20Ready-brightgreen)](https://github.com/yourusername/mycrm)
[![Version](https://img.shields.io/badge/Version-5.0.0-blue)](https://github.com/yourusername/mycrm)

Enterprise-ready CRM system for transportation and logistics management with comprehensive integrations. Built with vanilla PHP, MySQL, and modern JavaScript. Features SMS, Email, Payment Gateway, Push Notifications, Telegram Bot, ERP/1C sync, GPS tracking, and advanced export capabilities.

## ✨ Features

### Core Functionality
- **📋 Application Management** - Complete lifecycle management for transportation orders
- **👨‍💼 Driver Management** - Comprehensive driver profiles and assignment system
- **🚗 Vehicle Management** - Fleet tracking and maintenance scheduling
- **🏢 Company Management** - Multi-company support with hierarchical structure
- **👥 User Management** - Role-based access control (Admin, Dispatcher, Manager, Driver)

### Advanced Features
- **📊 Real-time Analytics** - Dashboard with KPIs, charts, and performance metrics
- **🗺️ Live Tracking** - Yandex Maps integration with real-time GPS tracking
- **💰 Billing & Payments** - Invoice generation and payment tracking
- **🔔 Real-time Notifications** - SSE-based live notifications with browser alerts
- **📱 Mobile Responsive** - Fully optimized for mobile devices (320px+)
- **🌙 Dark Mode** - System-wide dark theme with localStorage persistence
- **📈 Reports & Export** - Comprehensive reporting with CSV/Excel/PDF/JSON export
- **🔧 Maintenance Tracking** - Vehicle maintenance scheduling and history

### 🔌 Stage 5: Enterprise Integrations
- **📱 SMS Integration** - SMS.ru and Twilio support for driver/client notifications
- **📧 Email Integration** - SMTP with HTML templates for automated communications
- **💳 Payment Gateways** - Yandex.Kassa and Stripe integration with webhook support
- **🔔 Push Notifications** - Firebase Cloud Messaging for mobile apps
- **🤖 Telegram Bot** - Command-based bot for managers with statistics
- **🏢 ERP/1C Integration** - Bidirectional sync with external ERP systems
- **📍 GPS Tracking** - Real-time location tracking and history
- **📦 Export Service** - Multi-format data export (CSV, Excel, PDF, JSON)

### Security & Performance
- **🔒 CSRF Protection** - Token-based protection for all forms
- **⚡ Rate Limiting** - API and login protection against abuse
- **🛡️ XSS Prevention** - Input sanitization and validation
- **🔐 Session Security** - Secure cookies with HttpOnly and SameSite
- **📝 Activity Logging** - Comprehensive audit trail
- **💾 Automatic Backups** - Scheduled database backups with compression
- **🚀 Performance Optimized** - Caching, compression, and query optimization

## 🖥️ Technology Stack

### Backend
- **PHP 8.0+** - Modern PHP with strict typing
- **MySQL 8.0+** - Reliable relational database
- **PDO** - Secure database access layer
- **Apache/Nginx** - Web server with security headers

### Frontend
- **Vanilla JavaScript** - No framework dependencies, modular architecture
- **Bootstrap 5** - Responsive UI components
- **Font Awesome 6** - Icon library
- **Chart.js** - Data visualization
- **Yandex Maps API** - Mapping and geolocation

### DevOps
- **Docker** - Containerized deployment
- **Docker Compose** - Multi-container orchestration
- **Git** - Version control
- **Structured Logging** - JSON-based log format

## 📦 Quick Start

### Using Docker (Recommended)

```bash
# Clone repository
git clone https://github.com/yourusername/mycrm.git
cd mycrm

# Configure environment
cp .env.example .env
nano .env  # Edit database credentials

# Start containers
docker-compose up -d

# Access application
open http://localhost
```

### Manual Installation

See [INSTALLATION.md](INSTALLATION.md) for detailed instructions.

## 🔧 Configuration

### Environment Variables

Copy `.env.example` to `.env` and configure:

```env
# Database
DB_HOST=localhost
DB_NAME=crm_db
DB_USER=crm_user
DB_PASSWORD=your_secure_password

# Application
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Features
FEATURE_DARK_MODE=true
FEATURE_2FA=false
REALTIME_ENABLED=true
```

### Security Setup

1. **Change default passwords** immediately after installation
2. **Enable HTTPS** in production (Let's Encrypt recommended)
3. **Configure firewall** to restrict database access
4. **Set secure session** cookies in `.env`
5. **Enable rate limiting** for API endpoints

## 📚 Documentation

- **[Installation Guide](INSTALLATION.md)** - Step-by-step installation
- **[API Documentation](API.md)** - Complete API reference
- **[User Guide](DOCUMENTATION.md)** - Feature documentation
- **[Integrations Guide](INTEGRATIONS.md)** - 🆕 Comprehensive integration setup
- **[Stage 5 Summary](STAGE5_SUMMARY.md)** - 🆕 Stage 5 features and changes
- **[Changelog](CHANGELOG.md)** - Version history and release notes
- **[Stage 3 Implementation](STAGE3_IMPLEMENTATION.md)** - Development history
- **[Stage 4 Summary](STAGE4_SUMMARY.md)** - Production readiness features

## 🎯 System Architecture

```
┌─────────────────────────────────────────────────────────┐
│                    Client Layer                          │
│  (Browser, Mobile, Progressive Web App)                  │
└─────────────────┬───────────────────────────────────────┘
                  │
┌─────────────────▼───────────────────────────────────────┐
│                 Presentation Layer                       │
│  - Responsive UI (Bootstrap 5)                           │
│  - Dark Mode Support                                     │
│  - Mobile Navigation                                     │
│  - Real-time Updates (SSE)                               │
└─────────────────┬───────────────────────────────────────┘
                  │
┌─────────────────▼───────────────────────────────────────┐
│                Application Layer                         │
│  - PHP Controllers                                       │
│  - Security Manager (CSRF, XSS, Rate Limiting)           │
│  - Session Management                                    │
│  - Activity Logger                                       │
└─────────────────┬───────────────────────────────────────┘
                  │
┌─────────────────▼───────────────────────────────────────┐
│                  Data Layer                              │
│  - MySQL 8.0+ with PDO                                   │
│  - Optimized Indexes                                     │
│  - Automated Backups                                     │
│  - Activity Audit Log                                    │
└─────────────────────────────────────────────────────────┘
```

## 🔐 Security Features

### Implemented Security Measures

✅ **CSRF Protection** - All forms protected with rotating tokens  
✅ **XSS Prevention** - Input sanitization and output encoding  
✅ **SQL Injection Protection** - Prepared statements with PDO  
✅ **Rate Limiting** - Per-user and per-IP limits on sensitive operations  
✅ **Session Security** - HttpOnly, Secure, SameSite cookies  
✅ **Security Headers** - CSP, X-Frame-Options, HSTS, etc.  
✅ **Activity Logging** - Comprehensive audit trail  
✅ **Password Hashing** - bcrypt with cost factor 12  
✅ **Input Validation** - Server-side validation for all inputs  
✅ **File Upload Security** - Type and size validation  

### Recommended Production Setup

1. Enable HTTPS with valid SSL certificate
2. Configure strict CSP headers
3. Enable HSTS preloading
4. Regular security audits
5. Keep dependencies updated
6. Monitor security logs

## 📊 Performance Optimizations

- **OpCache** - PHP bytecode caching
- **Gzip Compression** - Reduced transfer sizes
- **Asset Minification** - Compressed CSS/JS
- **Database Indexing** - Optimized query performance
- **Browser Caching** - Static asset caching
- **Lazy Loading** - Images and non-critical resources
- **CDN Ready** - Separate static assets structure

## 🧪 Testing & Quality

### Running Tests

```bash
# Run cleanup (remove debug code)
php scripts/cleanup.php --dry-run

# Create database backup
php scripts/backup.php create

# Check system health
curl http://localhost/health.php
```

### Quality Checklist

- ✅ No debug code in production
- ✅ All forms have CSRF protection
- ✅ Input validation on all endpoints
- ✅ Error handling with user-friendly messages
- ✅ Structured logging enabled
- ✅ Backups configured and tested
- ✅ Security headers configured
- ✅ Mobile responsiveness verified

## 🔄 Backup & Recovery

### Automated Backups

```bash
# Create manual backup
php scripts/backup.php create

# List available backups
php scripts/backup.php list

# Restore from backup
php scripts/backup.php restore backup_crm_db_2024-01-12_10-30-00.sql.gz
```

### Cron Job Setup

```cron
# Daily backup at 2 AM
0 2 * * * /usr/bin/php /var/www/html/crm/scripts/backup.php create

# Weekly cleanup of old logs
0 0 * * 0 find /var/www/html/crm/logs -type f -mtime +30 -delete
```

## 🚀 Deployment

### Production Checklist

- [ ] Configure `.env` with production values
- [ ] Set `APP_DEBUG=false`
- [ ] Enable HTTPS and force redirect
- [ ] Configure security headers
- [ ] Set up automated backups
- [ ] Configure monitoring and alerts
- [ ] Test all critical functionality
- [ ] Change default admin password
- [ ] Configure rate limiting
- [ ] Enable error logging
- [ ] Set up log rotation
- [ ] Configure firewall rules

### Docker Deployment

```bash
# Production build
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d

# View logs
docker-compose logs -f web

# Scale workers
docker-compose up -d --scale worker=3
```

## 📈 Monitoring

### Health Check Endpoint

```bash
# Check system health
curl http://your-domain.com/health.php
```

Response:
```json
{
    "status": "healthy",
    "timestamp": 1705058400,
    "checks": {
        "database": {"status": "healthy"},
        "disk": {"status": "healthy", "usage_percent": 45.2},
        "memory": {"status": "healthy"}
    }
}
```

### Monitoring Tools Integration

- **Prometheus** - Metrics collection
- **Grafana** - Visualization dashboards
- **Sentry** - Error tracking (optional)
- **New Relic** - APM (optional)

## 🤝 Contributing

This is a private/proprietary project. For authorized contributors:

1. Create feature branch from `main`
2. Follow PSR-12 coding standards
3. Add tests for new features
4. Update documentation
5. Submit pull request for review

## 📝 License

Copyright © 2024 CRM.PROFTRANSFER. All rights reserved.

This is proprietary software. Unauthorized copying, distribution, or use is strictly prohibited.

## 👥 Support

- **Email**: support@proftransfer.com
- **Documentation**: See `DOCUMENTATION.md`
- **Issues**: GitHub Issues (for authorized users)

## 🎉 Acknowledgments

- Bootstrap Team - UI Framework
- Font Awesome - Icon Library
- Chart.js - Data Visualization
- Yandex - Maps API
- All open-source contributors

---

**Built with ❤️ for modern transportation management**

*Production-ready, secure, and scalable.*
