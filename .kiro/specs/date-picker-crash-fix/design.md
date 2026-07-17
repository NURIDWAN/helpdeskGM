# Date Picker Crash Fix - Bugfix Design

## Overview

VueDatePicker v14 (`@vuepic/vue-datepicker ^14.0.0`) crashes on mount in `DailyRecordForm.vue` because the `locale` prop receives a string `"id"` instead of a `date-fns` Locale object. The fix imports the Indonesian locale object from `date-fns/locale/id` and binds it to the `:locale` prop. This is a minimal, single-line change in the template plus an import addition — no logic changes required.

## Glossary

- **Bug_Condition (C)**: The condition that triggers the crash — `locale` prop receives a string value instead of a date-fns Locale object
- **Property (P)**: The desired behavior — VueDatePicker mounts without error and renders the calendar with Indonesian locale
- **Preservation**: Existing date selection, model update, month navigation, and report dot indicator behaviors must remain unchanged
- **VueDatePicker**: The `@vuepic/vue-datepicker` v14 component used for date input
- **date-fns Locale object**: A structured object exported from `date-fns/locale/{code}` containing locale-specific date formatting rules
- **DailyRecordForm.vue**: The Vue component at `fe/src/views/admin/dailyrecord/DailyRecordForm.vue` that contains the affected date picker

## Bug Details

### Bug Condition

The bug manifests when VueDatePicker is mounted with `locale="id"` (a static string attribute). In v14, the component's internal `formatLocale` logic calls `locale.preprocessor`, which is `undefined` on a plain string, causing a TypeError crash.

**Formal Specification:**
```
FUNCTION isBugCondition(input)
  INPUT: input of type VueDatePickerProps
  OUTPUT: boolean
  
  RETURN typeof input.locale === "string"
         AND VueDatePicker version >= 14
         AND component attempts to mount
END FUNCTION
```

### Examples

- **Crash case**: `<VueDatePicker locale="id" />` → TypeError: Cannot read properties of undefined (reading 'preprocessor')
- **Correct case**: `<VueDatePicker :locale="idLocaleObject" />` → Calendar renders with Indonesian day/month names
- **No locale case**: `<VueDatePicker />` → Calendar renders with default English locale (no crash)
- **Edge case**: `<VueDatePicker :locale="null" />` → Falls back to default locale (no crash, but not desired)

## Expected Behavior

### Preservation Requirements

**Unchanged Behaviors:**
- Date selection must continue to update `form.date` in `yyyy-MM-dd` format via `handleDateUpdate`
- Month/year navigation must continue to trigger `handleMonthYearChange` and fetch report dates
- Report dot indicators must continue to appear on dates with existing reports when `branch_id` is selected
- The `model-type="yyyy-MM-dd"` configuration must continue to produce string model values
- The `auto-apply` behavior must remain (no confirm button needed)
- Edit mode must continue to display the stored date correctly

**Scope:**
All inputs that do NOT involve the locale prop resolution should be completely unaffected by this fix. This includes:
- Date selection via click
- Month/year navigation via arrow buttons
- Custom day slot rendering (dot indicators)
- Model value formatting
- Form submission with date value

## Hypothesized Root Cause

Based on the bug description and VueDatePicker v14 changelog, the root cause is clear:

1. **Breaking API Change in v14**: VueDatePicker v14 changed the `locale` prop from accepting a BCP 47 string (like `"id"`) to requiring a `date-fns` Locale object. The component internally calls `locale.preprocessor` or accesses locale structure properties that only exist on the date-fns Locale object.

2. **Static String Binding**: The template uses `locale="id"` (static attribute) which passes the literal string `"id"`. This worked in earlier versions of VueDatePicker but not in v14.

3. **Missing date-fns Import**: The file does not import any date-fns locale. The `date-fns` package is available as a transitive dependency of `@vuepic/vue-datepicker` but the Indonesian locale object has never been explicitly imported.

## Correctness Properties

Property 1: Bug Condition - Date Picker Mounts Without Crash

_For any_ VueDatePicker mount where the locale prop is bound to the imported `date-fns` Indonesian Locale object, the component SHALL render successfully without throwing a TypeError, and the calendar SHALL display day/month names in Bahasa Indonesia.

**Validates: Requirements 2.1, 2.2**

Property 2: Preservation - Date Selection and Model Update

_For any_ date selection interaction on the fixed date picker, the component SHALL produce the same `form.date` value in `yyyy-MM-dd` format as it would have with a functioning locale, preserving all date model binding, month navigation events, and report dot indicator behavior.

**Validates: Requirements 3.1, 3.2, 3.3, 3.4**

