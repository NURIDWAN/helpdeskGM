# E2E Regression Fixes Bugfix Design

## Overview

Dokumen ini menetapkan strategi perbaikan untuk lima regresi e2e yang dijabarkan
di `bugfix.md`. Perbaikan menyentuh lima area kode berbeda tetapi berbagi tema
umum: (a) memutus jalur "false logout" yang menyebabkan Bug 1 dan 2, (b)
menyelaraskan permission gating dan navigasi detail untuk Bug 3, (c) menjamin
ketersediaan data cabang untuk Bug 4, dan (d) menyelaraskan path Vue Router untuk
Bug 5. Setiap fix dirancang agar minim invasif, dapat diobservasi dari e2e, dan
tetap mempertahankan perilaku semua alur yang saat ini benar (lihat klausa 3.x di
`bugfix.md`).

## Glossary

- **Bug_Condition (C)**: Kondisi input `isBugCondition(input)` pada `bugfix.md`
  yang bernilai `true` jika input memicu setidaknya satu dari lima regresi.
- **Property (P)**: Perilaku yang diinginkan pada input yang memenuhi C —
  session tetap aktif, entitas ter-persist, kontrol export terlihat, navigasi
  detail tidak 404, dropdown cabang berisi opsi, dan URL `/admin/roles/create`
  ter-render sebagai `RoleForm`.
- **Preservation**: Perilaku yang harus tidak berubah pada input di luar C
  (klausa 3.1–3.9), terutama pembersihan token pada 401 asli, list existing yang
  masih benar, dan perilaku role `user` di layout `app.*`.
- **axiosInstance**: Instance axios yang di-share di `fe/src/plugins/axios.js`;
  request interceptor menambahkan `Authorization: Bearer <token>` dari cookie,
  response interceptor menangani 401/403/404/422/5xx.
- **`handleSubmit` (UserForm)**: Fungsi di `fe/src/views/admin/user/UserForm.vue`
  yang memanggil `createUser`/`updateUser` di `stores/user.js`.
- **`handleSubmit` (TicketCategoryForm)**: Fungsi di
  `fe/src/views/admin/ticketcategory/TicketCategoryForm.vue`.
- **`FormPermintaanController@show`**: Handler backend
  `GET /api/v1/form-permintaan/{id}` dengan filter permission
  `form-permintaan-list` dan filter data via `FormPermintaanRepository::getById`.
- **`BranchController@index`**: Handler `GET /api/v1/branches` yang membutuhkan
  salah satu permission `branch-list|branch-create|branch-edit|branch-delete`.
- **BranchSeeder**: `api/database/seeders/BranchSeeder.php` yang saat ini hanya
  memasukkan satu cabang default ("Kantor Pusat").
- **RoleForm**: `fe/src/views/admin/role/RoleForm.vue` yang di-mount pada route
  `admin.role.create`/`admin.role.edit` (path `/admin/role/create`).

## Bug Details

### Bug Condition

Bug condition adalah gabungan dari lima cabang di `isBugCondition` (lihat
`bugfix.md`). Semua cabang berbagi bentuk umum: aksi UI yang dilakukan oleh
pengguna dengan permission yang cukup, namun sistem menghasilkan efek samping
yang tidak sesuai (redirect ke login, data tidak muncul, tombol tak tampak,
navigasi 404, dropdown kosong).

**Formal Specification (ringkas):**

```
FUNCTION isBugCondition(input)
  INPUT: input of type E2EAction
  OUTPUT: boolean

  RETURN triggers_bug1_user_save_session_loss(input)
      OR triggers_bug2_ticket_category_save_session_loss(input)
      OR triggers_bug3a_missing_export_control(input)
      OR triggers_bug3b_detail_navigation_404(input)
      OR triggers_bug4_empty_branch_dropdown(input)
      OR triggers_bug5_admin_roles_create_url_404(input)
END FUNCTION
```

### Examples

