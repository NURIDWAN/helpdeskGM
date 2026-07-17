# Requirements Document

## Introduction

Fitur ini menambahkan kolom `date` (tipe DATE) ke tabel `daily_records` agar pencatatan harian cabang tidak lagi bergantung pada `created_at` untuk filtering dan validasi duplikat. Dengan kolom `date` eksplisit, pengguna dapat memilih tanggal pencatatan secara manual (termasuk tanggal di masa depan) melalui date picker di frontend, sementara backend memperbarui semua query, validasi, dan logic `previous_readings` agar konsisten menggunakan kolom `date`.

## Glossary

- **System**: Aplikasi Helpdesk GM (backend Laravel 11 + frontend Vue 3)
- **DailyRecord**: Model Eloquent yang merepresentasikan catatan harian cabang pada tabel `daily_records`
- **DailyRecordForm**: Komponen Vue 3 form untuk membuat atau mengedit catatan harian
- **DailyRecordRepository**: Kelas repository yang menangani query database untuk DailyRecord
- **DailyRecordStoreRequest**: Form Request Laravel yang memvalidasi input pembuatan DailyRecord
- **DateColumn**: Kolom `date` bertipe DATE pada tabel `daily_records`
- **PreviousReadings**: Data pembacaan meter dari catatan harian sebelumnya yang digunakan sebagai nilai pembuka (opening)
- **Branch**: Cabang/outlet yang terdaftar dalam sistem

## Requirements

### Requirement 1: Migrasi Kolom Date

**User Story:** As a developer, I want to add a `date` column to the `daily_records` table via a new migration, so that each record has an explicit date independent of `created_at`.

#### Acceptance Criteria

1. THE System SHALL provide a new migration file that adds a DateColumn of type DATE to the `daily_records` table using ALTER TABLE
2. THE System SHALL set the DateColumn as NOT NULL
3. WHEN the migration is rolled back, THE System SHALL remove the DateColumn from the `daily_records` table
4. THE System SHALL preserve the existing migration file `2025_11_09_091537_create_daily_records_table.php` without modification

### Requirement 2: Model DailyRecord Update

**User Story:** As a developer, I want the DailyRecord model to include `date` in its fillable array, so that the column can be mass-assigned.

#### Acceptance Criteria

1. THE DailyRecord SHALL include `date` in the `$fillable` property
2. THE DailyRecord SHALL cast the `date` attribute to a `date` type

### Requirement 3: Validasi Input Date pada Store Request

**User Story:** As a user, I want the system to validate the date field when creating a daily record, so that only valid dates are accepted.

#### Acceptance Criteria

1. THE DailyRecordStoreRequest SHALL require the `date` field as mandatory
2. THE DailyRecordStoreRequest SHALL validate that the `date` field is a valid date in `Y-m-d` format
3. THE DailyRecordStoreRequest SHALL accept date values in the past, present, and future
4. WHEN a DailyRecord with the same branch_id and DateColumn value already exists, THE DailyRecordStoreRequest SHALL reject the request with a descriptive error message indicating the branch already has a record for that date

### Requirement 4: Repository Query Menggunakan Kolom Date

**User Story:** As a user, I want all date-based filtering to use the `date` column, so that records are filtered by their intended recording date.

#### Acceptance Criteria

1. WHEN the DailyRecordRepository receives a `startDate` filter, THE DailyRecordRepository SHALL filter records where DateColumn is greater than or equal to the startDate value
2. WHEN the DailyRecordRepository receives an `endDate` filter, THE DailyRecordRepository SHALL filter records where DateColumn is less than or equal to the endDate value
3. THE DailyRecordRepository SHALL order records by DateColumn in descending order instead of `created_at`

### Requirement 5: Logic Previous Readings Menggunakan Kolom Date

**User Story:** As a user, I want previous readings to be determined based on the `date` column, so that opening meter values are accurately sourced from the chronologically previous record.

#### Acceptance Criteria

1. WHEN the Controller fetches previous readings for the `show` endpoint, THE System SHALL find the previous DailyRecord for the same branch where DateColumn is less than the current record DateColumn, ordered by DateColumn descending
2. WHEN the Controller handles `getPreviousReadings` request with a date parameter, THE System SHALL find the previous DailyRecord for the specified branch where DateColumn is less than the provided date, ordered by DateColumn descending
3. WHEN no previous DailyRecord exists for the same branch, THE System SHALL return empty arrays for electricity and utility readings

### Requirement 6: Frontend Date Picker

**User Story:** As a user, I want a date picker on the daily record form, so that I can select the recording date manually.

#### Acceptance Criteria

1. THE DailyRecordForm SHALL display a date picker input field for selecting the recording date
2. WHEN the DailyRecordForm is opened for a new record, THE DailyRecordForm SHALL set the date picker default value to the current date (today)
3. THE DailyRecordForm SHALL allow the user to select any date including past and future dates
4. WHEN the user submits the form, THE DailyRecordForm SHALL include the selected date value in the `date` field of the API request payload

### Requirement 7: Validasi Duplikat per Cabang per Tanggal

**User Story:** As a user, I want the system to prevent duplicate daily records for the same branch on the same date, so that data integrity is maintained.

#### Acceptance Criteria

1. WHEN a user attempts to create a DailyRecord with a branch_id and date combination that already exists, THE System SHALL reject the request with HTTP status 422
2. THE System SHALL include an error message identifying the branch name and the conflicting date in the rejection response
3. WHEN the DailyRecordForm checks for existing records before submission, THE DailyRecordForm SHALL use the selected DateColumn value instead of the current date for the duplicate check