## Fix Implementation

### Changes Required

Assuming our root cause analysis is correct:

**File**: `fe/src/views/admin/dailyrecord/DailyRecordForm.vue`

**Specific Changes**:

1. **Add date-fns locale import** (in `<script setup>` block):
   - Add: `import { id as idLocale } from 'date-fns/locale/id';`
   - Place after existing imports (e.g., after the `luxon` import line)

2. **Change locale prop binding** (in `<template>` block):
   - Before: `locale="id"` (static string attribute)
   - After: `:locale="idLocale"` (dynamic binding to imported Locale object)

3. **Add date-fns as explicit dependency** (in `fe/package.json`):
   - Add `"date-fns": "^4.1.0"` to `dependencies`
   - Although `date-fns` is available as a transitive dependency of `@vuepic/vue-datepicker`, adding it explicitly ensures the import resolves reliably and documents the dependency

### No Other Files Affected

Only `DailyRecordForm.vue` uses `locale="id"` in the entire codebase. No other components need changes.

## Testing Strategy

### Validation Approach

The testing strategy follows a two-phase approach: first, surface counterexamples that demonstrate the bug on unfixed code, then verify the fix works correctly and preserves existing behavior.

### Exploratory Bug Condition Checking

**Goal**: Surface counterexamples that demonstrate the bug BEFORE implementing the fix. Confirm that passing a string locale causes a crash.

**Test Plan**: Write a component mount test using `@vue/test-utils` that mounts VueDatePicker with `locale="id"` (string) and verifies it throws an error. Run on UNFIXED code to confirm the crash.

**Test Cases**:
1. **String Locale Mount Test**: Mount VueDatePicker with `locale="id"` string → expect crash (will fail on unfixed code)
2. **Object Locale Mount Test**: Mount VueDatePicker with imported `id` locale object → expect success (will pass on both)
3. **No Locale Mount Test**: Mount VueDatePicker without locale prop → expect success with English default

**Expected Counterexamples**:
- Mounting with `locale="id"` produces `TypeError: Cannot read properties of undefined (reading 'preprocessor')`
- Confirms root cause: v14 requires a Locale object, not a string

### Fix Checking

**Goal**: Verify that for all inputs where the bug condition holds, the fixed function produces the expected behavior.

**Pseudocode:**
```
FOR ALL input WHERE isBugCondition(input) DO
  result := mountVueDatePicker({ ...input, locale: idLocaleObject })
  ASSERT no_error(result)
  ASSERT component_rendered(result)
  ASSERT locale_applied(result, "id")
END FOR
```

### Preservation Checking

**Goal**: Verify that for all inputs where the bug condition does NOT hold, the fixed function produces the same result as the original function.

**Pseudocode:**
```
FOR ALL input WHERE NOT isBugCondition(input) DO
  ASSERT datePickerFixed(input).modelValue = datePickerOriginal(input).modelValue
  ASSERT datePickerFixed(input).emittedEvents = datePickerOriginal(input).emittedEvents
END FOR
```

**Testing Approach**: Property-based testing is recommended for preservation checking because:
- It generates many date inputs automatically across the valid date range
- It catches edge cases like leap years, month boundaries, and year transitions
- It provides strong guarantees that date selection behavior is unchanged

**Test Plan**: Verify date model binding behavior is identical before and after the fix. Since the fix only changes the locale prop type (not any logic), preservation testing focuses on ensuring the model value format and event emissions remain consistent.

**Test Cases**:
1. **Date Selection Preservation**: Verify selecting any date produces `yyyy-MM-dd` string in model value
2. **Month Navigation Preservation**: Verify month/year change emits correct event with month and year values
3. **Edit Mode Date Display**: Verify pre-populated date values display correctly
4. **Report Indicator Preservation**: Verify dot indicators appear for dates in `reportDates` set

### Unit Tests

- Test that VueDatePicker mounts without error when given the imported idLocale object
- Test that `handleDateUpdate` still produces `yyyy-MM-dd` format strings
- Test that `handleMonthYearChange` still emits correct month/year values
- Test edge case: undefined/null date input handling

### Property-Based Tests

- Generate random valid dates (2020-2030) and verify `handleDateUpdate` always produces valid `yyyy-MM-dd` strings
- Generate random month/year combinations and verify `handleMonthYearChange` correctly formats them
- Generate random date strings and verify model-type parsing produces consistent results

### Integration Tests

- Mount full DailyRecordForm component and verify date picker renders
- Test selecting a date updates `form.date` reactively
- Test month navigation triggers report date fetching when branch is selected
- Test form submission includes correct date value
