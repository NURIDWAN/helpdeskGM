# Requirements Document

## Introduction

Fitur ini menambahkan konfigurasi role baru yang spesifik untuk fitur Form Permintaan pada sistem helpdeskGM. Saat ini, sistem memiliki 4 role (superadmin, admin, staff, user) dengan permission form-permintaan yang sudah ada (menu, list, create, confirm, view-all). Fitur ini menambahkan role-role baru dengan workflow approval yang lebih granular, sehingga alur permintaan memiliki tahapan persetujuan yang jelas (pembuatan → review → approval) dengan role yang bertanggung jawab di setiap tahap.

## Glossary

- **Role_Management_System**: Modul yang bertanggung jawab untuk membuat, menyimpan, dan mengelola role serta assignment permission menggunakan Spatie Laravel Permission
- **Form_Permintaan_System**: Modul yang bertanggung jawab untuk membuat, menyimpan, memvalidasi, dan menampilkan form permintaan
- **Role**: Sekumpulan permission yang diberikan kepada user untuk mengontrol akses fitur
- **Permission**: Hak akses spesifik yang menentukan aksi apa yang bisa dilakukan user pada fitur tertentu
- **Approval_Workflow**: Alur persetujuan form permintaan dari pembuatan hingga disetujui
- **Approver**: Role yang memiliki wewenang untuk menyetujui (approve) form permintaan
- **Reviewer**: Role yang memiliki wewenang untuk mereview form permintaan sebelum disetujui oleh Approver
- **Permission_Seeder**: Seeder database yang mendefinisikan semua permission yang tersedia dalam sistem
- **Role_Seeder**: Seeder database yang mendefinisikan role dan mengassign permission ke masing-masing role
- **Permission_Config**: Konfigurasi frontend yang mengelompokkan permission berdasarkan fitur untuk halaman Role Management

## Requirements

### Requirement 1: Tambah Permission Baru untuk Workflow Approval

**User Story:** Sebagai admin, saya ingin sistem memiliki permission tambahan untuk form permintaan, sehingga workflow approval dapat dikonfigurasi lebih granular per role.

#### Acceptance Criteria

1. THE PermissionSeeder SHALL mendaftarkan permission `form-permintaan-review` dengan guard `sanctum` menggunakan metode `firstOrCreate` sehingga tidak terjadi duplikasi jika seeder dijalankan ulang.
2. THE PermissionSeeder SHALL mendaftarkan permission `form-permintaan-reject` dengan guard `sanctum` menggunakan metode `firstOrCreate` sehingga tidak terjadi duplikasi jika seeder dijalankan ulang.
3. THE PermissionSeeder SHALL mendaftarkan permission `form-permintaan-edit` dengan guard `sanctum` menggunakan metode `firstOrCreate` sehingga tidak terjadi duplikasi jika seeder dijalankan ulang.
4. THE PermissionSeeder SHALL mendaftarkan permission `form-permintaan-delete` dengan guard `sanctum` menggunakan metode `firstOrCreate` sehingga tidak terjadi duplikasi jika seeder dijalankan ulang.
5. WHEN RolePermissionSeeder dijalankan, THE RolePermissionSeeder SHALL meng-exclude permission `form-permintaan-review`, `form-permintaan-reject`, `form-permintaan-edit`, dan `form-permintaan-delete` dari role `user` sehingga user biasa tidak dapat melakukan aksi approval workflow.
6. WHEN RolePermissionSeeder dijalankan, THE RolePermissionSeeder SHALL meng-exclude permission `form-permintaan-reject` dan `form-permintaan-delete` dari role `staff` sehingga staff hanya dapat melakukan review dan edit tanpa reject atau hapus.

### Requirement 2: Buat Role Approver untuk Form Permintaan

**User Story:** Sebagai admin, saya ingin memiliki role Approver yang khusus bertanggung jawab menyetujui form permintaan, sehingga hanya orang yang berwenang yang dapat approve permintaan.

