# PRD: Form Permintaan Improvements

## Ringkasan

Peningkatan pada modul Form Permintaan di aplikasi Helpdesk GM, mencakup tiga area utama: export data list (PDF/Excel), penyederhanaan status menjadi read-only untuk status final, dan navigasi langsung ke ticket terkait dari halaman detail.

## Latar Belakang & Masalah

- Admin belum bisa mengekspor daftar form permintaan untuk kebutuhan dokumentasi atau review, padahal fitur serupa sudah ada di modul Ticket.
- Status "completed" pada form permintaan tidak relevan lagi dan dapat membingungkan pengguna karena status "approved" seharusnya sudah final.
- Admin/pengguna tidak dapat mengakses ticket terkait langsung dari halaman detail form permintaan, sehingga perlu mencari manual di modul Ticket.

## Tujuan

1. Admin dapat mengekspor daftar form permintaan (PDF & Excel) dengan filter aktif yang sedang diterapkan.
2. Status "approved" dan "rejected" ditampilkan sebagai badge read-only, dan opsi "completed" dihapus dari seluruh alur status.
3. Pengguna dapat mengklik kode ticket di halaman detail form permintaan untuk langsung menuju halaman detail ticket terkait.

## Target Pengguna

- **Admin**: mengelola, memfilter, dan mengekspor daftar form permintaan.
- **User/App context**: melihat detail form permintaan dan ticket terkait.

## Lingkup (Scope)

### Termasuk
- Export PDF & Excel dari halaman list Form Permintaan dengan filter aktif (search, outlet, jenis permintaan, status, tanggal mulai/akhir).
- Endpoint backend baru: `GET /form-permintaan/export/pdf` dan `GET /form-permintaan/export/excel`.
- Perubahan tampilan status pada kolom list: badge read-only untuk "approved"/"rejected", dropdown untuk "progress"/"pending".
- Penghapusan opsi "completed" dari filter status dan dropdown status.
- RouterLink pada kode ticket di halaman detail form permintaan, mengarah ke halaman detail ticket sesuai context (admin/app).

### Tidak Termasuk
- Perubahan skema database (tidak ada tabel/kolom baru).
- Perubahan pada modul Ticket itu sendiri.
- Migrasi data status "completed" yang sudah ada (backend tetap menerima nilai ini untuk backward compatibility).

## Kebutuhan Fungsional

| # | Kebutuhan | Prioritas |
|---|-----------|-----------|
| 1 | Export PDF dari list Form Permintaan dengan filter aktif | Tinggi |
| 2 | Export Excel dari list Form Permintaan dengan filter aktif | Tinggi |
| 3 | Tombol & dropdown Export pada header halaman list | Tinggi |
| 4 | Endpoint backend export dengan validasi filter dan permission `form-permintaan-list` | Tinggi |
| 5 | Status "approved"/"rejected" tampil sebagai badge read-only | Sedang |
| 6 | Opsi status filter diperbarui (tanpa "completed") | Sedang |
| 7 | Hyperlink ke ticket terkait di halaman detail | Sedang |

## Kebutuhan Non-Fungsional

- Export tetap dapat diakses meski tidak ada data yang cocok dengan filter (file kosong dengan header saja).
- Proses export menampilkan loading indicator dan menonaktifkan tombol saat berjalan, untuk mencegah duplikasi request.
- Pola implementasi mengikuti pola export yang sudah ada di modul Ticket (DomPDF untuk PDF, PhpSpreadsheet untuk Excel) demi konsistensi kode.

## Metrik Keberhasilan

- Admin dapat berhasil mengekspor PDF/Excel dengan berbagai kombinasi filter tanpa error.
- Tidak ada laporan bug terkait status yang berubah tidak sengaja pada record "approved"/"rejected".
- Navigasi ke ticket terkait berfungsi baik di context admin maupun app.

## Risiko & Asumsi

- **Asumsi**: Permission `form-permintaan-list` sudah cukup untuk mengakses fitur export tanpa permission tambahan.
- **Risiko**: Query export tanpa pagination dapat menjadi lambat jika volume data besar — saat ini belum ada limit khusus, mengikuti pola existing `getAllPaginated`.
- **Risiko**: Perubahan status enum di frontend tidak menghapus data lama dengan status "completed" di database, sehingga UI perlu menangani nilai ini secara graceful (fallback rendering).

## Status Implementasi

Fitur ini telah diimplementasikan sepenuhnya melalui spec `form-permintaan-improvements` (requirements.md, design.md, tasks.md) dan seluruh task telah selesai serta terverifikasi (lint, build, dan pengecekan route).
