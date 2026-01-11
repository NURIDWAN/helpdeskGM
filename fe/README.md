# Helpdesk GM Frontend

Frontend Vue 3 untuk Helpdesk System General Maintenance - sistem manajemen tiket, work order, dan laporan pekerjaan untuk maintenance gedung.

## Dokumentasi

| Dokumen | Deskripsi |
|---------|-----------|
| [README.md](README.md) | Dokumentasi utama (ini) |
| [docs/WORKFLOW.md](docs/WORKFLOW.md) | Alur proses & flowchart sistem (Mermaid) |

## Tech Stack

| Category | Technology |
|----------|------------|
| Framework | Vue 3 + Composition API |
| Build Tool | Vite 6 |
| State Management | Pinia 3 |
| Styling | TailwindCSS 4 |
| Routing | Vue Router 4 |
| HTTP Client | Axios |
| Icons | Lucide Vue Next |
| Charts | Chart.js |
| Date/Time | Luxon |
| Testing | Vitest + Vue Test Utils |

## Prerequisites

- Node.js >= 18.x
- npm >= 9.x

## Installation

1. Clone repository
```bash
git clone <repository-url>
cd fe
```

2. Install dependencies
```bash
npm install
```

3. Setup environment
```bash
cp .env.example .env
```

4. Edit `.env` dan sesuaikan konfigurasi:
```env
VITE_API_BASE_URL=http://localhost:8000/api/v1
```

## Development

Jalankan development server:
```bash
npm run dev
```

Aplikasi akan berjalan di `http://localhost:5173`

## Build

Build untuk production:
```bash
npm run build
```

Preview production build:
```bash
npm run preview
```

## Testing

```bash
# Jalankan test dalam watch mode
npm run test

# Jalankan test sekali
npm run test:run

# Test dengan coverage
npm run test:coverage

# Test dengan UI
npm run test:ui
```

---

## Role & Permission System

Aplikasi ini menggunakan sistem **Role-Based Access Control (RBAC)** dengan permission yang granular. Setiap user memiliki role, dan setiap role memiliki kumpulan permission yang menentukan akses ke fitur.

### Tipe Layout

| Layout | Permission | Deskripsi |
|--------|------------|-----------|
| **Admin Layout** | `system-admin-panel-access` | Akses ke panel admin dengan sidebar lengkap |
| **App Layout** | - | Layout sederhana untuk user biasa (membuat tiket, melihat status) |

### Role Presets

Berikut adalah preset role yang tersedia untuk mempercepat konfigurasi:

#### 1. Viewer Only
**Deskripsi:** Hanya dapat melihat data tanpa melakukan perubahan

| Fitur | Akses |
|-------|-------|
| Dashboard | Lihat menu, halaman, metrik, grafik |
| Tiket | Lihat daftar |
| Work Order (SPK) | Lihat daftar |
| Laporan Pekerjaan | Lihat daftar |
| Laporan Harian | Lihat daftar |

---

#### 2. Staff Operasional
**Deskripsi:** Akses penuh ke tiket, SPK, dan laporan pekerjaan untuk operasional harian

| Fitur | Akses |
|-------|-------|
| Dashboard | Lihat menu, halaman, metrik |
| Tiket | Lihat, buat, edit, ubah status |
| Balasan Tiket | Lihat, buat, edit |
| Lampiran Tiket | Lihat, upload |
| Work Order (SPK) | Lihat, buat, edit, ubah status |
| Laporan Pekerjaan | Lihat, buat, edit |
| Lampiran Laporan | Lihat, upload |

---

#### 3. Admin Cabang
**Deskripsi:** Kelola laporan harian dan data cabang

| Fitur | Akses |
|-------|-------|
| Dashboard | Lihat menu, halaman, metrik, grafik |
| Cabang | Lihat daftar, edit |
| Laporan Harian | Lihat, buat, edit |
| Pembacaan Utility | Lihat, input, edit (gas, air) |
| Meter Listrik | Lihat daftar |
| Pembacaan Listrik | Lihat, input, edit |

---

#### 4. Supervisor
**Deskripsi:** Akses lengkap untuk supervisi tim dengan kemampuan delete

