# Requirements Document

## Introduction

Dokumen ini mendefinisikan kebutuhan untuk menambahkan dukungan Progressive Web App (PWA) pada aplikasi frontend "GA Maintenance". Fitur PWA memungkinkan aplikasi dapat di-install di perangkat pengguna, bekerja secara offline, memiliki strategi caching yang efisien, dan memberikan notifikasi update versi baru. Implementasi menggunakan library `vite-plugin-pwa` yang terintegrasi dengan Vite 6 dan Vue 3.

## Glossary

- **PWA_Plugin**: Plugin `vite-plugin-pwa` yang dikonfigurasi di Vite untuk menghasilkan service worker dan web app manifest secara otomatis
- **Service_Worker_PWA**: Service worker yang dihasilkan oleh Workbox melalui PWA_Plugin untuk menangani caching dan offline support
- **Push_Notification_SW**: Service worker existing (`browser-notification-sw.js`) yang menangani push notification
- **Web_App_Manifest**: File JSON yang mendeskripsikan metadata aplikasi PWA (nama, ikon, warna tema, display mode)
- **Install_Prompt**: Event `beforeinstallprompt` dari browser yang memungkinkan aplikasi menampilkan prompt instalasi PWA kepada pengguna
- **Update_Prompt**: Komponen UI yang menginformasikan pengguna bahwa versi baru aplikasi tersedia dan menawarkan opsi reload
- **Offline_Fallback**: Halaman HTML statis yang ditampilkan ketika pengguna tidak memiliki koneksi internet dan resource yang diminta tidak tersedia di cache
- **Cache_Strategy**: Strategi Workbox yang menentukan bagaimana request di-cache dan disajikan (NetworkFirst, CacheFirst, StaleWhileRevalidate)
- **Precache**: Mekanisme caching resource statis (JS, CSS, HTML) saat service worker di-install untuk ketersediaan offline
- **Runtime_Cache**: Mekanisme caching resource dinamis (API calls, gambar, font) saat runtime berdasarkan pola URL

## Requirements

### Requirement 1: Instalasi dan Konfigurasi PWA Plugin

**User Story:** Sebagai developer, saya ingin mengkonfigurasi vite-plugin-pwa di proyek Vite, sehingga aplikasi dapat menghasilkan service worker dan manifest secara otomatis saat build.

#### Acceptance Criteria

1. THE PWA_Plugin SHALL terdaftar sebagai devDependency di `package.json` dengan nama package `vite-plugin-pwa`
2. THE PWA_Plugin SHALL di-import dan ditambahkan ke array `plugins` di dalam file `vite.config.js` bersama plugin yang sudah ada (`@vitejs/plugin-vue`)
3. WHEN perintah `vite build` dijalankan dalam mode production, THE PWA_Plugin SHALL menghasilkan file service worker (`sw.js`) dan file manifest (`manifest.webmanifest`) di dalam folder output build (`dist/`)
4. THE PWA_Plugin SHALL dikonfigurasi dengan strategi `generateSW` dari Workbox untuk menghasilkan service worker secara otomatis
5. THE PWA_Plugin SHALL dikonfigurasi dengan `registerType: 'prompt'` agar pengguna dapat memilih kapan update diterapkan
6. IF perintah `vite build` gagal karena konfigurasi PWA_Plugin tidak valid, THEN THE Build_Process SHALL menampilkan error message yang mengindikasikan kesalahan konfigurasi plugin dan menghentikan proses build dengan exit code non-zero

### Requirement 2: Web App Manifest

**User Story:** Sebagai pengguna, saya ingin aplikasi memiliki manifest yang lengkap, sehingga aplikasi dapat di-install dan ditampilkan dengan identitas visual yang benar di perangkat saya.

#### Acceptance Criteria

1. THE PWA_Plugin SHALL menghasilkan Web_App_Manifest dengan field `name` bernilai "GA Maintenance"
2. THE PWA_Plugin SHALL menghasilkan Web_App_Manifest dengan field `short_name` bernilai "GA Maint"
3. THE PWA_Plugin SHALL menghasilkan Web_App_Manifest dengan field `description` berupa string non-kosong dengan panjang antara 10 sampai 200 karakter yang mengandung kata "helpdesk" atau "maintenance"
4. THE PWA_Plugin SHALL menghasilkan Web_App_Manifest dengan field `theme_color` dan `background_color` masing-masing berupa string valid CSS color dalam format hex 6-digit (contoh: "#RRGGBB")
5. THE PWA_Plugin SHALL menghasilkan Web_App_Manifest dengan field `display` bernilai "standalone"
6. THE PWA_Plugin SHALL menghasilkan Web_App_Manifest dengan field `start_url` bernilai "/"
7. THE PWA_Plugin SHALL menghasilkan Web_App_Manifest dengan field `icons` yang memuat ikon berukuran 192x192 dan 512x512 pixel dalam format PNG dengan field `type` bernilai "image/png"
8. THE PWA_Plugin SHALL menghasilkan Web_App_Manifest dengan field `icons` yang memuat setidaknya satu ikon dengan field `purpose` bernilai "maskable" untuk tampilan adaptif di Android
9. THE PWA_Plugin SHALL menghasilkan Web_App_Manifest dengan field `scope` bernilai "/"

