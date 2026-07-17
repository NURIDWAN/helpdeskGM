# Technical Design: Date Picker Report Indicator

## Overview

This feature replaces the native `<input type="date">` on the DailyRecordForm with `@vuepic/vue-datepicker`, adding visual dot indicators for dates that already have daily record entries. A lightweight backend endpoint provides the list of dates for a given branch and month.

## Architecture

The feature spans two layers:

1. **Backend (Laravel)**: New `getReportDates` method on `DailyRecordController` with a dedicated route, querying the `daily_records` table for dates matching `branch_id` + month.
2. **Frontend (Vue 3)**: Replace native date input with `@vuepic/vue-datepicker`, adding a composable to fetch report dates and a custom day slot to render indicator dots.

```
┌─────────────────────────────────────────────────────┐
│  DailyRecordForm.vue                                │
│  ┌───────────────────────────────────────────────┐  │
│  │  VueDatePicker                                │  │
│  │  ├─ @update-month-year → fetchReportDates()   │  │
│  │  ├─ day-slot → renders dot if date in set     │  │
│  │  └─ v-model (form.date, YYYY-MM-DD)          │  │
│  └───────────────────────────────────────────────┘  │
│                        │                            │
│  watch(form.branch_id) → fetchReportDates()         │
└────────────────────────┼────────────────────────────┘
                         │ GET /daily-records/report-dates
                         │     ?branch_id=X&month=YYYY-MM
                         ▼
┌─────────────────────────────────────────────────────┐
│  DailyRecordController@getReportDates               │
│  → DailyRecord::where(branch_id, month) → dates[]  │
└─────────────────────────────────────────────────────┘
```

## Components and Interfaces

### Backend

#### New Method: `DailyRecordController::getReportDates`

```php
/**
 * Get dates that have daily records for a specific branch and month.
 */
public function getReportDates(Request $request)
{
    $validated = $request->validate([
        'branch_id' => 'required|integer|exists:branches,id',
        'month' => ['required', 'string', 'regex:/^\d{4}-\d{2}$/'],
    ]);

    try {
        $dates = DailyRecord::where('branch_id', $validated['branch_id'])
            ->whereYear('date', substr($validated['month'], 0, 4))
            ->whereMonth('date', substr($validated['month'], 5, 2))
            ->pluck('date')
            ->map(fn ($date) => $date->format('Y-m-d'))
            ->values()
            ->toArray();

        return ResponseHelper::jsonResponse(true, 'Report dates fetched', $dates, 200);
    } catch (\Throwable $e) {
        return ResponseHelper::jsonResponse(false, 'Error fetching report dates', null, 500);
    }
}
```

#### Route Registration

```php
// In api/routes/api.php, within the authenticated middleware group,
// BEFORE the apiResource route:
Route::get('daily-records/report-dates', [DailyRecordController::class, 'getReportDates']);
```

#### Middleware

Uses the same permission middleware already applied to list/read actions: `daily-record-list|daily-record-create|daily-record-edit|daily-record-delete`.

### Frontend

#### New Composable: `useReportDates`

Location: `fe/src/composables/useReportDates.js`

```javascript
import { ref, watch } from 'vue'
import { axiosInstance as api } from '@/plugins/axios'
import { DateTime } from 'luxon'

export function useReportDates(branchId, currentMonth) {
  const reportDates = ref(new Set())
  const loading = ref(false)

  const fetchReportDates = async (month) => {
    if (!branchId.value) {
      reportDates.value = new Set()
      return
    }

    const monthStr = month || DateTime.now().toFormat('yyyy-MM')

    loading.value = true
    try {
      const response = await api.get('/daily-records/report-dates', {
        params: {
          branch_id: branchId.value,
          month: monthStr,
        },
      })

      if (response.data.success) {
        reportDates.value = new Set(response.data.data)
      } else {
        reportDates.value = new Set()
      }
    } catch (error) {
      console.error('Failed to fetch report dates:', error)
      reportDates.value = new Set()
    } finally {
      loading.value = false
    }
  }

  const hasReport = (dateStr) => {
    return reportDates.value.has(dateStr)
  }

  return {
    reportDates,
    loading,
    fetchReportDates,
    hasReport,
  }
}
```

