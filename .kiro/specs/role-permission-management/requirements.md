# Requirements Document

## Introduction

Peningkatan fitur Role & Permission Management pada aplikasi Helpdesk GM untuk mempermudah pengaturan hak akses per fitur. Sistem saat ini menggunakan Spatie Laravel Permission dengan ~70 permission granular yang dikelompokkan ke dalam 14 modul fitur. Tujuan utama adalah menyediakan antarmuka yang lebih intuitif agar admin dapat mengatur permission per fitur dengan cepat tanpa harus memilih satu per satu permission secara manual.

## Glossary

- **Permission_Manager**: Komponen frontend yang mengelola state pemilihan permission pada form role
- **Role_API**: Backend API endpoint yang menangani operasi CRUD role dan sinkronisasi permission
- **Feature_Group**: Pengelompokan permission berdasarkan fitur bisnis (misalnya: Manajemen Tiket, Laporan Harian)
- **Permission_Toggle**: Kontrol UI untuk mengaktifkan/menonaktifkan satu permission individual
- **Feature_Toggle**: Kontrol UI untuk mengaktifkan/menonaktifkan seluruh permission dalam satu fitur sekaligus
- **Permission_Matrix**: Tampilan tabel yang menunjukkan mapping antara role dan permission per fitur
- **Role_Preset**: Template kumpulan permission yang sudah dikonfigurasi untuk mempercepat pembuatan role
- **Dependency_Resolver**: Logika yang secara otomatis mengaktifkan permission prasyarat saat permission tertentu dipilih

## Requirements

### Requirement 1: Feature-Level Bulk Toggle

**User Story:** Sebagai admin, saya ingin mengaktifkan atau menonaktifkan seluruh permission dalam satu fitur sekaligus, sehingga saya tidak perlu memilih permission satu per satu.

#### Acceptance Criteria

1. WHEN admin mengklik Feature_Toggle pada sebuah Feature_Group yang tidak aktif penuh (status "none" atau "partial"), THE Permission_Manager SHALL mengaktifkan semua permission dalam Feature_Group tersebut termasuk permission di seluruh sub-fitur yang dimiliki Feature_Group itu
2. WHEN admin mengklik Feature_Toggle yang sudah aktif penuh (semua permission dalam Feature_Group termasuk sub-fiturnya berstatus selected), THE Permission_Manager SHALL menonaktifkan semua permission dalam Feature_Group tersebut kecuali permission yang masih dibutuhkan sebagai dependency oleh permission aktif di Feature_Group lain
3. WHILE jumlah permission aktif dalam suatu Feature_Group lebih dari 0 namun kurang dari total permission dalam Feature_Group tersebut, THE Permission_Manager SHALL menampilkan Feature_Toggle dalam status indeterminate (visual checkbox sebagian terpilih)
4. WHEN Feature_Toggle diaktifkan, THE Dependency_Resolver SHALL secara otomatis mengaktifkan semua permission prasyarat yang dibutuhkan oleh permission dalam Feature_Group tersebut sesuai definisi permissionDependencies, termasuk permission prasyarat yang berada di luar Feature_Group yang sedang di-toggle
5. IF Feature_Toggle dinonaktifkan namun satu atau lebih permission dalam Feature_Group tersebut merupakan dependency aktif dari permission di Feature_Group lain, THEN THE Permission_Manager SHALL mempertahankan permission yang di-lock tersebut dalam keadaan aktif dan tetap menampilkan status indeterminate pada Feature_Toggle

### Requirement 2: Sub-Feature Toggle

**User Story:** Sebagai admin, saya ingin mengatur akses pada level sub-fitur (misalnya: Balasan Tiket, Lampiran Tiket), sehingga saya bisa memberikan akses parsial yang lebih granular pada fitur yang memiliki sub-komponen.

#### Acceptance Criteria

