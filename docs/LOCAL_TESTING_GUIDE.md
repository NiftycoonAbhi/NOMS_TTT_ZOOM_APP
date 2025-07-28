# TTT Zoom Project - Local Testing Guide

## 📋 **Prerequisites**

### Required Software
- ✅ XAMPP (Apache + MySQL + PHP 8.0+)
- ✅ Web Browser (Chrome/Firefox recommended)
- ✅ Text Editor (VS Code recommended)
- ✅ Zoom Account with API credentials

---

## 🚀 **Step-by-Step Testing Setup**

### **Step 1: Start XAMPP Services**

1. Open XAMPP Control Panel
2. Start **Apache** service
3. Start **MySQL** service
4. Verify both services show "Running" status

```
✅ Apache: Running (Port 80)
✅ MySQL: Running (Port 3306)
```

### **Step 2: Database Setup**

1. **Open phpMyAdmin**
   - Click "Admin" next to MySQL in XAMPP
   - Or go to: `http://localhost/phpmyadmin`

2. **Create Database**
   ```sql
   CREATE DATABASE ttt_zoom_system;
   ```

3. **Import Database Schema**
   - Select `ttt_zoom_system` database
   - Click "Import" tab
   - Choose file: `c:\xampp\htdocs\NOMS_TTT_ZOOM_APP\TTT_NOMS_ZOOM\ttt_zoom.sql`
   - Click "Go" to import

4. **Verify Tables Created**
   ```sql
   SHOW TABLES;
   ```
   Should show:
   - zoom_api_credentials
   - meeting_att_head
   - meeting_att_details
   - student_details
   - courses
   - batchs
   - branch_details
   - zoom

### **Step 3: Configure Database Connection**

1. **Edit Database Configuration**
   - Open: `c:\xampp\htdocs\NOMS_TTT_ZOOM_APP\TTT_NOMS_ZOOM\db\dbconn.php`
   - Update credentials:

   ```php
   <?php
   $servername = "localhost";
   $username = "root";          // Default XAMPP username
   $password = "";              // Default XAMPP password (empty)
   $dbname = "ttt_zoom_system"; // Your database name
   
   // Create connection
   $conn = new mysqli($servername, $username, $password, $dbname);
   
   // Check connection
   if ($conn->connect_error) {
       die("Connection failed: " . $conn->connect_error);
   }
   ?>
   ```

### **Step 4: Set Up Zoom API Credentials**

