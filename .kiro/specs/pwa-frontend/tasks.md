# Implementation Plan: PWA Frontend

## Overview

Implementasi Progressive Web App (PWA) pada aplikasi "GA Maintenance" menggunakan `vite-plugin-pwa` dengan strategi `generateSW` dan `registerType: 'prompt'`. Implementasi mencakup konfigurasi plugin, aset PWA, offline fallback, composable untuk install/update flow, komponen UI, dan integrasi di App.vue.

## Tasks

- [x] 1. Install vite-plugin-pwa dan konfigurasi dasar
  - [x] 1.1 Install vite-plugin-pwa dan konfigurasi di vite.config.js
    - Install `vite-plugin-pwa` sebagai devDependency
    - Import `VitePWA` dari `vite-plugin-pwa` di `vite.config.js`
    - Tambahkan `VitePWA({...})` ke array plugins bersama `vue()`
    - Konfigurasi `registerType: 'prompt'` dan `strategies: 'generateSW'`
    - Konfigurasi `workbox.importScripts: ['/browser-notification-sw.js']`
    - Konfigurasi `workbox.navigateFallback: '/offline.html'`
    - Konfigurasi `workbox.navigateFallbackDenylist: [/^\/api\//]`
    - Tambahkan runtime caching rules sesuai design:
      - Static assets (JS/CSS): CacheFirst, maxEntries 60, maxAge 30 hari
      - Google Fonts: CacheFirst, maxEntries 30, maxAge 365 hari
      - API (/api/*): NetworkFirst, timeout 5 detik, maxEntries 100, maxAge 7 hari
      - Images: CacheFirst, maxEntries 100, maxAge 30 hari
    - Konfigurasi manifest object dengan semua field (name, short_name, description, theme_color, background_color, display, start_url, scope, icons)
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 2.7, 2.8, 2.9, 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 3.8, 5.1_

- [x] 2. Buat aset PWA dan offline fallback
  - [x] 2.1 Buat PWA icons di public/
    - Buat file `public/pwa-192x192.png` (placeholder 192x192 PNG)
    - Buat file `public/pwa-512x512.png` (placeholder 512x512 PNG)
    - Buat file `public/pwa-512x512-maskable.png` (placeholder maskable icon)
    - _Requirements: 2.7, 2.8_

  - [x] 2.2 Buat offline.html fallback page
    - Buat file `public/offline.html` sebagai HTML statis self-contained
    - Gunakan inline CSS saja (tidak ada external resource)
    - Tampilkan nama "GA Maintenance" dan pesan offline
    - Tambahkan tombol reload yang memanggil `window.location.reload()`
    - Pastikan valid HTML5 dan accessible
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6_

- [x] 3. Implementasi composables PWA
  - [x] 3.1 Buat composable usePwaInstall
    - Buat file `src/composables/usePwaInstall.js`
    - Implementasi capture event `beforeinstallprompt` dan simpan referensi
    - Implementasi deteksi standalone mode via `matchMedia('(display-mode: standalone)')`
    - Implementasi `promptInstall()` yang memanggil `prompt()` pada saved event
    - Implementasi `dismissInstall()` untuk menyembunyikan prompt
    - Listen event `appinstalled` untuk cleanup state
    - Tampilkan toast error via `vue-sonner` jika prompt dipanggil saat event null
    - Export reactive state: `isInstallable`, `isStandalone`, `promptInstall`, `dismissInstall`
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5, 6.6, 6.7_

  - [x] 3.2 Buat composable usePwaUpdate
    - Buat file `src/composables/usePwaUpdate.js`
    - Import dan gunakan `useRegisterSW` dari `virtual:pwa-register/vue`
    - Implementasi `updateApp()` yang mengirim `SKIP_WAITING` dan reload halaman
    - Implementasi `dismissUpdate()` yang set `updateDismissed` tanpa side effect
    - Implementasi timeout 5 detik untuk reload gagal dengan toast error
    - Reset `updateDismissed` pada route change via `router.afterEach`
    - Export reactive state: `needRefresh`, `offlineReady`, `updateApp`, `dismissUpdate`, `updateDismissed`
    - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5, 7.6, 7.7, 7.8_

- [x] 4. Checkpoint - Verifikasi composables
  - Ensure all tests pass, ask the user if questions arise.

- [x] 5. Implementasi komponen UI PWA
  - [x] 5.1 Buat komponen PwaInstallPrompt.vue
    - Buat file `src/components/app/PwaInstallPrompt.vue`
    - Gunakan `usePwaInstall` composable
    - Tampilkan banner/tombol install hanya jika `isInstallable && !isStandalone`
    - Tombol install memanggil `promptInstall()`
    - Tombol dismiss memanggil `dismissInstall()`
    - Styling menggunakan Tailwind CSS 4 classes
    - Pastikan accessible (role, aria-label)
    - _Requirements: 6.2, 6.3, 6.4, 6.5, 6.6_

  - [x] 5.2 Buat komponen PwaUpdatePrompt.vue
    - Buat file `src/components/app/PwaUpdatePrompt.vue`
    - Gunakan `usePwaUpdate` composable
    - Tampilkan toast persistent jika `needRefresh && !updateDismissed`
    - Tombol "Perbarui" memanggil `updateApp()`
    - Tombol "Nanti" memanggil `dismissUpdate()`
    - Non-blocking positioning (tidak menutupi konten interaktif)
    - Pastikan accessible (`role="alert"`, aria attributes)
    - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5, 7.6_

  - [x] 5.3 Integrasikan komponen PWA di App.vue
    - Import `PwaInstallPrompt` dan `PwaUpdatePrompt` di `App.vue`
    - Tambahkan kedua komponen di template setelah `<RouterView />` dan `<ToastContainer />`
    - _Requirements: 6.2, 7.1_

- [x] 6. Checkpoint - Verifikasi integrasi UI
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 7. Unit tests
  - [ ]* 7.1 Write unit tests untuk konfigurasi PWA
    - Buat file `tests/unit/pwa-config.spec.js`
    - Test manifest fields (name, short_name, description, colors, icons, display, scope, start_url)
    - Test runtime caching rules (strategies, timeout, expiration)
    - Test workbox config (importScripts, navigateFallback, navigateFallbackDenylist)
    - _Requirements: 1.1-1.5, 2.1-2.9, 3.1-3.8, 5.1_

  - [ ]* 7.2 Write unit tests untuk offline fallback
    - Buat file `tests/unit/pwa-offline.spec.js`
    - Verifikasi HTML berisi "GA Maintenance" dan pesan offline
    - Verifikasi ada tombol reload
    - Verifikasi menggunakan inline styling saja (tidak ada external resources)
    - _Requirements: 4.1-4.6_

  - [ ]* 7.3 Write unit tests untuk usePwaInstall
    - Buat file `tests/unit/usePwaInstall.spec.js`
    - Test event capture mengubah `isInstallable` ke true
    - Test `promptInstall()` saat event null menampilkan toast error
    - Test `dismissInstall()` mengubah state
    - Test `appinstalled` event cleanup
    - Test standalone detection
    - _Requirements: 6.1-6.7_

  - [ ]* 7.4 Write unit tests untuk usePwaUpdate
    - Buat file `tests/unit/usePwaUpdate.spec.js`
    - Test `updateApp()` memanggil updateServiceWorker
    - Test `dismissUpdate()` set updateDismissed tanpa side effect
    - Test reload timeout menampilkan toast error
    - Test route change me-reset updateDismissed
    - _Requirements: 7.1-7.8_

- [ ] 8. Property-based tests
  - [ ]* 8.1 Write property test untuk install prompt event capture
    - Buat file `tests/properties/pwa-install.property.spec.js`
    - **Property 1: Install prompt event capture mengubah state**
    - Generate random BeforeInstallPromptEvent-like objects, verify `isInstallable` menjadi true dan referensi event tersimpan
    - Minimum 100 iterasi
    - **Validates: Requirements 6.1**

  - [ ]* 8.2 Write property test untuk visibilitas tombol install
    - **Property 2: Visibilitas tombol install adalah fungsi dari state**
    - Generate random boolean combinations (isInstallable, isStandalone), verify tombol visible iff `isInstallable === true && isStandalone === false`
    - Minimum 100 iterasi
    - **Validates: Requirements 6.2, 6.6**

  - [ ]* 8.3 Write property test untuk appinstalled cleanup
    - **Property 3: Event appinstalled membersihkan semua state install**
    - Generate random states dengan non-null deferredPrompt, fire appinstalled, verify `isInstallable === false` dan `deferredPrompt === null`
    - Minimum 100 iterasi
    - **Validates: Requirements 6.4**

  - [ ]* 8.4 Write property test untuk dismiss update
    - Buat file `tests/properties/pwa-update.property.spec.js`
    - **Property 4: Dismiss update tidak memicu side effect**
    - Generate random states dengan needRefresh=true, call dismissUpdate, verify `updateDismissed === true` tanpa memanggil updateServiceWorker dan tanpa reload
    - Minimum 100 iterasi
    - **Validates: Requirements 7.5**

  - [ ]* 8.5 Write property test untuk route navigation reset
    - **Property 5: Navigasi route me-reset state dismissed**
    - Generate random states dengan (updateDismissed=true, needRefresh=true), simulate route change, verify `updateDismissed === false`
    - Minimum 100 iterasi
    - **Validates: Requirements 7.7**

- [ ] 9. Integration/build tests
  - [ ]* 9.1 Write integration test untuk build output
    - Buat file `tests/integration/pwa-build.spec.js`
    - Jalankan `vite build` dan verifikasi `dist/sw.js` dan `dist/manifest.webmanifest` ada
    - Verifikasi manifest.webmanifest berisi field yang benar
    - _Requirements: 1.3, 2.1-2.9_

- [x] 10. Final checkpoint - Verifikasi semua tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property tests validate universal correctness properties from the design document
- Unit tests validate specific examples and edge cases
- PWA icons (task 2.1) menggunakan placeholder PNG — ganti dengan ikon branded sebenarnya saat tersedia
- File `browser-notification-sw.js` tidak dimodifikasi, hanya di-import oleh service worker PWA via `importScripts`
- Semua composable mengikuti pattern existing (`useBrowserNotifications.js`) untuk konsistensi

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1", "2.1", "2.2"] },
    { "id": 1, "tasks": ["3.1", "3.2"] },
    { "id": 2, "tasks": ["5.1", "5.2"] },
    { "id": 3, "tasks": ["5.3"] },
    { "id": 4, "tasks": ["7.1", "7.2", "7.3", "7.4"] },
    { "id": 5, "tasks": ["8.1", "8.2", "8.3", "8.4", "8.5"] },
    { "id": 6, "tasks": ["9.1"] }
  ]
}
```
