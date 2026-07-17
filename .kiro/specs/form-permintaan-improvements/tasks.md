# Implementation Plan: Form Permintaan Improvements

## Overview

Implement three improvements to the Form Permintaan module: (1) PDF/Excel export from admin list page with active filters forwarded, (2) status simplification removing "completed" and making "approved"/"rejected" read-only badges, and (3) clickable RouterLink to related ticket on the detail page. Implementation follows the existing TicketExport/TicketController export pattern using DomPDF and PhpSpreadsheet.

## Tasks

- [x] 1. Backend Export Infrastructure
  - [x] 1.1 Create FormPermintaanExport class for Excel export
    - Create `app/Exports/FormPermintaanExport.php` following the `TicketExport` pattern
    - Use PhpSpreadsheet directly with `Spreadsheet`, `Xlsx` writer, and `StreamedResponse`
    - Implement `getRecords()` method with same filtering logic as `getAllPaginated` (search, branch_id, request_type, status, start_date, end_date) without pagination
    - Headers: No, No Permintaan, Tanggal, Pemohon, Outlet, Jenis Permintaan, Prioritas, Status
    - Apply header styling (bold, colored background) matching TicketExport
    - Return `StreamedResponse` with correct XLSX content-type and filename `form_permintaan_{timestamp}.xlsx`
    - _Requirements: 2.1, 2.2, 2.3, 4.3, 4.5_

  - [x] 1.2 Create Blade template for PDF export
    - Create `resources/views/form-permintaan/export-list.blade.php`
    - Landscape A4 layout with table columns: No, No Permintaan, Tanggal, Pemohon, Outlet, Jenis Permintaan, Prioritas, Status
    - Style matching the existing `exports/tickets-pdf.blade.php` pattern
    - Render `$records` collection passed from controller
    - Handle empty records gracefully (show headers only)
    - _Requirements: 1.1, 4.5_

  - [x] 1.3 Add exportPdf and exportExcel methods to FormPermintaanController
    - Add `exportPdf(Request $request)` method: validate optional filter params, build query using same filtering as `getAllPaginated` without pagination, use `Pdf::loadView('form-permintaan.export-list')` with landscape A4, return `$pdf->download()` with filename `form_permintaan_{timestamp}.pdf`
    - Add `exportExcel(Request $request)` method: validate same filter params, instantiate `FormPermintaanExport` with filters array, return `$export->download()`
    - Add shared `getFilteredRecords(array $filters)` private method to avoid code duplication
    - Update `middleware()` to include `exportPdf` and `exportExcel` in the `form-permintaan-list` permission only array
    - _Requirements: 1.1, 1.2, 2.1, 2.2, 4.1, 4.2, 4.3, 4.4_

  - [x] 1.4 Register export routes in api.php
    - Add `Route::get('form-permintaan/export/pdf', ...)` and `Route::get('form-permintaan/export/excel', ...)` routes
    - Place these routes **before** the `form-permintaan/{id}` route to avoid route parameter conflicts
    - _Requirements: 4.1, 4.2_

- [x] 2. Checkpoint - Verify backend export
  - Ensure export endpoints are reachable and controller methods compile without errors, ask the user if questions arise.

- [x] 3. Frontend Export Feature on List Page
  - [x] 3.1 Add export dropdown UI and logic to FormPermintaanList.vue
    - Import `Download`, `FileSpreadsheet` icons from lucide-vue-next
    - Add `showExportMenu` and `exportLoading` refs
    - Add `buildExportParams()` function that collects active filter values (status, branchId→branch_id, requestType→request_type, startDate→start_date, endDate→end_date, searchQuery→search)
    - Add `handleExportPdf()` async function: close menu, set loading, axios GET `/form-permintaan/export/pdf` with `params: buildExportParams()` and `responseType: 'blob'`, create blob URL, trigger download, cleanup; catch error → set `formPermintaanStore.error = 'Gagal export PDF'`
    - Add `handleExportExcel()` async function: same pattern with `/form-permintaan/export/excel` endpoint and `.xlsx` extension
    - Add click-outside handler to close export dropdown
    - Add Export button in the header area alongside "Buat Form Permintaan" and "Filter" buttons, with dropdown showing "Export PDF" and "Export Excel" options
    - Disable export button and show loading indicator while export is in progress
    - _Requirements: 1.1, 1.3, 1.4, 1.5, 2.1, 2.3, 2.4, 2.5, 3.1, 3.2, 3.3_

- [x] 4. Status Column Simplification on List Page
  - [x] 4.1 Update status rendering logic in FormPermintaanList.vue
    - Remove `completed` from `statusLabels` object (keep: progress, pending, approved, rejected)
    - Update `statusOptions` to exclude "Completed" from filter dropdown
    - Modify `#cell-status` template: render read-only badge `<span>` when value is `'approved'` or `'rejected'` (or user lacks `form-permintaan-confirm` permission); render `<select>` dropdown only when value is `'progress'` or `'pending'` and user has permission
    - Dropdown options: Progress, Approved, Rejected (no Completed, no Pending since pending items would have their own options)
    - Badge styling: `bg-blue-100 text-blue-800` for approved, `bg-red-100 text-red-800` for rejected, matching ticket list badge pattern
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 6.1, 6.2_

- [x] 5. Ticket Hyperlink on Detail Page
  - [x] 5.1 Add RouterLink for ticket code in FormPermintaanDetail.vue
    - In the "Ticket Terkait" section, replace plain text `<p>{{ formData.ticket.code || "-" }}</p>` with a `<RouterLink>` element
    - Use `:to="{ name: \`${routePrefix}.ticket.detail\`, params: { id: formData.ticket.id } }"` leveraging the existing `routePrefix` computed property
    - Style with `class="text-blue-600 hover:text-blue-800 hover:underline font-medium"` for visual link distinction
    - Keep the ticket description text below the link unchanged
    - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5_

- [x] 6. Final Checkpoint
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- The export pattern follows the existing TicketList.vue + TicketController implementation exactly
- Export routes must be registered before `form-permintaan/{id}` to prevent route parameter conflicts
- Status validation on backend `index()` method still accepts `completed` for backwards compatibility but frontend removes it from options
- The `routePrefix` computed property already exists in FormPermintaanDetail.vue and resolves correctly for both admin/app contexts

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1", "1.2"] },
    { "id": 1, "tasks": ["1.3", "4.1", "5.1"] },
    { "id": 2, "tasks": ["1.4"] },
    { "id": 3, "tasks": ["3.1"] }
  ]
}
```
