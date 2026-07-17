# Implementation Plan: Role & Permission untuk Fitur Permintaan

## Overview

Implementasi penambahan 4 permission baru dan 2 role baru untuk mendukung workflow approval pada Form Permintaan. Perubahan mencakup backend (seeder, middleware, controller, routes) dan frontend (permissionConfig.js), beserta test coverage.

## Tasks

- [x] 1. Update PermissionSeeder dengan permission baru
  - [x] 1.1 Tambahkan 4 permission baru ke array `$permissions` di PermissionSeeder.php
    - Tambahkan `'review'`, `'reject'`, `'edit'`, `'delete'` ke array key `'form-permintaan'`
    - File: `api/database/seeders/PermissionSeeder.php`
    - _Requirements: 1.1, 1.2, 1.3, 1.4_

- [x] 2. Update RoleSeeder dengan role baru dan assignment permission
  - [x] 2.1 Tambahkan role `approver-permintaan` dan `reviewer-permintaan` di RoleSeeder.php
    - Buat role `approver-permintaan` dengan `firstOrCreate` dan `syncPermissions`: `form-permintaan-menu`, `form-permintaan-list`, `form-permintaan-confirm`, `form-permintaan-view-all`, `form-permintaan-reject`
    - Buat role `reviewer-permintaan` dengan `firstOrCreate` dan `syncPermissions`: `form-permintaan-menu`, `form-permintaan-list`, `form-permintaan-review`, `form-permintaan-view-all`
    - File: `api/database/seeders/RoleSeeder.php`
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 3.1, 3.2, 3.3, 3.4_

  - [x] 2.2 Update assignment permission untuk role `staff` dan existing roles
    - Tambah `form-permintaan-reject` ke array `$staffPermissions`
    - Role `admin` otomatis mendapat permission baru karena filter hanya exclude `role-create/edit/delete` dan `whatsapp-setting-*` dan `user-activity-*`
    - Role `user` TIDAK ditambahkan permission baru (tetap hanya menu, list, create)
    - Role `superadmin` otomatis mendapat semua via `$allPermissions`
    - File: `api/database/seeders/RoleSeeder.php`
    - _Requirements: 1.5, 1.6, 6.1, 6.2, 6.3, 6.4, 6.5_

- [x] 3. Update FormPermintaanController middleware dan tambah method baru
  - [x] 3.1 Update `middleware()` method di FormPermintaanController
    - Pisahkan `update` dari group `form-permintaan-create` ke `form-permintaan-edit`
    - Pisahkan `destroy` dari group `form-permintaan-create` ke `form-permintaan-delete`
    - Tambah middleware `form-permintaan-review` untuk action `review`
    - Tambah middleware `form-permintaan-reject` untuk action `reject`
    - File: `api/app/Http/Controllers/FormPermintaanController.php`
    - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5_

  - [x] 3.2 Tambah method `review()` dan `reject()` di FormPermintaanController
    - `review(string $id)`: set status = 'reviewed', record reviewed_by
    - `reject(Request $request, string $id)`: set status = 'rejected', record rejected_by, optional reason
    - Gunakan pattern yang sama dengan method `confirm()` yang sudah ada
    - File: `api/app/Http/Controllers/FormPermintaanController.php`
    - _Requirements: 7.1, 7.2, 7.7_

  - [x] 3.3 Tambahkan route baru di api.php
    - Tambah `Route::put('form-permintaan/{id}/review', [FormPermintaanController::class, 'review'])`
    - Tambah `Route::put('form-permintaan/{id}/reject', [FormPermintaanController::class, 'reject'])`
    - Tempatkan setelah route `confirm` yang sudah ada
    - File: `api/routes/api.php`
    - _Requirements: 7.1, 7.2_

- [ ] 4. Checkpoint - Verifikasi backend
  - Ensure all tests pass, ask the user if questions arise.

