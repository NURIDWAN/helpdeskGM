# PRD: Helpdesk GM — General Maintenance Helpdesk System

Dokumen ini merupakan Product Requirements Document (PRD) tingkat project yang mendokumentasikan keseluruhan sistem **Helpdesk GM** (juga dikenal sebagai "GA Maintenance"). Dokumen ini bersifat living document dan mengikat lintas modul; PRD spesifik-fitur berada di dalam `.kiro/specs/<nama-fitur>/`.

## 1. Ringkasan Produk

Helpdesk GM adalah aplikasi web internal untuk mengelola aktivitas maintenance gedung (General Affair) lintas cabang/outlet. Sistem menghubungkan pengguna cabang, tim maintenance, dan manajemen dalam satu alur kerja end-to-end mulai dari pelaporan tiket, penerbitan Surat Perintah Kerja (SPK/Work Order), pengerjaan dan pelaporan hasil kerja, pencatatan operasional harian (utility gas, air, listrik), hingga permintaan pengadaan/servis melalui Form Permintaan.

Aplikasi terdiri dari dua sisi:

- **Backend API** (`api/`) berbasis Laravel 12 (PHP 8.2+), berperan sebagai sumber kebenaran data, otentikasi via Sanctum, dan otorisasi via Spatie Permission.
- **Frontend SPA/PWA** (`fe/`) berbasis Vue 3 + Vite 6, sebagai antarmuka utama untuk seluruh peran pengguna, terinstal sebagai PWA dengan dukungan offline dan push notification.

## 2. Tujuan Bisnis

1. Memusatkan pencatatan gangguan dan permintaan pemeliharaan seluruh cabang dalam satu sistem yang dapat ditelusuri.
2. Mempercepat siklus penyelesaian dari pelaporan tiket sampai laporan pekerjaan selesai.
3. Menyediakan visibilitas data operasional (usage utility harian, kinerja staff, tren tiket) untuk pengambilan keputusan.
4. Mendigitalisasi Form Permintaan (pengadaan/penggantian/servis/jasa) menggantikan form fisik.
5. Menjamin kontrol akses berbasis peran yang granular sehingga tiap pengguna hanya melihat data dan fitur yang relevan.

## 3. Target Pengguna & Peran

Aplikasi menggunakan **RBAC granular** melalui Spatie Permission. Ada empat role utama plus dua role khusus alur permintaan:

| Role | Layout | Cakupan Akses |
|------|--------|--------------|
| `superadmin` | Admin | Seluruh permission tanpa kecuali, termasuk manajemen role dan pengaturan WhatsApp. |
| `admin` | Admin | Seluruh permission kecuali `role-create/edit/delete`, `whatsapp-setting-*`, dan `user-activity-*`. |
| `staff` | Admin | Operasional tiket, work order, work report, dan form permintaan (confirm/reject/view-all). Tidak memiliki akses daily/utility/electricity record. |
| `user` | App | Membuat tiket, mencatat laporan harian cabang & utility, melihat form permintaan (read-only). |
| `approver-permintaan` | Admin | Khusus untuk confirm/reject form permintaan (`form-permintaan-confirm`, `-reject`, `-view-all`). |
| `reviewer-permintaan` | Admin | Khusus untuk review form permintaan (`form-permintaan-review`, `-view-all`). |

Pemilihan layout ditentukan permission `system-admin-panel-access`:

- Memiliki permission → diarahkan ke rute `admin.*` (Admin Layout dengan sidebar penuh).
- Tanpa permission → diarahkan ke rute `app.*` (App Layout ringkas untuk end-user).

Preset role tambahan (Viewer Only, Staff Operasional, Admin Cabang, Supervisor) tersedia di `fe/src/config/permissionConfig.js` untuk mempercepat konfigurasi baru.

## 4. Ruang Lingkup Fungsional (Modul)

Setiap modul memiliki daftar permission `menu`, `list`, `create`, `edit`, `delete`, dan sebagian tambahan `update-status`/`view-all` yang di-seed di `PermissionSeeder`.

### 4.1 Otentikasi & Profil
- Login/logout via Sanctum personal access token dengan rate limit `throttle:login`.
- Update profil pengguna termasuk foto profil dan integrasi Telegram (widget login + generate token).
- Endpoint: `POST /auth/login`, `GET /auth/me`, `PUT /auth/me`, `POST /auth/me/photo`, `POST /auth/logout`.