1. **Get Zoom API Credentials**
   - Go to [Zoom Marketplace](https://marketplace.zoom.us/)
   - Create a Server-to-Server OAuth app
   - Note down: Account ID, Client ID, Client Secret

2. **Add Credentials to Database**
   - Open phpMyAdmin
   - Go to `zoom_api_credentials` table
   - Add your credentials:

   ```sql
   INSERT INTO zoom_api_credentials (account_id, client_id, client_secret, name, is_active) 
   VALUES ('YOUR_ACCOUNT_ID', 'YOUR_CLIENT_ID', 'YOUR_CLIENT_SECRET', 'Test Account', 1);
   ```

### **Step 5: System Verification**

1. **Run System Check**
   - Open browser: `http://localhost/NOMS_TTT_ZOOM_APP/TTT_NOMS_ZOOM/system_check.php`
   - Verify all checks pass:
     - ✅ Database Connection
     - ✅ Database Tables
     - ✅ Zoom API Credentials
     - ✅ Critical Files
     - ✅ Directory Permissions
     - ✅ PHP Extensions
     - ✅ Session Support

2. **Fix Any Issues**
   - Red items need fixing before proceeding
   - Yellow items are warnings but system may still work

---

## 🧪 **Testing Workflow**

### **Test 1: Admin Login & Account Selection**

1. **Access Admin Panel**
   ```
   http://localhost/NOMS_TTT_ZOOM_APP/TTT_NOMS_ZOOM/admin/select_zoom_account.php
   ```

2. **Expected Behavior:**
   - Shows list of available Zoom accounts
   - Can select an account
   - Redirects to admin dashboard

3. **Test Results:**
   - ✅ Account selection works
   - ✅ Session maintained
   - ✅ Dashboard loads

### **Test 2: Admin Dashboard**

1. **Access Dashboard**
   ```
   http://localhost/NOMS_TTT_ZOOM_APP/TTT_NOMS_ZOOM/admin/admin_dashboard.php
   ```

2. **Expected Features:**
   - ✅ Account switcher in header
   - ✅ Logout button functional
   - ✅ Live meetings tab
   - ✅ Past meetings tab
   - ✅ Meeting creation form

3. **Test Logout:**
   - Click account dropdown in header
   - Click "Logout"
   - Should redirect to account selection

### **Test 3: Meeting Management**

1. **Create Test Meeting (External)**
   - Use Zoom web interface or API
   - Create a test meeting
   - Note the meeting ID

2. **View Meeting Details**
   ```
   http://localhost/NOMS_TTT_ZOOM_APP/TTT_NOMS_ZOOM/admin/meeting_details.php?meeting_id=MEETING_ID
   ```

3. **Expected Features:**
   - ✅ Meeting information displayed
   - ✅ Attendance list (if any)
   - ✅ Export button functional
   - ✅ Student details links

### **Test 4: Student Registration**

1. **Access Student Interface**
   ```
   http://localhost/NOMS_TTT_ZOOM_APP/TTT_NOMS_ZOOM/Home/index.php
   ```

2. **Test Student Registration:**
   - Select course and batch
   - Enter meeting ID
   - Add students
   - Verify data saved

3. **Check Database:**
   ```sql
   SELECT * FROM zoom WHERE meeting_id = 'YOUR_MEETING_ID';
   ```

### **Test 5: Webhook Testing (Advanced)**

1. **Install ngrok (for webhook testing)**
   - Download from: https://ngrok.com/
   - Extract and run: `ngrok http 80`
   - Note the HTTPS URL (e.g., `https://abc123.ngrok.io`)

2. **Configure Webhook URL**
   - In Zoom App settings, set webhook URL:
   ```
   https://abc123.ngrok.io/NOMS_TTT_ZOOM_APP/TTT_NOMS_ZOOM/zoom-webhook.php
   ```

3. **Test Webhook:**
   - Join/leave a test meeting
   - Check logs: `logs/zoom_webhook_debug.log`
   - Verify attendance recorded in database

---

## 🔍 **Troubleshooting Common Issues**

### **Database Connection Issues**
```
Error: "Connection failed"
Solution: Check dbconn.php credentials, ensure MySQL is running
```

### **Session Issues**
```
Error: "Headers already sent"
Solution: Check for any output before session_start() calls
```

### **File Permission Issues**
```
Error: "Permission denied"
Solution: Ensure logs/ and data/ directories are writable
```

### **Zoom API Issues**
```
Error: "Invalid credentials"
Solution: Verify API credentials in zoom_api_credentials table
```

---

## 📊 **Test Data Setup**

### **Sample Students**
```sql
INSERT INTO student_details (student_id, student_name, course, batch, branch, status) VALUES
('TTT-TEST-001', 'Test Student 1', '10- ICSE', '10th ICSE Evening Batch', ' B-01', 1),
('TTT-TEST-002', 'Test Student 2', '10- ICSE', '10th ICSE Evening Batch', ' B-01', 1);
```

### **Sample Meeting Assignment**
```sql
INSERT INTO zoom (student_id, meeting_id, course, batch, branch, zoom_credentials_id) VALUES
('TTT-TEST-001', 'YOUR_MEETING_ID', '10- ICSE', '10th ICSE Evening Batch', ' B-01', 1),
('TTT-TEST-002', 'YOUR_MEETING_ID', '10- ICSE', '10th ICSE Evening Batch', ' B-01', 1);
```

---

## ✅ **Complete Testing Checklist**

### **Basic Functionality**
- [ ] Database connection working
- [ ] Admin login/logout working
- [ ] Account switching functional
- [ ] Dashboard displaying correctly
- [ ] Meeting details accessible
- [ ] Student registration working

### **Advanced Features**
- [ ] Multi-account switching
- [ ] Attendance export working
- [ ] Webhook receiving events
- [ ] Real-time participant tracking
- [ ] Session management secure

### **UI/UX Testing**
- [ ] Responsive design working
- [ ] Logout buttons in all headers
- [ ] Navigation working correctly
- [ ] Error messages displaying
- [ ] Success messages showing

### **Security Testing**
- [ ] Session timeout working
- [ ] Unauthorized access blocked
- [ ] SQL injection protection
- [ ] XSS protection active

---

## 🚀 **Ready for Live Deployment**

Once all local tests pass:

1. **Export Database**
   ```sql
   mysqldump -u root -p ttt_zoom_system > ttt_zoom_backup.sql
   ```

2. **Prepare for Live Server**
   - Update database credentials for production
   - Configure proper webhook URLs
   - Set up SSL certificates
   - Configure cron jobs if needed

3. **Deploy and Monitor**
   - Upload files to live server
   - Import database
   - Test all functionality
   - Monitor logs for issues

---

**🎉 Your TTT Zoom system is now ready for comprehensive local testing!**