#### Acceptance Criteria

1. THE Role_API SHALL menyediakan role `approver-permintaan` dengan guard `sanctum` di Role_Seeder menggunakan metode `firstOrCreate` sehingga seeder bersifat idempoten saat dijalankan ulang.
2. THE Role_API SHALL mengassign tepat 5 permission berikut pada role `approver-permintaan` menggunakan `syncPermissions`: `form-permintaan-menu`, `form-permintaan-list`, `form-permintaan-confirm`, `form-permintaan-view-all`, `form-permintaan-reject` — tanpa permission tambahan lainnya.
3. THE Role_API SHALL memastikan role `approver-permintaan` tidak memiliki permission `form-permintaan-create` sehingga approver tidak dapat membuat form permintaan sendiri.
4. IF Role_Seeder dijalankan dan role `approver-permintaan` sudah ada di database, THEN THE Role_API SHALL memperbarui permission role tersebut sesuai daftar pada kriteria 2 tanpa membuat role duplikat.

### Requirement 3: Buat Role Reviewer untuk Form Permintaan

**User Story:** Sebagai admin, saya ingin memiliki role Reviewer yang bertanggung jawab mereview form permintaan sebelum sampai ke Approver, sehingga ada tahapan verifikasi sebelum persetujuan final.

#### Acceptance Criteria

1. THE Role_Management_System SHALL menyediakan role `reviewer-permintaan` dengan guard `sanctum` di Role_Seeder menggunakan pola `firstOrCreate` sehingga menjalankan seeder berulang kali tidak menduplikasi role.
2. WHEN role `reviewer-permintaan` dibuat, THE Role_Management_System SHALL mengassign tepat 4 permission berikut menggunakan `syncPermissions`: `form-permintaan-menu`, `form-permintaan-list`, `form-permintaan-review`, `form-permintaan-view-all`.
3. THE Role_Management_System SHALL memastikan role `reviewer-permintaan` tidak memiliki permission `form-permintaan-confirm`, `form-permintaan-create`, `form-permintaan-reject`, `form-permintaan-edit`, maupun `form-permintaan-delete` sehingga reviewer hanya dapat mereview tanpa melakukan aksi approval, pembuatan, penolakan, pengeditan, atau penghapusan form permintaan.
4. THE Role_Management_System SHALL tidak memasukkan role `reviewer-permintaan` ke dalam daftar SYSTEM_ROLES sehingga role ini dapat dihapus atau diubah namanya oleh admin melalui Role_API.

### Requirement 4: Update Konfigurasi Frontend Permission

**User Story:** Sebagai admin yang mengelola role, saya ingin permission baru form permintaan muncul di halaman Role Management, sehingga saya dapat mengkonfigurasi permission tersebut saat membuat atau mengedit role.

#### Acceptance Criteria

