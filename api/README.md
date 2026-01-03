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
ddev exec php artisan test
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
