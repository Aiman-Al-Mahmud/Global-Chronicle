# Admin Dashboard Access Guide

## ✅ Admin Dashboard is Now Ready!

### 🔐 Admin Credentials

**Email:** `admin@gmail.com`  
**Password:** `admin123`

### 📍 Access URLs

1. **Frontend Homepage:** `http://127.0.0.1:8000/`
2. **Login Page:** `http://127.0.0.1:8000/login`
3. **Admin Dashboard:** `http://127.0.0.1:8000/admin`

### 🚀 How to Access Admin Dashboard

#### Step 1: Login
1. Go to `http://127.0.0.1:8000/login`
2. Enter email: `admin@gmail.com`
3. Enter password: `admin123`
4. Click "Login"

#### Step 2: Access Admin
- After successful login, you'll be redirected
- Navigate to `http://127.0.0.1:8000/admin`
- OR click on your username in the navbar and look for admin link

### 🛡️ Security Notes

**IMPORTANT:** This is a default password. For production:
1. Change this password immediately
2. Use a strong password with:
   - Minimum 12 characters
   - Mix of uppercase, lowercase, numbers, and symbols
3. Enable two-factor authentication if available

### 📋 User Roles

The system supports three user roles:
- **Admin** - Full access to all features
- **Editor** - Can manage content
- **Author** - Can create and edit own content

Your current user has **admin** role, which gives full access.

### 🎨 Admin Features Available

Once logged in to `/admin`, you'll have access to:
- ✅ Dashboard - Overview and statistics
- ✅ News Management - Create, edit, delete news articles
- ✅ Pulling - News pulling/scraping features
- ✅ RSS Sources - Manage RSS feeds
- ✅ Categories - Organize content categories
- ✅ Tags - Manage article tags
- ✅ Media - Upload and manage images/files
- ✅ Advertisements - Manage ads
- ✅ Users - User management
- ✅ Settings - System configuration

### 🐛 Troubleshooting

**Issue: "Not Found - The requested resource /admin was not found"**
- **Cause:** You're not logged in
- **Solution:** Login first at `/login`, then access `/admin`

**Issue: "403 Unauthorized access"**
- **Cause:** Your user doesn't have admin role
- **Solution:** Use the admin account provided above

**Issue: "Page redirects to login"**
- **Cause:** Session expired
- **Solution:** Login again

**Issue: "CSS not loading properly"**
- **Cause:** Assets not published
- **Solution:** Clear cache with:
  ```bash
  php artisan cache:clear
  php artisan config:clear
  php artisan view:clear
  ```

### 🔄 Password Reset (If Needed)

If you forget your password, run this command:

```bash
cd /home/aiman/News/newsman
php artisan tinker --execute="\$user = App\Models\User::where('email', 'admin@gmail.com')->first(); \$user->password = Hash::make('yournewpassword'); \$user->save(); echo 'Password updated!';"
```

Replace `yournewpassword` with your desired password.

### ✨ CSS Already Fixed

The admin CSS has been copied from Laranews project and the paths have been fixed in the master template. The admin dashboard should now have proper styling.

**Admin CSS Location:** `/public/admin/css/`
**Includes:**
- Bootstrap CSS
- Custom admin styles
- Icon fonts
- Responsive layouts

### 📝 Next Steps

1. ✅ Login with the credentials above
2. ✅ Access the admin dashboard
3. ✅ Explore the features
4. ⚠️ Change your password from the settings
5. 🎉 Start managing your news website!

---

## Quick Access Checklist

- [ ] Server is running (`php artisan serve`)
- [ ] Database is connected
- [ ] Login at `/login`
- [ ] Use credentials: admin@gmail.com / admin123
- [ ] Navigate to `/admin`
- [ ] Verify CSS is loading properly
- [ ] Change default password

---

**Enjoy your admin dashboard! 🎉**
