# Implementation Plan: Riset Listrik Turunan (Selective Electricity Meter Reset)

## Overview

Add granular reset capability for electricity meters in the Daily Usage Report's reset dialog. The backend gains an optional `electricity_meter_ids` parameter to selectively reset specific meter readings, while the frontend adds a two-step flow with a Meter Selector panel (checkboxes + "Pilih Semua") when "Listrik" is selected as the reset category.

## Tasks

- [x] 1. Backend: Add electricity_meter_ids validation and conditional filter
  - [x] 1.1 Add electricity_meter_ids validation rules to resetDailyUsageReport
    - In `api/app/Http/Controllers/DailyRecordController.php`, add `'electricity_meter_ids' => 'nullable|array'` and `'electricity_meter_ids.*' => 'integer|exists:electricity_meters,id'` to the validation array in `resetDailyUsageReport` method
    - _Requirements: 3.1, 3.5, 3.6_

  - [x] 1.2 Add conditional whereIn filter for electricity_meter_ids in reset logic
    - In the `DB::transaction` closure within `resetDailyUsageReport`, modify the `ElectricityReading` query to apply `->whereIn('electricity_meter_id', $meterIds)` only when `electricity_meter_ids` is provided and non-empty
    - Empty array `[]` should be treated same as omitted (reset all)
    - Ignore `electricity_meter_ids` when category is `gas` or `water`
    - Ensure the `electricity_readings` count in the response reflects the actual number of updated records
    - _Requirements: 3.2, 3.3, 3.4, 3.7, 4.1, 4.2_

  - [ ]* 1.3 Write PHP unit tests for reset with meter IDs
    - Test that providing valid `electricity_meter_ids` only resets those specific meters' readings
    - Test that omitting `electricity_meter_ids` resets all electricity readings (backward compat)
    - Test that empty array `[]` resets all electricity readings
    - Test that invalid meter IDs return 422 validation error
    - Test that `electricity_meter_ids` is ignored for gas/water categories
    - Test that response count matches actual updated records
    - **Property 5: Selective Reset Isolation**
    - **Property 6: Invalid Meter ID Rejection**
    - **Property 7: Response Count Accuracy**
    - **Property 8: Non-Electricity Category Ignores Meter IDs**
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 4.1, 4.2_

- [x] 2. Checkpoint - Backend tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 3. Frontend: Add Meter Selector UI in Reset Dialog
  - [x] 3.1 Add meter-related reactive state and computed properties to DailyUsageReport.vue
    - Add `meterList`, `selectedMeterIds`, `meterLoading`, `meterError` refs
    - Add `allMetersSelected` computed (true iff all meters selected)
    - Add `resetButtonDisabled` computed (disabled when electricity selected with no meters chosen, or loading/error)
    - Add `toggleSelectAll` and `toggleMeter` functions
    - _Requirements: 1.4, 1.5, 1.6, 1.7, 1.8, 2.4, 2.5_

  - [x] 3.2 Add meter fetching logic when "Listrik" category is selected
    - Watch `resetCategory` — when it changes to `'electricity'`, fetch meters from `GET /branches/{branchId}/electricity-meters`
    - Filter response to only include meters where `is_active === true`
    - Set `meterLoading` during fetch and `meterError` on failure
    - Clear `selectedMeterIds` and `meterList` when category changes away from electricity
    - _Requirements: 5.1, 5.2, 5.3, 5.4_

  - [x] 3.3 Add Meter Selector UI template in the reset modal body
    - Conditionally render the Meter Selector panel below category selection when `resetCategory === 'electricity'`
    - Show loading spinner while meters are being fetched
    - Show error message if fetch fails
    - Show "Tidak ada meter listrik aktif untuk cabang ini." if no active meters
    - Render "Pilih Semua" checkbox at top
    - Render individual meter checkboxes with label `{meter_name} ({location})`
    - Disable confirm button based on `resetButtonDisabled` computed
    - _Requirements: 1.1, 1.2, 1.3, 1.9, 1.10, 2.1, 2.2, 2.3_

  - [x] 3.4 Update handleResetDailyUsage to include electricity_meter_ids in payload
    - When `resetCategory === 'electricity'` and `selectedMeterIds` is non-empty, include `electricity_meter_ids: selectedMeterIds.value` in the POST payload
    - For gas/water, do not include the parameter
    - _Requirements: 3.1, 3.2_

  - [ ]* 3.5 Write frontend unit tests for Meter Selector logic
    - Test Select All toggles all meters on/off
    - Test individual toggle updates selectedMeterIds correctly
    - Test allMetersSelected computed reflects correct state
    - Test resetButtonDisabled is true when no meters selected for electricity
    - Test resetButtonDisabled is false when at least one meter selected
    - Test meter list only shows active meters
    - **Property 1: Select All Bidirectional Invariant**
    - **Property 4: Reset Button Enablement Invariant**
    - _Requirements: 1.4, 1.5, 1.6, 1.7, 1.8, 2.4, 2.5_

- [x] 4. Checkpoint - All backend and frontend tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 5. Integration wiring and final verification
  - [x] 5.1 Verify end-to-end flow: category selection → meter fetch → meter selection → reset API call
    - Ensure the full flow works: selecting "Listrik" fetches meters, selecting meters enables button, confirming sends correct payload with `electricity_meter_ids`, and API responds with accurate count
    - Verify backward compatibility: selecting "Gas" or "Air" proceeds without meter selector
    - Verify that resetting all meters (via "Pilih Semua") produces same result as omitting parameter
    - _Requirements: 1.1, 2.1, 2.2, 2.3, 3.2, 3.3, 4.1, 4.2, 4.3, 4.4_

- [x] 6. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- The existing `GET /branches/{branchId}/electricity-meters` endpoint is reused (no changes needed)
- No database migrations required — uses existing ElectricityMeter and ElectricityReading models
- PHP (Laravel) for backend, Vue.js (Composition API) for frontend
- Property tests validate universal correctness properties from the design document

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1"] },
    { "id": 1, "tasks": ["1.2", "3.1"] },
    { "id": 2, "tasks": ["1.3", "3.2"] },
    { "id": 3, "tasks": ["3.3", "3.4"] },
    { "id": 4, "tasks": ["3.5"] },
    { "id": 5, "tasks": ["5.1"] }
  ]
}
```
