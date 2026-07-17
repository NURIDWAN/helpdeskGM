# E2E Regression Fixes Bugfix Requirements Document

## Introduction

Skenario end-to-end (e2e) menemukan lima regresi pada admin panel HelpdeskGM yang
membuat alur kritikal gagal untuk admin, superadmin, dan approver:

1. Simpan user baru (POST `/api/v1/users`) mengembalikan admin ke layar login dan
   user baru tidak muncul di "Data User".
2. Simpan kategori tiket baru (POST `/api/v1/ticket-categories`) mengembalikan admin
   ke layar login dan kategori tidak muncul di "Kategori Tiket".
3. Halaman "Daftar Form Permintaan" (`/admin/form-permintaan`) di akun uji tidak
   memperlihatkan kontrol export/print/PDF, dan klik pada baris permintaan menuju
   halaman 404.
4. Laporan Daily Usage (`/admin/daily-usage-report`) menampilkan dropdown "Pilih
   Cabang" tanpa opsi sehingga tombol export Excel/PDF selalu terkunci dengan pesan
   "Pilih Cabang Terlebih Dahulu".
5. URL langsung `/admin/roles/create` menampilkan "404 Halaman Tidak Ditemukan"
   sehingga jalur "buat role → tolak konfigurasi tidak lengkap" tidak dapat diuji.

Kelima regresi menghalangi validasi lima skenario e2e resmi (lihat bagian
Verification Criteria). Bugfix ini mengonsolidasikan analisis dan target perbaikan
tanpa mengubah kode aplikasi.

## Bug Analysis

### Current Behavior (Defect)

Setiap klausa di bagian ini berpasangan dengan klausa yang bernomor sama di bagian
"Expected Behavior (Correct)".

1.1 WHEN admin (role `admin`/`superadmin`) mengisi form Tambah User pada
`/admin/user/create` dengan payload valid dan menekan tombol "Simpan User", THEN
UI melompat ke `/auth/login`, `Cookies.token` dihapus, toast "Sesi Anda telah
berakhir. Silakan login kembali." muncul, dan setelah login ulang user baru tidak
ditemukan di `/admin/users` (baik saat load ulang paginasi maupun saat search nama
persis).

1.2 WHEN admin membuka `/admin/ticket-category/create`, mengisi Nama Kategori valid
dan menekan tombol "Simpan", THEN UI melompat ke `/auth/login`, `Cookies.token`
dihapus, toast sesi berakhir muncul, dan kategori baru tidak muncul di
`/admin/ticket-categories` (search nama persis tetap "Tidak ada data").

1.3 WHEN akun uji dengan permission `form-permintaan-view-all` (misalnya
`approver-permintaan`) membuka `/admin/form-permintaan`, THEN kontrol
Export/Ekspor/Cetak/Print/Unduh/Download/PDF tidak terlihat di header halaman
sehingga tombol `Export` tidak dapat diklik.

1.4 WHEN pengguna yang sama mengklik salah satu baris (kolom "No. Permintaan")
pada `/admin/form-permintaan`, THEN aplikasi bernavigasi ke route bernama
`app.form-permintaan.detail`, guard router mengarahkan ke `admin.form-permintaan.detail`
untuk pengguna dengan akses admin, tetapi respons detail (`GET /api/v1/form-permintaan/{id}`)
mengembalikan 404/kosong sehingga `FormPermintaanDetail.loadFormData` melakukan
`router.push({ name: 'error.notfound' })` dan halaman 404 tampil.

1.5 WHEN admin membuka `/admin/daily-usage-report` lalu mengklik "Filter", THEN
dropdown "Pilih Cabang" hanya berisi opsi placeholder ("Pilih Cabang") tanpa opsi
cabang lain, sehingga syarat wajib `filters.branch_id` tidak dapat dipenuhi dan
tombol "Export Excel"/"Export PDF" selalu memicu error `Silakan pilih cabang
terlebih dahulu untuk export laporan`.

1.6 WHEN pengguna membuka URL `/admin/roles/create` (plural `roles`), THEN Vue
Router mencocokkan catch-all `/:pathMatch(.*)*` dan me-render `NotFound.vue`
dengan judul "404 Halaman Tidak Ditemukan", meskipun form pembuatan role sudah
terdaftar di path `/admin/role/create` (singular).

