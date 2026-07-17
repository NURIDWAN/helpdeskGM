# Technical Design Document

## Overview

This document describes the technical design for adding granular electricity meter reset capability to the Daily Usage Report's reset feature. The current reset endpoint resets all ElectricityReading records for a branch. This enhancement introduces an optional `electricity_meter_ids` parameter that allows superadmins to selectively reset specific meters. On the frontend, a two-step flow is added: when "Listrik" is selected as the reset category, a Meter Selector panel appears with checkboxes for individual meters.

## Architecture

The feature follows the existing Laravel API + Vue.js SPA architecture:

```
┌──────────────────────────────────────────────────────────┐
│  Frontend (Vue.js)                                        │
│  DailyUsageReport.vue                                     │
│    ├── Reset Dialog (existing)                            │
│    │   ├── Step 1: Category Selection (existing)          │
│    │   └── Step 2: Meter Selector (NEW)                   │
│    │       ├── "Pilih Semua" checkbox                     │
│    │       └── Individual meter checkboxes                │
│    └── API call with electricity_meter_ids[]              │
└─────────────────────────┬────────────────────────────────┘
                          │ POST /daily-records/report/daily-usage/reset
                          │ { ...existing, electricity_meter_ids: [1,2,3] }
                          ▼
┌──────────────────────────────────────────────────────────┐
│  Backend (Laravel)                                        │
│  DailyRecordController@resetDailyUsageReport              │
│    ├── Validation: electricity_meter_ids optional array   │
│    └── DailyUsageReportService (or inline logic)          │
│        └── Conditional ElectricityReading query filter     │
└──────────────────────────────────────────────────────────┘
```

## Components and Interfaces

### Frontend Components

#### Meter Selector (embedded in Reset Dialog within DailyUsageReport.vue)

The Meter Selector is not a separate component file but is rendered conditionally inside the existing reset modal when `resetCategory === 'electricity'`.

**State:**
```javascript
// New reactive state in DailyUsageReport.vue
const meterList = ref([]);             // Fetched active meters for selected branch
const selectedMeterIds = ref([]);       // Array of selected meter IDs
const meterLoading = ref(false);        // Loading state for meter fetch
const meterError = ref(null);           // Error state for meter fetch

// Computed
const allMetersSelected = computed(() => 
  meterList.value.length > 0 && selectedMeterIds.value.length === meterList.value.length
);

const resetButtonDisabled = computed(() => {
  if (resetCategory.value === 'electricity') {
    return selectedMeterIds.value.length === 0 || meterLoading.value || !!meterError.value;
  }
  return false; // Gas/Water: no meter selection needed
});
```

**Behavior:**
- When `resetCategory` changes to `'electricity'`, fetch meters from `GET /branches/{branchId}/electricity-meters`
- Filter response to only include meters where `is_active === true`
- Display each meter as: `{meter_name} ({location})`
- "Pilih Semua" checkbox toggles all meters on/off
- "Pilih Semua" auto-checks when all individual meters are selected
- "Pilih Semua" auto-unchecks when any meter is deselected from full selection

#### Select All Toggle Logic

```javascript
const toggleSelectAll = (checked) => {
  if (checked) {
    selectedMeterIds.value = meterList.value.map(m => m.id);
  } else {
    selectedMeterIds.value = [];
  }
};

const toggleMeter = (meterId) => {
  const idx = selectedMeterIds.value.indexOf(meterId);
  if (idx > -1) {
    selectedMeterIds.value.splice(idx, 1);
  } else {
    selectedMeterIds.value.push(meterId);
  }
};
```

### Backend Components

#### DailyRecordController@resetDailyUsageReport (Modified)

**Validation changes:**
```php
$validated = $request->validate([
    'user_id' => 'nullable|integer',
    'branch_id' => 'required|integer|exists:branches,id',
    'start_date' => 'nullable|date',
    'end_date' => 'nullable|date',
    'category' => 'nullable|in:gas,water,electricity',
    'electricity_meter_ids' => 'nullable|array',
    'electricity_meter_ids.*' => 'integer|exists:electricity_meters,id',
]);
```

**Reset logic modification:**
```php
$electricityUpdated = 0;
if (!$category || $category === UtilityCategory::ELECTRICITY->value) {
    $query = ElectricityReading::whereIn('daily_record_id', $dailyRecordIds);

    // Apply meter filter if provided and non-empty
    $meterIds = $validated['electricity_meter_ids'] ?? [];
    if (!empty($meterIds)) {
        $query->whereIn('electricity_meter_id', $meterIds);
    }

    $electricityUpdated = $query->update(['meter_value' => 0]);
}
```

**Key design decisions:**
- Empty array `[]` is treated as "no filter" (same as omitted) — uses `!empty()` check
- The `electricity_meter_ids` parameter is ignored when category is `gas` or `water`
- Validation uses `exists:electricity_meters,id` to ensure all provided IDs are valid

## Interfaces

### API Interface Changes

#### POST /daily-records/report/daily-usage/reset