1. WHEN sebuah Feature_Group memiliki sub-fitur, THE Permission_Manager SHALL menampilkan toggle terpisah untuk setiap sub-fitur beserta indikator jumlah permission aktif versus total permission dalam sub-fitur tersebut
2. WHEN admin mengaktifkan sub-fitur toggle, THE Permission_Manager SHALL mengaktifkan semua permission dalam sub-fitur tersebut dalam waktu tidak lebih dari 1 detik
3. WHEN admin menonaktifkan sub-fitur toggle, THE Permission_Manager SHALL menonaktifkan semua permission dalam sub-fitur tersebut kecuali permission yang masih dibutuhkan sebagai dependensi oleh permission aktif di luar sub-fitur tersebut
4. WHEN admin mengaktifkan sub-fitur yang memiliki daftar dependsOn, THE Dependency_Resolver SHALL secara otomatis mengaktifkan setiap permission yang tercantum dalam daftar dependsOn sub-fitur tersebut dan menampilkan indikator visual bahwa permission tersebut diaktifkan karena dependensi
5. IF admin menonaktifkan permission prasyarat yang masih dibutuhkan oleh sub-fitur lain yang aktif, THEN THE Dependency_Resolver SHALL mempertahankan permission prasyarat tersebut tetap aktif dan menampilkan indikator bahwa permission tersebut terkunci karena dependensi

### Requirement 3: Permission Summary per Feature

**User Story:** Sebagai admin, saya ingin melihat ringkasan jumlah permission yang aktif per fitur pada halaman daftar role, sehingga saya bisa dengan cepat memahami cakupan akses setiap role.

#### Acceptance Criteria

1. THE Permission_Manager SHALL menampilkan badge untuk setiap Feature_Group pada halaman daftar role, dengan format teks "{jumlah permission aktif}/{total permission}" (contoh: "3/5") untuk setiap role
2. IF sebuah role memiliki seluruh permission dalam Feature_Group aktif, THEN THE Permission_Manager SHALL menampilkan badge dengan indikator "Penuh" menggantikan format numerik untuk Feature_Group tersebut
3. IF sebuah role tidak memiliki permission apapun dalam Feature_Group, THEN THE Permission_Manager SHALL tidak menampilkan badge untuk Feature_Group tersebut
4. WHEN halaman daftar role dimuat, THE Permission_Manager SHALL menghitung dan menampilkan ringkasan permission berdasarkan data permission terkini yang tersimpan di database

### Requirement 4: Role Preset Templates

**User Story:** Sebagai admin, saya ingin membuat role baru berdasarkan template preset yang sudah ada, sehingga saya bisa mempercepat pembuatan role tanpa harus mengkonfigurasi dari awal.

#### Acceptance Criteria

1. WHEN admin membuka form pembuatan atau pengeditan role, THE Permission_Manager SHALL menampilkan dropdown untuk memilih Role_Preset yang tersedia beserta label dan deskripsi singkat masing-masing preset
2. WHEN admin memilih sebuah Role_Preset, THE Permission_Manager SHALL menggantikan seluruh permission yang sedang terpilih dengan konfigurasi permission dari preset tersebut
3. WHEN admin memilih Role_Preset, THE Permission_Manager SHALL tetap mengizinkan admin untuk menambah atau menghapus permission individual setelah preset diterapkan
4. THE Role_API SHALL menyediakan minimal 3 preset bawaan yaitu Staff (akses operasional tiket, SPK, dan laporan), User (akses dasar tiket dan laporan harian), dan Admin (akses semua fitur kecuali manajemen role penuh dan pengaturan WhatsApp)
5. WHEN admin memilih Role_Preset, THE Permission_Manager SHALL memperbarui jumlah total permission terpilih yang ditampilkan di UI sesuai dengan permission hasil preset

### Requirement 5: Duplikasi Role

**User Story:** Sebagai admin, saya ingin menduplikasi role yang sudah ada dengan semua permission-nya, sehingga saya bisa membuat variasi role baru berdasarkan role yang serupa.

#### Acceptance Criteria

