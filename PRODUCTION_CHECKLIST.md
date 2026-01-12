# 🚀 Production Deployment Checklist

Use this checklist before deploying CRM.PROFTRANSFER to production environment.

## Pre-Deployment

### 🔐 Security

- [ ] Change all default passwords (admin, database)
- [ ] Generate strong random passwords (16+ characters)
- [ ] Set `APP_DEBUG=false` in `.env`
- [ ] Set `APP_ENV=production` in `.env`
- [ ] Configure `.env` with production database credentials
- [ ] Enable HTTPS and configure SSL certificates
- [ ] Uncomment HTTPS redirect in `.htaccess`
- [ ] Set `SESSION_SECURE=true` in `.env`
- [ ] Verify CSRF protection is enabled
- [ ] Verify rate limiting is enabled
- [ ] Configure security headers (CSP, HSTS, etc.)
- [ ] Review and restrict file upload permissions
- [ ] Disable directory listing
- [ ] Remove or secure phpMyAdmin access
- [ ] Set restrictive file permissions (644 for files, 755 for directories)
- [ ] Ensure `.env` is not accessible via web
- [ ] Verify `config/database.php` is protected

### 🗄️ Database

- [ ] Create production database
- [ ] Create database user with limited privileges
- [ ] Import schema: `mysql -u user -p database < sql/schema.sql`
- [ ] Apply Stage 4 migration: `php scripts/apply_stage4_migration.php`
- [ ] Add necessary indexes for performance
- [ ] Configure database backups
- [ ] Test database connection
- [ ] Set appropriate timezone in MySQL
- [ ] Configure max connections limit
- [ ] Enable slow query log for monitoring

### ⚙️ Server Configuration

- [ ] Install PHP 8.0 or higher
- [ ] Install all required PHP extensions (see composer.json)
- [ ] Configure PHP settings (memory_limit, upload_max_filesize, etc.)
- [ ] Enable OpCache for PHP
- [ ] Configure Apache/Nginx virtual host
- [ ] Enable mod_rewrite (Apache) or rewrite rules (Nginx)
- [ ] Enable mod_headers for security headers
- [ ] Enable mod_deflate/gzip compression
- [ ] Configure error logging
- [ ] Set appropriate timezone in php.ini
- [ ] Disable `expose_php` in php.ini
- [ ] Configure session settings

### 📁 File System

- [ ] Create required directories (logs, backups, uploads)
- [ ] Set correct ownership: `chown -R www-data:www-data /path/to/crm`
- [ ] Set correct permissions: `chmod -R 755 .`
- [ ] Make logs writable: `chmod -R 775 logs`
- [ ] Make backups writable: `chmod -R 775 backups`
- [ ] Make uploads writable: `chmod -R 775 uploads`
- [ ] Protect sensitive files: `chmod 600 .env config/database.php`
- [ ] Verify .gitignore is properly configured

### 🔧 Application

- [ ] Run cleanup script: `php scripts/cleanup.php --dry-run`
- [ ] Remove all debug code and console.log
- [ ] Remove test files (test_*.php, *_test.php, debug*.php)
- [ ] Remove backup files (*.backup, *.bak, *~)
- [ ] Review and address TODO/FIXME comments
- [ ] Test all critical functionality
- [ ] Verify all forms have CSRF tokens
- [ ] Test user authentication and authorization
- [ ] Test password reset functionality
- [ ] Verify email notifications work
- [ ] Test file upload functionality and limits

### 📊 Monitoring & Logging

- [ ] Configure error logging directory
- [ ] Set up log rotation
- [ ] Configure structured logging
- [ ] Test health check endpoint: `/health.php`
- [ ] Set up uptime monitoring (optional)
- [ ] Configure error alerting (optional)
- [ ] Set up performance monitoring (optional)

## Deployment

### 🚢 Initial Deployment

- [ ] Create deployment branch or tag
- [ ] Review all code changes
- [ ] Test in staging environment
- [ ] Create database backup of production (if updating)
- [ ] Put site in maintenance mode (if updating)
- [ ] Deploy code to production server
- [ ] Run database migrations
- [ ] Clear OpCache: `php -r "opcache_reset();"`
- [ ] Test critical functionality
- [ ] Check error logs
- [ ] Remove maintenance mode

### 🐳 Docker Deployment (Alternative)