- [x] 5. Update permissionConfig.js di frontend
  - [x] 5.1 Tambah 4 permission baru ke featureGroups.formPermintaan.permissions
    - Tambah `form-permintaan-review`, `form-permintaan-reject`, `form-permintaan-edit`, `form-permintaan-delete` dengan label dan deskripsi sesuai design
    - Tempatkan setelah permission `form-permintaan-view-all`
    - File: `fe/src/config/permissionConfig.js`
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.9_

  - [x] 5.2 Tambah 4 dependency baru ke permissionDependencies
    - Tambah dependency untuk `form-permintaan-review`, `form-permintaan-reject`, `form-permintaan-edit`, `form-permintaan-delete` → masing-masing membutuhkan `form-permintaan-list` dan `form-permintaan-menu`
    - File: `fe/src/config/permissionConfig.js`
    - _Requirements: 4.5, 4.6, 4.7, 4.8, 4.10_

  - [x] 5.3 Tambah 2 role preset baru dan update preset staff
    - Tambah preset `approver-permintaan` dengan icon "CheckCircle" dan 5 permission
    - Tambah preset `reviewer-permintaan` dengan icon "Eye" dan 4 permission
    - Update preset `staff` untuk menambahkan `form-permintaan-reject`
    - File: `fe/src/config/permissionConfig.js`
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_

- [x] 6. Checkpoint - Verifikasi frontend config
  - Ensure all tests pass, ask the user if questions arise.
0- [ ] 7. Backend tests
  - [ ] 7.1 Buat test file FormPermintaanSeederTest untuk verifikasi seeder
    - Test permission seeder mendaftarkan 4 permission baru
    - Test role seeder membuat `approver-permintaan` dan `reviewer-permintaan` dengan permission yang benar
    - Test idempotence: jalankan seeder 2x, verifikasi state identik
    - Test permission assignment per role sesuai matrix
    - File: `api/tests/Feature/FormPermintaanSeederTest.php`
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 2.1, 2.2, 2.3, 2.4, 3.1, 3.2, 3.3, 6.1, 6.2, 6.3, 6.4, 6.5_

  - [ ] 7.2 Buat test file FormPermintaanPermissionTest untuk verifikasi middleware
    - Test user tanpa permission mendapat 403 pada endpoint review, reject, update, destroy
    - Test user dengan permission yang benar mendapat akses pada endpoint
    - Test user tanpa token mendapat 401
    - Test role approver-permintaan bisa confirm dan reject tapi tidak bisa review
    - Test role reviewer-permintaan bisa review tapi tidak bisa confirm atau reject
    - File: `api/tests/Feature/FormPermintaanPermissionTest.php`
    - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5, 7.6, 7.7_

- [ ] 8. Frontend tests
  - [ ] 8.1 Buat test file permissionConfig.formPermintaan.test.js
    - Test `featureGroups.formPermintaan` memiliki 9 permission entries
    - Test `permissionDependencies` memiliki 4 entry baru dengan dependency yang benar
    - Test `rolePresets` memiliki `approver-permintaan` dan `reviewer-permintaan`
    - Test preset `staff` memiliki `form-permintaan-reject`
    - File: `fe/tests/config/permissionConfig.formPermintaan.test.js`
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7, 4.8, 5.2, 5.3, 5.4_

  - [ ]* 8.2 Buat property test untuk dependency resolution (Property 4)
    - **Property 4: Dependency auto-resolution for new permissions**
    - Untuk setiap permission baru, toggle ON harus auto-enable `form-permintaan-list` dan `form-permintaan-menu`
    - Gunakan `fast-check` dengan minimum 100 iterations
    - **Validates: Requirements 4.5, 4.6, 4.7, 4.8, 4.10**
    - File: `fe/tests/composables/formPermintaanDependency.property.test.js`

  - [ ]* 8.3 Buat property test untuk preset application correctness (Property 5)
    - **Property 5: Preset application correctness**
    - Untuk setiap preset baru, apply harus menghasilkan exact permission set yang didefinisikan
    - Gunakan `fast-check` dengan minimum 100 iterations
    - **Validates: Requirements 5.2, 5.3, 5.5**
    - File: `fe/tests/composables/formPermintaanDependency.property.test.js`

- [ ] 9. Final checkpoint - Pastikan semua test pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property tests validate universal correctness properties (dependency resolution & preset application)
- Unit tests validate specific examples and edge cases
- Role `admin` otomatis mendapat permission baru karena logic filter di RoleSeeder sudah meng-include semua permission kecuali yang di-exclude secara eksplisit
- Role `superadmin` otomatis mendapat semua permission via `Permission::all()`

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1"] },
    { "id": 1, "tasks": ["2.1", "5.1"] },
    { "id": 2, "tasks": ["2.2", "3.1", "5.2"] },
    { "id": 3, "tasks": ["3.2", "5.3"] },
    { "id": 4, "tasks": ["3.3"] },
    { "id": 5, "tasks": ["7.1", "7.2", "8.1"] },
    { "id": 6, "tasks": ["8.2", "8.3"] }
  ]
}
```
