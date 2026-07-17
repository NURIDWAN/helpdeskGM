# Implementation Plan: Update Harian Cabang

## Overview

Menambahkan kolom `date` eksplisit ke fitur catatan harian cabang. Implementasi mencakup migration baru, update model/repository/controller di backend Laravel 11, penambahan date picker di frontend Vue 3, dan update logika duplicate check serta previous readings agar konsisten menggunakan kolom `date`.

## Tasks

- [x] 1. Database migration dan model update
  - [x] 1.1 Buat migration baru untuk menambahkan kolom `date` ke tabel `daily_records`
    - Buat file migration `add_date_column_to_daily_records_table`
    - Tambahkan kolom `date` bertipe DATE, NOT NULL, setelah kolom `user_id`
    - Tambahkan composite unique index `(branch_id, date)` untuk integritas data
    - Implementasikan `down()` yang menghapus unique index dan kolom `date`
    - _Requirements: 1.1, 1.2, 1.3, 1.4_

  - [x] 1.2 Update model DailyRecord
    - Tambahkan `'date'` ke array `$fillable`
    - Tambahkan cast `'date' => 'date'` ke property `$casts`
    - _Requirements: 2.1, 2.2_

- [x] 2. Backend validation dan repository update
  - [x] 2.1 Update DailyRecordStoreRequest dengan validasi field `date`
    - Tambahkan rule `'date' => ['required', 'date_format:Y-m-d']`
    - Ubah logika duplikat dari `created_at`-based menjadi query `DailyRecord::where('branch_id', $branchId)->where('date', $date)`
    - Update error message agar menampilkan nama cabang dan tanggal yang konflik
    - Tambahkan `'date'` ke `attributes()` method
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 7.1, 7.2_

  - [x] 2.2 Update DailyRecordRepository untuk menggunakan kolom `date`
    - Ubah `orderBy('created_at', 'desc')` menjadi `orderBy('date', 'desc')` pada method `getAll()`
    - Ubah filter `whereDate('created_at', '>=', $startDate)` menjadi `where('date', '>=', $startDate)`
    - Ubah filter `whereDate('created_at', '<=', $endDate)` menjadi `where('date', '<=', $endDate)`
    - _Requirements: 4.1, 4.2, 4.3_

  - [ ]* 2.3 Write property test: Date Range Filtering - Start Date
    - **Property 3: Date Range Filtering - Start Date**
    - **Validates: Requirements 4.1**
    - Buat test dengan random DailyRecords dan random startDate filter
    - Verifikasi semua records yang dikembalikan memiliki `date >= startDate`

  - [ ]* 2.4 Write property test: Date Range Filtering - End Date
    - **Property 4: Date Range Filtering - End Date**
    - **Validates: Requirements 4.2**
    - Buat test dengan random DailyRecords dan random endDate filter
    - Verifikasi semua records yang dikembalikan memiliki `date <= endDate`

  - [ ]* 2.5 Write property test: Date-Based Ordering
    - **Property 5: Date-Based Ordering**
    - **Validates: Requirements 4.3**
    - Buat test dengan random set DailyRecords
    - Verifikasi records dikembalikan terurut berdasarkan `date` descending

- [x] 3. Checkpoint - Pastikan migration dan backend berjalan
  - Ensure all tests pass, ask the user if questions arise.

- [x] 4. Controller update untuk previous readings
  - [x] 4.1 Update method `show()` di DailyRecordController
    - Ubah query previous record dari `where('created_at', '<', $dailyRecord->created_at)` menjadi `where('date', '<', $dailyRecord->date)`
    - Ubah `orderBy('created_at', 'desc')` menjadi `orderBy('date', 'desc')`
    - Update `record_date` response dari `$previousRecord->created_at` menjadi `$previousRecord->date`
    - _Requirements: 5.1, 5.3_

  - [x] 4.2 Update method `getPreviousReadings()` di DailyRecordController
    - Ubah parsing date agar menggunakan field `date` dari request
    - Ubah query dari `where('created_at', '<', $date)` menjadi `where('date', '<', $date)`
    - Ubah `orderBy('created_at', 'desc')` menjadi `orderBy('date', 'desc')`
    - Update `record_date` response agar menggunakan `$previousRecord->date`
    - _Requirements: 5.1, 5.2, 5.3_

  - [ ]* 4.3 Write property test: Previous Record Lookup by Date
    - **Property 6: Previous Record Lookup by Date**
    - **Validates: Requirements 5.1, 5.2, 5.3**
    - Buat test dengan random branch yang memiliki multiple DailyRecords
    - Verifikasi previous record yang dikembalikan memiliki `date` terbesar yang < reference date
    - Verifikasi jika tidak ada previous record, return empty arrays

