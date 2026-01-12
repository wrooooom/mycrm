# 📦 Installation Guide - CRM.PROFTRANSFER

Comprehensive guide for deploying CRM.PROFTRANSFER in production environment.

## 📋 Table of Contents

- [System Requirements](#system-requirements)
- [Quick Start with Docker](#quick-start-with-docker)
- [Manual Installation](#manual-installation)
- [Configuration](#configuration)
- [Database Setup](#database-setup)
- [Security Setup](#security-setup)
- [Performance Optimization](#performance-optimization)
- [Troubleshooting](#troubleshooting)

## 🖥️ System Requirements

### Minimum Requirements

- **PHP**: 8.0 or higher
- **MySQL**: 8.0 or higher (or MariaDB 10.5+)
- **Apache/Nginx**: Latest stable version
- **RAM**: 2GB minimum, 4GB recommended
- **Disk Space**: 500MB minimum, 2GB recommended
- **OS**: Linux (Ubuntu 20.04+, CentOS 8+), Windows Server, macOS

### Required PHP Extensions

```bash
php -m | grep -E 'pdo|pdo_mysql|mysqli|mbstring|json|session|curl|gd|zip'
```

Required extensions:
- `pdo`
- `pdo_mysql`
- `mysqli`
- `mbstring`
- `json`
- `session`
- `curl`
- `gd` (for image manipulation)
- `zip` (for backups)

## 🐳 Quick Start with Docker

The fastest way to get started is using Docker.

### Prerequisites

- [Docker](https://docs.docker.com/get-docker/) (20.10+)
- [Docker Compose](https://docs.docker.com/compose/install/) (v2.0+)

### Steps

1. **Clone the repository**

```bash
git clone https://github.com/yourusername/mycrm.git
cd mycrm
```

2. **Configure environment**

```bash
cp .env.example .env
nano .env
```

Edit database credentials and other settings.

3. **Start containers**

```bash
docker-compose up -d
```

4. **Import database**

```bash
docker-compose exec web php scripts/setup.php
```

5. **Access the application**

Open browser: `http://localhost`

Default credentials:
- **Username**: `admin`
- **Password**: `admin123`

**⚠️ Change default password immediately!**

### Docker Commands

```bash
# View logs
docker-compose logs -f

# Stop containers
docker-compose stop

# Restart containers
docker-compose restart

# Remove containers
docker-compose down

# Access shell
docker-compose exec web bash
```

## 🔧 Manual Installation

### Step 1: System Preparation

#### Ubuntu/Debian

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install Apache
sudo apt install apache2 -y

# Install PHP and extensions
sudo apt install php8.2 php8.2-cli php8.2-fpm php8.2-mysql \
  php8.2-mbstring php8.2-xml php8.2-gd php8.2-curl \
  php8.2-zip php8.2-intl -y

# Install MySQL
sudo apt install mysql-server -y

# Enable Apache modules
sudo a2enmod rewrite headers ssl
sudo systemctl restart apache2
```

#### CentOS/RHEL

```bash
# Update system
sudo yum update -y

# Install Apache
sudo yum install httpd -y

# Enable EPEL and Remi repositories
sudo yum install epel-release -y
sudo yum install https://rpms.remirepo.net/enterprise/remi-release-8.rpm -y

# Enable PHP 8.2
sudo yum module enable php:remi-8.2 -y

# Install PHP and extensions
sudo yum install php php-mysqlnd php-mbstring php-xml \
  php-gd php-curl php-zip php-intl -y

# Install MySQL
sudo yum install mysql-server -y

# Start services
sudo systemctl start httpd
sudo systemctl start mysqld
sudo systemctl enable httpd
sudo systemctl enable mysqld
```

### Step 2: Download Application

```bash
# Navigate to web root
cd /var/www/html

# Clone repository
sudo git clone https://github.com/yourusername/mycrm.git crm
cd crm

# Set permissions
sudo chown -R www-data:www-data /var/www/html/crm
sudo chmod -R 755 /var/www/html/crm
sudo chmod -R 775 logs backups uploads
```

### Step 3: Configure Apache

Create virtual host configuration:

```bash
sudo nano /etc/apache2/sites-available/crm.conf
```

Add configuration:

```apache
<VirtualHost *:80>
    ServerName crm.yourdomain.com
    ServerAdmin admin@yourdomain.com
    DocumentRoot /var/www/html/crm
    
    <Directory /var/www/html/crm>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/crm_error.log
    CustomLog ${APACHE_LOG_DIR}/crm_access.log combined
</VirtualHost>
```

Enable site:

```bash
sudo a2ensite crm.conf
sudo systemctl reload apache2
```

### Step 4: Database Setup

```bash
# Secure MySQL installation
sudo mysql_secure_installation

# Create database and user
sudo mysql -u root -p
```

In MySQL console:

```sql
CREATE DATABASE crm_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'crm_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON crm_db.* TO 'crm_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

Import schema:

```bash
mysql -u crm_user -p crm_db < sql/schema.sql
```

### Step 5: Configure Application

```bash
# Copy environment file
cp .env.example .env

# Edit configuration
nano .env
```

Update database credentials:

```env
DB_HOST=localhost
DB_NAME=crm_db
DB_USER=crm_user
DB_PASSWORD=your_secure_password
```

### Step 6: Set Permissions

```bash
# Create required directories
mkdir -p logs backups uploads

# Set ownership
sudo chown -R www-data:www-data .

# Set permissions
sudo chmod -R 755 .
sudo chmod -R 775 logs backups uploads
sudo chmod 600 .env
sudo chmod 600 config/database.php
```

### Step 7: Verify Installation

Visit: `http://your-domain.com/health.php`

You should see:

```json
{
    "status": "healthy",
    "checks": {
        "database": {"status": "healthy"},
        "php": {"status": "healthy"}
    }
}
```

## ⚙️ Configuration

### Environment Variables

Edit `.env` file:

```env
# Application
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database
DB_HOST=localhost
DB_PORT=3306
DB_NAME=crm_db
DB_USER=crm_user
DB_PASSWORD=your_secure_password

# Security
RATE_LIMIT_ENABLED=true
CSRF_TOKEN_LIFETIME=3600

# Features
FEATURE_DARK_MODE=true
FEATURE_2FA=false
```

### Database Configuration

Edit `config/database.php` if not using `.env`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'crm_db');
define('DB_USER', 'crm_user');
define('DB_PASS', 'your_secure_password');
```

## 🔒 Security Setup

### 1. SSL/HTTPS Configuration

#### Using Let's Encrypt (Recommended)

```bash
# Install Certbot
sudo apt install certbot python3-certbot-apache -y

# Obtain certificate
sudo certbot --apache -d crm.yourdomain.com

# Auto-renewal
sudo certbot renew --dry-run
```

#### Manual SSL Certificate

Update Apache config:

```apache
<VirtualHost *:443>
    ServerName crm.yourdomain.com
    DocumentRoot /var/www/html/crm
    
    SSLEngine on
    SSLCertificateFile /path/to/certificate.crt
    SSLCertificateKeyFile /path/to/private.key
    SSLCertificateChainFile /path/to/chain.crt
    
    # ... rest of configuration
</VirtualHost>
```

### 2. Force HTTPS

Uncomment in `.htaccess`:

```apache
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### 3. Set Secure Session Cookies

In `.env`:

```env
SESSION_SECURE=true
SESSION_HTTPONLY=true
```

### 4. Change Default Passwords

```bash
php scripts/change-password.php admin new_secure_password
```

### 5. Configure Firewall

```bash
# UFW (Ubuntu)
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw allow 22/tcp
sudo ufw enable

# Firewalld (CentOS)
sudo firewall-cmd --permanent --add-service=http
sudo firewall-cmd --permanent --add-service=https
sudo firewall-cmd --reload
```

## ⚡ Performance Optimization

### 1. Enable PHP OpCache

Edit `php.ini`:

```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2
```

### 2. Configure MySQL

Edit `/etc/mysql/my.cnf`:

```ini
[mysqld]
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
max_connections = 200
query_cache_size = 32M
query_cache_type = 1
```

Restart MySQL:

```bash
sudo systemctl restart mysql
```

### 3. Enable Gzip Compression

Already configured in `.htaccess`.

Verify Apache module:

```bash
sudo a2enmod deflate
sudo systemctl restart apache2
```

### 4. Setup Cron Jobs

```bash
sudo crontab -e
```

Add:

```cron
# Daily database backup at 2 AM
0 2 * * * /usr/bin/php /var/www/html/crm/scripts/backup.php create

# Clean old logs weekly
0 0 * * 0 find /var/www/html/crm/logs -type f -mtime +30 -delete

# Health check every 5 minutes
*/5 * * * * curl -f http://localhost/health.php > /dev/null 2>&1
```

## 🔧 Troubleshooting

### Database Connection Fails

```bash
# Check MySQL is running
sudo systemctl status mysql

# Test connection
mysql -u crm_user -p -h localhost crm_db

# Check credentials in .env
```

### Permission Denied Errors

```bash
# Fix ownership
sudo chown -R www-data:www-data /var/www/html/crm

# Fix permissions
sudo chmod -R 755 /var/www/html/crm
sudo chmod -R 775 logs backups uploads
```

### 500 Internal Server Error

```bash
# Check Apache error log
sudo tail -f /var/log/apache2/error.log

# Check PHP error log
sudo tail -f /var/www/html/crm/logs/php_errors.log

# Enable debug mode temporarily
# In .env: APP_DEBUG=true
```

### White Page / No Output

```bash
# Check PHP errors
php -l /var/www/html/crm/index.php

# Check Apache configuration
sudo apache2ctl configtest
```

### Slow Performance

```bash
# Check MySQL slow queries
sudo mysql -e "SHOW FULL PROCESSLIST;"

# Enable query logging
# In /etc/mysql/my.cnf:
# slow_query_log = 1
# long_query_time = 2
```

## 📞 Support

- **Documentation**: See `DOCUMENTATION.md`
- **API Reference**: See `API.md`
- **GitHub Issues**: https://github.com/yourusername/mycrm/issues

## 📄 License

Copyright © 2024 CRM.PROFTRANSFER. All rights reserved.
