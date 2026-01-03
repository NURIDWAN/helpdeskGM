# Helpdesk System

Sistem manajemen helpdesk untuk pengelolaan tiket, work order, dan laporan kerja.

📖 **[Dokumentasi Lengkap (Notion)](https://abyssinian-drum-fbd.notion.site/Dokumentasi-Developer-Helpdesk-System-28e5c8649ea880f2bf55cd0a283df915)**

---

## 🛠️ Tech Stack

| Layer | Technology |
|-------|------------|
| **Backend** | Laravel 11, PHP 8.2, Sanctum, Spatie Permission |
| **Frontend** | Vue 3, Vite, Pinia, TailwindCSS, Vue Router |
| **Database** | MySQL 8.0 |
| **Development** | DDEV |
| **Integrations** | WhatsApp API (Fonnte), PDF Export (DomPDF) |

---

## 🏗️ Arsitektur Sistem

```
┌─────────────────────────────────────────────────────────────────┐
│                     Frontend (Vue.js SPA)                       │
│  Vue Router → Views/Pages → Components → Pinia Stores           │
│                        ↓ Axios (HTTP)                           │
├─────────────────────────────────────────────────────────────────┤
│                     Backend (Laravel)                           │
│  REST API → Controllers → Services → Repositories → Models     │
│                        ↓                                        │
│  Auth & RBAC (Spatie) ─ File Storage ─ Queue/Jobs              │
└─────────────────────────────────────────────────────────────────┘
         ↓                      ↓                    ↓
    Database           WhatsApp API            PDF Exporter
     (MySQL)             (Fonnte)              (WO/SPK)
```

---

## 📁 Struktur Project

```
helpdesk-system-main/
├── api/                    # Backend Laravel
│   ├── app/
│   │   ├── Http/Controllers/   # 21 Controllers
│   │   ├── Models/             # 18 Models
│   │   ├── Repositories/       # 15 Repositories
│   │   ├── Services/           # Business Logic
│   │   │   ├── WhatsAppNotificationService
│   │   │   ├── DailyUsageReportService
│   │   │   └── FileCompressionService
│   │   └── Observers/
│   ├── routes/
│   ├── database/
│   └── tests/
├── fe/                     # Frontend Vue.js
│   ├── src/
│   │   ├── stores/         # 21 Pinia stores
│   │   ├── views/          # Vue components
│   │   ├── components/     # Reusable components
│   │   └── plugins/        # Axios config
│   └── dist/
├── BUSINESS_DIAGRAM.md     # Diagram Bisnis
├── COMPREHENSIVE_QA_QC_REPORT.md
└── README.md
```

---

## 🎯 Modul Utama

| Modul | Fitur |
|-------|-------|
| **Ticketing** | CRUD, Multi-Staff Assignment, Status Update |
| **Work Order (SPK)** | CRUD, PDF Export, Status Sync |
| **Work Report** | CRUD, Template/Manual Input |
| **Daily Record** | Multi-meter (Gas, Air, Listrik), Usage Calculation |
| **WhatsApp Notification** | 4 Template, Personal & Group |
| **Dashboard** | Metrics, Charts, Rankings |

---

## 🚀 Quick Start

### Prerequisites
- [DDEV](https://ddev.readthedocs.io/) installed
- Node.js 18+

### Backend (Laravel)

```bash
cd api
ddev start
ddev composer install
ddev exec cp .env.example .env
ddev exec php artisan key:generate
ddev exec php artisan migrate --seed
```

### Frontend (Vue.js)

```bash
cd fe
npm install
npm run dev
```

---

## 🔐 Akun Default

| Email | Password | Role |
|-------|----------|------|
| superadmin@gmail.com | password | Superadmin |
| admin@gmail.com | password | Admin |
| staff@gmail.com | password | Staff |
| user@gmail.com | password | User |

---

## 🧪 Testing

```bash
cd api
ddev exec php artisan test
```

**Status**: ✅ 17 tests passed

---

## 📊 API Endpoints

Base URL: `/api/v1`

| Module | Endpoints |
|--------|-----------|
| Auth | `/auth/login`, `/auth/me`, `/auth/logout` |
| Tickets | `/tickets`, `/tickets/{id}`, `/tickets/export/*` |
| Work Orders | `/work-orders`, `/work-orders/{id}/pdf` |
| Work Reports | `/work-reports`, `/work-reports/export/*` |
| Daily Records | `/daily-records`, `/daily-records/report/*` |
| Dashboard | `/dashboard/*` |
| WhatsApp | `/whatsapp-settings`, `/whatsapp-templates` |

---

## 📋 Documentation

- [BUSINESS_DIAGRAM.md](./BUSINESS_DIAGRAM.md) - Flowchart & ERD
- [COMPREHENSIVE_QA_QC_REPORT.md](./COMPREHENSIVE_QA_QC_REPORT.md) - QA Report
- [PERMISSION_SYSTEM_REPORT.md](./PERMISSION_SYSTEM_REPORT.md) - RBAC Details
- [CHANGELOG.md](./CHANGELOG.md) - Version History

---

## 📝 License

MIT License