### 4.2 Dashboard
- Metrik ringkas: total tiket, work order, laporan harian.
- Grafik: distribusi status, tiket per cabang, tren tiket, tren laporan staff.
- Ranking staff (paling banyak menyelesaikan, tercepat) dan top outlet usage.
- Endpoint: `GET /dashboard/metrics|status-distribution|tickets-per-branch|top-staff-resolved|fastest-staff|tickets-trend|staff-reports-trend|all|top-outlet-usage`.

### 4.3 Manajemen User & Role
- CRUD user (dengan role assignment via Spatie).
- CRUD role beserta permission matrix (`GET /roles/matrix`, `GET /roles/presets`, `GET /permissions`).
- Monitoring aktivitas user dan activity log (audit trail perubahan data lewat trait `LogsActivity`).

### 4.4 Master Data
- **Cabang / Branch**: identitas outlet berikut kode cabang (dipakai untuk penomoran).
- **Kategori Tiket**: klasifikasi tiket.
- **Template Job**: template pekerjaan berulang yang dapat diassign ke cabang beserta jadwalnya (`GET /job-schedules`, `GET /job-schedules/today` untuk kalender).
- **Meter Listrik**: multi-meter per cabang, mendukung mode WBP/LWBP.

### 4.5 Ticketing
- CRUD tiket termasuk balasan (`ticket_replies`) dan lampiran (`ticket_attachments`).
- Kode tiket auto-generate dan dapat diakses via `GET /tickets/code/{code}`.
- Update status termasuk `close`, export list ke PDF/Excel, notifikasi ke staff/group.
- Command terjadwal:
  - `tickets:check-unassigned` (per menit) — alert jika tiket belum di-assign > 1 jam.
  - `tickets:auto-close` (per jam) — auto-close tiket resolved yang sudah lama.

### 4.6 Work Order (SPK) & Work Report
- Work Order terikat ke tiket (opsional), mendukung staff banyak (`work_order_staff`), dokumen SPK, dan status.
- Download PDF SPK per unit (`GET /work-orders/{id}/pdf`).
- Work Report mencatat hasil kerja beserta lampiran, dapat diekspor ke PDF/Excel.

### 4.7 Laporan Harian Cabang & Utility
- Daily Record per branch per tanggal (unique `(branch_id, date)`).
- Pembacaan utility: gas & air (`utility-readings`) serta listrik multi-meter (`electricity-readings`).
- Laporan Daily Usage lintas cabang dengan filter tanggal, export PDF/Excel, dan indikator tanggal yang sudah dilaporkan (`GET /daily-records/report-dates`).
- Reset laporan usage per tanggal untuk keperluan koreksi.

### 4.8 Form Permintaan (Pengadaan/Servis)
- Wizard multi-step untuk staff mengajukan permintaan (pembelian, penggantian, servis, penggantian part, jasa).
- Auto-generate nomor `{DD}/{OUTLET_CODE}/FP{YY}/{M}/{YYYY}` dengan sequence unik per outlet-bulan-tahun.
- Alur status: `pending → progress → approved | rejected` (opsi `completed` di-deprecated).
- Endpoint utama `form-permintaan/*` termasuk `confirm`, `review`, `reject`, upload/download attachment, download PDF, dan export PDF/Excel dari list.
- Terhubung ke tiket via kolom `ticket_id` sehingga detail bisa navigasi langsung ke ticket terkait.

### 4.9 Notifikasi Multi-Kanal
- **Browser Push Notification** melalui `minishlink/web-push` dan service worker `browser-notification-sw.js`.
- **WhatsApp**: pengaturan (`whatsapp-settings`) dan template pesan yang dapat di-preview & test-send (termasuk grup).
- **Telegram**: webhook publik `POST telegram/webhook`, personal linking via widget login atau token, dukungan group.
- Notification channel switching per event: pilih WhatsApp atau Telegram sebagai kanal aktif.

### 4.10 Progressive Web App
- Build menghasilkan `manifest.webmanifest` dan service worker (`sw.js`) via `vite-plugin-pwa` (`generateSW`, `registerType: 'prompt'`).
- Precache asset build + strategi runtime: NetworkFirst (HTML & API), CacheFirst (JS/CSS, gambar, Google Fonts) dengan expiration ditetapkan.
- Offline fallback HTML self-contained, install prompt via `beforeinstallprompt`, dan update prompt via toast persistent ketika SW baru dalam state waiting.
- Push notification SW di-import ke SW utama sehingga kedua fungsi berjalan tanpa konflik.

