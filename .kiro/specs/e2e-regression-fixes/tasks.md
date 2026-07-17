# Implementation Plan: E2E Regression Fixes

## Overview

Rencana implementasi untuk memperbaiki lima regresi e2e yang dikonsolidasikan di `bugfix.md` dan `design.md`:

1. Bug 1 — Create/edit user drops session and does not persist.
2. Bug 2 — Create ticket category drops session and does not persist.
3. Bug 3 — Missing export control pada "Daftar Form Permintaan" dan navigasi baris → 404.
4. Bug 4 — Dropdown "Pilih Cabang" di Daily Usage report kosong.
5. Bug 5 — `/admin/roles/create` mengembalikan 404.

Workflow mengikuti bug condition methodology: (a) tulis property-based exploration test yang GAGAL pada UNFIXED code untuk mengonfirmasi kelima bug, (b) tulis property-based preservation test yang LULUS pada UNFIXED code untuk mengunci baseline, (c) terapkan fix minim-invasif per bug, (d) verifikasi exploration test lolos dan preservation test tetap lolos, (e) jalankan ulang lima skenario e2e yang diberi nama secara verbatim.

## Tasks

- [ ] 1. Write bug condition exploration property tests
  - **Property 1: Bug Condition** - E2E Regressions Surface Counterexamples
  - **CRITICAL**: Property-based tests di bawah HARUS GAGAL pada kode UNFIXED — kegagalan mengonfirmasi bug ada
  - **DO NOT attempt to fix the test or the code when it fails**
  - **NOTE**: Tes ini mengencode expected behavior; sama-sama akan lolos setelah fix diterapkan
  - **GOAL**: Munculkan counterexample konkret untuk setiap sub-kondisi `isBugCondition` (Bug 1–5)
  - **Scoped PBT Approach**: Bug 1–4 memakai generator `fast-check`; Bug 5 deterministik → scoped ke path `/admin/roles/create` konkret
  - Tulis enam file test property-based (lima bug, dengan Bug 3 dibagi 3a/3b) sesuai Testing Strategy pada `design.md`:
    - `fe/tests/unit/stores/user.session.spec.js` (Bug 1) — property: untuk generator payload `UserStoreRequest` valid + mock axios yang mereturn 201 pada `POST /users` dan 401 pada request paralel non-auth, `Cookies.remove` tidak dipanggil dan `router.push('login')` tidak dipanggil
    - `fe/tests/unit/stores/ticketCategory.session.spec.js` (Bug 2) — property analog untuk payload TicketCategory
    - `fe/tests/unit/views/formPermintaanList.export.spec.js` (Bug 3a) — property: untuk generator subset permission yang mengandung minimal `form-permintaan-view-all`, `FormPermintaanList.vue` (admin) merender elemen yang cocok regex `Export|Ekspor|Cetak|Print|Unduh|Download|PDF`
    - `fe/tests/unit/views/formPermintaanDetail.notfound.spec.js` (Bug 3b) — property: untuk generator `formId` valid + status respons ∈ {200, 403, 404}, `AppFormPermintaanDetail` merender halaman detail (200), `error.forbidden` (403), atau pesan inline "Data tidak tersedia" (404) — bukan `error.notfound` untuk semua kasus
    - `fe/tests/unit/views/dailyUsageReport.branchOptions.spec.js` (Bug 4) — property: untuk generator `branches` panjang 0..10, jumlah `<option>` di dropdown = `branches.length + 1` (termasuk placeholder), dan tombol export non-disabled iff `branches.length > 0`
    - `fe/tests/unit/router/adminRolesCreateUrl.spec.js` (Bug 5) — scoped: `router.resolve('/admin/roles/create').name === 'admin.role.create'`
  - Semua test menggunakan `vitest` + `fast-check` yang sudah tersedia di `fe/package.json`
  - Jalankan semua test pada UNFIXED code: `npm run test:run -- tests/unit/stores/user.session.spec.js tests/unit/stores/ticketCategory.session.spec.js tests/unit/views/formPermintaanList.export.spec.js tests/unit/views/formPermintaanDetail.notfound.spec.js tests/unit/views/dailyUsageReport.branchOptions.spec.js tests/unit/router/adminRolesCreateUrl.spec.js`
  - **EXPECTED OUTCOME**: Semua tes GAGAL dengan counterexample yang menunjuk ke akar penyebab pada bagian Hypothesized Root Cause di `design.md`
  - Dokumentasikan counterexample yang muncul (mis. `Cookies.remove` dipanggil pada 201, permission set `{view-all}` tanpa `list` menyembunyikan tombol export, `router.push('error.notfound')` dipanggil pada 403, `branches.length = 0` menghasilkan dropdown kosong, `/admin/roles/create` di-resolve ke `not-found`)
  - Tandai task selesai ketika semua test dibuat, dijalankan, dan kegagalannya didokumentasikan
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 1.6_

