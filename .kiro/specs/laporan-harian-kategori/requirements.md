# Requirements Document

## Introduction

Fitur ini memperbaiki halaman Laporan Daily Usage agar pengguna dapat memilih kategori utilitas (Air, Gas, Listrik) secara lebih jelas dan menonjol. Saat ini, filter kategori tersembunyi dalam panel filter yang harus dibuka terlebih dahulu. Perubahan ini akan menampilkan pilihan kategori secara langsung (prominent) di halaman laporan, sehingga pengguna dapat dengan cepat beralih antara laporan Air, Gas, dan Listrik.

## Glossary

- **Sistem_Laporan**: Halaman Laporan Daily Usage pada frontend (DailyUsageReport.vue) yang menampilkan data penggunaan utilitas harian
- **Kategori**: Jenis utilitas yang tersedia yaitu Air (water), Gas (gas), dan Listrik (electricity)
- **Filter_Panel**: Panel filter tersembunyi (collapsible) yang berisi opsi filter seperti user, cabang, bulan, dan kategori
- **Pilihan_Kategori**: Komponen UI berupa tab atau tombol yang menampilkan pilihan kategori secara langsung tanpa perlu membuka filter panel
- **Backend_API**: Endpoint Laravel `/daily-records/report/daily-usage` yang menerima parameter `category`
- **Modal_Reset**: Modal konfirmasi yang muncul ketika SuperAdmin menekan tombol "Reset Usage", berisi dropdown pemilih kategori dan tombol konfirmasi/batal
- **Reset_API**: Endpoint Laravel POST `/daily-records/report/daily-usage/reset` yang menerima parameter `branch_id`, `start_date`, `end_date`, `category`, dan opsional `user_id`
- **Dropdown_Kategori_Reset**: Komponen dropdown di dalam Modal_Reset yang memungkinkan SuperAdmin memilih kategori spesifik yang akan direset

## Requirements

### Requirement 1: Pilihan Kategori Ditampilkan Secara Langsung

**User Story:** Sebagai pengguna, saya ingin melihat pilihan kategori (Air, Gas, Listrik) secara langsung di halaman laporan, sehingga saya dapat dengan cepat memilih jenis laporan yang ingin dilihat tanpa membuka panel filter.

#### Acceptance Criteria

1. WHEN halaman Laporan Daily Usage dimuat, THE Sistem_Laporan SHALL menampilkan Pilihan_Kategori berupa tab atau tombol untuk "Semua", "Air", "Gas", dan "Listrik" di area antara header halaman dan tabel data, dengan "Semua" terpilih sebagai default sehingga semua kategori ditampilkan
2. THE Pilihan_Kategori SHALL menampilkan empat opsi yang selalu terlihat tanpa perlu membuka panel filter: "Semua" (value: kosong), "Air" (value: water), "Gas" (value: gas), dan "Listrik" (value: electricity)
3. WHEN pengguna memilih salah satu Kategori pada Pilihan_Kategori, THE Sistem_Laporan SHALL memperbarui parameter category pada request API dan menampilkan ulang data laporan sesuai Kategori yang dipilih dalam waktu maksimal 3 detik (tidak termasuk waktu respons jaringan)
4. WHEN pengguna memilih Kategori pada Pilihan_Kategori, THE Sistem_Laporan SHALL menandai Kategori yang aktif dengan gaya visual yang berbeda dari opsi lainnya (misalnya warna latar berbeda atau garis bawah) sehingga hanya satu Kategori yang terlihat aktif pada satu waktu
5. WHEN pengguna memilih Kategori pada Pilihan_Kategori, THE Sistem_Laporan SHALL menyinkronkan nilai dropdown Kategori di dalam panel filter agar menampilkan nilai yang sama dengan Pilihan_Kategori yang dipilih
6. IF cabang belum dipilih (empty state ditampilkan), THEN THE Sistem_Laporan SHALL tetap menampilkan Pilihan_Kategori dalam keadaan dapat diklik, dan menyimpan pilihan pengguna sehingga ketika cabang dipilih, data langsung dimuat sesuai Kategori yang telah dipilih

### Requirement 2: Default Tampilan Laporan

**User Story:** Sebagai pengguna, saya ingin ada tampilan default ketika membuka halaman laporan, sehingga saya langsung melihat data yang relevan.

#### Acceptance Criteria

1. WHEN halaman Laporan Daily Usage dimuat pertama kali, THE Sistem_Laporan SHALL menetapkan filter kategori ke opsi "Semua Kategori" (nilai kosong) dan menetapkan filter bulan ke bulan berjalan sehingga tidak ada penyaringan kategori yang diterapkan
2. THE Pilihan_Kategori SHALL menyediakan opsi "Semua Kategori" sebagai pilihan pertama, diikuti oleh opsi individual: "Gas", "Air", dan "Listrik"
3. WHEN opsi "Semua Kategori" dipilih dan cabang telah ditentukan, THE Sistem_Laporan SHALL mengirim permintaan ke API tanpa parameter category sehingga API mengembalikan data untuk semua kategori (Gas, Air, dan Listrik) dan menampilkan kolom laporan ketiga kategori tersebut secara bersamaan dalam satu tabel
4. IF cabang belum dipilih saat halaman dimuat pertama kali, THEN THE Sistem_Laporan SHALL menampilkan empty state dengan pesan yang menginstruksikan pengguna untuk memilih cabang terlebih dahulu, tanpa memuat data laporan

