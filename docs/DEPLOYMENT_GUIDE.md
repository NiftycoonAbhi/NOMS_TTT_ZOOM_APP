# TTT ZOOM System - Deployment Guide

## 🚀 Local Development Setup (XAMPP)

### Prerequisites
- XAMPP with PHP 8.0+ 
- MySQL/MariaDB
- Modern web browser

### Local Installation Steps

1. **Extract Files**
   ```
   Extract to: C:\xampp\htdocs\NOMS_TTT_ZOOM_APP\TTT_NOMS_ZOOM\
   ```

2. **Start XAMPP Services**
   - Start Apache
   - Start MySQL

3. **Database Setup**
   ```sql
   -- Access phpMyAdmin (http://localhost/phpmyadmin)
   -- Create database: ttt_zoom_system
   -- Import: database/ttt_zoom_complete.sql
   ```

4. **Configuration**
   - Update database credentials in `config.php` if needed
   - Default local settings should work with XAMPP

5. **Run Installation**
   ```
   Visit: http://localhost/NOMS_TTT_ZOOM_APP/TTT_NOMS_ZOOM/install.php
   ```

6. **Access System**
   - Main: `http://localhost/NOMS_TTT_ZOOM_APP/TTT_NOMS_ZOOM/`
   - Admin: `http://localhost/NOMS_TTT_ZOOM_APP/TTT_NOMS_ZOOM/admin/`

---

## 🌐 Live Server Deployment

### Prerequisites
- Web hosting with PHP 8.0+
- MySQL/MariaDB database
- SSL certificate (recommended)
- SSH/FTP access

### Live Server Setup

1. **Upload Files**
   ```
   Upload all files to your web hosting directory
   Example: /public_html/ttt_zoom/
   ```

2. **Database Setup**
   ```sql
   -- Create database via hosting panel
   -- Import database/ttt_zoom_complete.sql
   -- Update database credentials in config.php
   ```

3. **Configuration Updates**
   ```php
   // config.php - Update for live environment
   define('DB_HOST', 'your_db_host');
   define('DB_USER', 'your_db_user');
   define('DB_PASS', 'your_db_password');
   define('DB_NAME', 'your_db_name');
   ```

4. **Zoom API Setup**
   ```
   - Configure Zoom Server-to-Server OAuth app
   - Add credentials to zoom_api_credentials table
   - Update webhook URL in Zoom app settings
   ```

5. **SSL & Security**
   ```
   - Ensure SSL certificate is installed
   - Update webhook URLs to use HTTPS
   - Set proper file permissions (755 for directories, 644 for files)
   ```

---

## 🔧 System Configuration

### Zoom API Credentials
```sql
INSERT INTO zoom_api_credentials (account_id, client_id, client_secret, name, is_active) 
VALUES ('YOUR_ACCOUNT_ID', 'YOUR_CLIENT_ID', 'YOUR_CLIENT_SECRET', 'Main Account', 1);
```

### Webhook Configuration
- Set webhook URL in Zoom app: `https://yourdomain.com/path/zoom-webhook.php`
- Enable events: Meeting ended, Participant joined/left

### File Permissions
```bash
# Set proper permissions
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;
chmod 755 logs/
```

---

## 🧪 Testing & Verification

### Health Check
```
Visit: http://yourdomain.com/path/health_check.php
```

### System Tests
1. Database connection
2. Zoom API connectivity  
3. Webhook processing
4. Student registration
5. Meeting creation
6. Attendance tracking

---

## 📋 Deployment Checklist

### Pre-Deployment
- [ ] Database imported from `database/ttt_zoom_complete.sql`
- [ ] Database credentials updated in `config.php`
- [ ] Zoom API credentials added to database
- [ ] Web server permissions set for logs/ directory
- [ ] SSL certificate configured (required for webhooks)
- [ ] Webhook URL configured in Zoom App settings

### Post-Deployment
- [ ] Health check passes
- [ ] Admin login works
- [ ] Student registration works
- [ ] Meeting creation works
- [ ] Webhook receives events
- [ ] Attendance data stores correctly

---

## 🛠️ Troubleshooting

### Common Issues

1. **Database Connection Failed**
   - Check credentials in `config.php`
   - Verify database exists
   - Check MySQL service status

2. **Zoom API Errors**
   - Verify API credentials in database
   - Check account permissions
   - Ensure OAuth app is activated

3. **Webhook Not Working**
   - Verify SSL certificate
   - Check webhook URL in Zoom app
   - Monitor `logs/zoom_webhook_errors.log`

4. **Permission Denied**
   - Set proper file permissions
   - Check logs/ directory is writable
   - Verify web server user permissions

### Log Files
- `logs/application.log` - General application logs
- `logs/zoom_api_errors.log` - Zoom API error logs
- `logs/zoom_webhook_errors.log` - Webhook error logs
- `logs/php_errors.log` - PHP error logs

---

## 📞 Support

For technical support or questions:
- Check health_check.php for system status
- Review log files for error details
- Verify configuration settings
- Test with minimal Zoom meeting first

---

**Version**: 2.0.0  
**Last Updated**: July 2025