- [ ] 2. Write preservation property tests (BEFORE implementing fix)
  - **Property 2: Preservation** - Non-Buggy Flows Unchanged
  - **IMPORTANT**: Ikuti observation-first methodology — jalankan alur non-buggy dulu pada UNFIXED code, catat outputnya, lalu bangun property test yang mengunci output tersebut
  - Cakup klausa preservation 3.1–3.9 dari `bugfix.md`
  - Tulis test property-based dan test contoh sesuai Testing Strategy pada `design.md`:
    - `fe/tests/unit/plugins/axios.auth401.spec.js` — property: `FOR ALL url ∈ {'/auth/me', '/auth/logout'}`, respons 401 tetap memicu `Cookies.remove('token')` dan `router.push({ name: 'login' })`
    - `fe/tests/unit/stores/errorHelper.preserve.spec.js` — property: untuk status ∈ {400, 422, 500}, `handleError` mengembalikan nilai yang sama sebelum dan sesudah perubahan
    - `fe/tests/unit/router/singularPaths.preserve.spec.js` — property: untuk setiap path singular `admin/user/create`, `admin/role/create`, `admin/branch/create`, `admin/ticket-category/create`, resolver menghasilkan nama route sesuai (tidak berubah setelah alias)
    - `api/tests/Feature/UsersListPreserveTest.php` — property (Pest dataset): untuk seed user default, `GET /api/v1/users/all/paginated?row_per_page=N` mengembalikan struktur dan jumlah data yang sama sebelum dan sesudah fix
    - `api/tests/Feature/TicketCategoriesListPreserveTest.php` — property analog untuk `GET /api/v1/ticket-categories`
    - `api/tests/Feature/FormPermintaanUserScopePreserveTest.php` — property: role `user` hanya melihat form permintaan miliknya (repository filter tidak berubah)
  - Jalankan tes:
    - FE: `npm run test:run -- tests/unit/plugins/axios.auth401.spec.js tests/unit/stores/errorHelper.preserve.spec.js tests/unit/router/singularPaths.preserve.spec.js`
    - BE (via DDEV): `ddev exec ./vendor/bin/pest --filter='UsersListPreserveTest|TicketCategoriesListPreserveTest|FormPermintaanUserScopePreserveTest'`
  - **EXPECTED OUTCOME**: Semua tes preservation LULUS pada kode UNFIXED — mengonfirmasi baseline yang harus dijaga
  - Tandai task selesai ketika semua test dibuat, dijalankan, dan lulus pada UNFIXED code
  - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 3.8, 3.9_