### Requirement 3: Sinkronisasi Filter Kategori

**User Story:** Sebagai pengguna, saya ingin pilihan kategori yang ditampilkan langsung tetap sinkron dengan filter di panel filter, sehingga tidak terjadi kebingungan.

#### Acceptance Criteria

1. WHEN pengguna memilih salah satu kategori ("gas", "water", atau "electricity") pada Pilihan_Kategori, THE Sistem_Laporan SHALL memperbarui nilai dropdown kategori pada Filter_Panel menjadi nilai yang sama dan memuat ulang data laporan sesuai kategori yang dipilih dalam siklus renderisasi yang sama
2. WHEN pengguna mengubah filter kategori pada dropdown Filter_Panel, THE Sistem_Laporan SHALL memperbarui status aktif pada Pilihan_Kategori agar menampilkan kategori yang sama dengan nilai dropdown yang dipilih dalam siklus renderisasi yang sama
3. WHEN halaman laporan pertama kali dimuat, THE Sistem_Laporan SHALL menampilkan Pilihan_Kategori tanpa kategori yang aktif (state "Semua Kategori") dan dropdown Filter_Panel dengan nilai kosong ("Semua Kategori") secara bersamaan
4. IF pengguna memilih opsi "Semua Kategori" pada salah satu kontrol (Pilihan_Kategori atau Filter_Panel), THEN THE Sistem_Laporan SHALL mengosongkan nilai kategori pada kedua kontrol dan menampilkan data laporan untuk semua kategori (gas, water, dan electricity)

### Requirement 4: Tampilan Tabel Sesuai Kategori

**User Story:** Sebagai pengguna, saya ingin tabel laporan hanya menampilkan kolom yang relevan sesuai kategori yang dipilih, sehingga laporan lebih mudah dibaca.

#### Acceptance Criteria

1. THE Sistem_Laporan SHALL selalu menampilkan kolom umum (Timestamp, Tanggal, Nama, Outlet, Total Customer) pada tabel laporan terlepas dari kategori yang dipilih
2. WHEN Kategori "Gas" dipilih, THE Sistem_Laporan SHALL menampilkan grup kolom "LAPORAN GAS" yang terdiri dari: Jenis Kompor, Jenis Gas, Closing, Opening, Total Pemakaian, Foto, Lokasi — dan menyembunyikan grup kolom Air dan Listrik
3. WHEN Kategori "Air" dipilih, THE Sistem_Laporan SHALL menampilkan grup kolom "LAPORAN AIR" yang terdiri dari: Closing, Opening, Total Pemakaian, Foto, Lokasi — dan menyembunyikan grup kolom Gas dan Listrik
4. WHEN Kategori "Listrik" dipilih, THE Sistem_Laporan SHALL menampilkan grup kolom "LAPORAN LISTRIK" yang terdiri dari: Nama, Lokasi, Closing, Opening, Pemakaian, Foto — dan menyembunyikan grup kolom Gas dan Air
5. WHEN Kategori "Semua" dipilih (atau tidak ada kategori dipilih), THE Sistem_Laporan SHALL menampilkan ketiga grup kolom (LAPORAN GAS, LAPORAN AIR, LAPORAN LISTRIK) secara bersamaan di samping kolom umum
6. IF suatu record memiliki lebih dari 1 meter listrik, THEN THE Sistem_Laporan SHALL menampilkan setiap meter pada baris terpisah dengan kolom umum dan kolom kategori lain di-merge menggunakan rowspan, serta menampilkan baris TOTAL di akhir grup meter tersebut; IF perhitungan total gagal karena data tidak lengkap, THEN THE Sistem_Laporan SHALL tetap menampilkan baris meter individual tanpa baris TOTAL
7. IF data laporan kosong untuk filter yang dipilih, THEN THE Sistem_Laporan SHALL menampilkan pesan "Tidak ada data" yang mencakup seluruh lebar kolom tabel sesuai kategori aktif

### Requirement 5: Export Sesuai Kategori yang Dipilih

**User Story:** Sebagai pengguna, saya ingin export (Excel/PDF) mengikuti kategori yang sedang dipilih, sehingga file export hanya berisi data kategori yang saya butuhkan.

#### Acceptance Criteria