- **Bug 1**: Login `admin@example.com` (role `admin`) → `/admin/user/create` →
  isi payload valid (email unik) → klik "Simpan User". Diharapkan: user tersimpan
  dan admin tetap login. Aktual: UI melompat ke `/auth/login`, toast sesi
  berakhir tampil, user tidak ada di `/admin/users`.
- **Bug 2**: Sama seperti Bug 1 tetapi endpoint `POST /api/v1/ticket-categories`
  di `/admin/ticket-category/create`.
- **Bug 3a**: Login superadmin → `/admin/form-permintaan`. Diharapkan: dropdown
  Export terlihat. Aktual: pada akun uji tertentu, tidak ada elemen yang cocok
  dengan regex `Export|Ekspor|Cetak|Print|Unduh|Download|PDF`.
- **Bug 3b**: Login sebagai user dengan akses admin, klik baris di daftar; URL
  berpindah ke `/admin/form-permintaan/<id>` lalu `AppFormPermintaanDetail`
  memanggil `fetchFormPermintaan(id)` yang gagal, sehingga route
  `error.notfound` ter-render.
- **Bug 4**: Login superadmin → `/admin/daily-usage-report` → Filter → dropdown
  "Pilih Cabang" hanya berisi placeholder. `GET /api/v1/branches` mengembalikan
  array kosong atau hanya cabang yang tidak muncul karena filter FE.
- **Bug 5**: Ketik langsung `/admin/roles/create` (plural) → `NotFound.vue`
  tampil karena router hanya mendaftarkan `role/create` (singular).

## Expected Behavior

### Preservation Requirements

**Unchanged Behaviors:**

- Pembersihan cookie `token` + redirect ke `login` pada respons 401 asli dari
  endpoint autentikasi (`GET /auth/me`, `POST /auth/logout`) tetap berjalan.
- Perilaku list `/admin/users` dan `/admin/ticket-categories` untuk data yang
  sudah ada (paginasi, search, sort by `created_at desc`) tidak berubah.
- Kontrol export baru pada `/admin/form-permintaan` hanya tampil untuk akun yang
  memenuhi `can('form-permintaan-list')` (tidak menambah akses baru untuk role
  `user`).
- Handler `FormPermintaanRepository::getById` yang membatasi non-`view-all` user
  hanya ke form miliknya/branch-nya tetap sama.
- Fetching branches untuk role `user` tetap ter-scope ke `currentUser.branch`
  seperti sebelumnya (auto-set `filters.branch_id`).
- Route existing `admin.role.create` (path `/admin/role/create`) tetap valid dan
  menuju `RoleForm.vue`.
- Semua route singular lain (`user/create`, `branch/create`,
  `ticket-category/create`, `role/:id/edit`, dst.) tetap ter-resolve seperti
  semula.

**Scope:**

Semua input di luar `isBugCondition` (klausa 3.x pada `bugfix.md`) tidak boleh
mengalami perubahan observasional pada response body, redirect, atau state
frontend. Ini mencakup pemanggilan CRUD entitas lain, alur login/logout normal,
akses non-admin, dan alur error 422/500 yang saat ini sudah benar.

## Hypothesized Root Cause

Berdasarkan investigasi kode, hipotesis akar penyebab per bug:

1. **Bug 1 & 2 — session drop saat submit form**
   - `axios.defaults.headers.common['Authorization'] = 'Bearer ${token}'` diset
     satu kali di module top-level `fe/src/plugins/axios.js` dengan
     `Cookies.get('token')` di initial load. Request interceptor menambahkan
     header per request; namun jika module dieksekusi sebelum cookie tersedia
     (mis. race condition saat baru login), fallback default `undefined` dapat
     dikirim. Setelah itu server merespons 401, response interceptor menghapus
     cookie dan mendorong ke `login`.
   - Alternatif: `axios.defaults` yang di-mutate ini shared dengan seluruh
     app-nya; jika ada request pararel (mis. `fetchBranches` di `UserForm`
     mounted) yang gagal 401 karena rate-limit throttle `throttle:api` (`'api'`
     middleware), respons 401 juga akan memicu logout meski request save berhasil.
   - Kemungkinan ketiga (spesifik ke ticket category): `store` memakai `api.post`
     di `stores/ticketCategory.js` yang me-throw error kembali ke component.
     Component memanggil `toast.error("Gagal menyimpan kategori")` lalu tetap
     `router.push` — tapi jika error adalah 401, interceptor sudah men-redirect
     ke login lebih dulu.
   - Tindakan: buat interceptor selektif — 401 hanya melakukan logout jika URL
     bukan endpoint mutasi tertentu **atau** jika request memang endpoint auth
     (`/auth/me`, `/auth/logout`). Untuk 401 non-auth, tampilkan toast dan biarkan
     component menangani. Pastikan cookie `token` tidak hilang oleh proses lain
     (SameSite, expiry). Tambahkan test dengan `MSW`/mock axios yang mengembalikan
     201 untuk memverifikasi tidak ada `Cookies.remove` atau redirect.

2. **Bug 3a — kontrol export tak tampak**
   - Kontrol export di `FormPermintaanList.vue` dibungkus
     `v-if="can('form-permintaan-list')"`. Beberapa role approver punya
     `form-permintaan-view-all` tapi mungkin tidak punya `form-permintaan-list`
     jika seeder belum memberikannya. Namun `role_presets.php` dan `RoleSeeder`
     menunjukkan `approver-permintaan` dan `reviewer-permintaan` selalu mendapat
     keduanya.
   - Hipotesis: akun uji e2e (mungkin superadmin baru atau akun kustom) tidak
     mendapat `form-permintaan-list` karena migrasi permission tidak lengkap,
     atau akun berada di layout `app` (route `app.form-permintaan`) yang tidak
     memiliki kontrol export sama sekali.
   - Tindakan: (i) pastikan admin/superadmin/approver punya
     `form-permintaan-list`; (ii) tampilkan kontrol export ketika
     `form-permintaan-list` **atau** `form-permintaan-view-all` benar, dan
     tambahkan `data-testid="form-permintaan-export"` agar e2e stabil.