- [ ] 3. Fix cross-cutting session drop (Bug 1 & 2)

  - [ ] 3.1 Refactor axios interceptor & default header handling
    - Hapus mutasi `axios.defaults.headers.common['Authorization']` di top-level `fe/src/plugins/axios.js`
    - Modifikasi cabang 401 pada response interceptor: hanya panggil `Cookies.remove` + redirect `login` jika `error.config.url` match `/^\/auth\//`; untuk URL lain, tampilkan toast dan `Promise.reject(error)` saja
    - Sertakan whitelist opsional untuk request `checkAuth` (bila endpoint auth `me` yang memanggil awal)
    - _Bug_Condition: isBugCondition(input) dengan `kind ∈ {'create_user', 'create_ticket_category'}`_
    - _Expected_Behavior: session tetap aktif, cookie tidak dihapus, tidak ada redirect ke login pada respons non-auth non-401_
    - _Preservation: 401 pada endpoint auth tetap men-clear cookie dan redirect (klausa 3.1); 422/500 tetap ditangani `errorHelper` (klausa 3.8)_
    - _Requirements: 2.1, 2.2, 3.1, 3.8_

  - [ ] 3.2 Bersihkan `stores/user.js#createUser` & `handleSubmit` UserForm
    - Hapus `router.push({ name: 'admin.users' })` dari `createUser` di `stores/user.js` — biarkan navigation di component
    - Di `fe/src/views/admin/user/UserForm.vue#handleSubmit`, hanya `router.push` jika `userStore.success` non-null dan `userStore.error` null; tambahkan `toast.success(userStore.success)` untuk feedback
    - _Bug_Condition: `kind = 'create_user'`_
    - _Expected_Behavior: user baru muncul di `/admin/users` setelah sukses dan session tetap aktif_
    - _Preservation: struktur payload dan validasi `UserStoreRequest` tidak berubah (klausa 3.2)_
    - _Requirements: 2.1, 3.2_

  - [ ] 3.3 Rapikan `handleSubmit` TicketCategoryForm
    - Di `fe/src/views/admin/ticketcategory/TicketCategoryForm.vue#handleSubmit`, tangkap error dari `categoryStore.createCategory` dan hanya `router.push` bila tidak ada error
    - Tambahkan `data-testid="ticket-category-save"` untuk stabilitas e2e
    - _Bug_Condition: `kind = 'create_ticket_category'`_
    - _Expected_Behavior: kategori baru terlihat di `/admin/ticket-categories` setelah sukses dan session tetap aktif_
    - _Preservation: perilaku list existing tidak berubah (klausa 3.3)_
    - _Requirements: 2.2, 3.3_

- [ ] 4. Fix Form Permintaan list export & detail navigation (Bug 3)

  - [ ] 4.1 Perlebar permission gating & tambah data-testid untuk kontrol export
    - Di `fe/src/views/admin/formpermintaan/FormPermintaanList.vue`, ubah `v-if="can('form-permintaan-list')"` pada wrapper Export menjadi `v-if="can('form-permintaan-list') || can('form-permintaan-view-all')"`
    - Tambahkan `data-testid="form-permintaan-export"` pada tombol Export dan `data-testid="form-permintaan-export-pdf"` / `-excel` pada dua item dropdown
    - _Bug_Condition: `kind = 'view_admin_form_permintaan_list'`_
    - _Expected_Behavior: element yang cocok regex `Export|Ekspor|Cetak|Print|Unduh|Download|PDF` tampak pada halaman admin form permintaan_
    - _Preservation: role `user` (tanpa dua permission tersebut) tetap tidak melihat kontrol export (klausa 3.4)_
    - _Requirements: 2.3, 3.4_

  - [ ] 4.2 Perbaiki navigasi baris ke detail
    - Di `handleDetail` (admin `FormPermintaanList.vue`), gunakan `admin.form-permintaan.detail` untuk konsistensi layout admin (guard tetap dapat mengarah ke `app.*` untuk role non-admin bila layout `app` diakses)
    - _Bug_Condition: `kind = 'click_form_permintaan_row'`_
    - _Expected_Behavior: navigasi mendarat pada halaman detail admin dengan meta permission `form-permintaan-view-all`_
    - _Preservation: layout `app.*` untuk role `user` tetap dipakai bila diakses via layout tersebut (klausa 3.4)_
    - _Requirements: 2.4, 3.4_

  - [ ] 4.3 Tangani respons non-200 di FormPermintaanDetail dengan proporsional
    - Di `fe/src/views/app/FormPermintaanDetail.vue#loadFormData`, cek `err.response?.status`:
      - 403 → `router.push({ name: 'error.forbidden' })`
      - 404 → set `formData.value = null` dan tampilkan pesan inline "Data form permintaan tidak ditemukan" tanpa navigasi paksa
      - status lain → tampilkan toast dan tetap di halaman
    - _Bug_Condition: `kind = 'click_form_permintaan_row'` untuk id valid_
    - _Expected_Behavior: pengguna dengan permission cukup mendarat pada halaman detail (bukan `error.notfound`)_
    - _Preservation: alur user role dengan branch mismatch tetap terlindungi backend (repository filter tidak berubah, klausa 3.9)_
    - _Requirements: 2.4, 3.9_