### Expected Behavior (Correct)

2.1 WHEN admin mengisi form Tambah User dengan payload valid dan menekan
"Simpan User", THEN backend SHALL memproses `POST /api/v1/users` dengan token
sesi aktif, mengembalikan HTTP 201, admin SHALL tetap terautentikasi (cookie
`token` tetap ada, tidak ada redirect ke `login`), toast sukses SHALL tampil, dan
user baru SHALL muncul di halaman `/admin/users` (baik pada halaman pertama sesuai
`orderBy created_at desc` maupun ketika di-search nama/email/identity_number
persis).

2.2 WHEN admin mengisi form Tambah Kategori Tiket dengan payload valid dan
menekan "Simpan", THEN backend SHALL memproses `POST /api/v1/ticket-categories`
dengan token aktif, mengembalikan HTTP 201, admin SHALL tetap terautentikasi,
toast "Kategori berhasil dibuat" SHALL tampil, dan kategori baru SHALL muncul di
`/admin/ticket-categories` (baik saat load ulang list maupun saat search nama
persis).

2.3 WHEN akun dengan permission `form-permintaan-view-all` membuka
`/admin/form-permintaan`, THEN header halaman SHALL menampilkan kontrol export
yang dapat ditemukan oleh selector `Export|Ekspor|Cetak|Print|Unduh|Download|PDF`
(baik ikon `Download` maupun label tekstual), dan menu export SHALL berisi opsi
PDF dan Excel yang memicu `GET /api/v1/form-permintaan/export/pdf` atau
`GET /api/v1/form-permintaan/export/excel`.

2.4 WHEN pengguna yang sama mengklik salah satu baris di
`/admin/form-permintaan`, THEN aplikasi SHALL bernavigasi ke halaman detail yang
me-render `AppFormPermintaanDetail.vue` dengan data form permintaan yang di-fetch
tanpa error, dan halaman detail SHALL menampilkan tombol Download PDF
(`GET /api/v1/form-permintaan/{id}/pdf`).

2.5 WHEN admin membuka `/admin/daily-usage-report` dan mengklik "Filter", THEN
dropdown "Pilih Cabang" SHALL berisi minimal satu opsi cabang aktif yang berasal
dari `GET /api/v1/branches`, dan setelah dipilih tombol "Export Excel"/"Export
PDF" SHALL aktif (tidak lagi memicu error `Silakan pilih cabang terlebih dahulu`).

2.6 WHEN pengguna dengan permission `role-create` mengakses URL
`/admin/roles/create`, THEN Vue Router SHALL mencocokkan URL tersebut ke route
`admin.role.create` (atau menyediakan route setara di path plural) dan me-render
`RoleForm.vue` dalam mode create, sehingga validasi "reject an incomplete role"
dapat dijalankan.

### Unchanged Behavior (Regression Prevention)

Klausa berikut mencatat perilaku yang saat ini benar dan harus tetap benar setelah
perbaikan.

3.1 WHEN `GET /api/v1/auth/me` mengembalikan HTTP 401 (token benar-benar
kadaluarsa atau tidak valid), THEN axios response interceptor SHALL CONTINUE TO
menghapus cookie `token`, menampilkan toast "Sesi Anda telah berakhir", dan
mendorong pengguna ke route `login`.

3.2 WHEN admin membuka `/admin/users` tanpa membuat user baru, THEN halaman
SHALL CONTINUE TO menampilkan daftar user yang ada sesuai paginasi dan search
seperti sebelum perbaikan.

3.3 WHEN admin membuka `/admin/ticket-categories` tanpa membuat kategori baru,
THEN halaman SHALL CONTINUE TO menampilkan daftar kategori yang ada, termasuk
kategori seed default, dengan paginasi dan search bekerja seperti sebelumnya.

3.4 WHEN pengguna dengan role `user` (tanpa `form-permintaan-view-all`) membuka
`/form-permintaan`, THEN akses list dan detail pada layout non-admin SHALL
CONTINUE TO bekerja tanpa 404 palsu, tetap menampilkan hanya form permintaan
milik cabang sendiri (kontrol repository `formPermintaanRepository->getAll` untuk
non-`view-all` tidak berubah).

