# Implementation Plan: Role & Permission Management

## Overview

Implementasi peningkatan fitur Role & Permission Management pada Helpdesk GM. Backend menggunakan Laravel + Spatie Permission (tanpa migrasi DB), frontend menggunakan Vue 3 + Pinia dengan composable `usePermissionManager`. Implementasi mencakup: enhanced bulk toggle, sub-feature toggle dengan dependency resolution, permission matrix view, preset templates, role duplication, search/filter, proteksi role sistem, dan validasi backend yang diperkuat.

## Tasks

- [x] 1. Setup dan foundation
  - [x] 1.1 Install fast-check sebagai dev dependency di frontend
    - Jalankan `npm install --save-dev fast-check` di direktori `fe/`
    - Buat direktori `fe/tests/unit/composables/` untuk test file baru
    - Buat direktori `fe/tests/unit/config/` untuk config test file
    - _Requirements: Testing infrastructure_

  - [x] 1.2 Enhance `usePermissionManager` composable — tambah cascading deselection dengan dialog konfirmasi
    - Modifikasi `fe/src/composables/usePermissionManager.js`
    - Tambahkan method `forceDeselect(permissionName)` yang melakukan cascading deselection rekursif pada semua permission dependen
    - Tambahkan method `getTransitiveDependents(permissionName)` yang mengembalikan semua permission yang bergantung secara transitif
    - Pastikan `togglePermission` mengembalikan info `dependents` saat deselection gagal agar UI bisa menampilkan dialog konfirmasi
    - Batasi recursive dependency resolution maksimal 3 level kedalaman
    - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5_

  - [x] 1.3 Enhance `usePermissionManager` composable — perbaiki toggle feature OFF agar memperhitungkan external locks
    - Modifikasi `toggleFeature` di `fe/src/composables/usePermissionManager.js`
    - Saat toggle OFF: hanya hapus permission yang TIDAK di-lock oleh permission di luar Feature_Group yang sedang di-toggle
    - Pastikan setelah toggle OFF yang sebagian, status feature menunjukkan 'partial' bukan 'none'
    - Terapkan logika yang sama pada `toggleSubFeature`
    - _Requirements: 1.2, 1.5, 2.3, 2.5_

  - [x] 1.4 Enhance `usePermissionManager` composable — perbaiki toggle feature ON agar resolve cross-group dependencies
    - Modifikasi `toggleFeature` di `fe/src/composables/usePermissionManager.js`
    - Saat toggle ON: setelah menambahkan semua permission dalam group, resolve dependency untuk setiap permission yang ditambahkan (termasuk dependency dari Feature_Group lain)
    - Terapkan logika yang sama pada `toggleSubFeature` — resolve dependsOn config saat sub-feature di-activate
    - _Requirements: 1.1, 1.4, 2.1, 2.2, 2.4_

- [x] 2. Checkpoint - Pastikan composable logic berjalan benar
  - Ensure all tests pass, ask the user if questions arise.

- [x] 3. Backend enhancements — System Role Protection
  - [x] 3.1 Enhance `RoleController` — tambah superadmin ke SYSTEM_ROLES dan proteksi immutability
    - Modifikasi `api/app/Http/Controllers/RoleController.php`
    - Definisikan konstanta `SYSTEM_ROLES = ['admin', 'staff', 'user', 'superadmin']` dan `IMMUTABLE_ROLES = ['superadmin']`
    - Di `update()`: tolak rename untuk semua SYSTEM_ROLES dengan 403, tolak permission change untuk superadmin dengan 403
    - Di `destroy()`: tolak penghapusan semua SYSTEM_ROLES dengan 403 (tambah superadmin)
    - Di `destroy()`: ubah response code dari 400 ke 422 untuk role dengan user, sertakan jumlah user dalam pesan error
    - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5_

  - [x] 3.2 Enhance `RoleStoreRequest` dan `RoleUpdateRequest` — perkuat validasi permission
    - Modifikasi `api/app/Http/Requests/RoleStoreRequest.php`: tambah rule `permissions.*` => `string|exists:permissions,name`
    - Modifikasi `api/app/Http/Requests/RoleUpdateRequest.php`: tambah rule serupa, handle `nullable` untuk permissions field
    - Pastikan duplicate permission tidak menghasilkan error (deduplikasi di controller sebelum sync)
    - Pastikan permissions null/absent tidak mengubah permission existing
    - Pastikan permissions array kosong `[]` menghapus semua permission
    - _Requirements: 10.1, 10.2, 10.3, 10.4, 10.5_

  - [x] 3.3 Enhance `RoleController@store` dan `@update` — handle deduplication dan null permissions
    - Modifikasi `api/app/Http/Controllers/RoleController.php`
    - Di `store()`: deduplikasi array permissions sebelum `syncPermissions()`
    - Di `update()`: hanya panggil `syncPermissions()` jika key `permissions` exists dan bukan null; jika array kosong, panggil `syncPermissions([])`
    - _Requirements: 10.3, 10.4, 10.5_

