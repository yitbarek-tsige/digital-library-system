# 📚 Simple Digital Library Management System

A complete PHP-based book library with admin dashboard, file uploads, and user-facing gallery.

## Features

### Admin Panel
- Secure login system with password hashing
- Dashboard with all books table view
- Add/Edit books with cover image and PDF upload
- Delete books with automatic file cleanup
- Responsive design

### User Side
- Beautiful book gallery with search functionality
- Filter by category
- Individual book detail pages
- PDF download support
- Mobile-friendly responsive layout

## File Structure

```
├── db.php                  # Database connection
├── auth.php                # Authentication functions
├── login.php               # Admin login page
├── logout.php              # Logout handler
├── admin.php               # Admin dashboard
├── book_form.php           # Add/Edit book form
├── admin_categories.php    # Add/Delete book categories
├── delete_book.php         # Delete book handler
├── index.php               # Public book gallery
├── details.php             # Book detail page
├── download.php            # File download handler
├── .htaccess               # Security rules
├── uploads/                # Uploaded files directory
└── database_schema.sql     # Database setup
```

## Setup Instructions

1. **Create Database**
   ```sql
   -- Run database_schema.sql in MySQL
   mysql -u root -p < database_schema.sql
   ```

2. **Configure Database**
   - Open `db.php` and update credentials:
   ```php
   $host = 'localhost';
   $dbname = 'book_library';
   $username = 'root';      // Your MySQL username
   $password = '';          // Your MySQL password
   ```

3. **Create Uploads Directory**
   ```bash
   mkdir uploads
   chmod 755 uploads
   ```

4. **Default Admin Credentials**
   - Username: `admin`
   - Password: `password`
   - **Important:** Change this password after first login!

5. **Security Notes**
   - Change default admin password immediately
   - Ensure `uploads/` directory is writable by web server
   - The `.htaccess` file prevents PHP execution in uploads
   - Consider moving uploads outside web root in production

## Security Features

- Password hashing with bcrypt
- Session-based authentication
- CSRF protection via session validation
- File type validation for uploads
- SQL injection prevention via prepared statements
- XSS protection via htmlspecialchars
- File extension and MIME type checking

## Requirements

- PHP 7.4+
- MySQL 5.7+ / MariaDB 10.2+
- Apache/Nginx with mod_rewrite (recommended)
- GD extension for image processing (optional)
