# Implementation Plan: Form Permintaan

## Overview

Implement the Form Permintaan (Request Form) feature following existing helpdesk patterns. The backend uses Laravel with Repository pattern, Spatie permissions, and FormRequest validation. The frontend uses Vue 3 with Pinia stores and a multi-step wizard form (same as TicketCreate). The implementation creates 3 database tables, backend API endpoints, and 3 Vue views with full CRUD support.

## Tasks

- [-] 1. Database migrations and Eloquent models
  - [x] 1.1 Create migration for `form_permintaan` table
    - Create migration file with columns: id, user_id (FK to users, restrictOnDelete), branch_id (FK to branches, restrictOnDelete), request_number (string max:50, unique), date, priority (string), request_type (string max:100), fa_number (string nullable max:100), reason (text nullable), status (string default:'pending'), timestamps
    - Add index on user_id and branch_id columns
    - _Requirements: 8.1, 8.4, 8.5, 8.9_

  - [x] 1.2 Create migration for `form_permintaan_items` table
    - Create migration file with columns: id, form_permintaan_id (FK to form_permintaan, cascadeOnDelete), product_description (string max:255), quantity (integer), uom (string max:50), notes (text nullable), timestamps
    - _Requirements: 8.2, 8.6_

  - [x] 1.3 Create migration for `form_permintaan_attachments` table
    - Create migration file with columns: id, form_permintaan_id (FK to form_permintaan, cascadeOnDelete), file_path (string max:255), file_name (string max:255), file_type (string max:100), file_size (unsignedInteger), timestamps
    - _Requirements: 8.3, 8.7_

  - [x] 1.4 Create FormPermintaan Eloquent model
    - Create `app/Models/FormPermintaan.php` with $table = 'form_permintaan', fillable fields, and relationships: user() BelongsTo, branch() BelongsTo, items() HasMany, attachments() HasMany
    - _Requirements: 8.1, 8.4, 8.5, 8.6, 8.7_

  - [x] 1.5 Create FormPermintaanItem Eloquent model
    - Create `app/Models/FormPermintaanItem.php` with fillable fields and formPermintaan() BelongsTo relationship
    - _Requirements: 8.2, 8.6_

  - [x] 1.6 Create FormPermintaanAttachment Eloquent model
    - Create `app/Models/FormPermintaanAttachment.php` with fillable fields and formPermintaan() BelongsTo relationship
    - _Requirements: 8.3, 8.7_

- [x] 2. Checkpoint - Run migrations and verify models
  - Ensure all tests pass, ask the user if questions arise.

- [x] 3. Backend repository, request validation, and resources
  - [x] 3.1 Create FormPermintaanRepositoryInterface
    - Create `app/Interfaces/FormPermintaanRepositoryInterface.php` with methods: create(array $data): FormPermintaan, getAllPaginated(?string $search, int $rowPerPage): LengthAwarePaginator, getById(string $id): FormPermintaan, addAttachment(string $formPermintaanId, array $fileData): FormPermintaanAttachment
    - _Requirements: 7.1, 7.2, 7.3, 7.5_

  - [x] 3.2 Create FormPermintaanRepository implementation
    - Create `app/Repositories/FormPermintaanRepository.php` implementing FormPermintaanRepositoryInterface
    - Implement create() with DB::transaction wrapping header + items creation + atomic request number generation using lockForUpdate (same pattern as TicketRepository.generateTicketCode)
    - Implement getAllPaginated() scoped to authenticated user with search on request_number, ordered by date desc
    - Implement getById() with eager loading of user, branch, items, attachments
    - Implement addAttachment() to store file metadata
    - Request number format: {DD}/{OUTLET_CODE}/FP{YY}/{M}/{YYYY} with sequence reset per month per outlet
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 7.5, 8.8_

  - [x] 3.3 Create FormPermintaanStoreRequest
    - Create `app/Http/Requests/FormPermintaanStoreRequest.php` with validation rules: branch_id (required, exists:branches,id), priority (required, in:low,medium,high,urgent), request_type (required, in:pembelian_produk_baru,penggantian_produk_lama,servis,penggantian_part,jasa), fa_number (required_if request_type is penggantian_produk_lama/servis/penggantian_part, nullable, string, max:100), reason (required_if request_type is pembelian_produk_baru, nullable, string), items (required, array, min:1, max:20), items.*.product_description (required, string, max:255), items.*.quantity (required, integer, min:1, max:9999), items.*.uom (nullable, string, max:50), items.*.notes (nullable, string)
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7, 7.6_

  - [x] 3.4 Create FormPermintaanResource and FormPermintaanItemResource and FormPermintaanAttachmentResource
    - Create `app/Http/Resources/FormPermintaanResource.php` returning id, request_number, date, priority, request_type, fa_number, reason, status, created_at, user (whenLoaded), branch (whenLoaded), items (whenLoaded), attachments (whenLoaded)
    - Create `app/Http/Resources/FormPermintaanItemResource.php` returning id, product_description, quantity, uom, notes
    - Create `app/Http/Resources/FormPermintaanAttachmentResource.php` returning id, file_path, file_name, file_type, file_size, url (full storage URL)
    - _Requirements: 7.1, 7.2, 7.3_

  - [x] 3.5 Register FormPermintaanRepositoryInterface binding in RepositoryServiceProvider
    - Add `$this->app->bind(FormPermintaanRepositoryInterface::class, FormPermintaanRepository::class);` to RepositoryServiceProvider register() method
    - _Requirements: 7.5_