- [x] 4. Backend enhancements — New Endpoints
  - [x] 4.1 Implement `GET /api/v1/roles/matrix` endpoint
    - Tambah method `matrix()` di `api/app/Http/Controllers/RoleController.php`
    - Query semua roles dengan permissions, baca feature groups config, hitung status per cell (full/partial/empty)
    - Buat file config `api/config/permission_features.php` yang mendefinisikan mapping feature group → permission names (mirror frontend permissionConfig)
    - Return response format: `{ roles: [...], matrix: { roleName: { featureKey: { selected, total, status } } }, features: [...] }`
    - Tambah route di `api/routes/api.php`: `Route::get('roles/matrix', ...)` (sebelum apiResource agar tidak bentrok)
    - Tambah middleware permission `role-list` pada method ini
    - _Requirements: 9.1, 9.2, 9.5_

  - [x] 4.2 Implement `GET /api/v1/roles/presets` endpoint
    - Tambah method `presets()` di `api/app/Http/Controllers/RoleController.php`
    - Buat file config `api/config/role_presets.php` yang mendefinisikan preset (Staff, User, Admin) dengan permission list
    - Return response format: `{ presets: { staff: { label, description, permissions: [...] }, ... } }`
    - Tambah route di `api/routes/api.php`: `Route::get('roles/presets', ...)`
    - _Requirements: 4.1, 4.4_

  - [x] 4.3 Enhance `RoleController@index` — tambah users_count dan permission counts per feature
    - Modifikasi `api/app/Http/Controllers/RoleController.php` method `index()`
    - Tambahkan `withCount('users')` pada query
    - Tambahkan field `is_system` (true jika name ada di SYSTEM_ROLES) pada response per role
    - _Requirements: 3.4, 8.5_

  - [x] 4.4 Enhance `RoleController@show` — tambah users_count
    - Modifikasi method `show()`: tambahkan `withCount('users')` dan field `users_count` pada response
    - _Requirements: 5.1_

- [x] 5. Checkpoint - Pastikan backend endpoints berfungsi
  - Ensure all tests pass, ask the user if questions arise.

- [x] 6. Frontend — DependencyConfirmDialog component
  - [x] 6.1 Buat komponen `DependencyConfirmDialog.vue`
    - Buat file `fe/src/components/admin/DependencyConfirmDialog.vue`
    - Props: `show` (Boolean), `permissionName` (String), `dependents` (Array of permission names), `permissionLabels` (Object mapping name to label)
    - Emit: `confirm`, `cancel`
    - Tampilkan daftar permission yang akan terpengaruh dengan label yang readable
    - Gunakan komponen UI yang sudah ada (mirip ConfirmationModal) untuk konsistensi
    - _Requirements: 7.2, 7.3_

