# Helpdesk API (Backend)

Backend API untuk Helpdesk System menggunakan Laravel 11.

## 🚀 Setup

```bash
ddev start
ddev composer install
ddev exec cp .env.example .env
ddev exec php artisan key:generate
ddev exec php artisan migrate --seed
```

## 🧪 Testing

```bash
ddev artisan test
```

## 📊 API Routes

Base: `/api/v1`

| Resource | Routes |
|----------|--------|
| Auth | `POST /auth/login`, `GET /auth/me`, `POST /auth/logout` |
| Tickets | `GET /tickets`, `POST /tickets`, `GET /tickets/{id}` |
| Work Orders | CRUD `/work-orders` |
| Work Reports | CRUD `/work-reports` |
| Daily Records | CRUD `/daily-records` |
| Dashboard | `GET /dashboard/*` |

## 📁 Structure

```
app/
├── Http/Controllers/    # 21 Controllers
├── Models/              # 18 Models
├── Repositories/        # 15 Repositories
└── Services/            # Business Logic
```

## 🔐 Permissions

69 permissions managed via Spatie Permission.

Roles: `superadmin`, `admin`, `staff`, `user`

## ⏰ Scheduled Tasks (PENTING!)

Aplikasi ini memiliki tugas terjadwal yang **WAJIB** dijalankan di server produksi.

### Setup Cron Job (Produksi)

Tambahkan entry berikut ke crontab server:

```bash
# Edit crontab
crontab -e

# Tambahkan baris ini
* * * * * cd /path/to/api && php artisan schedule:run >> /dev/null 2>&1
```

### Daftar Scheduled Commands

| Command | Interval | Deskripsi |
|---------|----------|-----------|
| `tickets:check-unassigned` | Setiap menit | Alert jika tiket belum di-assign > 1 jam |
| `tickets:auto-close` | Setiap jam | Auto-close tiket resolved yang sudah lama |

### Testing Manual

```bash
# Jalankan semua schedule
ddev exec php artisan schedule:run

# Jalankan command spesifik
ddev exec php artisan tickets:check-unassigned
ddev exec php artisan tickets:auto-close
```

> ⚠️ **PENTING**: Tanpa cron job, fitur notifikasi otomatis TIDAK akan berjalan!