### Requirement 3: Service Worker dan Strategi Caching

**User Story:** Sebagai pengguna, saya ingin aplikasi memiliki strategi caching yang efisien, sehingga halaman dapat dimuat dengan cepat dan resource tersedia meskipun koneksi lambat.

#### Acceptance Criteria

1. WHEN service worker di-install, THE Service_Worker_PWA SHALL melakukan Precache terhadap semua asset statis yang terdaftar dalam build manifest (JS, CSS, HTML)
2. WHEN request ke halaman HTML dilakukan, THE Service_Worker_PWA SHALL menggunakan Cache_Strategy NetworkFirst dengan timeout 3 detik
3. IF timeout 3 detik pada request halaman HTML terlampaui dan cache tersedia, THEN THE Service_Worker_PWA SHALL menyajikan response dari cache sebagai fallback
4. WHEN request ke asset statis (JS, CSS) dilakukan, THE Service_Worker_PWA SHALL menggunakan Cache_Strategy CacheFirst dengan expiration 30 hari dan maxEntries 60
5. WHEN request ke Google Fonts dilakukan, THE Service_Worker_PWA SHALL menggunakan Cache_Strategy CacheFirst dengan expiration 365 hari dan maxEntries 30
6. WHEN request ke endpoint API (/api/*) dilakukan, THE Service_Worker_PWA SHALL menggunakan Cache_Strategy NetworkFirst dengan timeout 5 detik agar data terbaru selalu diprioritaskan
7. IF timeout pada request API terlampaui dan cache tersedia, THEN THE Service_Worker_PWA SHALL menyajikan response dari cache sebagai fallback
8. WHEN request ke resource gambar dilakukan, THE Service_Worker_PWA SHALL menggunakan Cache_Strategy CacheFirst dengan expiration 30 hari dan maxEntries 100

### Requirement 4: Offline Support

**User Story:** Sebagai pengguna, saya ingin melihat halaman fallback yang informatif ketika tidak ada koneksi internet, sehingga saya mengetahui status koneksi dan dapat mencoba lagi.

#### Acceptance Criteria

1. THE PWA_Plugin SHALL menyertakan Offline_Fallback sebagai bagian dari precache manifest
2. WHEN pengguna melakukan navigation request ke halaman yang tidak tersedia di cache dan tidak ada koneksi internet, THE Service_Worker_PWA SHALL menampilkan Offline_Fallback sebagai respons hanya untuk navigation request (bukan untuk request API, gambar, atau asset lainnya)
3. THE Offline_Fallback SHALL menampilkan nama aplikasi "GA Maintenance" dan pesan yang menginformasikan bahwa koneksi internet tidak tersedia
4. THE Offline_Fallback SHALL menampilkan tombol yang ketika diklik akan melakukan reload halaman saat ini menggunakan `window.location.reload()`
5. THE Offline_Fallback SHALL menggunakan styling inline dan tidak bergantung pada external resource (CSS, font, atau JavaScript eksternal) agar dapat ditampilkan sepenuhnya dalam kondisi offline
6. THE Offline_Fallback SHALL merupakan file HTML statis yang valid dan self-contained sehingga dapat di-render tanpa resource tambahan

### Requirement 5: Integrasi dengan Push Notification Service Worker

**User Story:** Sebagai developer, saya ingin service worker PWA dan push notification service worker dapat bekerja bersamaan tanpa konflik, sehingga kedua fungsionalitas berjalan dengan benar.

#### Acceptance Criteria

1. THE Service_Worker_PWA SHALL mengimpor file `browser-notification-sw.js` menggunakan opsi `importScripts` di konfigurasi Workbox sehingga event listener push notification terdaftar di service worker utama
2. WHEN push event diterima oleh Service_Worker_PWA, THE Service_Worker_PWA SHALL menampilkan notifikasi melalui handler yang terdaftar dari file `browser-notification-sw.js` dengan menampilkan title, body, dan icon sesuai payload yang diterima
3. WHEN notificationclick event diterima oleh Service_Worker_PWA, THE Service_Worker_PWA SHALL membuka atau memfokuskan window ke URL yang terdapat di `notification.data.url` melalui handler yang terdaftar dari file `browser-notification-sw.js`
4. THE Push_Notification_SW SHALL tetap berada di direktori `public/` sebagai file terpisah bernama `browser-notification-sw.js` yang di-import oleh service worker utama
5. WHEN Service_Worker_PWA di-update dan diaktifkan, THE Service_Worker_PWA SHALL tetap mempertahankan push subscription yang aktif sehingga client tidak perlu melakukan re-subscribe ke push notification service
6. IF file `browser-notification-sw.js` gagal di-import oleh Service_Worker_PWA, THEN THE Service_Worker_PWA SHALL tetap berfungsi untuk caching dan offline capability tanpa menghentikan lifecycle service worker

### Requirement 6: Install Prompt Handling

**User Story:** Sebagai pengguna, saya ingin mendapat prompt untuk menginstall aplikasi di perangkat saya, sehingga saya dapat mengakses aplikasi dengan cepat dari home screen.

#### Acceptance Criteria

1. WHEN browser mengirimkan event `beforeinstallprompt`, THE Install_Prompt SHALL menyimpan event tersebut dan menandai status install prompt sebagai tersedia
2. WHILE status install prompt tersedia dan aplikasi belum berjalan dalam mode standalone, WHEN halaman selesai dimuat, THE Install_Prompt SHALL menampilkan tombol atau banner install dalam waktu maksimal 1 detik setelah event tersimpan
3. WHEN pengguna mengklik tombol install, THE Install_Prompt SHALL memanggil method `prompt()` pada event `beforeinstallprompt` yang tersimpan dan menampilkan dialog instalasi browser
4. WHEN pengguna menerima instalasi (event `appinstalled` diterima), THE Install_Prompt SHALL menyembunyikan tombol install dan menghapus referensi event yang tersimpan
5. WHEN pengguna menolak instalasi (user memilih dismiss pada dialog prompt), THE Install_Prompt SHALL menyembunyikan tombol install dan tidak menampilkan kembali tombol install sampai pengguna menutup dan membuka kembali tab browser atau melakukan navigasi ulang ke aplikasi
6. WHILE aplikasi berjalan dalam mode standalone (dideteksi melalui media query `display-mode: standalone`), THE Install_Prompt SHALL tidak menampilkan tombol install
7. IF method `prompt()` dipanggil namun event `beforeinstallprompt` yang tersimpan sudah tidak valid atau bernilai null, THEN THE Install_Prompt SHALL menampilkan notifikasi toast yang menginformasikan bahwa instalasi tidak dapat dilakukan saat ini dan menyembunyikan tombol install

### Requirement 7: Update Notification

**User Story:** Sebagai pengguna, saya ingin mendapat notifikasi ketika versi baru aplikasi tersedia, sehingga saya dapat memperbarui aplikasi untuk mendapatkan fitur dan perbaikan terbaru.

#### Acceptance Criteria

1. WHEN service worker baru terdeteksi dalam state "installed" dan menunggu aktivasi, THE Update_Prompt SHALL menampilkan notifikasi kepada pengguna bahwa versi baru tersedia dalam waktu tidak lebih dari 3 detik setelah deteksi
2. THE Update_Prompt SHALL menampilkan tombol "Perbarui" yang memungkinkan pengguna mengaktifkan versi baru
3. THE Update_Prompt SHALL menampilkan tombol "Nanti" yang memungkinkan pengguna menunda pembaruan
4. WHEN pengguna mengklik tombol "Perbarui", THE Update_Prompt SHALL mengirim pesan `SKIP_WAITING` ke service worker yang menunggu dan melakukan reload halaman
5. WHEN pengguna mengklik tombol "Nanti", THE Update_Prompt SHALL menutup notifikasi tanpa melakukan pembaruan
6. THE Update_Prompt SHALL ditampilkan sebagai toast notification non-blocking yang tetap terlihat tanpa batas waktu (persistent) sampai pengguna mengklik salah satu tombol, dan tidak menutupi konten interaktif utama halaman serta tidak memblokir input pengguna pada elemen lain di halaman
7. IF pengguna telah mengklik "Nanti" dan service worker yang menunggu masih dalam state "installed", THEN THE Update_Prompt SHALL menampilkan kembali notifikasi pada navigasi halaman berikutnya atau pada page reload berikutnya
8. IF reload halaman gagal dilakukan setelah pengiriman pesan `SKIP_WAITING` dalam waktu 5 detik, THEN THE Update_Prompt SHALL menampilkan pesan error yang mengindikasikan pembaruan gagal dan menyarankan pengguna untuk melakukan reload halaman secara manual