- [ ] 5. Fix Daily Usage report branch dropdown (Bug 4)

  - [ ] 5.1 Perbanyak seed cabang aktif
    - Ubah `api/database/seeders/BranchSeeder.php` agar `firstOrCreate` minimal dua cabang tambahan (`code` unik) selain "Kantor Pusat"
    - Pastikan idempotent (aman dijalankan berulang)
    - _Bug_Condition: `kind = 'open_daily_usage_report_branch_filter'`_
    - _Expected_Behavior: `GET /api/v1/branches` mengembalikan minimal dua cabang aktif_
    - _Preservation: cabang default "Kantor Pusat" tetap ada (klausa 3.5 tidak melarang penambahan)_
    - _Requirements: 2.5_

  - [ ] 5.2 Tambah fallback UI & test id di DailyUsageReport
    - Di `fe/src/views/admin/dailyrecord/DailyUsageReport.vue`, `await` `fetchBranches()` dan tampilkan alert "Belum ada cabang tersedia" ketika `branches.length === 0` setelah fetch selesai
    - Tambahkan `data-testid="daily-usage-branch-select"` pada `<select>` dan `data-testid="daily-usage-branch-option"` pada `<option>` cabang (non-placeholder)
    - _Bug_Condition: `kind = 'open_daily_usage_report_branch_filter'`_
    - _Expected_Behavior: dropdown menampilkan opsi cabang dari backend; tombol export tetap disabled bila belum ada cabang tersedia_
    - _Preservation: auto-set `filters.branch_id` untuk role `user` tetap berjalan (klausa 3.5)_
    - _Requirements: 2.5, 3.5_

- [ ] 6. Fix /admin/roles/create route (Bug 5)

  - [ ] 6.1 Tambahkan alias/redirect path plural untuk role create
    - Di `fe/src/router/index.js`, pada route `admin.role.create`, tambahkan `alias: ['roles/create']` (atau tambahkan entri terpisah `path: 'roles/create', redirect: { name: 'admin.role.create' }`)
    - _Bug_Condition: `kind = 'visit_admin_roles_create_url'` dengan url `/admin/roles/create`_
    - _Expected_Behavior: `router.resolve('/admin/roles/create')` menghasilkan `name === 'admin.role.create'` dan `RoleForm.vue` ter-render dalam mode create_
    - _Preservation: path singular `/admin/role/create` tetap valid (klausa 3.6, 3.7)_
    - _Requirements: 2.6, 3.6, 3.7_

- [ ] 7. Verifikasi tes exploration & preservation pasca-fix

  - [ ] 7.1 Verifikasi bug condition exploration tests kini lolos
    - **Property 1: Expected Behavior** - E2E Regressions Resolved
    - **IMPORTANT**: Jalankan ULANG test yang sama dari task 1 — jangan tulis test baru
    - `npm run test:run -- tests/unit/stores/user.session.spec.js tests/unit/stores/ticketCategory.session.spec.js tests/unit/views/formPermintaanList.export.spec.js tests/unit/views/formPermintaanDetail.notfound.spec.js tests/unit/views/dailyUsageReport.branchOptions.spec.js tests/unit/router/adminRolesCreateUrl.spec.js`
    - **EXPECTED OUTCOME**: Semua exploration tests LULUS (konfirmasi bug teratasi)
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6_

  - [ ] 7.2 Verifikasi preservation tests tetap lolos
    - **Property 2: Preservation** - Non-Buggy Flows Unchanged
    - **IMPORTANT**: Jalankan ULANG test yang sama dari task 2 — jangan tulis test baru
    - FE: `npm run test:run -- tests/unit/plugins/axios.auth401.spec.js tests/unit/stores/errorHelper.preserve.spec.js tests/unit/router/singularPaths.preserve.spec.js`
    - BE: `ddev exec ./vendor/bin/pest --filter='UsersListPreserveTest|TicketCategoriesListPreserveTest|FormPermintaanUserScopePreserveTest'`
    - **EXPECTED OUTCOME**: Semua preservation tests LULUS (tidak ada regresi)
    - Konfirmasi semua test lain di suite juga tetap lolos (`npm run test:run` dan `ddev exec ./vendor/bin/pest`)
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 3.8, 3.9_