#### DatePicker Integration in DailyRecordForm

Replace the native date `FormField` with `VueDatePicker`:

```vue
<script setup>
import VueDatePicker from '@vuepic/vue-datepicker'
import '@vuepic/vue-datepicker/dist/main.css'
import { useReportDates } from '@/composables/useReportDates'
import { DateTime } from 'luxon'

// ... existing imports ...

const branchIdRef = computed(() => form.branch_id)
const { reportDates, fetchReportDates, hasReport } = useReportDates(branchIdRef)

// Track displayed month for fetching
const displayedMonth = ref(DateTime.now().toFormat('yyyy-MM'))

// Watch branch changes
watch(branchIdRef, (newVal) => {
  if (newVal) {
    fetchReportDates(displayedMonth.value)
  } else {
    reportDates.value = new Set()
  }
})

// Handle month/year navigation
const handleMonthYearChange = ({ month, year }) => {
  const monthStr = `${year}-${String(month).padStart(2, '0')}`
  displayedMonth.value = monthStr
  fetchReportDates(monthStr)
}

// Format model value to YYYY-MM-DD
const handleDateUpdate = (modelData) => {
  if (modelData) {
    const dt = DateTime.fromJSDate(modelData)
    form.date = dt.toFormat('yyyy-MM-dd')
  } else {
    form.date = ''
  }
}
</script>
```

#### Day Slot Template

```vue
<template>
  <VueDatePicker
    :model-value="form.date"
    @update:model-value="handleDateUpdate"
    @update-month-year="handleMonthYearChange"
    :enable-time-picker="false"
    auto-apply
    :format="'yyyy-MM-dd'"
    locale="id"
  >
    <template #day="{ day, date }">
      <div class="relative flex flex-col items-center">
        <span>{{ day }}</span>
        <span
          v-if="hasReport(DateTime.fromJSDate(date).toFormat('yyyy-MM-dd'))
                 && form.date !== DateTime.fromJSDate(date).toFormat('yyyy-MM-dd')"
          class="absolute -bottom-1 w-1.5 h-1.5 rounded-full bg-blue-500"
        ></span>
      </div>
    </template>
  </VueDatePicker>
</template>
```

### API Endpoint

| Method | Path | Query Params | Response |
|--------|------|--------------|----------|
| GET | `/daily-records/report-dates` | `branch_id` (required, integer), `month` (required, YYYY-MM) | `{ success: true, data: ["2025-06-01", "2025-06-05", ...] }` |

#### Request Validation

| Parameter | Type | Rules |
|-----------|------|-------|
| `branch_id` | integer | required, exists in branches table |
| `month` | string | required, format `YYYY-MM` (regex: `/^\d{4}-\d{2}$/`) |

#### Error Response (422)

```json
{
  "success": false,
  "message": "The given data was invalid.",
  "data": {
    "branch_id": ["The branch id field is required."],
    "month": ["The month format is invalid."]
  }
}
```

### Composable Interface

```typescript
// useReportDates(branchId: Ref<string|number>, currentMonth?: Ref<string>)
// Returns:
{
  reportDates: Ref<Set<string>>      // Set of 'YYYY-MM-DD' strings
  loading: Ref<boolean>
  fetchReportDates: (month?: string) => Promise<void>
  hasReport: (dateStr: string) => boolean
}
```

## Data Models

No schema changes are needed. The existing `daily_records` table already has:

| Column | Type | Usage |
|--------|------|-------|
| `id` | bigint | Primary key |
| `branch_id` | integer (FK) | Filter by branch |
| `date` | date | The date column used for lookup |
| `user_id` | integer (FK) | Not relevant for this query |
| `total_customers` | integer | Not relevant for this query |

### Index Consideration

The query `WHERE branch_id = ? AND date BETWEEN ? AND ?` will benefit from a composite index. If one doesn't already exist:

```sql
CREATE INDEX idx_daily_records_branch_date ON daily_records(branch_id, date);
```

## Error Handling

| Scenario | Backend Behavior | Frontend Behavior |
|----------|-----------------|-------------------|
| Missing/invalid `branch_id` | 422 with validation message | N/A (frontend always sends valid branch_id) |
| Missing/invalid `month` | 422 with validation message | N/A (frontend constructs month from DatePicker state) |
| No records found | 200 with empty array | Shows no dots (empty Set) |
| Server error | 500 with error message | Catches error, logs to console, shows no dots |
| Network failure | N/A | Catches error, logs to console, shows no dots |
| No branch selected | N/A (frontend won't call) | Shows no dots, skips fetch |

## Performance Considerations

- The endpoint returns only date strings (not full records), keeping payload small (~30 strings max per month).
- A `Set` is used on the frontend for O(1) lookup per day cell.
- Fetches are triggered only on branch change or month navigation (not on every render).
- No debounce needed since branch changes and month navigation are infrequent user interactions.

## Testing Strategy

### Unit Tests (Backend - PHP)
- Validate `getReportDates` returns correct dates for a given branch/month
- Validate 422 response for missing/invalid `branch_id`
- Validate 422 response for missing/invalid `month` format
- Validate empty array when no records exist
- Validate authentication middleware is applied

### Unit Tests (Frontend - Vitest)
- `useReportDates` composable: verify `fetchReportDates` calls API with correct params
- `useReportDates` composable: verify `hasReport` returns correct boolean
- `useReportDates` composable: verify Set is cleared on error or empty branch
- DatePicker integration: verify `form.date` is set to YYYY-MM-DD on date selection
- DatePicker integration: verify dot is rendered for dates in reportDates Set
- DatePicker integration: verify dot is NOT rendered for the selected date
- DatePicker integration: verify no dots when branch_id is empty

### Property Tests (Vitest + fast-check)
- Report dates correctness: for random branch/date data, verify endpoint returns correct subset
- Invalid parameter validation: for random invalid inputs, verify 422 response
- Date format invariant: for random date selections, verify YYYY-MM-DD output
- Indicator rendering: for random report date sets, verify dot presence/absence matches

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system—essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Report Dates Belong to Requested Branch and Month

*For any* valid `branch_id` and `month` parameter, all dates returned by the ReportDatesEndpoint SHALL belong to the specified branch and fall within the specified month (same year and month), and no DailyRecord for that branch and month in the database SHALL be absent from the response.

**Validates: Requirements 1.1**

### Property 2: Invalid Parameters Produce Validation Error

*For any* request to the ReportDatesEndpoint where `branch_id` is missing/non-integer/non-existent OR `month` is missing/not in YYYY-MM format, the response SHALL have HTTP status 422.

**Validates: Requirements 1.2, 1.3**

### Property 3: Date Binding Format Invariant

*For any* date selected through the DatePicker component, the resulting `form.date` value SHALL be a string matching the format `YYYY-MM-DD` (regex: `/^\d{4}-\d{2}-\d{2}$/`).

**Validates: Requirements 2.2**

### Property 4: Reactive Data Fetching on Parameter Change

*For any* change to `branch_id` (to a non-empty value) or any month/year navigation event in the DatePicker, the system SHALL issue a GET request to `/daily-records/report-dates` with the current `branch_id` and the currently displayed month in YYYY-MM format.

**Validates: Requirements 3.1, 3.2**

### Property 5: Indicator Rendering Matches Fetched Report Dates

*For any* set of dates returned by the ReportDatesEndpoint, each date in the set that is NOT the currently selected date SHALL have a visible ReportIndicator dot in its corresponding DatePicker day cell, and dates NOT in the set SHALL NOT have a ReportIndicator dot.

**Validates: Requirements 3.3, 3.6, 4.3**

### Property 6: No Indicators Without Active Branch

*For any* state where `branch_id` is empty or null, no DatePicker day cell SHALL display a ReportIndicator dot.

**Validates: Requirements 3.4**