- [x] 4. Backend controller and routes
  - [x] 4.1 Create FormPermintaanController
    - Create `app/Http/Controllers/FormPermintaanController.php` implementing HasMiddleware
    - Implement middleware() with Spatie PermissionMiddleware: 'form-permintaan-create' for store/uploadAttachment, 'form-permintaan-list' for index/show
    - Implement store(FormPermintaanStoreRequest $request) — calls repository create(), returns 201 with FormPermintaanResource
    - Implement index(Request $request) — validates row_per_page (required int) and search (nullable string), calls repository getAllPaginated(), returns PaginateResource
    - Implement show(string $id) — calls repository getById(), returns FormPermintaanResource, handles ModelNotFoundException with 404
    - Implement uploadAttachment(Request $request, string $id) — validates file (required, mimes:jpg,jpeg,png,pdf, max:10240), stores to 'form-permintaan/' disk, calls repository addAttachment(), returns 201
    - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.6, 7.7, 7.8, 7.9_

  - [x] 4.2 Register API routes for form-permintaan
    - Add routes inside the auth:sanctum middleware group in `routes/api.php`:
      - `Route::get('form-permintaan', [FormPermintaanController::class, 'index']);`
      - `Route::post('form-permintaan', [FormPermintaanController::class, 'store']);`
      - `Route::get('form-permintaan/{id}', [FormPermintaanController::class, 'show']);`
      - `Route::post('form-permintaan/{id}/attachments', [FormPermintaanController::class, 'uploadAttachment']);`
    - _Requirements: 7.1, 7.2, 7.3, 7.7_

  - [x] 4.3 Add form-permintaan permissions to PermissionSeeder
    - Add 'form-permintaan-create' and 'form-permintaan-list' permissions to the PermissionSeeder
    - _Requirements: 7.7, 9.1_

- [ ] 5. Checkpoint - Verify backend API
  - Ensure all tests pass, ask the user if questions arise.

- [x] 6. Frontend Pinia store
  - [x] 6.1 Create formPermintaan.js Pinia store
    - Create `fe/src/stores/formPermintaan.js` following the ticket.js pattern
    - State: forms (array), meta (pagination object), form (single record), loading, error, success
    - Actions: createFormPermintaan(payload) → POST /form-permintaan, fetchFormPermintaanPaginated(params) → GET /form-permintaan, fetchFormPermintaan(id) → GET /form-permintaan/{id}, uploadAttachment(formId, file) → POST /form-permintaan/{formId}/attachments
    - Use axiosInstance, handleError, useToast for success/error notifications
    - _Requirements: 1.1, 3.1, 5.1, 6.1_

