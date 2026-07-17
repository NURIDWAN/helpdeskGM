# Implementation Plan: Laporan Harian Kategori

## Overview

Menambahkan komponen inline category tabs pada halaman DailyUsageReport.vue untuk menampilkan pilihan kategori (Semua, Gas, Air, Listrik) secara langsung di halaman tanpa perlu membuka panel filter. Implementasi menggunakan `filters.category` sebagai single source of truth sehingga sinkronisasi otomatis antara tabs dan dropdown filter.

## Tasks

- [ ] 1. Tambahkan icon imports dan definisi categoryOptions
  - [ ] 1.1 Import icons Flame, Droplets, Zap dari lucide-vue-next dan definisikan array categoryOptions
    - Tambahkan `Flame, Droplets, Zap` ke import statement lucide-vue-next yang sudah ada
    - Definisikan `categoryOptions` array dengan 4 opsi: `{ value: '', label: 'Semua Kategori', icon: null }`, `{ value: 'gas', label: 'Gas', icon: Flame }`, `{ value: 'water', label: 'Air', icon: Droplets }`, `{ value: 'electricity', label: 'Listrik', icon: Zap }`
    - Tambahkan fungsi `selectCategory(categoryValue)` yang set `filters.value.category = categoryValue` dan panggil `handleFilterChange()`
    - _Requirements: 1.1, 1.2, 2.2_

- [ ] 2. Implementasi category tabs di template
  - [ ] 2.1 Tambahkan section category tabs di template antara Alert dan Filter Panel
    - Sisipkan `<div class="flex flex-wrap gap-2">` setelah Alert section dan sebelum Filter section
    - Render `<button>` per opsi menggunakan `v-for="option in categoryOptions"`
    - Bind `@click="selectCategory(option.value)"` pada setiap button
    - Tampilkan icon menggunakan `<component :is="option.icon" v-if="option.icon" :size="16" />`
    - Tampilkan `{{ option.label }}` sebagai text button
    - _Requirements: 1.1, 1.2, 1.6, 2.2_

  - [ ] 2.2 Tambahkan conditional styling untuk active/inactive state pada tab buttons
    - Gunakan `:class` binding dengan kondisi `filters.category === option.value`
    - Active state: `'bg-blue-600 text-white shadow-sm'`
    - Inactive state: `'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'`
    - Base classes: `'inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-200'`
    - _Requirements: 1.4, 3.1, 3.2, 3.3_

  - [ ] 2.3 Tambahkan responsive classes untuk tampilan mobile
    - Pada container div tabs, tambahkan responsive classes: `flex flex-wrap gap-2 sm:gap-3`
    - Pastikan buttons memiliki `min-h-[44px]` untuk touch target mobile
    - Tambahkan `text-sm sm:text-sm` dan `px-3 sm:px-4 py-2.5` untuk padding yang cukup pada mobile
    - _Requirements: 6.1, 6.2_

- [ ] 3. Checkpoint - Verifikasi visual dan sinkronisasi
  - Ensure all tests pass, ask the user if questions arise.
  - Verifikasi: tabs muncul di halaman, klik tab mengubah `filters.category`, dropdown di filter panel juga berubah nilainya, data reload sesuai kategori

- [ ] 4. Verifikasi integrasi export dan kolom tabel
  - [ ] 4.1 Verifikasi export Excel dan PDF meneruskan parameter category yang benar
    - Pastikan fungsi `handleExport` dan `handleExportPdf` yang sudah ada mengirim `filters.value.category || 'all'` ke API
    - Jika belum ada, tambahkan `params.category = filters.value.category || 'all'` pada kedua fungsi export
    - Verifikasi error handling menampilkan Alert jika export gagal
    - _Requirements: 5.1, 5.2, 5.3, 5.4_

  - [ ] 4.2 Verifikasi kolom tabel sudah sesuai dengan kategori yang dipilih
    - Pastikan logika `v-if` untuk grup kolom Gas (7 kolom), Air (5 kolom), dan Listrik sudah menggunakan `filters.category`
    - Pastikan common columns (Timestamp, Tanggal, Nama, Outlet, Total Customer) selalu ditampilkan
    - Pastikan fungsi `getColspan()` menghitung colspan dengan benar berdasarkan kategori aktif
    - Pastikan pesan "Tidak ada data" menggunakan `getColspan()` untuk full-width
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.7_