1. THE Permission_Config SHALL menambahkan permission `form-permintaan-review` dengan label "Review Form" dan deskripsi "Mereview form permintaan sebelum disetujui" pada feature group `formPermintaan`, ditempatkan setelah permission `form-permintaan-confirm`.
2. THE Permission_Config SHALL menambahkan permission `form-permintaan-reject` dengan label "Tolak Form" dan deskripsi "Menolak form permintaan" pada feature group `formPermintaan`, ditempatkan setelah permission `form-permintaan-review`.
3. THE Permission_Config SHALL menambahkan permission `form-permintaan-edit` dengan label "Edit Form" dan deskripsi "Mengedit form permintaan yang sudah dibuat" pada feature group `formPermintaan`, ditempatkan setelah permission `form-permintaan-reject`.
4. THE Permission_Config SHALL menambahkan permission `form-permintaan-delete` dengan label "Hapus Form" dan deskripsi "Menghapus form permintaan" pada feature group `formPermintaan`, ditempatkan setelah permission `form-permintaan-edit`.
5. THE Permission_Config SHALL menambahkan dependency `form-permintaan-review` yang membutuhkan `form-permintaan-list` dan `form-permintaan-menu`.
6. THE Permission_Config SHALL menambahkan dependency `form-permintaan-reject` yang membutuhkan `form-permintaan-list` dan `form-permintaan-menu`.
7. THE Permission_Config SHALL menambahkan dependency `form-permintaan-edit` yang membutuhkan `form-permintaan-list` dan `form-permintaan-menu`.
8. THE Permission_Config SHALL menambahkan dependency `form-permintaan-delete` yang membutuhkan `form-permintaan-list` dan `form-permintaan-menu`.
9. WHEN admin membuka form pembuatan atau pengeditan role, THE Permission_Manager SHALL menampilkan keempat permission baru (`form-permintaan-review`, `form-permintaan-reject`, `form-permintaan-edit`, `form-permintaan-delete`) sebagai Permission_Toggle yang dapat diaktifkan atau dinonaktifkan di dalam section feature group `formPermintaan`, sehingga total permission yang ditampilkan pada feature group tersebut menjadi 9.
10. WHEN admin mengaktifkan salah satu permission baru (`form-permintaan-review`, `form-permintaan-reject`, `form-permintaan-edit`, atau `form-permintaan-delete`), THE Dependency_Resolver SHALL secara otomatis mengaktifkan `form-permintaan-list` dan `form-permintaan-menu` sesuai dependency yang didefinisikan di kriteria 5-8.

### Requirement 5: Update Role Presets di Frontend

**User Story:** Sebagai admin, saya ingin role presets di halaman Role Management mencerminkan role baru (Approver dan Reviewer), sehingga saya dapat dengan cepat membuat role baru berdasarkan preset.

#### Acceptance Criteria

1. THE Permission_Config SHALL menambahkan permission `form-permintaan-reject` (label: "Tolak Form", deskripsi: "Menolak form permintaan") dan `form-permintaan-review` (label: "Review Form", deskripsi: "Mereview form permintaan sebelum approval") pada objek `permissions` di feature group `formPermintaan`, serta menambahkan entri dependensi untuk kedua permission tersebut pada `permissionDependencies` dengan prasyarat `form-permintaan-list` dan `form-permintaan-menu`.
2. THE Permission_Config SHALL menambahkan role preset `approver-permintaan` pada objek `rolePresets` dengan label "Approver Permintaan", deskripsi "Menyetujui atau menolak form permintaan", icon "CheckCircle", dan daftar permission: `form-permintaan-menu`, `form-permintaan-list`, `form-permintaan-confirm`, `form-permintaan-view-all`, `form-permintaan-reject`.
3. THE Permission_Config SHALL menambahkan role preset `reviewer-permintaan` pada objek `rolePresets` dengan label "Reviewer Permintaan", deskripsi "Mereview form permintaan sebelum approval", icon "Eye", dan daftar permission: `form-permintaan-menu`, `form-permintaan-list`, `form-permintaan-review`, `form-permintaan-view-all`.
4. THE Permission_Config SHALL memperbarui role preset `staff` untuk menambahkan permission `form-permintaan-reject` pada daftar permission yang sudah ada, tanpa menghapus atau mengubah permission lain dalam preset `staff`.
5. WHEN admin memilih Role_Preset `approver-permintaan` atau `reviewer-permintaan`, THE Permission_Manager SHALL menerapkan seluruh permission yang terdefinisi dalam preset tersebut dan menampilkan jumlah permission terpilih yang sesuai (5 permission untuk approver-permintaan, 4 permission untuk reviewer-permintaan).

### Requirement 6: Update Assignment Permission Role Existing

**User Story:** Sebagai admin, saya ingin role yang sudah ada (admin, staff) mendapatkan permission baru yang sesuai, sehingga backward compatibility terjaga.

#### Acceptance Criteria