- [-] 7. Frontend views
  - [x] 7.1 Create FormPermintaanCreate.vue (multi-step wizard)
    - Create `fe/src/views/app/FormPermintaanCreate.vue` as a 2-step wizard form (same pattern as TicketCreate.vue)
    - Step 1 (Form Information): Tanggal (auto-filled current date, readonly), User (auto-filled from auth store, readonly), Outlet (dropdown from /branches API using branch store), Prioritas (selector: Low, Medium, High, Urgent), Jenis Permintaan (selector: Pembelian produk baru, Penggantian produk lama, Servis, Penggantian part, Jasa)
    - Conditional fields: show FA Number input when request_type is penggantian_produk_lama/servis/penggantian_part; show Alasan input when request_type is pembelian_produk_baru
    - Line items table: No (auto), Deskripsi Produk (required), QTY (integer min:1 max:9999, required), UoM, Catatan; add/remove row buttons; max 20 items
    - Client-side validation before proceeding to Step 2
    - Step 2 (Attachments): File upload area accepting JPG, PNG, PDF; max 10 files; max 10MB per file; image preview thumbnails; remove file button; skip allowed
    - On Step 1 submit: call createFormPermintaan() from store; on Step 2: upload each file via uploadAttachment(); on completion redirect to detail or list
    - Use Tailwind CSS 4 for styling, Lucide icons for UI elements
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 1.7, 1.8, 1.9, 1.10, 1.11, 1.12, 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 3.8, 3.9, 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7, 4.8, 4.9_

  - [x] 7.2 Create FormPermintaanList.vue (paginated table)
    - Create `fe/src/views/app/FormPermintaanList.vue` with a paginated table
    - Columns: No. Permintaan, Tanggal, Outlet, Jenis Permintaan, Prioritas, Status
    - Sorted by Tanggal descending (newest first), 10 per page with pagination controls
    - Only shows forms belonging to logged-in user (handled by API)
    - Click row to navigate to detail view
    - Empty state message when no records exist
    - Error state when API fails to load
    - "Buat Form Permintaan" button linking to create page
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 5.6_

  - [x] 7.3 Create FormPermintaanDetail.vue (read-only detail)
    - Create `fe/src/views/app/FormPermintaanDetail.vue` displaying all form details
    - Header section: No. Permintaan, Tanggal, User, Outlet, Prioritas, Jenis Permintaan, and conditionally FA Number or Alasan based on request_type
    - Items table: No, Deskripsi Produk, QTY, UoM, Catatan
    - Attachments section: clickable thumbnails for images, clickable file name links for PDFs, preview dialog on click
    - Error handling for non-existent or unauthorized access (redirect to error page)
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5_

- [x] 8. Frontend routing and navigation
  - [x] 8.1 Register form-permintaan routes in Vue Router
    - Add routes under the App layout in `fe/src/router/index.js`:
      - `/form-permintaan` → name: 'app.form-permintaan', component: FormPermintaanList, meta: { requiresAuth: true, title: 'Form Permintaan', permission: 'form-permintaan-list' }
      - `/form-permintaan/create` → name: 'app.form-permintaan.create', component: FormPermintaanCreate, meta: { requiresAuth: true, title: 'Buat Form Permintaan', permission: 'form-permintaan-create' }
      - `/form-permintaan/:id` → name: 'app.form-permintaan.detail', component: FormPermintaanDetail, meta: { requiresAuth: true, title: 'Detail Form Permintaan', permission: 'form-permintaan-list' }
    - Use lazy loading with `() => import(...)` pattern
    - _Requirements: 9.1, 9.3, 9.4_

  - [x] 8.2 Add "Form Permintaan" navigation link in App layout sidebar
    - Add navigation item labeled "Form Permintaan" in the App layout sidebar
    - Link to `app.form-permintaan` route
    - Show active state when current route name starts with 'app.form-permintaan'
    - Hide link when user does not have 'form-permintaan-list' permission
    - Use appropriate Lucide icon (e.g., ClipboardList or FileText)
    - _Requirements: 9.2, 9.5_

- [x] 9. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP (none in this plan since no property-based tests are applicable)
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- The backend follows the exact same patterns as the Ticket module (Repository pattern, FormRequest, Resource, Controller with HasMiddleware)
- The frontend follows the TicketCreate.vue multi-step wizard pattern
- Attachment uploads are per-file after the main form record is created (same as ticket attachments)
- Request number generation uses lockForUpdate within a transaction for atomicity

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1", "1.2", "1.3"] },
    { "id": 1, "tasks": ["1.4", "1.5", "1.6"] },
    { "id": 2, "tasks": ["3.1", "3.3", "3.4"] },
    { "id": 3, "tasks": ["3.2", "3.5"] },
    { "id": 4, "tasks": ["4.1", "4.3"] },
    { "id": 5, "tasks": ["4.2"] },
    { "id": 6, "tasks": ["6.1"] },
    { "id": 7, "tasks": ["7.1", "7.2", "7.3"] },
    { "id": 8, "tasks": ["8.1", "8.2"] }
  ]
}
```
0--