| Fitur | Akses |
|-------|-------|
| Dashboard | Lihat menu, halaman, metrik, grafik, ranking staff, tren |
| Tiket | CRUD lengkap + ubah status |
| Balasan Tiket | CRUD lengkap |
| Lampiran Tiket | Lihat, upload, hapus |
| Work Order (SPK) | CRUD lengkap + ubah status |
| Laporan Pekerjaan | CRUD lengkap |
| Lampiran Laporan | Lihat, upload, hapus |
| Laporan Harian | CRUD lengkap |
| Pembacaan Utility | CRUD lengkap |

---

## Daftar Fitur & Permission

### 1. Akses Sistem
| Permission | Deskripsi |
|------------|-----------|
| `system-admin-panel-access` | Akses ke Admin Layout (panel admin) |

### 2. Dashboard
| Permission | Deskripsi |
|------------|-----------|
| `dashboard-menu` | Menampilkan menu dashboard di sidebar |
| `dashboard-view` | Melihat halaman utama dashboard |
| `dashboard-view-metrics` | Melihat kartu metrik (total tiket, SPK, dll) |
| `dashboard-view-charts` | Melihat grafik statistik |
| `dashboard-view-staff-rankings` | Melihat peringkat kinerja staff |
| `dashboard-view-trends` | Melihat analisis tren data |
| `dashboard-view-all` | Akses data dashboard semua cabang |

### 3. Manajemen User
| Permission | Deskripsi |
|------------|-----------|
| `user-menu` | Menampilkan menu user di sidebar |
| `user-list` | Melihat daftar semua user |
| `user-create` | Membuat user baru |
| `user-edit` | Mengedit data user |
| `user-delete` | Menghapus user dari sistem |

### 4. Manajemen Role
| Permission | Deskripsi |
|------------|-----------|
| `role-menu` | Menampilkan menu role di sidebar |
| `role-list` | Melihat daftar role |
| `role-create` | Membuat role baru |
| `role-edit` | Mengedit role dan permission |
| `role-delete` | Menghapus role |

### 5. Manajemen Cabang
| Permission | Deskripsi |
|------------|-----------|
| `branch-menu` | Menampilkan menu cabang di sidebar |
| `branch-list` | Melihat daftar cabang |
| `branch-create` | Menambah cabang baru |
| `branch-edit` | Mengedit data cabang |
| `branch-delete` | Menghapus cabang |
| `branch-view-all` | Akses data semua cabang |

### 6. Template Job
| Permission | Deskripsi |
|------------|-----------|
| `job-template-menu` | Menampilkan menu template job di sidebar |
| `job-template-list` | Melihat daftar template |
| `job-template-create` | Membuat template baru |
| `job-template-edit` | Mengedit template |
| `job-template-delete` | Menghapus template |
| `job-template-view-all` | Akses template semua cabang |

### 7. Manajemen Tiket
| Permission | Deskripsi |
|------------|-----------|
| `ticket-menu` | Menampilkan menu tiket di sidebar |
| `ticket-list` | Melihat daftar tiket |
| `ticket-create` | Membuat tiket baru |
| `ticket-edit` | Mengedit data tiket |
| `ticket-delete` | Menghapus tiket |
| `ticket-update-status` | Mengubah status tiket |
| `ticket-view-all` | Akses tiket semua cabang |

#### 7.1 Balasan Tiket
| Permission | Deskripsi |
|------------|-----------|
| `ticket-reply-list` | Melihat daftar balasan tiket |
| `ticket-reply-create` | Menambah balasan pada tiket |
| `ticket-reply-edit` | Mengedit balasan |
| `ticket-reply-delete` | Menghapus balasan |

#### 7.2 Lampiran Tiket
| Permission | Deskripsi |
|------------|-----------|
| `ticket-attachment-list` | Melihat daftar lampiran |
| `ticket-attachment-create` | Mengupload file lampiran |
| `ticket-attachment-delete` | Menghapus lampiran |

### 8. Kategori Tiket
| Permission | Deskripsi |
|------------|-----------|
| `ticket-category-menu` | Menampilkan menu kategori di sidebar |
| `ticket-category-list` | Melihat daftar kategori |
| `ticket-category-create` | Membuat kategori baru |
| `ticket-category-edit` | Mengedit kategori |
| `ticket-category-delete` | Menghapus kategori |

### 9. Surat Perintah Kerja (SPK)
| Permission | Deskripsi |
|------------|-----------|
| `work-order-menu` | Menampilkan menu SPK di sidebar |
| `work-order-list` | Melihat daftar SPK |
| `work-order-create` | Membuat SPK baru |
| `work-order-edit` | Mengedit SPK |
| `work-order-delete` | Menghapus SPK |
| `work-order-update-status` | Mengubah status SPK |
| `work-order-view-all` | Akses SPK semua cabang |