3.5 WHEN pengguna dengan role `user` membuka `/daily-usage-report`, THEN aplikasi
SHALL CONTINUE TO auto-set `filters.branch_id` dari `currentUser.branch.id` dan
langsung memuat data laporan tanpa harus memilih cabang manual.

3.6 WHEN pengguna dengan permission `role-list` mengakses `/admin/roles`, THEN
`RoleList.vue` SHALL CONTINUE TO tampil dengan tombol "Tambah Role" mengarah ke
route `admin.role.create` yang sudah ada (path singular
`/admin/role/create`).

3.7 WHEN admin membuka form entitas lain yang menggunakan path singular yang sama
(mis. `/admin/user/create`, `/admin/branch/create`, `/admin/ticket-category/create`,
`/admin/role/:id/edit`), THEN semua route tersebut SHALL CONTINUE TO ter-resolve
ke halaman form yang benar tanpa 404.

3.8 WHEN backend mengembalikan 422 (validasi) atau 500 (server error) pada
endpoint `store`/`update` mana pun, THEN handler UI SHALL CONTINUE TO memakai
`errorHelper.handleError` sehingga menampilkan pesan validasi/error tanpa
menghapus cookie `token` atau memaksa logout.

3.9 WHEN pengguna dengan permission `form-permintaan-view-all` memanggil
`GET /api/v1/form-permintaan?...`, THEN backend SHALL CONTINUE TO mengembalikan
data form permintaan lintas cabang dengan filter search/branch/request_type/status
seperti sekarang.

## Verification Criteria (E2E Scenario Mapping)

Perbaikan divalidasi ulang dengan menjalankan kembali lima skenario e2e berikut,
persis dengan judul aslinya:

1. **User, Role, and Permission Management: Create or edit a user account** —
   validasi klausa 1.1/2.1/3.1/3.2. Skenario harus: login sebagai admin, buka
   `/admin/user/create`, isi payload valid (`name`, `email`, `password`,
   `branch_id`, `position`, `identity_number`, `type`, minimal 1 role dari
   `RoleSeeder`), klik "Simpan User"; PASS jika tidak ada redirect ke `login`,
   toast sukses tampil, dan user baru terlihat di `/admin/users` termasuk search
   nama persis.

2. **Master Data Management: Manage ticket categories** — validasi klausa
   1.2/2.2/3.1/3.3. Skenario harus: login sebagai admin, buka
   `/admin/ticket-category/create`, isi Nama unik dan klik "Simpan"; PASS jika
   tidak ada redirect ke `login`, toast "Kategori berhasil dibuat" tampil, dan
   kategori baru terlihat di `/admin/ticket-categories` termasuk search nama
   persis.

3. **Form Permintaan: Export a form permintaan PDF or list** — validasi klausa
   1.3/1.4/2.3/2.4/3.4/3.9. Skenario harus: login dengan role yang punya
   `form-permintaan-view-all` (mis. `superadmin`), buka `/admin/form-permintaan`,
   temukan kontrol Export via selector `Export|Ekspor|Cetak|Print|Unduh|Download|PDF`,
   klik salah satu baris untuk sampai ke detail; PASS jika kontrol export terlihat
   dan menu Export PDF/Excel dapat memicu unduhan, serta detail permintaan tampil
   tanpa halaman 404.

4. **Daily Records and Utility Reporting: Export a daily usage report** —
   validasi klausa 1.5/2.5/3.5. Skenario harus: login sebagai admin, buka
   `/admin/daily-usage-report`, klik "Filter", pilih Cabang dari dropdown "Pilih
   Cabang", pilih bulan default; PASS jika minimal satu opsi cabang tampil,
   dropdown menerima pilihan, dan tombol "Export Excel"/"Export PDF" menghasilkan
   file (tanpa error "Pilih Cabang Terlebih Dahulu").

5. **User, Role, and Permission Management: Reject an incomplete role
   configuration** — validasi klausa 1.6/2.6/3.6/3.7. Skenario harus: login
   sebagai superadmin, buka URL `/admin/roles/create`; PASS jika `RoleForm.vue`
   ter-render dalam mode create (tanpa 404), mengosongkan Nama Role atau tidak
   memilih satu permission pun memicu penolakan submit sesuai aturan
   `RoleStoreRequest` (nama wajib, minimal 3 karakter).