1. WHEN admin memilih aksi duplikasi pada sebuah role, THE Permission_Manager SHALL membuka form pembuatan role baru dengan seluruh permission dari role sumber sudah terisi otomatis
2. WHEN form duplikasi dibuka, THE Permission_Manager SHALL mengosongkan field nama role dan menonaktifkan tombol simpan hingga admin mengisi nama role baru dengan panjang 3 sampai 50 karakter
3. IF role sumber adalah role sistem (admin, staff, user, superadmin), THEN THE Permission_Manager SHALL tetap mengizinkan duplikasi
4. IF admin mengisi nama role yang sudah digunakan oleh role lain, THEN THE Permission_Manager SHALL menampilkan pesan error yang menunjukkan bahwa nama role sudah terpakai dan mencegah penyimpanan
5. WHEN admin mengisi nama role baru yang valid dan menyimpan hasil duplikasi, THE Role_API SHALL membuat role baru dengan seluruh permission yang sama dengan role sumber dan mengembalikan konfirmasi berhasil

### Requirement 6: Permission Search dan Filter

**User Story:** Sebagai admin, saya ingin mencari permission berdasarkan nama atau fitur, sehingga saya bisa dengan cepat menemukan dan mengatur permission tertentu tanpa harus scroll seluruh daftar.

#### Acceptance Criteria

1. THE Permission_Manager SHALL menyediakan input pencarian pada halaman form role yang menerima maksimal 100 karakter
2. WHEN admin mengetik minimal 1 karakter di input pencarian, THE Permission_Manager SHALL memfilter daftar menggunakan case-insensitive partial match dan hanya menampilkan Feature_Group yang nama grupnya atau nama permission di dalamnya mengandung kata kunci tersebut
3. WHILE filter pencarian aktif, THE Permission_Manager SHALL mempertahankan status centang (checked/unchecked) seluruh permission termasuk yang tersembunyi oleh filter
4. WHEN admin mengosongkan input pencarian, THE Permission_Manager SHALL menampilkan kembali seluruh Feature_Group dan permission dengan status centang yang tetap sesuai kondisi terakhir
5. WHEN hasil pencarian tidak menemukan permission maupun Feature_Group yang cocok, THE Permission_Manager SHALL menampilkan pesan bahwa tidak ada permission yang sesuai dengan kata kunci yang dimasukkan

### Requirement 7: Permission Dependency Auto-Resolution

**User Story:** Sebagai admin, saya ingin sistem secara otomatis mengaktifkan permission prasyarat saat saya memilih permission tertentu, sehingga saya tidak perlu mengingat hubungan dependensi antar permission.

#### Acceptance Criteria

1. WHEN admin mengaktifkan permission yang memiliki dependensi, THE Dependency_Resolver SHALL secara otomatis mengaktifkan seluruh permission prasyarat secara rekursif hingga maksimal 3 level kedalaman dependensi
2. WHEN admin menonaktifkan permission prasyarat yang masih memiliki permission dependen aktif, THE Dependency_Resolver SHALL menampilkan dialog konfirmasi yang mencantumkan daftar nama permission yang bergantung padanya
3. IF admin mengkonfirmasi penonaktifan permission prasyarat, THEN THE Dependency_Resolver SHALL menonaktifkan seluruh permission yang bergantung pada permission prasyarat tersebut secara rekursif
4. THE Dependency_Resolver SHALL menampilkan indikator visual yang membedakan permission yang diaktifkan secara otomatis karena dependensi dari permission yang diaktifkan manual oleh admin
5. IF admin mencoba menonaktifkan permission yang diaktifkan otomatis karena dependensi sementara permission dependen masih aktif, THEN THE Dependency_Resolver SHALL mencegah penonaktifan dan menampilkan pesan yang menjelaskan permission mana yang harus dinonaktifkan terlebih dahulu

### Requirement 8: Proteksi Role Sistem