- [x] 5. Checkpoint - Pastikan backend logic konsisten
  - Ensure all tests pass, ask the user if questions arise.

- [x] 6. Frontend form update
  - [x] 6.1 Tambahkan date picker ke DailyRecordForm.vue
    - Tambahkan `date` ke reactive form state dengan default `new Date().toISOString().split('T')[0]`
    - Tambahkan input `type="date"` dengan `v-model="form.date"` ke template sebelum field lainnya
    - Tambahkan label "Tanggal" dengan indikator required
    - Include `date` field di API request payload saat submit
    - _Requirements: 6.1, 6.2, 6.3, 6.4_

  - [x] 6.2 Update duplicate check di DailyRecordForm.vue
    - Ubah fungsi `checkDuplicateRecord` agar menggunakan `form.date` sebagai parameter `start_date` dan `end_date`
    - Pastikan duplicate check dipanggil ulang saat user mengubah tanggal (watch `form.date`)
    - _Requirements: 7.3_

  - [x] 6.3 Update previous readings fetch di DailyRecordForm.vue
    - Tambahkan parameter `date: form.date` ke request `/daily-records/previous-readings`
    - Pastikan previous readings di-refetch saat user mengubah tanggal
    - _Requirements: 5.2, 6.4_

- [ ] 7. Testing
  - [ ]* 7.1 Write property test: Date Format Validation
    - **Property 1: Date Format Validation**
    - **Validates: Requirements 3.2, 3.3**
    - Buat test dengan random strings, verifikasi hanya format `Y-m-d` yang valid diterima
    - Verifikasi tanggal masa lalu, sekarang, dan masa depan semua diterima

  - [ ]* 7.2 Write property test: Duplicate Detection per Branch per Date
    - **Property 2: Duplicate Detection per Branch per Date**
    - **Validates: Requirements 3.4, 7.1, 7.2**
    - Buat test dengan random branch_id + date combinations
    - Verifikasi duplikat ditolak dengan HTTP 422
    - Verifikasi error message mengandung nama cabang dan tanggal

  - [ ]* 7.3 Write unit tests untuk DailyRecord model dan StoreRequest
    - Test `$fillable` mengandung `'date'`
    - Test `$casts` mengandung `'date' => 'date'`
    - Test validasi menolak request tanpa field `date`
    - Test validasi menolak format tanggal selain `Y-m-d`
    - _Requirements: 2.1, 2.2, 3.1, 3.2_

- [x] 8. Final checkpoint - Pastikan semua test pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property tests validate universal correctness properties from the design document
- Unit tests validate specific examples and edge cases
- Migration file yang ada (`2025_11_09_091537_create_daily_records_table.php`) TIDAK dimodifikasi sesuai requirement 1.4
- Existing data perlu di-handle terpisah (set kolom `date` dari `created_at` untuk records yang sudah ada)

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1"] },
    { "id": 1, "tasks": ["1.2"] },
    { "id": 2, "tasks": ["2.1", "2.2"] },
    { "id": 3, "tasks": ["2.3", "2.4", "2.5", "4.1", "4.2"] },
    { "id": 4, "tasks": ["4.3", "6.1"] },
    { "id": 5, "tasks": ["6.2", "6.3"] },
    { "id": 6, "tasks": ["7.1", "7.2", "7.3"] }
  ]
}
```
