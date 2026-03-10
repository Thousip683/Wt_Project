# Beyond Classroom

A comprehensive student-focused web platform for managing college academics, competitive exam preparation, and career-oriented learning.

## Stage 1: Foundation & Authentication ✅

### Features Implemented
- ✅ Secure user registration and login system
- ✅ Password hashing with bcrypt
- ✅ Session management
- ✅ CSRF protection
- ✅ Input sanitization and validation
- ✅ User profile management
- ✅ Clean, responsive UI
- ✅ Flash message system
- ✅ User dashboard (basic layout)

### Project Structure
```
beyond-classroom/
├── config/
│   ├── database.php       # Database connection
│   └── config.php          # Application configuration
├── includes/
│   ├── header.php          # Common header
│   ├── footer.php          # Common footer
│   └── functions.php       # Helper functions
├── auth/
│   ├── login.php           # User login
│   ├── register.php        # User registration
│   └── logout.php          # Logout functionality
├── assets/
│   ├── css/
│   │   └── style.css       # Main stylesheet
│   └── js/
│       └── main.js         # JavaScript functionality
├── database/
│   └── schema.sql          # Database schema
├── dashboard.php           # Main dashboard
├── profile.php             # User profile page
└── index.php               # Landing page
```

## Installation & Setup

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- phpMyAdmin (optional)

### Step 1: Database Setup
1. Open phpMyAdmin or MySQL command line
2. Import the database schema:
   ```bash
   mysql -u root -p < database/schema.sql
   ```
   Or manually execute the SQL in `database/schema.sql`

### Step 2: Configure Database Connection
1. Open `config/database.php`
2. Update the following constants with your database credentials:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'beyond_classroom');
   ```

### Step 3: Configure Site URL
1. Open `config/config.php`
2. Update the SITE_URL constant:
   ```php
   define('SITE_URL', 'http://localhost:8000');
   ```
Command to run the Application: php -S localhost:8000

### Step 4: Test the Application
1. Open your browser and navigate to: `http://localhost:8000'`
2. You will be redirected to the login page
3. Use the test account or register a new one:
   - **Test Account:**
     - Email: test@example.com
     - Password: password

## Testing Checklist

### User Registration ✅
- [ ] Register with valid details
- [ ] Try registering with existing email (should fail)
- [ ] Try weak password (should show error)
- [ ] Try mismatched passwords (should show error)
- [ ] Verify email validation

### User Login ✅
- [ ] Login with correct credentials
- [ ] Try wrong password (should fail)
- [ ] Try non-existent email (should fail)
- [ ] Verify session persistence

### Profile Management ✅
- [ ] View profile information
- [ ] Update name, course, semester
- [ ] Verify email cannot be changed
- [ ] Check if changes persist after logout/login

### Security ✅
- [ ] Verify passwords are hashed in database
- [ ] Test CSRF protection
- [ ] Verify session timeout
- [ ] Test SQL injection prevention (try special chars)

### UI/UX ✅
- [ ] Check responsive design on mobile
- [ ] Verify flash messages appear and auto-hide
- [ ] Test navigation between pages
- [ ] Check logout functionality

## Technologies Used
- **Backend:** PHP (Procedural with security best practices)
- **Database:** MySQL
- **Frontend:** HTML5, CSS3, Vanilla JavaScript
- **Icons:** Font Awesome 6
- **Security:** Password hashing, prepared statements, CSRF tokens

## Security Features
- Password hashing with bcrypt
- Prepared SQL statements (SQL injection prevention)
- CSRF token protection
- Input sanitization
- Session management
- Security headers (XSS, Clickjacking protection)

## What's Next?
**Stage 2: Academic Management Core**
- Subject management
- Timetable creation
- Assignment tracking
- Exam scheduling
- Workload calculator

## Support
For issues or questions, refer to the inline comments in the code or review the implementation guidelines.

---
**Status:** Stage 1 Complete ✅  
**Next Stage:** Stage 2 (Academic Management)