- [x] 7. Frontend — Enhanced RoleForm dengan dependency dialog dan duplicate mode
  - [x] 7.1 Enhance `RoleForm.vue` — integrasikan DependencyConfirmDialog
    - Modifikasi `fe/src/views/admin/role/RoleForm.vue`
    - Import dan register `DependencyConfirmDialog`
    - Saat `togglePermission` mengembalikan `{ success: false, dependents: [...] }`, tampilkan dialog
    - Jika user konfirmasi dari dialog, panggil `forceDeselect()` pada composable
    - Jika user batal, tutup dialog tanpa perubahan
    - _Requirements: 7.2, 7.3_

  - [x] 7.2 Enhance `RoleForm.vue` — implementasi duplicate mode via query param
    - Modifikasi `fe/src/views/admin/role/RoleForm.vue`
    - Deteksi query param `?duplicate={roleId}` pada `onMounted`
    - Jika ada, fetch role source via `fetchRole(duplicateId)`, set permissions dari source tapi kosongkan nama
    - Tampilkan pesan bahwa form ini adalah duplikasi dari role X
    - Disable tombol simpan sampai nama role baru diisi (min 3, max 50 karakter)
    - _Requirements: 5.1, 5.2, 5.3_

  - [x] 7.3 Enhance `RoleForm.vue` — tambah validasi nama role duplicate
    - Modifikasi `fe/src/views/admin/role/RoleForm.vue`
    - Saat submit gagal dengan error nama duplikat (422), tampilkan inline error di bawah field nama
    - _Requirements: 5.4_

- [x] 8. Frontend — Enhanced RoleList dengan duplicate action dan permission badges
  - [x] 8.1 Enhance `RoleList.vue` — pastikan duplicate button tersedia untuk SEMUA role (termasuk sistem)
    - Modifikasi `fe/src/views/admin/role/RoleList.vue`
    - Ubah kondisi duplicate button: hapus `!isProtectedRole(item.name)` — duplikasi diizinkan untuk semua role termasuk sistem
    - _Requirements: 5.3_

  - [x] 8.2 Enhance `RoleList.vue` — perbaiki permission summary badges
    - Modifikasi `fe/src/views/admin/role/RoleList.vue`
    - Badge harus menunjukkan format "{selected}/{total}" per feature
    - Jika semua permission dalam feature aktif, tampilkan "Penuh" sebagai gantinya
    - Jangan tampilkan badge untuk feature yang tidak memiliki permission aktif (0/N)
    - _Requirements: 3.1, 3.2, 3.3_

- [x] 9. Frontend — PermissionMatrix view
  - [x] 9.1 Buat komponen `PermissionMatrixCell.vue`
    - Buat file `fe/src/components/admin/PermissionMatrixCell.vue`
    - Props: `status` ('full' | 'partial' | 'empty'), `roleName` (String), `featureKey` (String), `permissions` (Array)
    - Tampilkan indikator visual berdasarkan status: ikon penuh/sebagian/kosong dengan warna berbeda
    - Emit `click` untuk menampilkan detail permission
    - _Requirements: 9.2, 9.3_

  - [x] 9.2 Buat view `PermissionMatrix.vue`
    - Buat file `fe/src/views/admin/role/PermissionMatrix.vue`
    - Panggil endpoint `GET /api/v1/roles/matrix`
    - Render tabel dengan kolom = roles, baris = feature groups (14 modul)
    - Tampilkan header dengan nama role, body dengan `PermissionMatrixCell` per sel
    - Click pada sel menampilkan popover/modal dengan detail permission (nama + status aktif/tidak) — read-only
    - Handle error state (tampilkan pesan error dengan retry button)
    - _Requirements: 9.1, 9.2, 9.3, 9.4, 9.5_

  - [x] 9.3 Tambah route untuk PermissionMatrix
    - Modifikasi `fe/src/router/index.js`
    - Tambah route: `{ path: 'roles/matrix', name: 'admin.roles.matrix', component: PermissionMatrix }`
    - Pastikan route ditempatkan sebelum route `role/:id/edit` agar tidak bentrok
    - Tambah link ke matrix dari `RoleList.vue` (button/link di header)
    - _Requirements: 9.1_

- [x] 10. Frontend — Pinia store enhancement
  - [x] 10.1 Enhance Pinia role store — tambah actions untuk matrix dan presets
    - Modifikasi `fe/src/stores/role.js`
    - Tambah state: `matrix: null`, `presets: {}`
    - Tambah action `fetchMatrix()`: GET `/roles/matrix`
    - Tambah action `fetchPresets()`: GET `/roles/presets`
    - _Requirements: 4.1, 9.1_