### 10. Laporan Pekerjaan
| Permission | Deskripsi |
|------------|-----------|
| `work-report-menu` | Menampilkan menu laporan di sidebar |
| `work-report-list` | Melihat daftar laporan |
| `work-report-create` | Membuat laporan baru |
| `work-report-edit` | Mengedit laporan |
| `work-report-delete` | Menghapus laporan |
| `work-report-view-all` | Akses laporan semua cabang |

#### 10.1 Lampiran Laporan
| Permission | Deskripsi |
|------------|-----------|
| `work-report-attachment-list` | Melihat daftar lampiran |
| `work-report-attachment-create` | Mengupload file lampiran |
| `work-report-attachment-delete` | Menghapus lampiran |

### 11. Laporan Harian
| Permission | Deskripsi |
|------------|-----------|
| `daily-record-menu` | Menampilkan menu laporan harian di sidebar |
| `daily-record-list` | Melihat daftar laporan harian |
| `daily-record-create` | Membuat laporan harian baru |
| `daily-record-edit` | Mengedit laporan harian |
| `daily-record-delete` | Menghapus laporan harian |
| `daily-record-view-all` | Akses laporan semua cabang |

#### 11.1 Pembacaan Utility (Gas & Air)
| Permission | Deskripsi |
|------------|-----------|
| `utility-reading-list` | Melihat daftar pembacaan utility |
| `utility-reading-create` | Menginput pembacaan utility |
| `utility-reading-edit` | Mengedit pembacaan utility |
| `utility-reading-delete` | Menghapus pembacaan utility |
| `utility-reading-view-all` | Akses pembacaan semua cabang |

### 12. Manajemen Listrik

#### 12.1 Meter Listrik
| Permission | Deskripsi |
|------------|-----------|
| `electricity-meter-menu` | Menampilkan menu meter di sidebar |
| `electricity-meter-list` | Melihat daftar meter |
| `electricity-meter-create` | Menambah meter baru |
| `electricity-meter-edit` | Mengedit data meter |
| `electricity-meter-delete` | Menghapus meter |

#### 12.2 Pembacaan Meter Listrik
| Permission | Deskripsi |
|------------|-----------|
| `electricity-reading-list` | Melihat daftar pembacaan |
| `electricity-reading-create` | Menginput pembacaan meter |
| `electricity-reading-edit` | Mengedit pembacaan |
| `electricity-reading-delete` | Menghapus pembacaan |

### 13. Pengaturan WhatsApp
| Permission | Deskripsi |
|------------|-----------|
| `whatsapp-setting-menu` | Menampilkan menu WhatsApp di sidebar |
| `whatsapp-setting-list` | Melihat pengaturan WhatsApp |
| `whatsapp-setting-edit` | Mengubah pengaturan WhatsApp |

### 14. Monitoring Aktivitas User
| Permission | Deskripsi |
|------------|-----------|
| `user-activity-menu` | Menampilkan menu aktivitas di sidebar |
| `user-activity-list` | Melihat log aktivitas user |

---

## Project Structure