**Request Body (updated):**
```json
{
  "branch_id": 1,
  "user_id": null,
  "start_date": "2025-01-01",
  "end_date": "2025-01-31",
  "category": "electricity",
  "electricity_meter_ids": [1, 3, 5]
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| branch_id | integer | Yes | Branch to reset |
| user_id | integer | No | Filter by user |
| start_date | date | No | Start date filter |
| end_date | date | No | End date filter |
| category | string | No | "gas", "water", or "electricity" |
| electricity_meter_ids | array\<int\> | No | **NEW** - Specific meter IDs to reset. If omitted or empty, resets all. |

**Response (unchanged structure):**
```json
{
  "success": true,
  "message": "Daily usage berhasil direset ke 0.",
  "data": {
    "daily_records": 15,
    "utility_readings": 0,
    "electricity_readings": 8
  }
}
```

**Validation Error (422) for invalid meter IDs:**
```json
{
  "success": false,
  "message": "The selected electricity_meter_ids.0 is invalid.",
  "data": null
}
```

### Existing Endpoint Used (No Changes)

#### GET /branches/{branchId}/electricity-meters

Returns all electricity meters for a branch. The frontend filters for `is_active === true` client-side.

## Data Models

No database schema changes required. The feature uses existing models:

### ElectricityMeter (existing)
| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| branch_id | bigint FK | Branch reference |
| meter_name | string | Display name |
| meter_number | string | Physical meter number |
| location | string | Location description |
| power_capacity | decimal(8,2) | Power capacity |
| is_active | boolean | Active status |

### ElectricityReading (existing)
| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| daily_record_id | bigint FK | DailyRecord reference |
| electricity_meter_id | bigint FK | ElectricityMeter reference |
| meter_value | decimal | Current reading value |
| photo | string | Photo path |

## Error Handling

| Scenario | HTTP Code | Message |
|----------|-----------|---------|
| Non-superadmin attempts reset | 403 | "Hanya superadmin yang dapat melakukan reset daily usage." |
| Invalid meter ID in array | 422 | "The selected electricity_meter_ids.X is invalid." |
| Missing branch_id | 422 | "The branch id field is required." |
| No matching daily records | 404 | "Tidak ada daily record yang cocok dengan filter reset." |
| Meter fetch fails (frontend) | — | Error message displayed, button disabled |
| No active meters for branch | — | "Tidak ada meter listrik aktif untuk cabang ini." |

## Testing Strategy

- **Unit tests (backend):** Verify validation rules accept/reject correct inputs, verify the ElectricityReading query builder correctly applies the `whereIn` filter for meter IDs.
- **Unit tests (frontend):** Verify the Select All toggle logic, meter filtering, button enablement state, and label rendering.
- **Property tests (backend):** Property 5 (selective reset isolation) — generate random subsets of meter IDs and verify only specified readings are zeroed. Property 6 (invalid ID rejection) — generate non-existent IDs and verify 422 response.
- **Property tests (frontend):** Property 1 (Select All invariant) — generate random meter lists and selection states, verify bidirectional consistency. Property 4 (button enablement) — generate various selection states, verify button state.
- **Integration tests:** Verify the full flow from frontend → API → database for both selective and full reset scenarios.

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system — essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Select All Bidirectional Invariant

For any list of N active electricity meters (N ≥ 1), the "Pilih Semua" checkbox state SHALL be `true` if and only if the number of individually selected meters equals N. Checking "Pilih Semua" sets all N meters as selected; unchecking it clears all selections.

**Validates: Requirements 1.5, 1.6, 1.7, 1.8**

### Property 2: Active Meters Display Correspondence

For any list of electricity meters fetched from the API (containing both active and inactive meters), the Meter Selector SHALL display checkboxes for exactly those meters where `is_active === true`, and the count of rendered checkboxes SHALL equal the count of active meters.

**Validates: Requirements 1.3, 1.9, 5.2**

### Property 3: Meter Label Rendering

For any ElectricityMeter with `meter_name` and `location` fields, the rendered label in the Meter Selector SHALL contain both the `meter_name` value and the `location` value.

**Validates: Requirements 1.2**

### Property 4: Reset Button Enablement Invariant

For any state where the reset category is "electricity" and the Meter Selector is visible, the reset confirmation button SHALL be enabled if and only if `selectedMeterIds.length > 0` and no loading/error state exists.

**Validates: Requirements 2.4, 2.5**

### Property 5: Selective Reset Isolation

For any non-empty subset S of electricity meter IDs belonging to a branch, when the Reset API is called with `electricity_meter_ids = S` and `category = "electricity"`, only ElectricityReading records whose `electricity_meter_id ∈ S` SHALL have their `meter_value` set to 0. All ElectricityReading records whose `electricity_meter_id ∉ S` SHALL retain their original `meter_value` unchanged.

**Validates: Requirements 3.2**

### Property 6: Invalid Meter ID Rejection

For any integer value that does not exist as an `id` in the `electricity_meters` table, including it in the `electricity_meter_ids` array SHALL cause the Reset API to return a 422 validation error without performing any reset.

**Validates: Requirements 3.5, 3.6**

### Property 7: Response Count Accuracy

For any successful reset operation, the `electricity_readings` count in the response payload SHALL equal the exact number of ElectricityReading records whose `meter_value` was updated to 0 by that operation.

**Validates: Requirements 3.7**

### Property 8: Non-Electricity Category Ignores Meter IDs

For any reset request where `category` is "gas" or "water", the presence of `electricity_meter_ids` in the payload (regardless of its contents) SHALL have no effect on the reset behavior — the result SHALL be identical to a request without the `electricity_meter_ids` parameter.

**Validates: Requirements 4.2**
