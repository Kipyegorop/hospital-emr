# 🖥️ Smart Hospital EMR - Local Desktop Deployment

## 🎯 **Zero Cloud Costs - Run Everything on Your Desktop!**

This guide shows you how to run the complete Smart Hospital EMR system locally on your Mac/Windows/Linux desktop without any cloud services or monthly costs.

## 🚀 **Quick Start (Recommended)**

### **Option 1: Super Simple Start**
```bash
# Just run this one command:
./start-local.sh
```

That's it! The script will:
- ✅ Check all requirements
- ✅ Set up the database
- ✅ Start both servers
- ✅ Give you access URLs

### **Option 2: Manual Start**
```bash
# Terminal 1: Backend
cd backend
php artisan serve --port=8000

# Terminal 2: Frontend  
cd frontend
npm run dev
```

## 📋 **System Requirements**

### **Required Software (Free)**
- **PHP 8.1+** - [Download from php.net](https://www.php.net/downloads.php)
- **Composer** - [Download from getcomposer.org](https://getcomposer.org/download/)
- **Node.js 18+** - [Download from nodejs.org](https://nodejs.org/)
- **Git** - [Download from git-scm.com](https://git-scm.com/downloads)

### **Database Options (Choose One)**
1. **SQLite** (Default - No setup needed)
2. **MySQL** (More features)
3. **PostgreSQL** (Advanced features)

## 🛠️ **Installation Steps**

### **Step 1: Install Requirements**

#### **On macOS (using Homebrew):**
```bash
# Install Homebrew if you don't have it
/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"

# Install requirements
brew install php composer node git
```

#### **On Windows:**
1. Download and install PHP from [php.net](https://www.php.net/downloads.php)
2. Download and install Composer from [getcomposer.org](https://getcomposer.org/download/)
3. Download and install Node.js from [nodejs.org](https://nodejs.org/)
4. Download and install Git from [git-scm.com](https://git-scm.com/downloads)

#### **On Ubuntu/Debian:**
```bash
sudo apt update
sudo apt install php8.2 php8.2-cli php8.2-mysql php8.2-xml php8.2-curl php8.2-zip composer nodejs npm git
```

### **Step 2: Clone and Setup**
```bash
# Clone the repository (if not already done)
git clone <your-repo-url>
cd smart-hospital

# Make scripts executable
chmod +x start-local.sh stop-local.sh
```

### **Step 3: Start the System**
```bash
# Start everything
./start-local.sh
```

## 🌐 **Access Your System**

After running `./start-local.sh`, you'll see:

```
🎉 Smart Hospital EMR System is now running locally!
==================================================

📍 Access Points:
   Frontend: http://localhost:5173
   Backend:  http://localhost:8000
   API:      http://localhost:8000/api

🔑 Login Credentials:
   Email:    admin@smarthospital.com
   Password: password
```

## 🎮 **Using Your System**

### **1. Open Your Browser**
Go to: **http://localhost:5173**

### **2. Login**
- Email: `admin@smarthospital.com`
- Password: `password`

### **3. Explore Features**
- **Dashboard** - Hospital statistics
- **Patients** - Manage patient records
- **Appointments** - Schedule management
- **Users** - Staff management
- **Reports** - Analytics

## 🔧 **Configuration Options**

### **Database Configuration**

#### **Option 1: SQLite (Default - Easiest)**
No configuration needed! The system uses SQLite by default.

#### **Option 2: MySQL (More Features)**
```bash
# Install MySQL
brew install mysql  # macOS
# or
sudo apt install mysql-server  # Ubuntu

# Start MySQL
brew services start mysql  # macOS
# or
sudo systemctl start mysql  # Ubuntu

# Create database
mysql -u root -p
CREATE DATABASE smart_hospital;
```

Then update `backend/.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=smart_hospital
DB_USERNAME=root
DB_PASSWORD=your_password
```

### **Port Configuration**
If ports 8000 or 5173 are busy, you can change them:

#### **Backend Port:**
```bash
cd backend
php artisan serve --port=8001  # Use port 8001
```

#### **Frontend Port:**
```bash
cd frontend
npm run dev -- --port 3000  # Use port 3000
```

## 🐳 **Docker Option (Advanced)**

If you want a more production-like setup:

```bash
# Install Docker Desktop
# Download from: https://www.docker.com/products/docker-desktop

# Start with Docker
docker-compose up -d

# Access at:
# Frontend: http://localhost:3000
# Backend: http://localhost:8000
```

## 📊 **Performance Optimization**

### **For Better Performance:**

1. **Enable OPcache (PHP)**
```bash
# Edit php.ini
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=4000
```

2. **Use Redis for Caching**
```bash
# Install Redis
brew install redis  # macOS
sudo apt install redis-server  # Ubuntu

# Start Redis
brew services start redis  # macOS
sudo systemctl start redis  # Ubuntu
```

3. **Optimize Laravel**
```bash
cd backend
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 🔒 **Security for Local Use**

### **Basic Security:**
1. **Change default passwords**
2. **Use HTTPS in production**
3. **Regular backups**
4. **Keep software updated**

### **Backup Your Data:**
```bash
# Backup database
cd backend
php artisan backup:run

# Backup files
tar -czf hospital_backup_$(date +%Y%m%d).tar.gz backend/storage frontend/dist
```

## 🚨 **Troubleshooting**

### **Common Issues:**

#### **Port Already in Use:**
```bash
# Find what's using the port
lsof -i :8000
lsof -i :5173

# Kill the process
kill -9 <PID>
```

#### **Permission Errors:**
```bash
# Fix Laravel permissions
cd backend
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

#### **Database Connection Error:**
```bash
# Reset database
cd backend
php artisan migrate:fresh --seed
```

#### **Frontend Build Errors:**
```bash
# Clear and reinstall
cd frontend
rm -rf node_modules package-lock.json
npm install
npm run dev
```

## 📱 **Mobile Access**

### **Access from Other Devices on Same Network:**

1. **Find your IP address:**
```bash
# macOS/Linux
ifconfig | grep "inet " | grep -v 127.0.0.1

# Windows
ipconfig | findstr "IPv4"
```

2. **Start with network access:**
```bash
# Backend
cd backend
php artisan serve --host=0.0.0.0 --port=8000

# Frontend
cd frontend
npm run dev -- --host 0.0.0.0
```

3. **Access from mobile:**
- Frontend: `http://YOUR_IP:5173`
- Backend: `http://YOUR_IP:8000`

## 💾 **Data Persistence**

### **Your data is stored in:**
- **Database**: `backend/database/database.sqlite` (SQLite)
- **Uploads**: `backend/storage/app/public/`
- **Logs**: `backend/storage/logs/`

### **To backup everything:**
```bash
# Create backup
tar -czf smart_hospital_backup_$(date +%Y%m%d_%H%M%S).tar.gz \
  backend/database/database.sqlite \
  backend/storage \
  backend/.env
```

## 🎯 **Production Deployment (When Ready)**

When you're ready to go live:

### **Option 1: VPS (Virtual Private Server)**
- **DigitalOcean**: $5/month
- **Linode**: $5/month  
- **Vultr**: $2.50/month

### **Option 2: Shared Hosting**
- **Hostinger**: $2/month
- **Namecheap**: $3/month

### **Option 3: Free Hosting**
- **Vercel** (Frontend): Free
- **Railway** (Backend): Free tier
- **PlanetScale** (Database): Free tier

## 🆘 **Getting Help**

### **If Something Goes Wrong:**

1. **Check logs:**
```bash
tail -f backend.log
tail -f frontend.log
```

2. **Restart everything:**
```bash
./stop-local.sh
./start-local.sh
```

3. **Reset to defaults:**
```bash
cd backend
php artisan migrate:fresh --seed
```

## 🎉 **You're All Set!**

Your Smart Hospital EMR system is now running locally on your desktop with:
- ✅ **Zero monthly costs**
- ✅ **Complete functionality**
- ✅ **Professional interface**
- ✅ **All modules working**
- ✅ **Easy to maintain**

**Start using your system at: http://localhost:5173**

---

**💡 Pro Tip:** Bookmark http://localhost:5173 and add it to your desktop for quick access!