- [ ] Build Docker image: `docker-compose build`
- [ ] Test container locally
- [ ] Push image to registry (if using)
- [ ] Deploy to production: `docker-compose up -d`
- [ ] Check container status: `docker-compose ps`
- [ ] View logs: `docker-compose logs -f`
- [ ] Test health check

## Post-Deployment

### ✅ Verification

- [ ] Access application in browser
- [ ] Verify HTTPS is working
- [ ] Test user login
- [ ] Test application creation
- [ ] Test driver assignment
- [ ] Test vehicle management
- [ ] Test real-time notifications
- [ ] Test mobile responsiveness
- [ ] Test dark mode toggle
- [ ] Verify analytics dashboard loads
- [ ] Test reports and exports
- [ ] Verify maps integration works
- [ ] Check browser console for errors
- [ ] Verify no PHP errors in logs

### 🔒 Security Verification

- [ ] Test CSRF protection works
- [ ] Test rate limiting (try multiple failed logins)
- [ ] Verify security headers: https://securityheaders.com
- [ ] Test file upload restrictions
- [ ] Verify session timeout works
- [ ] Check for information disclosure in errors
- [ ] Test SQL injection prevention
- [ ] Test XSS prevention
- [ ] Verify HTTPS redirect works
- [ ] Check SSL certificate validity

### 📈 Performance

- [ ] Run performance audit (Lighthouse, PageSpeed)
- [ ] Verify page load times < 3 seconds
- [ ] Check database query performance
- [ ] Verify caching is working
- [ ] Test with multiple concurrent users
- [ ] Monitor server resources (CPU, RAM, disk)
- [ ] Verify gzip compression is working
- [ ] Check image optimization

### 🔄 Backup & Recovery

- [ ] Test manual backup: `php scripts/backup.php create`
- [ ] Verify backup file is created and valid
- [ ] Test backup restoration in test environment
- [ ] Set up automated backup cron job
- [ ] Configure backup retention policy
- [ ] Document backup procedure
- [ ] Test disaster recovery plan

### 📋 Documentation

- [ ] Update README.md with production URL
- [ ] Document server configuration
- [ ] Document database credentials location
- [ ] Create runbook for common issues
- [ ] Document backup/restore procedure
- [ ] Document deployment procedure
- [ ] Create user guides for each role
- [ ] Document API endpoints
- [ ] Update changelog with deployment date

### 🔔 Communication

- [ ] Notify team of successful deployment
- [ ] Provide access credentials to admins
- [ ] Share production URL
- [ ] Schedule training session for users
- [ ] Create support documentation
- [ ] Set up support channels

## Ongoing Maintenance

### Daily

- [ ] Monitor error logs
- [ ] Check system health endpoint
- [ ] Review security logs
- [ ] Monitor server resources

### Weekly

- [ ] Verify automated backups are running
- [ ] Check disk space
- [ ] Review performance metrics
- [ ] Update security patches

### Monthly

- [ ] Test backup restoration
- [ ] Review and rotate logs
- [ ] Update dependencies
- [ ] Security audit
- [ ] Performance optimization review

## Rollback Plan

In case of critical issues:

1. **Enable Maintenance Mode**
   ```bash
   touch maintenance.flag
   ```

2. **Restore Database Backup**
   ```bash
   php scripts/backup.php restore <backup-file>
   ```

3. **Rollback Code**
   ```bash
   git checkout <previous-stable-tag>
   ```

4. **Clear Cache**
   ```bash
   php -r "opcache_reset();"
   ```

5. **Verify Functionality**

6. **Disable Maintenance Mode**
   ```bash
   rm maintenance.flag
   ```

## Emergency Contacts

- **System Administrator**: _________________
- **Database Administrator**: _________________
- **Security Team**: _________________
- **Development Team**: _________________
- **Support Email**: support@proftransfer.com

## Notes

_Add any production-specific notes here_

---

## Deployment Sign-off

| Role | Name | Date | Signature |
|------|------|------|-----------|
| Developer | _______ | _______ | _______ |
| QA | _______ | _______ | _______ |
| DevOps | _______ | _______ | _______ |
| Security | _______ | _______ | _______ |
| Manager | _______ | _______ | _______ |

---

**Document Version**: 1.0  
**Last Updated**: 2024-01-12  
**Next Review**: 2024-02-12
