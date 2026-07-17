# Bugfix Requirements Document

## Introduction

Komponen Date Picker (`@vuepic/vue-datepicker` v14) pada halaman "Buat Laporan Harian Cabang" (`/admin/daily-record/create`) mengalami crash saat mount. Crash ini disebabkan oleh penggunaan prop `locale` yang salah — komponen menerima string `"id"` padahal v14 membutuhkan object `Locale` dari `date-fns/locale`. Error yang muncul: `TypeError: Cannot read properties of undefined (reading 'preprocessor')`.

## Bug Analysis

### Current Behavior (Defect)

1.1 WHEN komponen VueDatePicker di-mount dengan prop `locale="id"` (string) THEN the system crash dengan error `TypeError: Cannot read properties of undefined (reading 'preprocessor')` karena v14 mengharapkan object Locale dari date-fns, bukan string

1.2 WHEN halaman DailyRecordForm dibuka (create atau edit) THEN the system menampilkan Vue warning "Unhandled error during execution of mounted hook" dan form date picker tidak dapat digunakan

### Expected Behavior (Correct)

2.1 WHEN komponen VueDatePicker di-mount dengan prop locale berisi object `Locale` dari `date-fns/locale/id` THEN the system SHALL menampilkan date picker dengan benar tanpa error, menggunakan format tanggal dan nama hari/bulan dalam Bahasa Indonesia

2.2 WHEN halaman DailyRecordForm dibuka (create atau edit) THEN the system SHALL menampilkan form lengkap termasuk date picker yang berfungsi normal dan user dapat memilih tanggal

### Unchanged Behavior (Regression Prevention)

3.1 WHEN user memilih tanggal pada date picker THEN the system SHALL CONTINUE TO mengupdate `form.date` dalam format `yyyy-MM-dd`

3.2 WHEN user menavigasi bulan pada date picker dan branch_id sudah dipilih THEN the system SHALL CONTINUE TO menampilkan dot indicator pada tanggal yang sudah memiliki laporan

3.3 WHEN user membuka halaman edit laporan harian THEN the system SHALL CONTINUE TO menampilkan tanggal yang tersimpan pada date picker

3.4 WHEN branch_id belum dipilih THEN the system SHALL CONTINUE TO tidak menampilkan dot indicator pada date picker

---

### Bug Condition (Derivation)

**Bug Condition Function:**

```pascal
FUNCTION isBugCondition(X)
  INPUT: X of type VueDatePickerProps
  OUTPUT: boolean
  
  // Returns true when locale prop is a string instead of a date-fns Locale object
  RETURN typeof X.locale = "string"
END FUNCTION
```

**Property Specification - Fix Checking:**

```pascal
// Property: Fix Checking - Locale prop is always a valid date-fns Locale object
FOR ALL X WHERE isBugCondition(X) DO
  result ← mountVueDatePicker(X with locale = dateFnsLocaleObject)
  ASSERT no_error(result) AND component_rendered(result)
END FOR
```

**Preservation Goal:**

```pascal
// Property: Preservation Checking - All existing behavior is maintained
FOR ALL X WHERE NOT isBugCondition(X) DO
  ASSERT F(X) = F'(X)
  // Date selection, month navigation, report indicators all work the same
END FOR
```
