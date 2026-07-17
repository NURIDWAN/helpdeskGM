# Design Document: Update Harian Cabang

## Overview

Dokumen ini menjelaskan arsitektur dan desain teknis untuk menambahkan kolom `date` eksplisit ke fitur catatan harian cabang. Perubahan mencakup migration baru, update model/repository/controller di backend Laravel 11, dan penambahan date picker di frontend Vue 3.

## Architecture

Arsitektur mengikuti pola yang sudah ada di project:

```
┌─────────────────────┐     ┌────────────────────────────────┐
│   Vue 3 Frontend    │     │       Laravel 11 Backend        │
│                     │     │                                  │
│  DailyRecordForm    │────▶│  DailyRecordStoreRequest        │
│  (+ date picker)    │     │         │                        │
│                     │     │         ▼                        │
│  Pinia Store        │◀────│  DailyRecordController           │
│                     │     │         │                        │
│                     │     │         ▼                        │
│                     │     │  DailyRecordRepositoryInterface  │
│                     │     │         │                        │
│                     │     │         ▼                        │
│                     │     │  DailyRecordRepository           │
│                     │     │         │                        │
│                     │     │         ▼                        │
│                     │     │  DailyRecord Model               │
│                     │     │         │                        │
│                     │     │         ▼                        │
│                     │     │  daily_records table (+ date)    │
└─────────────────────┘     └────────────────────────────────┘
```

## Components and Interfaces

### 1. Database Migration

File baru: `database/migrations/YYYY_MM_DD_HHMMSS_add_date_column_to_daily_records_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_records', function (Blueprint $table) {
            $table->date('date')->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('daily_records', function (Blueprint $table) {
            $table->dropColumn('date');
        });
    }
};
```

Catatan: Kolom `date` NOT NULL tanpa default value. Data existing perlu di-handle terpisah (misalnya via seeder atau manual update menggunakan `created_at` sebagai fallback).

### 2. DailyRecord Model Update

```php
// app/Models/DailyRecord.php
protected $fillable = [
    'user_id',
    'branch_id',
    'date',          // Tambahan baru
    'total_customers',
];

protected $casts = [
    'date' => 'date',
];
```

### 3. DailyRecordStoreRequest Update

Validasi `date` ditambahkan sebagai field wajib dengan format `Y-m-d`. Logika duplikat diubah dari `created_at`-based menjadi `date`-based:

```php
public function rules(): array
{
    return [
        'branch_id' => ['required', 'exists:branches,id'],
        'date' => ['required', 'date_format:Y-m-d'],
        'user_id' => ['nullable', 'exists:users,id'],
        'total_customers' => ['nullable', 'integer', 'min:0'],
    ];
}
```

Custom validation rule untuk duplikat per cabang per tanggal:

```php
// Di dalam rules() untuk branch_id atau sebagai after() validation
function ($attribute, $value, $fail) {
    $date = $this->input('date');
    $branchId = $this->input('branch_id');

    $existingRecord = DailyRecord::where('branch_id', $branchId)
        ->where('date', $date)
        ->first();

    if ($existingRecord) {
        $branchName = $existingRecord->branch->name ?? 'cabang ini';
        $fail("Cabang {$branchName} sudah memiliki catatan harian untuk tanggal {$date}.");
    }
}
```

### 4. DailyRecordRepository Update

Semua query yang sebelumnya menggunakan `created_at` untuk filtering tanggal diubah ke kolom `date`:

```php
// Ordering
$query = DailyRecord::with([...])
    ->orderBy('date', 'desc');

// Date filtering
if ($startDate) {
    $query->where('date', '>=', $startDate);
}

if ($endDate) {
    $query->where('date', '<=', $endDate);
}
```

### 5. DailyRecordController Update

#### show() - Previous Readings Logic

```php
$previousRecord = DailyRecord::where('branch_id', $dailyRecord->branch_id)
    ->where('date', '<', $dailyRecord->date)
    ->orderBy('date', 'desc')
    ->with(['electricityReadings', 'utilityReadings'])
    ->first();
```

#### getPreviousReadings() - Explicit Date Parameter

```php
$date = $request->date; // Now uses the explicit date column

$previousRecord = DailyRecord::where('branch_id', $request->branch_id)
    ->where('date', '<', $date)
    ->orderBy('date', 'desc')
    ->with(['electricityReadings', 'utilityReadings'])
    ->first();
```

### 6. Frontend - DailyRecordForm.vue

#### Date Picker Component

Menambahkan input `type="date"` di form dengan binding ke `form.date`:

```javascript
const form = reactive({
  branch_id: "",
  user_id: "",
  date: new Date().toISOString().split('T')[0], // Default: hari ini (Y-m-d)
  total_customers: "",
});
```

Template:

```html
<FormField label="Tanggal" required>
  <input
    type="date"
    v-model="form.date"
    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
  />
</FormField>
```

#### Payload Update