- [ ] 5. Checkpoint - Verifikasi fungsionalitas lengkap
  - Ensure all tests pass, ask the user if questions arise.
  - Verifikasi: semua kategori menampilkan kolom yang benar, export berjalan dengan parameter kategori, responsive layout pada mobile, sinkronisasi bidirectional antara tabs dan dropdown

- [ ] 6. Property-based tests dan unit tests
  - [ ]* 6.1 Write property test untuk bidirectional category synchronization
    - **Property 1: Bidirectional category synchronization**
    - **Validates: Requirements 1.5, 3.1, 3.2, 3.4**
    - Gunakan fast-check untuk generate random category values dari set {'', 'gas', 'water', 'electricity'}
    - Verifikasi bahwa set `filters.category` dari tab maupun dropdown menghasilkan nilai yang sama di kedua kontrol

  - [ ]* 6.2 Write property test untuk exclusive active tab indicator
    - **Property 3: Exclusive active tab indicator**
    - **Validates: Requirements 1.4**
    - Gunakan fast-check untuk generate random category values
    - Verifikasi bahwa tepat satu button memiliki active class dan sisanya memiliki inactive class

  - [ ]* 6.3 Write property test untuk colspan calculation
    - **Property 6: Colspan matches visible columns**
    - **Validates: Requirements 4.7**
    - Gunakan fast-check untuk generate random category values
    - Verifikasi bahwa `getColspan()` mengembalikan jumlah kolom yang benar: 5 (common) + 7 (gas jika visible) + 5 (water jika visible) + 11 (electricity jika visible)

  - [ ]* 6.4 Write property test untuk electricity rowspan calculation
    - **Property 5: Electricity rowspan calculation**
    - **Validates: Requirements 4.6**
    - Gunakan fast-check untuk generate array electricity meters dengan panjang 0-10
    - Verifikasi bahwa `getElectricityRowspan()` mengembalikan length + 1 jika length > 1, atau 1 jika length <= 1

  - [ ]* 6.5 Write property test untuk export category parameter
    - **Property 7: Export passes correct category parameter**
    - **Validates: Requirements 5.1, 5.2, 5.3**
    - Gunakan fast-check untuk generate random category values
    - Verifikasi bahwa export mengirim category value yang dipilih, atau 'all' jika value kosong

  - [ ]* 6.6 Write unit tests untuk initial render dan interaksi
    - Test initial render: 4 tab buttons muncul, "Semua Kategori" aktif secara default
    - Test klik tab: `filters.category` berubah dan `loadReportData` dipanggil
    - Test empty branch state: tabs tetap rendered dan clickable
    - Test column visibility per kategori
    - _Requirements: 1.1, 1.3, 1.6, 2.1, 4.1, 4.2, 4.3, 4.4, 4.5_

- [ ] 7. Final checkpoint - Pastikan semua berjalan dengan baik
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Tidak ada perubahan backend — API sudah mendukung parameter `category`
- Sinkronisasi otomatis karena tabs dan dropdown menggunakan `filters.category` sebagai single source of truth
- File utama yang dimodifikasi: `fe/src/views/admin/dailyrecord/DailyUsageReport.vue`
- Icons menggunakan lucide-vue-next yang sudah terinstall di project
- Property tests menggunakan fast-check library

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1"] },
    { "id": 1, "tasks": ["2.1"] },
    { "id": 2, "tasks": ["2.2", "2.3"] },
    { "id": 3, "tasks": ["4.1", "4.2"] },
    { "id": 4, "tasks": ["6.1", "6.2", "6.3", "6.4", "6.5", "6.6"] }
  ]
}
```