1. WHEN Role_Seeder dijalankan, THE Role_Management_System SHALL mengassign permission `form-permintaan-review`, `form-permintaan-reject`, `form-permintaan-edit`, dan `form-permintaan-delete` ke role `admin` sebagai tambahan dari permission admin yang sudah ada, tanpa menghapus atau mengubah permission lain yang dimiliki role `admin`.
2. WHEN Role_Seeder dijalankan, THE Role_Management_System SHALL mengassign permission `form-permintaan-reject` ke role `staff` sehingga role `staff` memiliki permission form-permintaan: `form-permintaan-menu`, `form-permintaan-list`, `form-permintaan-confirm`, `form-permintaan-view-all`, dan `form-permintaan-reject`.
3. WHEN Role_Seeder dijalankan, THE Role_Management_System SHALL mempertahankan role `user` dengan hanya permission form-permintaan: `form-permintaan-menu`, `form-permintaan-list`, dan `form-permintaan-create`, tanpa penambahan permission `form-permintaan-review`, `form-permintaan-reject`, `form-permintaan-edit`, atau `form-permintaan-delete`.
4. WHEN Role_Seeder dijalankan, THE Role_Management_System SHALL mengassign seluruh permission form-permintaan ke role `superadmin` yaitu: `form-permintaan-menu`, `form-permintaan-list`, `form-permintaan-create`, `form-permintaan-confirm`, `form-permintaan-view-all`, `form-permintaan-review`, `form-permintaan-reject`, `form-permintaan-edit`, dan `form-permintaan-delete`.
5. IF Role_Seeder dijalankan lebih dari satu kali, THEN THE Role_Management_System SHALL menghasilkan state permission yang identik dengan eksekusi pertama tanpa duplikasi assignment pada tabel role_has_permissions.

### Requirement 7: Proteksi API Endpoint dengan Permission Baru

**User Story:** Sebagai developer, saya ingin endpoint API form permintaan dilindungi dengan permission baru, sehingga hanya user dengan role yang sesuai yang dapat melakukan aksi review, reject, edit, dan delete.

#### Acceptance Criteria

1. THE Form_Permintaan_System SHALL melindungi endpoint review form permintaan dengan middleware Spatie permission `form-permintaan-review` sehingga request ditolak sebelum controller logic dieksekusi jika user tidak memiliki permission tersebut.
2. THE Form_Permintaan_System SHALL melindungi endpoint reject form permintaan dengan middleware Spatie permission `form-permintaan-reject` sehingga request ditolak sebelum controller logic dieksekusi jika user tidak memiliki permission tersebut.
3. THE Form_Permintaan_System SHALL melindungi endpoint edit form permintaan (PUT `/form-permintaan/{id}`) dengan middleware Spatie permission `form-permintaan-edit` sehingga request ditolak sebelum controller logic dieksekusi jika user tidak memiliki permission tersebut.
4. THE Form_Permintaan_System SHALL melindungi endpoint delete form permintaan (DELETE `/form-permintaan/{id}`) dengan middleware Spatie permission `form-permintaan-delete` sehingga request ditolak sebelum controller logic dieksekusi jika user tidak memiliki permission tersebut.
5. IF seorang user yang terautentikasi namun tidak memiliki permission yang dibutuhkan mengakses salah satu endpoint tersebut, THEN THE Form_Permintaan_System SHALL mengembalikan response dengan HTTP status 403 dan body JSON berformat `{ "success": false, "message": "..." }` yang mengindikasikan akses ditolak, tanpa mengeksekusi logika bisnis endpoint.
6. IF seorang user yang tidak terautentikasi (tanpa token Sanctum valid) mengakses salah satu endpoint tersebut, THEN THE Form_Permintaan_System SHALL mengembalikan response dengan HTTP status 401 dan body JSON berformat `{ "success": false, "message": "..." }` yang mengindikasikan user belum login.
7. WHEN user yang memiliki permission yang sesuai mengakses endpoint yang dilindungi, THE Form_Permintaan_System SHALL meneruskan request ke controller logic dan memproses aksi sesuai fungsinya secara normal.