```javascript
const payload = {
  branch_id: form.branch_id,
  user_id: form.user_id || null,
  date: form.date,  // Tambahan baru
  total_customers: form.total_customers ? parseInt(form.total_customers) : null,
};
```

#### Duplicate Check Update

```javascript
const checkDuplicateRecord = async (branchId) => {
  if (isEdit.value || !branchId) return false;

  try {
    const response = await api.get('/daily-records', {
      params: {
        branch_id: branchId,
        start_date: form.date,  // Gunakan date yang dipilih, bukan hari ini
        end_date: form.date,
        limit: 1
      }
    });

    if (response.data.data && response.data.data.length > 0) {
      return true;
    }
  } catch (e) {
    console.error("Failed to check duplicate", e);
  }
  return false;
};
```

#### Previous Readings Fetch Update

```javascript
const response = await api.get('/daily-records/previous-readings', {
    params: {
        branch_id: newBranchId,
        date: form.date  // Kirim date yang dipilih
    }
});
```

## Data Models

### daily_records Table (Updated)

| Column          | Type         | Nullable | Notes                      |
|-----------------|--------------|----------|----------------------------|
| id              | BIGINT(PK)   | NO       | Auto increment             |
| user_id         | BIGINT(FK)   | NO       | References users.id        |
| branch_id       | BIGINT(FK)   | NO       | References branches.id     |
| **date**        | **DATE**     | **NO**   | **Kolom baru**             |
| total_customers | INTEGER      | YES      |                            |
| created_at      | TIMESTAMP    | YES      | Laravel timestamp          |
| updated_at      | TIMESTAMP    | YES      | Laravel timestamp          |

### Unique Constraint

Disarankan menambahkan composite unique index pada `(branch_id, date)` untuk menjamin integritas data di level database:

```php
$table->unique(['branch_id', 'date']);
```

## Error Handling

| Scenario                                | HTTP Code | Response                                                |
|-----------------------------------------|-----------|---------------------------------------------------------|
| Date field kosong/tidak ada             | 422       | Validation error: "The date field is required."         |
| Date format salah (bukan Y-m-d)        | 422       | Validation error: "The date field must match format Y-m-d." |
| Duplikat branch_id + date              | 422       | "Cabang {nama} sudah memiliki catatan harian untuk tanggal {date}." |
| Branch tidak ditemukan                  | 422       | Validation error: "The selected branch_id is invalid."  |
| Record tidak ditemukan (show/update)    | 404       | "Daily Record tidak ditemukan"                          |
| Server error                            | 500       | "Terjadi kesalahan"                                     |

## Testing Strategy

### Unit Tests (Example-Based)
- Verifikasi model `$fillable` mengandung `date`
- Verifikasi model `$casts` mengandung `'date' => 'date'`
- Verifikasi validasi menolak request tanpa field `date`
- Verifikasi error message duplikat mengandung nama cabang dan tanggal
- Verifikasi frontend form default date = hari ini
- Verifikasi payload form mengandung field `date`

### Property Tests (100+ iterasi)
- Date format validation (random strings vs valid Y-m-d)
- Duplicate detection (random branch + date combinations)
- Date range filtering (random records + filter values)
- Ordering verification (random record sets)
- Previous record lookup (random branch histories)

### Integration Tests
- End-to-end flow: create record with date → fetch → verify date persisted
- Previous readings endpoint with real database

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system—essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Date Format Validation

*For any* string submitted as the `date` field, the system SHALL accept it if and only if it matches the `Y-m-d` format and represents a valid calendar date (past, present, or future). Any other format SHALL be rejected with a validation error.

**Validates: Requirements 3.2, 3.3**

### Property 2: Duplicate Detection per Branch per Date

*For any* branch_id and date combination, if a DailyRecord already exists with that exact combination, attempting to create another DailyRecord with the same branch_id and date SHALL be rejected with HTTP 422 and an error message containing the branch name and the conflicting date.

**Validates: Requirements 3.4, 7.1, 7.2**

### Property 3: Date Range Filtering - Start Date

*For any* set of DailyRecords and any startDate filter value, all records returned by the repository SHALL have a `date` column value greater than or equal to the startDate.

**Validates: Requirements 4.1**

### Property 4: Date Range Filtering - End Date

*For any* set of DailyRecords and any endDate filter value, all records returned by the repository SHALL have a `date` column value less than or equal to the endDate.

**Validates: Requirements 4.2**

### Property 5: Date-Based Ordering

*For any* set of DailyRecords returned by the repository without additional sorting, the records SHALL be ordered by the `date` column in descending order (most recent date first).

**Validates: Requirements 4.3**

### Property 6: Previous Record Lookup by Date

*For any* branch with multiple DailyRecords and any reference date D, the "previous record" returned SHALL be the record for the same branch with the maximum `date` value that is strictly less than D. If no such record exists, the system SHALL return empty arrays for electricity and utility readings.

**Validates: Requirements 5.1, 5.2, 5.3**
