# ⚡ Quick Start Guide - WHUSNET Admin Payment

**Estimated Time:** 5 minutes  
**Prerequisites:** Docker & Docker Compose installed  
**Goal:** Get the application running locally

---

## 🚀 5-Minute Setup

### Step 1: Clone & Navigate (30 seconds)

```bash
git clone <repository-url>
cd Admin-Payment
```

### Step 2: Environment Setup (1 minute)

```bash
# Copy environment file
cp .env.example .env

# Generate application key (will be done in container)
```

### Step 3: Start Docker Services (2 minutes)

```bash
# Start all services
docker-compose up -d

# Wait for services to be ready (check with)
docker-compose ps
```

Expected output:
```
NAME                STATUS
whusnet-app         Up
whusnet-nginx       Up
whusnet-db          Up
whusnet-redis       Up
whusnet-horizon     Up
whusnet-reverb      Up
```

### Step 4: Application Setup (1.5 minutes)

```bash
# Enter app container
docker exec -it whusnet-app bash

# Inside container:
composer install
php artisan key:generate
php artisan migrate
php artisan storage:link

# (Optional) Seed sample data
php artisan db:seed

# Exit container
exit
```

### Step 5: Access Application (30 seconds)

Open your browser and navigate to:
- **Application:** http://localhost:8000
- **API Docs:** http://localhost:8000/docs/api
- **phpMyAdmin:** http://localhost:8080
- **Horizon:** http://localhost:8000/horizon

---

## 🔐 Default Login Credentials

After seeding, you can login with:

| Role | Email | Password |
|------|-------|----------|
| Owner | owner@whusnet.com | password |
| Admin | admin@whusnet.com | password |
| Atasan | atasan@whusnet.com | password |
| Teknisi | teknisi@whusnet.com | password |

> ⚠️ **Security Note:** Change these passwords immediately in production!

---

## ✅ Verification Checklist

Verify your installation:

- [ ] Application loads at http://localhost:8000
- [ ] Can login with default credentials
- [ ] Dashboard displays without errors
- [ ] Horizon dashboard accessible at /horizon
- [ ] API documentation loads at /docs/api
- [ ] Redis is running (check Horizon)
- [ ] Database connection works (check dashboard stats)

---

## 🎯 Your First Transaction

Let's create your first Rembush (Reimbursement) transaction:

### 1. Login as Teknisi
- Email: `teknisi@whusnet.com`
- Password: `password`

### 2. Navigate to Rembush
- Click **"Buat Transaksi"** button
- Select **"Rembush"**

### 3. Upload Receipt
- Upload a sample receipt image
- Wait for OCR processing (15-30 seconds)
- System will auto-fill data from the receipt

### 4. Complete Form
- Verify extracted data
- Select category
- Choose branch allocation
- Click **"Submit"**

### 5. Approve Transaction (as Admin)
- Logout and login as `admin@whusnet.com`
- Go to Dashboard
- Find pending transaction
- Click **"Approve"**

### 6. Upload Payment Proof
- Click **"Upload Bukti Bayar"**
- Select payment method (Transfer/Cash)
- Upload payment proof
- Submit

**Congratulations!** 🎉 You've completed your first transaction flow!

---

## 🐛 Common Issues

### Issue: "Connection refused" when accessing app

**Solution:**
```bash
# Check if all containers are running
docker-compose ps

# Restart services
docker-compose restart
```

### Issue: "Database connection error"

**Solution:**
```bash
# Check database container
docker-compose logs db

# Verify .env database credentials match docker-compose.yml
DB_HOST=whusnet-db
DB_PORT=3306
DB_DATABASE=admin-payment
```

### Issue: "Queue not processing"

**Solution:**
```bash
# Check Horizon
docker-compose logs horizon

# Restart Horizon
docker-compose restart horizon
```

### Issue: "WebSocket connection failed"

**Solution:**
```bash
# Check Reverb service
docker-compose logs reverb

# Verify Reverb configuration in .env
REVERB_APP_ID=your-app-id
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
```

---

## 📚 Next Steps

Now that you have the application running:

1. 📖 **Read the full documentation:** [README.md](../../README.md)
2. 🏗️ **Understand the architecture:** [ARCHITECTURE_DIAGRAM.md](../architecture/ARCHITECTURE_DIAGRAM.md)
3. 🗄️ **Learn the database structure:** [DATABASE_SCHEMA.md](../architecture/DATABASE_SCHEMA.md)
4. 🔧 **Configure for your needs:** [CONFIGURATION.md](CONFIGURATION.md)
5. 🤝 **Start contributing:** [CONTRIBUTING.md](../contributing/CONTRIBUTING.md)

---

## 🆘 Need Help?

- 📖 **Documentation:** [DOCUMENTATION_INDEX.md](../../DOCUMENTATION_INDEX.md)
- 🐛 **Troubleshooting:** [TROUBLESHOOTING.md](../operations/TROUBLESHOOTING.md)
- 💬 **Support:** [support@whusnet.com]
- 📝 **Issues:** Create an issue in the repository

---

## 🎓 Learning Resources

### Video Tutorials
- [ ] System Overview (Coming Soon)
- [ ] First Transaction Walkthrough (Coming Soon)
- [ ] Admin Dashboard Tour (Coming Soon)

### Documentation
- [Full Installation Guide](INSTALLATION.md)
- [Configuration Guide](CONFIGURATION.md)
- [User Roles & Permissions](../reference/ROLES_PERMISSIONS.md)

---

**Setup Time:** ~5 minutes  
**Last Updated:** 4 Mei 2026  
**Maintainer:** WHUSNET Development Team

---

*Having trouble? Check [TROUBLESHOOTING.md](../operations/TROUBLESHOOTING.md) or contact support.*
