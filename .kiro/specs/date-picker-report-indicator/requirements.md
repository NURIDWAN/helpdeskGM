# Requirements Document

## Introduction

Fitur ini menambahkan indikator visual pada date picker di halaman form "Tambah/Edit Laporan Harian Cabang" untuk menunjukkan tanggal-tanggal yang sudah memiliki record laporan harian. Input date native (`<input type="date">`) diganti dengan komponen `@vuepic/vue-datepicker` yang mendukung kustomisasi slot per-tanggal. Backend menyediakan endpoint ringan yang mengembalikan daftar tanggal (tanpa data lengkap) berdasarkan cabang dan bulan yang dipilih.

## Glossary

- **DatePicker**: Komponen `@vuepic/vue-datepicker` yang menggantikan native date input pada form DailyRecordForm
- **DailyRecordForm**: Halaman Vue (`DailyRecordForm.vue`) untuk membuat atau mengedit laporan harian cabang
- **ReportIndicator**: Dot/titik berwarna yang ditampilkan di bawah angka tanggal pada DatePicker untuk menandakan bahwa tanggal tersebut sudah memiliki laporan harian
- **DailyRecord**: Model Laravel yang menyimpan data laporan harian cabang dengan kolom `branch_id` dan `date`
- **ReportDatesEndpoint**: Endpoint API `GET /daily-records/report-dates` yang mengembalikan daftar tanggal yang memiliki laporan harian untuk cabang dan bulan tertentu
- **ActiveBranch**: Cabang yang sedang dipilih pada field branch_id di form DailyRecordForm

## Requirements

### Requirement 1: Backend Endpoint untuk Daftar Tanggal Laporan

**User Story:** As a developer, I want a lightweight API endpoint that returns only dates with existing daily records for a given branch and month, so that the frontend can efficiently mark those dates on the date picker.

#### Acceptance Criteria

1. WHEN a GET request is received at `/daily-records/report-dates` with `branch_id` and `month` (format YYYY-MM) parameters, THE ReportDatesEndpoint SHALL return a JSON response containing an array of date strings (format YYYY-MM-DD) where DailyRecord entries exist for the specified ActiveBranch within the specified month.
2. WHEN the `branch_id` parameter is missing or invalid, THE ReportDatesEndpoint SHALL return a 422 validation error response with a descriptive message.
3. WHEN the `month` parameter is missing or invalid, THE ReportDatesEndpoint SHALL return a 422 validation error response with a descriptive message.
4. WHEN no DailyRecord entries exist for the specified branch and month, THE ReportDatesEndpoint SHALL return a successful response with an empty array.
5. THE ReportDatesEndpoint SHALL require authentication and authorization consistent with existing daily-record-list permission middleware.

### Requirement 2: Penggantian Native Date Input dengan Vue Datepicker

**User Story:** As a user filling in the daily record form, I want a custom date picker component instead of the native date input, so that I can see visual indicators for dates that already have reports.

#### Acceptance Criteria

1. THE DailyRecordForm SHALL use the DatePicker component from `@vuepic/vue-datepicker` library to replace the native `<input type="date">` for the "Tanggal" field.
2. THE DatePicker SHALL bind its selected value to the `form.date` reactive property in YYYY-MM-DD format.
3. THE DatePicker SHALL display the label "Tanggal" with the Calendar icon, consistent with the existing FormField styling.
4. THE DatePicker SHALL apply Tailwind CSS styling that visually integrates with the existing form design.

### Requirement 3: Fetch dan Tampilkan Indikator Tanggal per Cabang

**User Story:** As a user, I want to see which dates already have daily reports for the selected branch, so that I can avoid creating duplicate records and quickly identify gaps.

#### Acceptance Criteria

1. WHEN the ActiveBranch value changes on DailyRecordForm, THE DatePicker SHALL fetch report dates from the ReportDatesEndpoint for the currently displayed month and the newly selected ActiveBranch.
2. WHEN the DatePicker navigates to a different month, THE DatePicker SHALL fetch report dates from the ReportDatesEndpoint for the new month and the current ActiveBranch.
3. WHEN report dates are successfully fetched, THE DatePicker SHALL display a ReportIndicator (colored dot below the date number) on each date that has an existing DailyRecord for the ActiveBranch.
4. WHILE no ActiveBranch is selected, THE DatePicker SHALL display dates without any ReportIndicator.
5. IF the ReportDatesEndpoint request fails, THEN THE DatePicker SHALL display dates without ReportIndicator and log the error to the console without showing an error to the user.
6. WHEN the ActiveBranch changes, THE DatePicker SHALL clear previously displayed ReportIndicators before showing new ones for the updated branch.

### Requirement 4: Desain Visual Indikator

**User Story:** As a user, I want the report indicator to be visually clear but not intrusive, so that I can easily identify dates with existing reports without the indicator obscuring the date number.

#### Acceptance Criteria

1. THE ReportIndicator SHALL be rendered as a small colored dot (diameter 6-8px) positioned below the date number within the DatePicker day cell.
2. THE ReportIndicator SHALL use a distinct color (e.g., blue or green) that contrasts with the DatePicker background and does not conflict with the selected-date highlight color.
3. THE ReportIndicator SHALL remain visible when the date cell is hovered but SHALL NOT appear on the currently selected date cell to avoid visual clutter.
4. THE ReportIndicator SHALL be visible on both light backgrounds (normal dates) and darker backgrounds (today's date highlight) within the DatePicker.