- [x] 11. Checkpoint - Pastikan frontend components terintegrasi
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 12. Property-based tests untuk composable logic
  - [ ]* 12.1 Write property test — Feature toggle ON activates all permissions in group
    - **Property 1: Feature toggle ON activates all permissions in group**
    - **Validates: Requirements 1.1**
    - Buat file `fe/tests/unit/composables/usePermissionManager.property.test.js`
    - Gunakan fast-check `fc.constantFrom(...Object.keys(featureGroups))` sebagai generator feature key
    - Verifikasi bahwa setelah toggleFeature ON, semua permission dalam group ada di selectedPermissions

  - [ ]* 12.2 Write property test — Feature toggle OFF preserves externally-locked permissions
    - **Property 2: Feature toggle OFF preserves externally-locked permissions only**
    - **Validates: Requirements 1.2, 1.5**
    - Gunakan generator: pilih feature key random, setup state dimana feature ON + ada lock dari feature lain
    - Verifikasi bahwa setelah toggle OFF, locked permissions tetap ada, yang lain hilang

  - [ ]* 12.3 Write property test — Feature status reflects partial selection
    - **Property 3: Feature status reflects partial selection as indeterminate**
    - **Validates: Requirements 1.3**
    - Generate subset random permissions dari sebuah feature (tidak kosong, tidak penuh)
    - Verifikasi `getFeatureStatus` mengembalikan 'partial'

  - [ ]* 12.4 Write property test — Feature toggle ON resolves cross-group dependencies
    - **Property 4: Feature toggle ON resolves all cross-group dependencies**
    - **Validates: Requirements 1.4, 2.4**
    - Toggle ON sebuah feature, lalu cek bahwa semua dependencies (termasuk dari group lain) juga terpilih

  - [ ]* 12.5 Write property test — Sub-feature toggle OFF preserves external locks
    - **Property 5: Sub-feature toggle OFF preserves externally-locked permissions**
    - **Validates: Requirements 2.3, 2.5**
    - Setup sub-feature active + lock dari luar, toggle OFF, verifikasi locked permissions tetap

  - [ ]* 12.6 Write property test — Permission count accuracy
    - **Property 6: Permission count per feature is accurate**
    - **Validates: Requirements 3.1**
    - Generate random array of valid permission names
    - Verifikasi `countSelectedByFeature` mengembalikan count yang cocok dengan intersection aktual

  - [ ]* 12.7 Write property test — Preset replaces all permissions exactly
    - **Property 7: Preset application replaces all permissions exactly**
    - **Validates: Requirements 4.2, 4.5**
    - Untuk setiap preset, apply preset lalu verifikasi selectedPermissions === preset.permissions (set equality)

  - [ ]* 12.8 Write property test — Preset does not lock from modification
    - **Property 8: Preset does not lock permissions from modification**
    - **Validates: Requirements 4.3**
    - Apply preset, lalu toggle random permission yang tidak di-lock, verifikasi toggle berhasil

  - [ ]* 12.9 Write property test — Search returns only matching groups
    - **Property 11: Search filter returns only matching groups**
    - **Validates: Requirements 6.2**
    - Generate random query substring dari known labels
    - Verifikasi filtered results hanya mengandung groups dengan match

  - [ ]* 12.10 Write property test — Search preserves selection state
    - **Property 12: Search preserves permission selection state**
    - **Validates: Requirements 6.3**
    - Record selection state, apply search, clear search, verifikasi state tidak berubah

  - [ ]* 12.11 Write property test — Recursive dependency resolution 3 levels
    - **Property 13: Recursive dependency resolution up to 3 levels**
    - **Validates: Requirements 7.1**
    - Toggle permission yang punya chain dependency, verifikasi semua level ter-resolve

  - [ ]* 12.12 Write property test — Cascading deselection removes all dependents
    - **Property 14: Cascading deselection removes all dependents**
    - **Validates: Requirements 7.3**
    - Setup chain, force deselect prasyarat, verifikasi semua dependents hilang

  - [ ]* 12.13 Write property test — Dependency lock identifies auto-activated permissions
    - **Property 15: Dependency lock correctly identifies auto-activated permissions**
    - **Validates: Requirements 7.4, 7.5**
    - Toggle permission, verifikasi dependency muncul di dependencyLocks dengan key yang benar