## Deriving the Bug Condition

Untuk konsolidasi ini kita mendefinisikan bug condition sebagai gabungan lima
sub-kondisi. Fungsi total `isBugCondition(input)` menghasilkan `true` jika input
memicu paling sedikit satu dari lima regresi yang diamati.

**Bug Condition Function:**

```pascal
FUNCTION isBugCondition(input)
  INPUT: input of type E2EAction
  // E2EAction ::= { kind, payload, authContext }
  //   kind ∈ { "create_user", "create_ticket_category",
  //            "view_admin_form_permintaan_list", "click_form_permintaan_row",
  //            "open_daily_usage_report_branch_filter",
  //            "visit_admin_roles_create_url" }

  OUTPUT: boolean

  IF input.kind = "create_user"
     AND input.authContext.hasPermission("user-create")
     AND isValid(input.payload as UserStoreRequest) THEN
    // Bug 1: submit user redirects to /auth/login dan record tidak persist
    RETURN observed_redirect_to_login(input)
           OR NOT persisted_in_users_list(input.payload)
  END IF

  IF input.kind = "create_ticket_category"
     AND input.authContext.hasPermission("ticket-category-create")
     AND isValid(input.payload as TicketCategoryPayload) THEN
    // Bug 2: submit kategori redirects to /auth/login dan record tidak persist
    RETURN observed_redirect_to_login(input)
           OR NOT persisted_in_ticket_categories_list(input.payload)
  END IF

  IF input.kind = "view_admin_form_permintaan_list"
     AND input.authContext.hasPermission("form-permintaan-view-all") THEN
    // Bug 3a: kontrol export tidak terlihat
    RETURN NOT exists_export_control_matching_regex(
             "Export|Ekspor|Cetak|Print|Unduh|Download|PDF")
  END IF

  IF input.kind = "click_form_permintaan_row"
     AND input.authContext.hasPermission("form-permintaan-view-all") THEN
    // Bug 3b: navigasi baris ke detail menghasilkan 404
    RETURN reached_route("error.notfound")
  END IF

  IF input.kind = "open_daily_usage_report_branch_filter"
     AND input.authContext.hasPermission("daily-record-list")
     AND NOT input.authContext.hasRole("user") THEN
    // Bug 4: dropdown "Pilih Cabang" kosong
    RETURN branches_dropdown_option_count(input) = 0
  END IF

  IF input.kind = "visit_admin_roles_create_url"
     AND input.authContext.hasPermission("role-create")
     AND input.payload.url = "/admin/roles/create" THEN
    // Bug 5: URL plural tidak match ke RoleForm
    RETURN rendered_route_name(input) IN ["not-found", "error.notfound"]
  END IF

  RETURN false
END FUNCTION
```

**Property Specification (fix checking):**

```pascal
// Property 1: Fix Checking - E2E regressions resolved
FOR ALL input WHERE isBugCondition(input) DO
  result ← perform(input, code = F')  // F' = fixed code
  ASSERT NOT isBugCondition_observed_in(result)
         AND session_token_still_valid_when_expected(input, result)
         AND persistence_visible_in_list_when_applicable(input, result)
END FOR
```

**Preservation Goal:**

```pascal
// Property 2: Preservation Checking - non-buggy inputs unchanged
FOR ALL input WHERE NOT isBugCondition(input) DO
  ASSERT observed_in(F, input) = observed_in(F', input)
END FOR
```

- **F** — kode aplikasi (frontend + backend) sebelum perbaikan konsolidasi ini.
- **F'** — kode setelah perbaikan konsolidasi (session/token loss ditangani, path
  route diselaraskan, dropdown cabang terisi, kontrol export tampil untuk viewer,
  navigasi detail form permintaan tidak 404).

Perhatikan bahwa banyak dari kelima bug ini kemungkinan memiliki satu penyebab
lintas (session/token loss ketika request non-401 diperlakukan sebagai 401 atau
ketika error respons dari `store/update` memicu redirect). Analisis akar penyebab
detail per bug dituangkan di `design.md`.