```
src/
├── App.vue                 # Root component
├── main.js                 # Application entry point
├── index.css               # Global styles
├── components/
│   ├── admin/              # Admin-specific components
│   │   └── Sidebar.vue
│   ├── app/                # App-specific components
│   │   └── Navbar.vue
│   ├── common/             # Reusable components
│   │   ├── Alert.vue
│   │   ├── AttachmentViewDialog.vue
│   │   ├── ConfirmationModal.vue
│   │   ├── DataTable.vue
│   │   ├── FormCard.vue
│   │   ├── FormField.vue
│   │   ├── JobCalendar.vue
│   │   ├── MultiSelect.vue
│   │   ├── Pagination.vue
│   │   ├── SearchableSelect.vue
│   │   ├── SearchInput.vue
│   │   └── ToastContainer.vue
│   └── dailyrecord/        # Daily record components
│       ├── GasWaterUtilityForm.vue
│       └── MultiMeterElectricityForm.vue
├── composables/            # Vue composables
│   └── usePermissionManager.js
├── config/                 # Configuration files
│   └── permissionConfig.js # Permission & role presets
├── helpers/                # Utility functions
│   ├── errorHelper.js
│   ├── format.js
│   ├── permissionHelper.js
│   └── toastHelper.js
├── layouts/
│   ├── Admin.vue           # Layout untuk admin (sidebar)
│   ├── App.vue             # Layout untuk user biasa
│   └── Auth.vue            # Layout untuk login/register
├── plugins/
│   └── axios.js            # Axios configuration
├── router/
│   └── index.js            # Vue Router configuration
├── stores/                 # Pinia stores (21 stores)
│   ├── auth.js             # Authentication
│   ├── branch.js           # Branch management
│   ├── dailyRecord.js      # Daily records
│   ├── dashboard.js        # Dashboard data
│   ├── electricityMeter.js # Electricity meters
│   ├── electricityReading.js
│   ├── jobSchedule.js
│   ├── jobTemplate.js
│   ├── role.js
│   ├── ticket.js
│   ├── ticketAttachment.js
│   ├── ticketCategory.js
│   ├── ticketReply.js
│   ├── toast.js
│   ├── user.js
│   ├── userActivity.js
│   ├── utilityReading.js
│   ├── whatsappSetting.js
│   ├── workOrder.js
│   ├── workReport.js
│   └── workReportAttachment.js
└── views/
    ├── admin/              # Admin pages
    │   ├── Dashboard.vue
    │   ├── Profile.vue
    │   ├── branch/         # Branch CRUD
    │   ├── dailyrecord/    # Daily record CRUD
    │   ├── jobtemplate/    # Job template CRUD
    │   ├── role/           # Role CRUD
    │   ├── ticket/         # Ticket CRUD
    │   ├── ticketcategory/ # Ticket category CRUD
    │   ├── user/           # User CRUD + Activity Monitor
    │   ├── whatsapp/       # WhatsApp settings
    │   ├── workorder/      # Work order CRUD
    │   └── workreport/     # Work report CRUD
    ├── app/                # User pages
    │   ├── Dashboard.vue
    │   ├── Profile.vue
    │   ├── TicketCreate.vue
    │   └── TicketDetail.vue
    ├── auth/
    │   ├── Login.vue
    │   └── Register.vue
    └── errors/
        ├── ErrorPage.vue
        ├── Forbidden.vue   # 403
        ├── NotFound.vue    # 404
        ├── ServerError.vue # 500
        └── Unauthorized.vue # 401
```

---

## Alur Kerja Aplikasi

### 1. User Biasa (Tanpa Admin Panel Access)
```
Login → App Dashboard → Buat Tiket → Lihat Status Tiket → Balas Tiket
                     → Laporan Harian Cabang (jika punya permission)
```

### 2. Staff Operasional
```
Login → Admin Dashboard → Kelola Tiket → Buat SPK dari Tiket 
                       → Kerjakan SPK → Buat Laporan Pekerjaan
```

### 3. Admin Cabang
```
Login → Admin Dashboard → Input Laporan Harian
                       → Input Pembacaan Meter (Listrik, Gas, Air)
                       → Lihat Laporan Usage
```

### 4. Supervisor/Manager
```
Login → Admin Dashboard → Monitoring Semua Tiket & SPK
                       → Lihat Ranking Staff
                       → Lihat Tren & Analisis
                       → Kelola User & Role
```

---

## Environment Variables

| Variable | Description | Example |
|----------|-------------|---------|
| `VITE_API_BASE_URL` | Base URL untuk API backend | `http://localhost:8000/api/v1` |

## Scripts

| Script | Description |
|--------|-------------|
| `npm run dev` | Jalankan development server |
| `npm run build` | Build untuk production |
| `npm run preview` | Preview production build |
| `npm run test` | Jalankan test (watch mode) |
| `npm run test:run` | Jalankan test sekali |
| `npm run test:coverage` | Test dengan coverage report |
| `npm run test:ui` | Test dengan Vitest UI |

## API Integration

Aplikasi ini menggunakan Axios untuk komunikasi dengan backend API. Konfigurasi axios tersedia di `src/plugins/axios.js` dengan fitur:
- Base URL dari environment variable
- Interceptor untuk JWT token
- Error handling global
- Redirect ke halaman error (401, 403, 500)

## Contributing

1. Buat branch baru dari `main`
2. Commit perubahan dengan pesan yang jelas
3. Push ke branch dan buat Pull Request
