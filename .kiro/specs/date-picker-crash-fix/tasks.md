# Implementation Plan: Date Picker Crash Fix

## Overview

Fix VueDatePicker v14 crash pada `DailyRecordForm.vue` yang disebabkan oleh prop `locale="id"` (string) yang seharusnya menerima object `Locale` dari `date-fns/locale/id`. Workflow mengikuti bug condition methodology: explore bug → preserve existing behavior → implement fix → validate.

## Tasks

- [x] 1. Write bug condition exploration test
  - **Property 1: Bug Condition** - VueDatePicker Crashes With String Locale
  - **CRITICAL**: This test MUST FAIL on unfixed code - failure confirms the bug exists
  - **DO NOT attempt to fix the test or the code when it fails**
  - **NOTE**: This test encodes the expected behavior - it will validate the fix when it passes after implementation
  - **GOAL**: Surface counterexamples that demonstrate the bug exists
  - **Scoped PBT Approach**: Scope the property to the concrete failing case: mounting VueDatePicker with `locale="id"` (string) triggers TypeError
  - Write a Vitest + `@vue/test-utils` test in `fe/src/views/admin/dailyrecord/__tests__/DailyRecordForm.datepicker.spec.js`
  - Test that mounting VueDatePicker with `locale="id"` (string) throws `TypeError: Cannot read properties of undefined (reading 'preprocessor')`
  - Test that mounting VueDatePicker with the imported `id` locale object from `date-fns/locale/id` renders without error
  - Use `fast-check` to generate arbitrary locale-like strings and verify that for the bug condition (`typeof locale === "string"`), the component fails to mount
  - Run test on UNFIXED code with `vitest run --reporter=verbose`
  - **EXPECTED OUTCOME**: Test FAILS (this is correct - it proves the bug exists)
  - Document counterexamples found (e.g., "VueDatePicker with locale='id' throws TypeError")
  - Mark task complete when test is written, run, and failure is documented
  - _Requirements: 1.1, 1.2_

- [x] 2. Write preservation property tests (BEFORE implementing fix)
  - **Property 2: Preservation** - Date Selection and Model Update Behavior
  - **IMPORTANT**: Follow observation-first methodology
  - Observe: `handleDateUpdate` with string input (e.g., `"2024-06-15"`) assigns directly to `form.date`
  - Observe: `handleDateUpdate` with Date object input formats to `yyyy-MM-dd` via Luxon
  - Observe: `handleDateUpdate` with null/undefined sets `form.date` to empty string
  - Observe: `handleMonthYearChange({ month: 6, year: 2024 })` produces `"2024-06"` format
  - Write property-based tests in `fe/src/views/admin/dailyrecord/__tests__/DailyRecordForm.preservation.spec.js` using `fast-check`:
    - For all valid date strings in `yyyy-MM-dd` format (years 2020-2030), `handleDateUpdate(dateStr)` sets `form.date` to that exact string
    - For all valid month (1-12) and year (2020-2030) combinations, `handleMonthYearChange` correctly formats the displayed month string as `yyyy-MM`
    - For null/undefined input, `handleDateUpdate` sets `form.date` to empty string
  - Verify tests pass on UNFIXED code (these behaviors are unrelated to the locale bug)
  - Run tests with `vitest run --reporter=verbose`
  - **EXPECTED OUTCOME**: Tests PASS (this confirms baseline behavior to preserve)
  - Mark task complete when tests are written, run, and passing on unfixed code
  - _Requirements: 3.1, 3.2, 3.3, 3.4_

- [x] 3. Fix for VueDatePicker locale crash

  - [x] 3.1 Add `date-fns` as explicit dependency
    - Add `"date-fns": "^4.1.0"` to `dependencies` in `fe/package.json`
    - Run `npm install` in `fe/` directory to install the package
    - _Bug_Condition: isBugCondition(input) where typeof input.locale === "string"_
    - _Expected_Behavior: date-fns locale object is available for import_
    - _Preservation: No behavior change, only ensures import resolves reliably_
    - _Requirements: 2.1, 2.2_

  - [x] 3.2 Import Indonesian locale and fix template binding
    - In `fe/src/views/admin/dailyrecord/DailyRecordForm.vue`:
    - Add import: `import { id as idLocale } from 'date-fns/locale/id';` after the Luxon import line
    - Change template: `locale="id"` → `:locale="idLocale"`
    - _Bug_Condition: isBugCondition(input) where typeof input.locale === "string" AND VueDatePicker v14_
    - _Expected_Behavior: VueDatePicker mounts without TypeError, renders calendar with Indonesian locale_
    - _Preservation: Date selection, model update, month navigation, dot indicators all unchanged_
    - _Requirements: 1.1, 1.2, 2.1, 2.2, 3.1, 3.2, 3.3, 3.4_

  - [x] 3.3 Verify bug condition exploration test now passes
    - **Property 1: Expected Behavior** - VueDatePicker Mounts Successfully With Locale Object
    - **IMPORTANT**: Re-run the SAME test from task 1 - do NOT write a new test
    - The test from task 1 encodes the expected behavior (mounting with locale object succeeds)
    - When this test passes, it confirms the expected behavior is satisfied
    - Run bug condition exploration test: `vitest run fe/src/views/admin/dailyrecord/__tests__/DailyRecordForm.datepicker.spec.js --reporter=verbose`
    - **EXPECTED OUTCOME**: Test PASSES (confirms bug is fixed)
    - _Requirements: 2.1, 2.2_

  - [x] 3.4 Verify preservation tests still pass
    - **Property 2: Preservation** - Date Selection and Model Update Behavior
    - **IMPORTANT**: Re-run the SAME tests from task 2 - do NOT write new tests
    - Run preservation property tests: `vitest run fe/src/views/admin/dailyrecord/__tests__/DailyRecordForm.preservation.spec.js --reporter=verbose`
    - **EXPECTED OUTCOME**: Tests PASS (confirms no regressions)
    - Confirm all date selection, model formatting, and month navigation behaviors are unchanged
    - _Requirements: 3.1, 3.2, 3.3, 3.4_

- [x] 4. Checkpoint - Ensure all tests pass
  - Run full test suite: `vitest run --reporter=verbose` from `fe/` directory
  - Verify both bug condition test and preservation tests pass
  - Verify no other tests are broken by the change
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- The fix is minimal: one import line + one template attribute change
- `date-fns` is already a transitive dependency of `@vuepic/vue-datepicker` but is added explicitly for reliable resolution
- `fast-check` is already available in devDependencies for property-based testing
- Vitest + `@vue/test-utils` + jsdom are already configured for component testing
- The bug is deterministic (always crashes with string locale on v14), so exploration test scopes to concrete case
- Preservation tests focus on `handleDateUpdate` and `handleMonthYearChange` logic which is unaffected by the locale fix

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1", "2"] },
    { "id": 1, "tasks": ["3.1"] },
    { "id": 2, "tasks": ["3.2"] },
    { "id": 3, "tasks": ["3.3", "3.4"] },
    { "id": 4, "tasks": ["4"] }
  ]
}
```