- [ ] 8. Verifikasi lima skenario e2e yang diberi nama

  - [ ] 8.1 Jalankan ulang "User, Role, and Permission Management: Create or edit a user account"
    - Login sebagai admin
    - Navigasi `/admin/user/create`, isi payload valid, klik "Simpan User"
    - Pastikan tidak ada redirect ke `login`, toast sukses tampil, user baru muncul di `/admin/users` (paginasi & search nama persis)
    - _Requirements: 2.1, 3.1, 3.2_

  - [ ] 8.2 Jalankan ulang "Master Data Management: Manage ticket categories"
    - Login sebagai admin
    - Navigasi `/admin/ticket-category/create`, isi Nama unik, klik "Simpan"
    - Pastikan tidak ada redirect ke `login`, toast "Kategori berhasil dibuat" tampil, kategori muncul di `/admin/ticket-categories`
    - _Requirements: 2.2, 3.1, 3.3_

  - [ ] 8.3 Jalankan ulang "Form Permintaan: Export a form permintaan PDF or list"
    - Login dengan role yang punya `form-permintaan-view-all` (mis. superadmin)
    - Buka `/admin/form-permintaan` — pastikan kontrol export terlihat via selector `Export|Ekspor|Cetak|Print|Unduh|Download|PDF`
    - Klik baris permintaan — pastikan halaman detail tampil (bukan 404)
    - _Requirements: 2.3, 2.4, 3.4, 3.9_

  - [ ] 8.4 Jalankan ulang "Daily Records and Utility Reporting: Export a daily usage report"
    - Login sebagai admin, buka `/admin/daily-usage-report`, klik "Filter"
    - Pilih cabang dari dropdown "Pilih Cabang"
    - Klik "Export Excel" dan "Export PDF" — pastikan file terunduh tanpa error
    - _Requirements: 2.5, 3.5_

  - [ ] 8.5 Jalankan ulang "User, Role, and Permission Management: Reject an incomplete role configuration"
    - Login sebagai superadmin, buka URL `/admin/roles/create`
    - Pastikan `RoleForm.vue` ter-render (bukan halaman 404)
    - Coba submit tanpa Nama Role / permissions — pastikan validasi menolak sesuai `RoleStoreRequest`
    - _Requirements: 2.6, 3.6, 3.7_

- [ ] 9. Checkpoint - Ensure all tests pass
  - Jalankan seluruh suite FE (`npm run test:run`) dan BE (`ddev exec ./vendor/bin/pest`)
  - Konfirmasi tidak ada regresi baru
  - Ajukan pertanyaan ke user jika ada temuan yang belum jelas atau perlu keputusan (mis. keputusan alias vs redirect untuk Bug 5, keputusan seeding cabang tambahan)

## Notes

- Kelima bug dijaga oleh satu spec agar akar penyebab lintas (session-drop pada Bug 1 & 2, alignment permission pada Bug 3) dapat ditangani konsisten.
- Fokus perbaikan minim-invasif: satu perubahan interceptor axios sudah mengatasi Bug 1 dan Bug 2; sisanya adalah perubahan lokal per file.
- `fast-check` dan `vitest` sudah tersedia di `fe/package.json`; Pest sudah dipakai di `api/tests`.
- Tidak ada perubahan pada spec lain (`role-permission-management`, `form-permintaan-improvements`, `update-harian-cabang`, `date-picker-*`).
- Sebelum menjalankan langkah 8, pastikan `ddev` sudah menjalankan `php artisan migrate:fresh --seed` sehingga `BranchSeeder` yang diperbarui (task 5.1) benar-benar aktif.

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1", "2"] },
    { "id": 1, "tasks": ["3.1"] },
    { "id": 2, "tasks": ["3.2", "3.3", "4.1", "4.2", "4.3", "5.1", "5.2", "6.1"] },
    { "id": 3, "tasks": ["7.1", "7.2"] },
    { "id": 4, "tasks": ["8.1", "8.2", "8.3", "8.4", "8.5"] },
    { "id": 5, "tasks": ["9"] }
  ]
}
```