3. **Bug 3b — navigasi detail 404**
   - `handleDetail` di `admin/formpermintaan/FormPermintaanList.vue` selalu
     `router.push({ name: 'app.form-permintaan.detail', ... })` (nama route
     `app.*`). Router `beforeEach` untuk admin memetakan `app.form-permintaan.detail`
     ke `admin.form-permintaan.detail`. Untuk pengguna admin, tujuan akhirnya
     `admin.form-permintaan.detail`. Namun `AppFormPermintaanDetail.loadFormData`
     menangani error apa pun dengan `router.push({ name: 'error.notfound' })`.
   - Hipotesis: pada akun uji, backend mengembalikan 403/404 dari
     `GET /api/v1/form-permintaan/{id}` karena filter di `FormPermintaanRepository::getById`
     ("user hanya lihat yang branchnya sama atau dibuatnya"). Akun uji
     kemungkinan tidak memiliki `form-permintaan-view-all` sehingga tidak dapat
     mengakses detail form milik cabang lain.
   - Tindakan: (i) di `admin/formpermintaan/FormPermintaanList.vue`, arahkan ke
     `admin.form-permintaan.detail` alih-alih `app.*` untuk konsistensi
     permission dan meta; (ii) di `AppFormPermintaanDetail.vue`, jangan langsung
     push ke `error.notfound` — bedakan 403 (tampilkan halaman "Akses Ditolak"
     lokal + link kembali) dan 404 (halaman detail dengan pesan "data tidak
     tersedia"), sehingga akun yang berhak masih dapat masuk halaman ketika
     backend mengembalikan data.

4. **Bug 4 — dropdown cabang kosong**
   - `fetchBranches()` di `DailyUsageReport.onMounted` memanggil
     `GET /api/v1/branches` tanpa filter. Backend mengembalikan semua branch.
     `branches` store diisi dari `response.data.data`.
   - `BranchSeeder.php` hanya seed 1 cabang default ("HDQT — Kantor Pusat").
     Jika environment uji e2e menghapus branch tersebut atau memakai DB baru
     tanpa `BranchSeeder`, dropdown akan kosong.
   - Hipotesis alternatif: `branches` store berhasil di-populate tetapi
     `filters.branch_id` di-set duluan oleh watcher lain sehingga select tidak
     menampilkan opsi. Namun kode template memanggil `v-for` langsung dari
     `branchStore.branches`, jadi jika array kosong dropdown pasti kosong.
   - Tindakan: (i) pastikan `BranchSeeder` di-run di lingkungan uji (dan
     idempotent seperti sekarang dengan `firstOrCreate`); (ii) tambahkan seeder
     minimal dua cabang aktif untuk skenario e2e; (iii) UI DailyUsageReport
     menampilkan pesan yang informatif ("Belum ada cabang. Hubungi admin.") jika
     `branches.length === 0` alih-alih menyembunyikan opsi.

5. **Bug 5 — /admin/roles/create 404**
   - Router hanya mendaftarkan `path: 'role/create'` (singular). URL
     `/admin/roles/create` (plural) tidak match dan jatuh ke catch-all
     `/:pathMatch(.*)*` → `NotFound.vue`.
   - Tindakan: tambahkan alias `path: 'roles/create'` yang mengarah ke komponen
     yang sama, atau tetap pada satu path canonical dan tambahkan redirect
     `roles/create` → `role/create`. Pola alias/redirect membuat e2e dan bookmark
     tetap valid tanpa perlu edit menu.

## Correctness Properties

Property 1: Bug Condition - E2E Regressions Resolved

_For any_ input where `isBugCondition(input)` returns `true` (kondisi apa pun
dari 1.1–1.6), the fixed code SHALL menghasilkan: (a) tidak ada redirect ke
`/auth/login` untuk input Bug 1 dan Bug 2 dan cookie `token` tetap valid,
sementara record ter-persist dan terlihat di list; (b) elemen export yang cocok
regex `Export|Ekspor|Cetak|Print|Unduh|Download|PDF` tampil untuk input Bug 3a;
(c) navigasi baris di daftar form permintaan me-render halaman detail (bukan
route `error.notfound`) untuk input Bug 3b; (d) dropdown "Pilih Cabang" berisi
minimal satu opsi cabang dan tombol export aktif untuk input Bug 4; (e) URL
`/admin/roles/create` me-render `RoleForm.vue` dalam mode create untuk input Bug
5.

**Validates: Requirements 2.1, 2.2, 2.3, 2.4, 2.5, 2.6**

Property 2: Preservation - Non-Buggy Flows Unchanged

_For any_ input where `isBugCondition(input)` returns `false` (klausa 3.1–3.9),
the fixed code SHALL produce the same observable behavior as the original code:
respons 401 asli dari endpoint auth tetap menghapus cookie dan redirect ke
login; list existing di `/admin/users` dan `/admin/ticket-categories` menampilkan
data yang sama; role `user` tetap tidak melihat kontrol export admin; alur
detail form permintaan untuk role `user` tetap terbatas pada branch sendiri;
route existing (`user/create`, `role/:id/edit`, `role/create` singular, dsb.)
tetap ter-resolve; error 422/500 tetap ditangani `errorHelper` tanpa memicu
logout.

**Validates: Requirements 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 3.8, 3.9**

## Fix Implementation

### Cross-Cutting Note — Session Loss Root Cause

Bug 1 dan Bug 2 sangat mungkin berbagi penyebab yang sama: response interceptor
axios menghapus cookie dan mendorong ke `login` untuk **setiap** respons 401,
termasuk 401 spurious yang datang dari request lain yang tidak terkait dengan
form save (mis. `fetchRoles` atau `fetchBranches` di `onMounted` yang gagal
karena token belum tersedia di header saat request pertama). Setelah cookie
dihapus, submit form yang sebenarnya berhasil (mengembalikan 201) sudah
kehilangan konteks sesi di client.

Perbaikan lintas file yang direkomendasikan (implementasi detail lihat sub-bagian
per-bug):

- Hentikan mutasi `axios.defaults.headers.common['Authorization']` di top-level
  module; andalkan sepenuhnya request interceptor yang membaca cookie fresh per
  request.
- Pada response interceptor 401, jangan langsung `Cookies.remove` + redirect
  jika URL request bukan endpoint `/auth/*`. Untuk request non-auth, tampilkan
  toast dan biarkan store menangani (mis. dengan `error.value`), lalu tunggu
  guard router `checkAuth` untuk melakukan logout resmi jika token benar-benar
  tidak valid.

### Bug 1 — User save session drop

**File**: `fe/src/plugins/axios.js`, `fe/src/stores/user.js`,
`fe/src/views/admin/user/UserForm.vue`.

**Function**: response interceptor axios; `createUser`/`updateUser` di store;
`handleSubmit` di form.

**Specific Changes**:

1. Hapus baris `axios.defaults.headers.common['Authorization'] = 'Bearer ${token}'`
   di top-level `axios.js` (biarkan hanya di request interceptor).
2. Ubah cabang 401 di response interceptor: jika `error.config.url` tidak match
   regex `/^\/auth\//`, tampilkan toast lokal dan `return Promise.reject(error)`
   tanpa `Cookies.remove` atau redirect. Cabang 401 pada URL `/auth/*` tetap
   berperilaku seperti semula.
3. Di `stores/user.js#createUser`, hapus `router.push({ name: 'admin.users' })`
   dari action agar redirect hanya terjadi di `handleSubmit` component setelah
   pengecekan `userStore.success`.
4. Pastikan `handleSubmit` di `UserForm.vue` menampilkan toast sukses/gagal
   berdasarkan `userStore.success`/`userStore.error` dan hanya `router.push`
   pada sukses.

### Bug 2 — Ticket category save session drop

**File**: `fe/src/plugins/axios.js`,
`fe/src/views/admin/ticketcategory/TicketCategoryForm.vue`,
`fe/src/stores/ticketCategory.js`.

**Function**: response interceptor (perubahan sama seperti Bug 1);
`handleSubmit` di form.

**Specific Changes**:

1. Bagikan perbaikan interceptor dari Bug 1 (satu perubahan mengatasi keduanya).
2. Di `handleSubmit`, tangkap error dari `categoryStore.createCategory` dan hanya
   melakukan `router.push` pada sukses (`categoryStore.error === null`).
3. Setelah sukses, panggil `categoryStore.fetchCategories({ search: form.name })`
   dulu (opsional) untuk memastikan cache list konsisten sebelum pindah halaman.

### Bug 3 — Form Permintaan list export & detail 404

**File**: `fe/src/views/admin/formpermintaan/FormPermintaanList.vue`,
`fe/src/views/app/FormPermintaanDetail.vue`, `fe/src/config/permissionConfig.js`,
`api/database/seeders/RolePermissionSeeder.php` (opsional bila perlu memastikan
role dapat `form-permintaan-list`).

**Function**: template header list, `handleDetail`, `loadFormData` di detail.

**Specific Changes**:

1. Ubah kondisi tampil kontrol export menjadi
   `can('form-permintaan-list') || can('form-permintaan-view-all')`.
2. Tambahkan `data-testid="form-permintaan-export"` pada tombol Export dan pada
   dropdown items PDF/Excel (`data-testid="form-permintaan-export-pdf"` /
   `-excel`).
3. Di `handleDetail`, gunakan route bernama sesuai layout:
   `router.push({ name: routePrefix.value + '.form-permintaan.detail', params: { id: item.id } })`
   atau selalu pakai `admin.form-permintaan.detail` di file admin.
4. Di `FormPermintaanDetail.loadFormData`, ganti redirect ke `error.notfound`
   dengan handling per status: 403 → `error.forbidden`, 404 → tampilkan pesan
   inline "Data form permintaan tidak ditemukan" tanpa navigasi paksa; error
   lain → toast dan tetap di halaman.
5. (Opsional) Pastikan seeder memasukkan `form-permintaan-list` untuk role
   `approver-permintaan` dan `reviewer-permintaan` (sudah ada) dan verifikasi di
   test.

### Bug 4 — Daily Usage report "Pilih Cabang" kosong

**File**: `api/database/seeders/BranchSeeder.php`,
`fe/src/views/admin/dailyrecord/DailyUsageReport.vue`.

**Function**: `BranchSeeder::run`; template dropdown Cabang dan
`onMounted`/watcher branches.

**Specific Changes**:

1. Perbanyak seed cabang menjadi minimal dua cabang aktif (mis. tambahkan
   "Cabang Uji Bandung" dan "Cabang Uji Surabaya" dengan `firstOrCreate` sehingga
   idempotent).
2. Pastikan `DailyUsageReport.onMounted` menunggu `fetchBranches()` selesai
   sebelum menampilkan panel filter (mis. `await fetchBranches()` + tampilkan
   loading state pada dropdown selama pending).
3. Tambahkan fallback UI: jika `branchStore.branches.length === 0` setelah fetch
   selesai, tampilkan alert "Belum ada cabang yang tersedia. Hubungi
   administrator." dan sembunyikan tombol export sampai ada opsi.
4. Tambahkan `data-testid="daily-usage-branch-select"` pada `<select>` cabang
   dan `data-testid="daily-usage-branch-option"` pada `<option>`.

### Bug 5 — /admin/roles/create 404

**File**: `fe/src/router/index.js`.

**Function**: definisi children route `/admin`.

**Specific Changes**:

1. Tambahkan alias untuk `admin.role.create`:

   ```js
   {
     path: 'role/create',
     alias: ['roles/create'],
     name: 'admin.role.create',
     ...
   }
   ```

   Alternatif: buat entri redirect `path: 'roles/create', redirect: { name: 'admin.role.create' }`.
2. (Opsional konsistensi) Terapkan pola serupa untuk `roles/:id/edit` bila
   diinginkan; namun tidak wajib karena e2e tidak menyentuh path itu.

## Testing Strategy

### Validation Approach

Strategi validasi dilakukan dalam dua fase: pertama, tulis tes eksplorasi (tanpa
mengubah kode) untuk mengekspos kelima bug pada kode UNFIXED; kedua, terapkan
perbaikan dan pastikan tes eksplorasi kini lolos serta tes preservation tetap
lolos.

### Exploratory Bug Condition Checking

**Goal**: Munculkan counterexample untuk kelima regresi sebelum implementasi.
Menegaskan hipotesis akar penyebab, atau membuktikan hipotesis salah sehingga
kita perlu re-hypothesize sebelum menerapkan fix.

**Test Plan**: Tulis property-based tests (fast-check untuk FE, Pest dataset
untuk BE) yang untuk setiap sub-kondisi `isBugCondition` menegaskan perilaku yang
diharapkan (Property 1). Jalankan pada kode UNFIXED — semua tes diharapkan gagal
dengan counterexample yang jelas. Untuk bug yang deterministik (Bug 5) properti
di-scope ke kasus konkret.

**Test Cases**:

1. **Bug 1 exploration (`fe/tests/unit/stores/user.session.spec.js`)**: Property
   untuk setiap `UserStoreRequest` payload valid — setelah `createUser`,
   `Cookies.remove` tidak boleh dipanggil pada 201 dan tidak ada
   `router.push('login')`. Akan gagal pada UNFIXED kode karena interceptor 401
   spurious.
2. **Bug 2 exploration (`fe/tests/unit/stores/ticketCategory.session.spec.js`)**:
   Property serupa untuk payload TicketCategory valid.
3. **Bug 3a exploration
   (`fe/tests/unit/views/formPermintaanList.export.spec.js`)**: Untuk setiap
   permission set yang mengandung `form-permintaan-view-all`, komponen
   `FormPermintaanList` (admin) harus me-render kontrol export dengan text/atribut
   yang cocok regex `Export|Ekspor|Cetak|Print|Unduh|Download|PDF`.
4. **Bug 3b exploration
   (`fe/tests/unit/views/formPermintaanDetail.notfound.spec.js`)**: Property
   untuk id form yang valid dengan permission `form-permintaan-view-all` — mock
   axios 200 → detail merender data; UNFIXED kode gagal ketika `fetch` 403/404
   dari `getById` karena langsung push ke `error.notfound`.
5. **Bug 4 exploration
   (`fe/tests/unit/views/dailyUsageReport.branchOptions.spec.js`)**: Property —
   untuk daftar cabang non-kosong dari backend, dropdown "Pilih Cabang" me-render
   ≥ 1 opsi selain placeholder. UNFIXED kode gagal jika seeder tidak menghasilkan
   cabang tambahan.
6. **Bug 5 exploration (`fe/tests/unit/router/adminRolesCreateUrl.spec.js`)**:
   Scoped test — resolve `/admin/roles/create` di router. UNFIXED gagal dengan
   matched route name `not-found`.

**Expected Counterexamples**:

- Bug 1/2: `Cookies.remove` dipanggil ketika mock `POST` mengembalikan 201 tetapi
  request paralel `GET /roles`/`GET /branches` mengembalikan 401.
- Bug 3a: kontrol export absent karena pengguna hanya memiliki
  `form-permintaan-view-all` tanpa `form-permintaan-list`.
- Bug 3b: `router.push('error.notfound')` dipanggil untuk semua status error.
- Bug 4: `select` hanya berisi opsi placeholder karena `branches` kosong.
- Bug 5: `router.resolve('/admin/roles/create').name === 'not-found'`.

Kemungkinan penyebab: (i) interceptor 401 terlalu agresif; (ii) permission
gating tidak inklusif; (iii) error handler generic; (iv) seed cabang minim; (v)
router path tidak plural.

### Fix Checking

**Goal**: Verifikasi bahwa untuk semua input yang memenuhi `isBugCondition`,
`F'` menghasilkan `expectedBehavior`.

**Pseudocode:**

```
FOR ALL input WHERE isBugCondition(input) DO
  result := perform(input, code = F')
  ASSERT expectedBehavior(input, result)
END FOR
```

### Preservation Checking

**Goal**: Verifikasi bahwa untuk semua input di luar `isBugCondition`, `F` dan
`F'` menghasilkan observasi identik.

**Pseudocode:**

```
FOR ALL input WHERE NOT isBugCondition(input) DO
  ASSERT observed_in(F, input) = observed_in(F', input)
END FOR
```

**Testing Approach**: Gunakan property-based testing (fast-check di FE, Pest
random dataset di BE) untuk mencakup ruang input yang luas. PBT lebih kuat
daripada tes contoh karena preservation harus berlaku untuk banyak kombinasi
role, payload, dan permission.

**Test Plan**: Observasi perilaku pada kode UNFIXED untuk input non-buggy
terlebih dahulu (mis. respons 401 asli pada `/auth/me`, list existing di
`/admin/users` yang punya beberapa user seed, role `user` di layout `app.*`),
lalu tulis property tests yang mengunci perilaku tersebut.

**Test Cases**:

1. **Preservation session 401 auth asli**: pada endpoint `/auth/me`, response
   401 → cookie dihapus, redirect ke login. Property: `FOR ALL url IN ['/auth/me', '/auth/logout'] ⇒ 401 handling unchanged`.
2. **Preservation list existing**: `GET /users` dan `GET /ticket-categories`
   pada seed default → data yang sama sebelum dan sesudah fix. Property: hasil
   `PaginateResource` identik untuk request parameter yang sama.
3. **Preservation navigasi role `user` di app**: `/form-permintaan` layout
   `app.*` — user hanya melihat form-nya sendiri; navigasi row detail me-render
   halaman detail untuk form miliknya.
4. **Preservation dropdown user role di DailyUsageReport**: role `user` tetap
   melihat `filters.branch_id` auto-set dari `currentUser.branch.id` dan tidak
   dihadapkan pada select cabang.
5. **Preservation route singular**: `router.resolve('/admin/role/create').name === 'admin.role.create'` tetap berlaku setelah alias/redirect ditambahkan.
6. **Preservation errorHelper**: 422/500 pada semua endpoint mutasi terus
   menghasilkan `error.value` (validation/message) tanpa memicu logout.

### Unit Tests

- Store `user` create tidak memicu logout pada respons 201.
- Store `ticketCategory` create tidak memicu logout pada respons 201.
- Interceptor axios memisahkan penanganan 401 auth vs non-auth.
- `FormPermintaanList` (admin) me-render kontrol export ketika permission
  inklusif.
- `FormPermintaanDetail` menangani 403/404 tanpa memaksa `error.notfound`.
- `DailyUsageReport` menampilkan opsi cabang non-kosong dan alert ketika kosong.
- Router meng-resolve `/admin/roles/create` ke `admin.role.create`.

### Property-Based Tests

- **PBT-1 (session preservation)**: untuk generator payload user dan ticket
  category valid, respons 2xx dari mutasi + respons 401 opsional dari request
  paralel non-auth → tidak ada `Cookies.remove` dan tidak ada redirect ke login.
- **PBT-2 (export gating)**: untuk generator subset permission
  `⊆ {'form-permintaan-list', 'form-permintaan-view-all',
  'form-permintaan-create'}`, kontrol export tampil iff
  `has('form-permintaan-list') || has('form-permintaan-view-all')`.
- **PBT-3 (detail navigation)**: untuk generator id form valid dan pengguna
  dengan `form-permintaan-view-all`, mock response 200 → halaman detail
  ter-render; mock 403 → halaman `error.forbidden` (bukan `error.notfound`);
  mock 404 → halaman detail dengan pesan inline (bukan navigasi keluar).
- **PBT-4 (branch options)**: untuk generator daftar branch dengan panjang
  0..10, dropdown menampilkan jumlah opsi yang tepat; ketika 0, alert
  ditampilkan.
- **PBT-5 (router path parity)**: untuk generator path `/admin/{singular}/create`
  dan `/admin/{plural}/create` (users↔user, roles↔role,
  ticket-categories↔ticket-category, branches↔branch), keduanya harus resolve ke
  route bernama `admin.{singular}.create` (setelah alias/redirect).

### Integration Tests

- Feature test Pest baru
  `api/tests/Feature/UserSessionBugTest.php`: skenario end-to-end backend +
  frontend (via HTTP) memastikan `POST /users` 201 tidak memicu 401 pada request
  berikutnya (`GET /users` dengan token sama).
- Feature test Pest `TicketCategorySessionBugTest.php`: analog untuk kategori.
- Feature test Pest `BranchesSeededTest.php`: memastikan setelah
  `db:seed --class=BranchSeeder`, `GET /api/v1/branches` mengembalikan minimal 2
  cabang aktif.
- E2E replay: menjalankan ulang kelima skenario e2e resmi (judul lengkap di
  bagian Verification Criteria `bugfix.md`) di lingkungan uji.