**User Story:** Sebagai admin, saya ingin role sistem (admin, staff, user, superadmin) terlindungi dari penghapusan dan penggantian nama, sehingga tidak terjadi kerusakan konfigurasi yang mengganggu operasional.

#### Acceptance Criteria

1. IF admin mengirim permintaan penghapusan role sistem (admin, staff, user, atau superadmin), THEN THE Role_API SHALL menolak permintaan tersebut dengan kode respon 403
2. IF admin mengirim permintaan pengubahan nama role sistem (admin, staff, user, atau superadmin), THEN THE Role_API SHALL menolak permintaan tersebut dengan kode respon 403
3. WHILE role yang diubah adalah role sistem selain superadmin, THE Role_API SHALL mengizinkan penambahan dan pencabutan permission pada role tersebut
4. IF admin mengirim permintaan perubahan permission pada role superadmin, THEN THE Role_API SHALL menolak permintaan tersebut dengan kode respon 403
5. IF admin mencoba menghapus role non-sistem yang masih memiliki minimal 1 user, THEN THE Role_API SHALL menolak permintaan dengan kode respon 422 dan pesan error yang menyertakan jumlah user yang masih menggunakan role tersebut

### Requirement 9: Permission Matrix View

**User Story:** Sebagai admin, saya ingin melihat tampilan matriks yang menunjukkan semua role dan permission-nya secara berdampingan, sehingga saya bisa membandingkan hak akses antar role dengan mudah.

#### Acceptance Criteria

1. THE Permission_Manager SHALL menyediakan halaman Permission_Matrix yang menampilkan tabel dengan kolom berupa seluruh role yang terdaftar di sistem dan baris berupa seluruh Feature_Group (14 modul fitur)
2. THE Permission_Manager SHALL menampilkan indikator pada setiap sel Permission_Matrix dengan tiga status: "Penuh" jika seluruh permission dalam Feature_Group aktif untuk role tersebut, "Sebagian" jika minimal 1 dan kurang dari total permission aktif, atau "Kosong" jika tidak ada permission yang aktif
3. WHEN admin mengklik sel pada Permission_Matrix, THE Permission_Manager SHALL menampilkan daftar seluruh permission dalam Feature_Group tersebut beserta status aktif atau tidak aktif untuk role yang dipilih
4. THE Permission_Manager SHALL menampilkan Permission_Matrix dalam mode read-only tanpa kemampuan mengubah permission langsung dari tampilan matriks
5. IF Permission_Matrix gagal memuat data role atau permission, THEN THE Permission_Manager SHALL menampilkan pesan error yang menjelaskan bahwa data tidak dapat dimuat

### Requirement 10: Validasi Permission Backend

**User Story:** Sebagai admin, saya ingin backend memvalidasi permission yang dikirim saat menyimpan role, sehingga tidak ada permission yang tidak valid atau tidak terdaftar masuk ke database.

#### Acceptance Criteria

1. WHEN Role_API menerima request pembuatan atau pembaruan role yang menyertakan field permissions, THE Role_API SHALL memvalidasi setiap item dalam array permissions terhadap kolom name pada tabel permissions di database dan menolak item yang tidak cocok dengan permission yang terdaftar
2. IF terdapat satu atau lebih permission dalam request yang tidak ditemukan di kolom name tabel permissions, THEN THE Role_API SHALL menolak seluruh request dengan kode respon 422 dan menyertakan response body yang memuat daftar nama permission yang tidak valid beserta pesan error per item
3. THE Role_API SHALL mengabaikan permission duplikat dalam request tanpa mengembalikan error dan hanya menyimpan satu instance dari setiap permission unik ke role
4. IF field permissions tidak disertakan atau bernilai null dalam request, THEN THE Role_API SHALL memproses request tanpa mengubah permission yang sudah terkait dengan role tersebut
5. WHEN Role_API menerima request dengan field permissions berupa array kosong, THE Role_API SHALL menghapus semua permission yang terkait dengan role tersebut
