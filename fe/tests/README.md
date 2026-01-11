# Frontend Unit Tests

Dokumentasi untuk menjalankan dan memahami unit tests di frontend.

## Setup

```bash
cd fe
npm install
```

## Menjalankan Tests

| Perintah | Deskripsi |
|----------|-----------|
| `npm test` | Menjalankan tests dalam mode watch (otomatis re-run saat file berubah) |
| `npm run test:run` | Menjalankan tests sekali saja |
| `npm run test:coverage` | Menjalankan tests dengan laporan coverage |
| `npm run test:ui` | Menjalankan Vitest UI (interface browser) |

## Struktur Tests

```
fe/tests/
├── setup.js                          # Global test setup & mocks
├── README.md                         # Dokumentasi ini
└── unit/
    ├── helpers/
    │   ├── errorHelper.test.js       # HTTP error handling (6 tests)
    │   ├── format.test.js            # Format helpers (14 tests)
    │   └── permissionHelper.test.js  # Permission helpers (12 tests)
    └── stores/
        ├── auth.test.js              # Auth store (10 tests)
        ├── branch.test.js            # Branch store (8 tests)
        └── ticket.test.js            # Ticket store (13 tests)
```

## Detail Test Files

### Helpers

| File | Tests | Deskripsi |
|------|-------|-----------|
| `errorHelper.test.js` | 6 | Handle HTTP errors (401, 400, 422, 500, unknown) |
| `format.test.js` | 14 | Format currency, date, bytes, numbers |
| `permissionHelper.test.js` | 12 | `can()`, `canOneOf()`, `hasRole()` functions |

### Stores

| File | Tests | Deskripsi |
|------|-------|-----------|
| `auth.test.js` | 10 | Login, logout, checkAuth, updateProfile |
| `branch.test.js` | 8 | CRUD operations untuk branches |
| `ticket.test.js` | 13 | CRUD + replies + close tickets |

## Menulis Test Baru

### Contoh Test untuk Store

```javascript
import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useMyStore } from '@/stores/myStore'

// Mock axios
vi.mock('@/plugins/axios', () => ({
    axiosInstance: {
        get: vi.fn(),
        post: vi.fn(),
    },
}))

import { axiosInstance } from '@/plugins/axios'

describe('My Store', () => {
    beforeEach(() => {
        setActivePinia(createPinia())
        vi.clearAllMocks()
    })

    it('should fetch data', async () => {
        const store = useMyStore()
        
        axiosInstance.get.mockResolvedValue({
            data: { data: [{ id: 1, name: 'Test' }] }
        })

        await store.fetchData()

        expect(axiosInstance.get).toHaveBeenCalledWith('/api/endpoint')
        expect(store.items).toHaveLength(1)
    })
})
```

### Contoh Test untuk Helper

```javascript
import { describe, it, expect } from 'vitest'
import { myHelper } from '@/helpers/myHelper'

describe('myHelper', () => {
    it('should return correct value', () => {
        const result = myHelper('input')
        expect(result).toBe('expected output')
    })
})
```

## Tips

1. **Gunakan `vi.clearAllMocks()`** di `beforeEach` untuk reset semua mock
2. **Mock axios** untuk menghindari HTTP calls asli
3. **Gunakan `setActivePinia(createPinia())`** untuk initialize Pinia stores
4. **Test edge cases** seperti error handling dan null values

## Troubleshooting

### Tests tidak jalan
```bash
# Clear cache dan reinstall
rm -rf node_modules
npm install
npm test
```

### Mock tidak bekerja
- Pastikan path import di mock sama persis dengan di file asli
- Gunakan `vi.mock()` sebelum import modules

### Coverage rendah
```bash
# Lihat detail coverage
npm run test:coverage
# Buka coverage/index.html di browser
```