## 5. Non-Fungsional

- **Keamanan**: seluruh endpoint (kecuali login & webhook Telegram) memerlukan `auth:sanctum` + Spatie permission middleware. Rate limiter `throttle:login` dan `throttle:api` diaktifkan.
- **Audit**: Trait `LogsActivity` mencatat perubahan model penting; log dapat dilihat di `/admin/activity-logs`.
- **Observability**: Sentry aktif di frontend (`@sentry/vue`) dan backend (`sentry/sentry-laravel`).
- **Kinerja**: index performa ditambahkan pada tabel besar (lihat migration `2026_01_10_120000_add_performance_indexes`). Query listing menggunakan pola repository `getAllPaginated` yang seragam.
- **Konsistensi UI**: komponen bersama di `fe/src/components/common/` (DataTable, Pagination, SearchInput, dsb.). Style TailwindCSS 4.
- **Format ekspor**: PDF via `barryvdh/laravel-dompdf`, Excel via `phpoffice/phpspreadsheet`.
- **Offline & Update**: strategi caching Workbox dengan timeout jelas, prompt update non-blocking dan persistent.

## 6. Tech Stack

### Backend (`api/`)
- PHP `^8.2`, Laravel `^12.0`
- Laravel Sanctum, Spatie Permission `^6`
- DomPDF, PhpSpreadsheet
- Web Push (`minishlink/web-push`)
- L5 Swagger untuk dokumentasi OpenAPI
- Sentry Laravel
- Pest & PHPUnit untuk testing, Laravel Pint untuk lint

### Frontend (`fe/`)
- Vue 3.5, Vite 6, Vue Router 4, Pinia 3
- TailwindCSS 4, Reka UI, Lucide icons
- Axios, Vue Sonner, Chart.js, Luxon, date-fns
- `vite-plugin-pwa` untuk PWA & Workbox
- `@vuepic/vue-datepicker`, `@vueuse/core`
- Vitest + Vue Test Utils + fast-check untuk property-based testing
- Sentry Vue

### Infrastruktur Development
- DDEV untuk lingkungan lokal backend (Docker-based).
- Vite dev server untuk frontend.
- Cron `* * * * * php artisan schedule:run` wajib di production untuk scheduled tasks.

## 7. Model Data (High-Level)

Migrations utama (lihat `api/database/migrations/`):

- `branches`, `users`, `personal_access_tokens`, `permission_tables` (Spatie).
- `tickets`, `ticket_categories`, `ticket_replies`, `ticket_attachments`, `ticket_staff`.
- `work_orders`, `work_order_staff`, `work_reports`, `work_report_attachments`.
- `job_types`, `job_templates`, `branch_job_templates`.
- `daily_records` (unique `(branch_id, date)`), `utility_categories`, `utility_readings`.
- `electricity_meters`, `electricity_readings`.
- `form_permintaan`, `form_permintaan_items`, `form_permintaan_attachments` (dengan kolom review/reject dan `ticket_id`).
- `whatsapp_settings`, `whatsapp_templates`.
- `browser_push_subscriptions`.
- `activity_logs`.

Semua model memakai foreign key dengan constraint sesuai kebutuhan (restrict untuk master data, cascade untuk detail item/attachment).

## 8. Routing (High-Level)

Base API: `/api/v1` (lihat `api/routes/api.php` untuk daftar lengkap).

Frontend routing (lihat `fe/src/router/index.js`) dipisah menjadi:

- `App` (`/`, `/tickets`, `/form-permintaan`, `/daily-records`, dst.) untuk end-user.
- `Admin` (`/admin/*`) untuk semua modul manajemen.
- `Auth` (`/auth/login`, `/auth/register`).
- Error pages (`/error/401|403|404|500`).

Router guard secara otomatis me-redirect antar layout berdasarkan permission `system-admin-panel-access` dan memvalidasi `meta.permission` / `meta.permissions` per rute.

## 9. Environment Variables

Frontend (`fe/.env`, `fe/.env.production`):