- [ ] 13. Backend tests
  - [ ]* 13.1 Write PHPUnit tests — System role protection
    - **Property 16: System roles cannot be deleted or renamed**
    - **Property 17: Superadmin permissions are immutable**
    - **Property 18: Non-superadmin system roles allow permission changes**
    - **Validates: Requirements 8.1, 8.2, 8.3, 8.4**
    - Modifikasi/extend `api/tests/Feature/RoleTest.php`
    - Test DELETE pada admin, staff, user, superadmin → expect 403
    - Test rename pada system roles → expect 403
    - Test permission update pada superadmin → expect 403
    - Test permission update pada admin/staff/user → expect 200

  - [ ]* 13.2 Write PHPUnit tests — Role deletion with users
    - **Property 19: Non-system role deletion blocked when users assigned**
    - **Validates: Requirements 8.5**
    - Buat role dengan user assigned, coba DELETE → expect 422 dengan user count di pesan

  - [ ]* 13.3 Write PHPUnit tests — Backend permission validation
    - **Property 21: Backend rejects invalid permissions with details**
    - **Property 22: Duplicate permissions are deduplicated silently**
    - **Property 23: Null permissions field preserves existing permissions**
    - **Property 24: Empty permissions array clears all permissions**
    - **Validates: Requirements 10.1, 10.2, 10.3, 10.4, 10.5**
    - Extend `api/tests/Feature/RoleTest.php`
    - Test POST/PUT dengan permission invalid → expect 422 dengan detail
    - Test POST/PUT dengan duplicates → expect success dengan unique stored
    - Test PUT tanpa field permissions → existing permissions unchanged
    - Test PUT dengan permissions: [] → all permissions removed

  - [ ]* 13.4 Write PHPUnit tests — Matrix endpoint
    - **Property 20: Matrix cell status correctness**
    - **Validates: Requirements 9.2**
    - Test GET `/roles/matrix` returns correct structure
    - Verifikasi status per cell (full/partial/empty) berdasarkan permissions yang di-assign

- [x] 14. Final integration dan wiring
  - [x] 14.1 Wire semua komponen — pastikan navigasi, breadcrumb, dan link antar halaman berfungsi
    - Verifikasi link "Lihat Matrix" dari RoleList ke PermissionMatrix
    - Verifikasi link "Duplikat" membuka form dengan query param yang benar
    - Verifikasi DependencyConfirmDialog muncul saat cascading deselection
    - Verifikasi preset dropdown bekerja end-to-end (fetch dari API + apply ke form)
    - Pastikan semua route teregistrasi dengan benar dan permission guard tepat
    - _Requirements: 4.1, 5.1, 7.2, 9.1_

  - [x] 14.2 Pastikan error handling konsisten di seluruh fitur
    - API errors ditampilkan via Alert component dengan auto-dismiss 5 detik
    - Network failure pada matrix ditampilkan inline dengan retry button
    - Role name taken error ditampilkan inline di bawah field nama
    - Lock warning ditampilkan sebagai amber banner
    - _Requirements: 5.4, 9.5_

- [x] 15. Final checkpoint - Pastikan semua test pass dan fitur terintegrasi
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property tests validate universal correctness properties from the design document (24 properties)
- Backend uses Pest/PHPUnit with data providers for parameterized testing
- Frontend uses Vitest + fast-check for property-based testing
- No database migration needed — leverages existing Spatie Permission tables
- Frontend `permissionConfig.js` is the single source of truth for feature groups, dependencies, and presets
- Backend `config/permission_features.php` mirrors frontend config for matrix calculations

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1"] },
    { "id": 1, "tasks": ["1.2", "1.3", "1.4", "3.1", "3.2"] },
    { "id": 2, "tasks": ["3.3", "4.1", "4.2", "4.3", "4.4", "6.1"] },
    { "id": 3, "tasks": ["7.1", "7.2", "8.1", "8.2", "9.1", "10.1"] },
    { "id": 4, "tasks": ["7.3", "9.2"] },
    { "id": 5, "tasks": ["9.3", "14.1", "14.2"] },
    { "id": 6, "tasks": ["12.1", "12.2", "12.3", "12.4", "12.5", "12.6", "12.7", "12.8", "12.9", "12.10", "12.11", "12.12", "12.13"] },
    { "id": 7, "tasks": ["13.1", "13.2", "13.3", "13.4"] }
  ]
}
```
