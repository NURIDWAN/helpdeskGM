# Implementation Plan: Date Picker Report Indicator

## Overview

Mengganti native date input pada DailyRecordForm dengan komponen `@vuepic/vue-datepicker` yang menampilkan indikator dot pada tanggal-tanggal yang sudah memiliki laporan harian. Backend menyediakan endpoint ringan untuk mengambil daftar tanggal berdasarkan cabang dan bulan.

## Tasks

- [x] 1. Backend endpoint untuk report dates
  - [x] 1.1 Tambahkan method `getReportDates` di DailyRecordController
    - Tambahkan method `getReportDates(Request $request)` pada `DailyRecordController`
    - Validasi parameter `branch_id` (required, integer, exists:branches,id) dan `month` (required, regex YYYY-MM)
    - Query `DailyRecord::where('branch_id', ...)->whereYear(...)->whereMonth(...)` lalu pluck `date`
    - Return response menggunakan `ResponseHelper::jsonResponse` dengan array tanggal format YYYY-MM-DD
    - Handle error dengan try-catch, return 500 jika terjadi exception
    - _Requirements: 1.1, 1.2, 1.3, 1.4_

  - [x] 1.2 Registrasikan route untuk `getReportDates`
    - Tambahkan `Route::get('daily-records/report-dates', ...)` di `api/routes/api.php`
    - Tempatkan SEBELUM `apiResource('daily-records', ...)` agar tidak ter-intercept oleh resource route
    - Gunakan middleware permission yang sama dengan daily-record-list
    - _Requirements: 1.5_

  - [ ]* 1.3 Write property test: Report Dates Belong to Requested Branch and Month
    - **Property 1: Report Dates Belong to Requested Branch and Month**
    - **Validates: Requirements 1.1**
    - Buat test dengan random branch_id dan month, seed DailyRecord data
    - Verifikasi semua tanggal yang dikembalikan berada dalam bulan yang diminta dan milik branch yang diminta
    - Verifikasi tidak ada DailyRecord untuk branch+month tersebut yang absen dari response

  - [ ]* 1.4 Write property test: Invalid Parameters Produce Validation Error
    - **Property 2: Invalid Parameters Produce Validation Error**
    - **Validates: Requirements 1.2, 1.3**
    - Buat test dengan random invalid branch_id (string, null, non-existent) dan invalid month (random strings, partial dates)
    - Verifikasi response selalu HTTP 422

- [x] 2. Checkpoint - Pastikan backend endpoint berfungsi
  - Ensure all tests pass, ask the user if questions arise.

- [x] 3. Install dependency dan buat composable frontend
  - [x] 3.1 Install `@vuepic/vue-datepicker` package
    - Jalankan `npm install @vuepic/vue-datepicker` di folder `fe/`
    - Verifikasi package tercatat di `fe/package.json` dependencies
    - _Requirements: 2.1_

  - [x] 3.2 Buat composable `useReportDates`
    - Buat file `fe/src/composables/useReportDates.js`
    - Implement `fetchReportDates(month)` yang memanggil `/daily-records/report-dates` dengan `branch_id` dan `month`
    - Simpan hasil di `ref(new Set())` untuk O(1) lookup
    - Implement `hasReport(dateStr)` yang mengecek apakah tanggal ada di Set
    - Handle error: catch exception, log ke console, set reportDates ke empty Set
    - Jika branch_id kosong, skip fetch dan clear reportDates
    - _Requirements: 3.1, 3.2, 3.4, 3.5_

  - [ ]* 3.3 Write property test: Date Binding Format Invariant
    - **Property 3: Date Binding Format Invariant**
    - **Validates: Requirements 2.2**
    - Buat test dengan random Date objects
    - Verifikasi output `form.date` selalu match format YYYY-MM-DD (regex `/^\d{4}-\d{2}-\d{2}$/`)