1. WHEN pengguna melakukan export Excel dengan kategori "gas", "water", atau "electricity" dipilih, THE Sistem_Laporan SHALL mengirim parameter `category` dengan nilai kategori yang dipilih ke Backend_API endpoint export Excel
2. WHEN pengguna melakukan export PDF dengan kategori "gas", "water", atau "electricity" dipilih, THE Sistem_Laporan SHALL mengirim parameter `category` dengan nilai kategori yang dipilih ke Backend_API endpoint export PDF
3. WHEN tidak ada kategori spesifik yang dipilih (nilai kosong) saat export, THE Sistem_Laporan SHALL mengirim parameter `category` dengan nilai "all" ke Backend_API
4. IF request export gagal atau Backend_API mengembalikan error, THEN THE Sistem_Laporan SHALL menampilkan pesan error yang menjelaskan kegagalan export dan tidak mengunduh file apapun

### Requirement 6: Responsif pada Mobile

**User Story:** Sebagai pengguna mobile, saya ingin pilihan kategori tetap mudah diakses pada layar kecil, sehingga saya dapat menggunakan fitur ini dari perangkat apapun.

#### Acceptance Criteria

1. WHILE layar berukuran mobile (lebar < 768px), THE Pilihan_Kategori SHALL ditampilkan dengan lebar penuh (100% dari lebar container) dan seluruh elemen terlihat dalam viewport tanpa memerlukan scroll horizontal
2. WHILE layar berukuran mobile (lebar < 768px), THE Pilihan_Kategori SHALL memiliki ukuran teks minimal 14px dan padding minimal 8px pada setiap sisi, serta tinggi area sentuh minimal 44px
3. WHILE layar berukuran mobile (lebar < 768px), IF panel filter dibuka, THEN THE Pilihan_Kategori SHALL ditampilkan dalam layout satu kolom (grid-cols-1) sehingga setiap field filter tersusun vertikal tanpa terpotong atau tersembunyi oleh overflow hidden

### Requirement 7: Modal Reset dengan Pemilih Kategori

**User Story:** Sebagai SuperAdmin, saya ingin modal konfirmasi reset daily usage memiliki dropdown pilihan kategori sendiri, sehingga saya dapat memilih kategori spesifik yang akan direset terlepas dari filter kategori yang sedang aktif di halaman.

#### Acceptance Criteria

1. WHEN SuperAdmin menekan tombol "Reset Usage", THE Modal_Reset SHALL menampilkan modal konfirmasi yang berisi Dropdown_Kategori_Reset dengan empat opsi: "Semua" (value: all), "Gas" (value: gas), "Air" (value: water), dan "Listrik" (value: electricity)
2. WHEN Modal_Reset dibuka, THE Dropdown_Kategori_Reset SHALL menetapkan nilai default sesuai kategori yang sedang aktif pada Pilihan_Kategori halaman; jika tidak ada kategori aktif (state "Semua Kategori"), maka default Dropdown_Kategori_Reset adalah "Semua"
3. THE Modal_Reset SHALL menampilkan pesan konfirmasi yang menyebutkan kategori yang akan direset berdasarkan pilihan pada Dropdown_Kategori_Reset, sehingga SuperAdmin mengetahui secara eksplisit kategori mana yang terdampak sebelum mengonfirmasi
4. WHEN SuperAdmin mengubah pilihan pada Dropdown_Kategori_Reset, THE Modal_Reset SHALL memperbarui pesan konfirmasi secara langsung untuk mencerminkan kategori baru yang dipilih tanpa menutup modal
5. WHEN SuperAdmin mengonfirmasi reset, THE Sistem_Laporan SHALL mengirim request ke Reset_API dengan parameter `category` sesuai nilai yang dipilih pada Dropdown_Kategori_Reset, bukan dari filter kategori halaman yang sedang aktif
6. WHEN SuperAdmin mengonfirmasi reset dengan opsi "Semua" dipilih, THE Sistem_Laporan SHALL mengirim parameter `category` dengan nilai "all" ke Reset_API sehingga data semua kategori (Gas, Air, Listrik) direset
7. WHEN SuperAdmin mengonfirmasi reset dengan salah satu kategori spesifik ("gas", "water", atau "electricity") dipilih, THE Sistem_Laporan SHALL mengirim parameter `category` dengan nilai kategori tersebut ke Reset_API sehingga hanya data kategori yang dipilih yang direset
8. IF SuperAdmin menekan tombol batal atau menutup Modal_Reset, THEN THE Sistem_Laporan SHALL menutup modal tanpa mengirim request reset dan tidak mengubah data laporan yang ditampilkan
9. IF request reset berhasil, THEN THE Sistem_Laporan SHALL menutup Modal_Reset, menampilkan notifikasi sukses yang menyebutkan kategori yang telah direset, dan memuat ulang data laporan sesuai filter halaman yang aktif
10. IF request reset gagal atau Reset_API mengembalikan error, THEN THE Sistem_Laporan SHALL menampilkan pesan error di dalam Modal_Reset tanpa menutup modal, sehingga SuperAdmin dapat mencoba lagi atau membatalkan; IF SuperAdmin menekan tombol batal saat pesan error ditampilkan, THEN modal SHALL tetap terbuka menampilkan error hingga SuperAdmin secara eksplisit menutup pesan error atau mencoba lagi
