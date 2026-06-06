# Admin Setup Guide - ANME LearnOps

## 🎯 Admin Panel Architecture

### Theme & UI

**Admin Panel Theme:**
- Primary: Blue (#3b82f6)
- Sidebar: Blue Gradient (900-800)
- Cards: White with shadows
- Responsive: Mobile-friendly

### Views Structure
```
resources/views/admin/
├── layouts/admin.blade.php (Main layout)
├── dashboard.blade.php
├── orders/index.blade.php
├── students/index.blade.php
├── courses/index.blade.php
```

## 📋 Database Setup

### Run Migrations
```bash
php artisan migrate
```

### Migrations Created
1. **course_mappings** - Moodle course sync tracking
2. **admin_audit_logs** - Admin action logging

## 👥 Roles & Permissions

### Role Hierarchy

**Super Admin** - Full access
**Admin** - Order, Student, Course, Settings management
**Instructor** - View courses and students
**Student** - View own orders

### Setup Roles
```bash
php artisan db:seed --class=AdminSeeder
```

### Default Admin Credentials
- Email: admin@example.com
- Password: Password123!

## 🎨 Theme Options

### Current Theme: Blue Gradient
```blade
<!-- Sidebar -->
from-blue-900 to-blue-800

<!-- Primary Color -->
#3b82f6
```

### Alternative Themes

**Green Theme:**
```blade
from-green-900 to-green-800
```

**Purple Theme:**
```blade
from-purple-900 to-purple-800
```

## 🗄️ Database Schemas

### course_mappings
```sql
- id: Primary Key
- course_id: Foreign Key (courses)
- moodle_course_id: Unique identifier from Moodle
- moodle_course_name: Course name
- moodle_category_id: Moodle category
- moodle_summary: Course description
- sync_status: pending|synced|error
- sync_error: JSON error details
- last_synced_at: Timestamp
```

### admin_audit_logs
```sql
- id: Primary Key
- admin_id: Foreign Key (users)
- action: create|update|delete|approve|reject
- model_type: Order|Payment|Course|etc
- model_id: Record ID
- changes: JSON of changes
- ip_address: Admin IP
- user_agent: Browser info
```

## 🔧 Configuration

### Enable Admin Panel
Already configured in `routes/web.php`:
```php
Route::prefix('admin')
    ->middleware(['auth', 'role:Super Admin|Admin'])
    ->name('admin.')
    ->group(function () {
        // Admin routes
    });
```

## 📊 Admin Dashboard Features

### Stats Cards
- Total Revenue
- Total Orders
- Total Students
- Active Courses

### Quick Actions
- Sync Courses from Moodle
- View Orders
- View Students
- Settings

## 🔐 Security

### Role-Based Access
```php
@can('approve_payment')
    <!-- Only if user has permission -->
@endcan
```

### Audit Logging
```php
AdminAuditLog::log(
    admin: auth()->user(),
    action: 'approve_payment',
    modelType: 'Payment',
    modelId: $payment->id
);
```

## 🚀 Quick Start

1. **Setup Database:**
   ```bash
   php artisan migrate
   php artisan db:seed --class=AdminSeeder
   ```

2. **Login:**
   - URL: `/admin`
   - Email: `admin@example.com`
   - Password: `Password123!`

3. **Sync Courses:**
   - Dashboard → Click "کورسز سنک کریں"

## ⚠️ Troubleshooting

### Admin 403 Error
```php
$user->assignRole('Super Admin');
```

### Permission Denied
```bash
php artisan cache:clear
php artisan config:clear
```

### Moodle Sync Failing
Check `/admin/settings/moodle-diagnostics`

---

**Last Updated:** June 6, 2026
**Version:** 1.0