| Variable | Deskripsi |
|----------|-----------|
| `VITE_API_BASE_URL` | Base URL API (mis. `/api/v1` untuk proxy dev, atau URL absolut untuk production). |
| `VITE_API_PROXY_TARGET` | Target proxy Vite untuk development. |
| `VITE_APP_NAME` | Nama aplikasi (default: "GA Maintenance"). |
| `VITE_APP_ENV` | Environment tag. |
| `VITE_SENTRY_DSN`, `VITE_SENTRY_TRACES_SAMPLE_RATE` | Konfigurasi Sentry. |

Backend menggunakan konfigurasi Laravel standar (`api/.env.example`), termasuk kredensial DB, Sanctum, service integration (Telegram bot token, WhatsApp gateway, VAPID keys untuk Web Push).

## 10. Deployment & Operasional

1. Backend: DDEV lokal atau server Nginx/Apache + PHP-FPM. Jalankan `composer install --no-dev`, `php artisan migrate --seed`, `php artisan storage:link`.
2. Frontend: `npm run build` menghasilkan `fe/dist/` yang bisa dilayani dari CDN atau reverse proxy web.
3. Cron backend WAJIB aktif di production untuk `tickets:check-unassigned` dan `tickets:auto-close`.
4. Queue worker (`php artisan queue:listen`) diperlukan bila konfigurasi queue bukan `sync` (misal untuk push/WA/Telegram).
5. VAPID keys perlu disediakan agar push notification bekerja.
6. Sentry DSN disarankan diisi di kedua sisi untuk pemantauan error di production.

## 11. Metrik Sukses

- **Kinerja proses**: rata-rata waktu tiket dari `open` ke `resolved`, jumlah tiket auto-close dari sistem, jumlah tiket unassigned yang dialihkan tepat waktu.
- **Adopsi**: persentase cabang yang mengisi daily record harian, jumlah form permintaan digital vs. baseline offline.
- **Kualitas data**: rasio laporan usage yang lengkap per bulan, jumlah error validation di form submission.
- **Stabilitas**: error rate Sentry (FE + BE) di bawah threshold yang disepakati.

## 12. Risiko & Mitigasi

- **Volume ekspor besar** — export list tanpa pagination dapat lambat. Mitigasi: pola `getAllPaginated` seragam dan pertimbangan chunked export bila dibutuhkan.
- **Notifikasi eksternal down** (WhatsApp/Telegram/Push) — mitigasi dengan channel switching di `notification-settings` dan retry queue.
- **Data legacy dengan status deprecated** (mis. form permintaan `completed`) — UI harus tetap render nilai tersebut secara graceful (fallback badge) meski opsi tidak dapat dipilih lagi.
- **PWA cache stale** — strategi `NetworkFirst` untuk HTML/API dan update prompt persistent memastikan versi baru tetap sampai ke pengguna.
- **Ketergantungan cron** — tanpa cron production, alert & auto-close tidak berjalan. Mitigasi: dokumentasi setup di `api/README.md` dan health-check periodik.

## 13. Roadmap Ringkas (Referensi Spec)

Spec detail per fitur berada di `.kiro/specs/`:

- `form-permintaan/` — implementasi awal modul Form Permintaan.
- `form-permintaan-improvements/` — export list, penyederhanaan status, navigasi ke ticket.
- `role-permission-management/` — matrix & preset role.
- `role-fitur-permintaan/` — role khusus approver/reviewer permintaan.
- `laporan-harian-kategori/` — kategorisasi laporan harian.
- `update-harian-cabang/` — penambahan kolom `date` eksplisit di daily record.
- `riset-listrik-turunan/` — perhitungan turunan konsumsi listrik.
- `dashboard-top-outlet-usage/` — widget top outlet.
- `date-picker-report-indicator/`, `date-picker-crash-fix/` — perbaikan date picker.
- `pwa-frontend/` — pengenalan PWA & offline support.

## 14. Referensi Cepat

- Backend routes: `api/routes/api.php`
- Permission katalog: `api/database/seeders/PermissionSeeder.php`
- Role & preset: `api/database/seeders/RoleSeeder.php` dan `fe/src/config/permissionConfig.js`
- Router frontend: `fe/src/router/index.js`
- Store Pinia: `fe/src/stores/`
- Komponen bersama: `fe/src/components/common/`
- Scheduled task backend: `api/README.md` bagian "Scheduled Tasks"
- Dokumentasi API: L5 Swagger (`/api/documentation` bila diaktifkan)
