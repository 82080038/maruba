# Maruba Koperasi - Complete Setup Guide

## 🚀 Quick Start

### Prerequisites
- Docker and Docker Compose installed
- Git installed
- Terminal/Command Line

### One-Command Setup
```bash
git clone https://github.com/82080038/maruba.git
cd maruba
chmod +x setup_complete.sh
./setup_complete.sh
```

## 🌐 Access Information

### Application URL
- **Main Application**: http://localhost:8080
- **Database**: localhost:3306 (MySQL)

### Login Credentials
| Role | Username | Password |
|------|----------|----------|
| Admin | admin | admin123 |
| Manager | manager | manager123 |
| Kasir | kasir | kasir123 |
| Teller | teller | teller123 |
| Surveyor | surveyor | surveyor123 |
| Collector | collector | collector123 |
| Akuntansi | akuntansi | akuntansi123 |
| Staf | staf | staf123 |

## 📋 Features

### ✅ Working Features
- **Authentication System**: Complete login/logout with 8 roles
- **Role-Based Navigation**: Different menus per role
- **Dashboard**: Statistics cards and quick actions
- **Bootstrap UI**: Professional, responsive interface
- **Quick Login**: One-click login buttons
- **Session Management**: Secure session handling
- **FontAwesome Icons**: Visual indicators throughout

### 🎯 Navigation Menu Per Role
- **Admin**: 8 menu items (Dashboard, Anggota, Simpanan, Pinjaman, Pembayaran, Survey, Laporan, Pengaturan)
- **Kasir**: 4 menu items (Dashboard, Simpanan, Pembayaran, Laporan)
- **Manager**: 5 menu items (Dashboard, Anggota, Simpanan, Pinjaman, Laporan)
- **Teller**: 3 menu items (Dashboard, Simpanan, Anggota)
- **Surveyor**: 3 menu items (Dashboard, Survey, Anggota)
- **Collector**: 3 menu items (Dashboard, Penagihan, Anggota)
- **Akuntansi**: 3 menu items (Dashboard, Akuntansi, Laporan Keuangan)

### 🔧 Quick Actions Per Role
- **Admin**: 6 actions (Tambah Anggota, Simpanan Baru, Pinjaman Baru, Pembayaran, Survey Baru, Laporan)
- **Kasir**: 4 actions (Simpanan Baru, Penarikan, Pembayaran, Laporan Harian)
- **Manager**: 4 actions (Tambah Anggota, Simpanan Baru, Pinjaman Baru, Laporan)
- **Teller**: 3 actions (Setoran Baru, Penarikan, Cek Saldo)
- **Surveyor**: 3 actions (Survey Baru, Daftar Survey, Registrasi Anggota)
- **Collector**: 3 actions (Jadwal Penagihan, Pembayaran Baru, Daftar Anggota)
- **Akuntansi**: 4 actions (Jurnal Umum, Buku Besar, Neraca, Laporan Laba Rugi)

## 🛠️ Development Setup

### Manual Setup (Alternative)
```bash
# Clone repository
git clone https://github.com/82080038/maruba.git
cd maruba

# Start MySQL
docker run -d --name maruba-mysql --network maruba-network -e MYSQL_ROOT_PASSWORD=root -e MYSQL_DATABASE=maruba -p 3306:3306 mysql:8.0

# Build and start application
docker build -t maruba-app .
docker run -d --name maruba-app --network maruba-network -p 8080:80 maruba-app
```

### Database Setup
```bash
# Import database (if needed)
docker exec -i maruba-mysql mysql -uroot -proot maruba < database_setup.sql
```

## 🔄 Docker Commands

### Start/Stop
```bash
# Start containers
docker start maruba-app maruba-mysql

# Stop containers
docker stop maruba-app maruba-mysql

# Restart containers
docker restart maruba-app maruba-mysql
```

### Rebuild Application
```bash
# Build new image
docker build -t maruba-app .

# Stop and remove old container
docker stop maruba-app
docker rm maruba-app

# Start new container
docker run -d --name maruba-app --network maruba-network -p 8080:80 maruba-app
```

### View Logs
```bash
# Application logs
docker logs maruba-app

# MySQL logs
docker logs maruba-mysql
```

## 🗄️ Database Information

### Connection Details
- **Host**: localhost
- **Port**: 3306
- **Database**: maruba
- **Username**: root
- **Password**: root

### Key Tables
- `users` - User authentication and roles
- `members` - Member data
- `loans` - Loan information
- `savings_accounts` - Savings accounts
- `repayments` - Payment records

## 🐛 Troubleshooting

### Common Issues

#### Application Not Loading
```bash
# Check if containers are running
docker ps

# Check application logs
docker logs maruba-app

# Restart containers
docker restart maruba-app maruba-mysql
```

#### Database Connection Issues
```bash
# Check MySQL container
docker logs maruba-mysql

# Test database connection
docker exec maruba-mysql mysql -uroot -proot -e "SHOW DATABASES;"
```

#### Port Conflicts
```bash
# Check port usage
netstat -tulpn | grep :8080

# Use different port
docker run -d --name maruba-app --network maruba-network -p 8081:80 maruba-app
```

## 📱 Mobile Access

The application is fully responsive and works on:
- Desktop browsers
- Tablets
- Mobile phones

## 🔒 Security Features

- Session-based authentication
- Role-based access control
- Input validation
- CSRF protection
- Secure password handling

## 📞 Support

For issues or questions:
1. Check the troubleshooting section
2. Review the logs
3. Verify Docker is running correctly
4. Ensure ports are available

## 🚀 Production Deployment

For production deployment:
1. Update environment variables
2. Configure SSL certificates
3. Set up proper domain
4. Configure backup systems
5. Monitor application performance

---

**Status**: ✅ Complete and Working  
**Last Updated**: 2026-03-06  
**Version**: 2.0.0