- [x] 4. Integrasi DatePicker di DailyRecordForm
  - [x] 4.1 Replace native date input dengan VueDatePicker di DailyRecordForm.vue
    - Import `VueDatePicker` dan CSS-nya (`@vuepic/vue-datepicker/dist/main.css`)
    - Import `useReportDates` composable dan `DateTime` dari luxon
    - Hapus native `<input type="date">` / FormField untuk "Tanggal"
    - Tambahkan `<VueDatePicker>` dengan props: `model-value`, `enable-time-picker=false`, `auto-apply`, `format="yyyy-MM-dd"`, `locale="id"`
    - Buat handler `handleDateUpdate` yang convert Date ke YYYY-MM-DD dan assign ke `form.date`
    - Buat handler `handleMonthYearChange` yang fetch report dates saat navigasi bulan
    - Styling dengan label "Tanggal" dan Calendar icon sesuai FormField pattern
    - _Requirements: 2.1, 2.2, 2.3, 2.4_

  - [x] 4.2 Implement day slot dengan report indicator dot
    - Tambahkan `#day` template slot pada VueDatePicker
    - Render dot (w-1.5 h-1.5 rounded-full bg-blue-500) di bawah angka tanggal jika `hasReport(date)` true
    - Sembunyikan dot pada tanggal yang sedang dipilih (`form.date`)
    - Pastikan dot visible di background terang dan gelap
    - _Requirements: 3.3, 4.1, 4.2, 4.3, 4.4_

  - [x] 4.3 Wire reactive fetching: watch branch_id dan month navigation
    - Watch `form.branch_id`: jika berubah ke value non-empty, panggil `fetchReportDates(displayedMonth)`
    - Watch `form.branch_id`: jika berubah ke empty, clear reportDates Set
    - Handle `@update-month-year` event dari DatePicker: update displayedMonth dan fetch
    - _Requirements: 3.1, 3.2, 3.6_

  - [ ]* 4.4 Write property test: Indicator Rendering Matches Fetched Report Dates
    - **Property 5: Indicator Rendering Matches Fetched Report Dates**
    - **Validates: Requirements 3.3, 3.6, 4.3**
    - Buat test dengan random set of report dates
    - Verifikasi dot ditampilkan untuk setiap tanggal di set KECUALI tanggal yang selected
    - Verifikasi tanggal yang TIDAK di set TIDAK memiliki dot

  - [ ]* 4.5 Write property test: No Indicators Without Active Branch
    - **Property 6: No Indicators Without Active Branch**
    - **Validates: Requirements 3.4**
    - Buat test dengan random states di mana branch_id kosong/null
    - Verifikasi tidak ada dot yang ditampilkan di cell manapun

  - [ ]* 4.6 Write property test: Reactive Data Fetching on Parameter Change
    - **Property 4: Reactive Data Fetching on Parameter Change**
    - **Validates: Requirements 3.1, 3.2**
    - Buat test dengan random branch_id changes dan random month navigations
    - Verifikasi setiap perubahan memicu GET request dengan parameter branch_id dan month yang benar

- [x] 5. Final checkpoint - Pastikan semua berfungsi end-to-end
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property tests validate universal correctness properties from the design document
- Route `daily-records/report-dates` HARUS didaftarkan sebelum `apiResource('daily-records', ...)` di routes/api.php agar tidak ter-intercept
- Package `@vuepic/vue-datepicker` ditambahkan sebagai dependency baru di frontend
- Composable menggunakan `Set` untuk O(1) lookup performance pada day slot rendering

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1", "3.1"] },
    { "id": 1, "tasks": ["1.2", "3.2"] },
    { "id": 2, "tasks": ["1.3", "1.4", "3.3"] },
    { "id": 3, "tasks": ["4.1"] },
    { "id": 4, "tasks": ["4.2", "4.3"] },
    { "id": 5, "tasks": ["4.4", "4.5", "4.6"] }
  ]
}
```
