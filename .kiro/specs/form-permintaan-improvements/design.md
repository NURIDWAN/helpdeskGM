# Design Document: Form Permintaan Improvements

## Overview

This feature adds three improvements to the Form Permintaan module:
1. **List Export** — PDF and Excel export from the admin list page, forwarding active filters
2. **Status Simplification** — Remove "completed" status, make "approved" final with read-only badge rendering
3. **Ticket Hyperlink** — Clickable RouterLink to related ticket on the detail page

The implementation follows the existing ticket export pattern exactly: `buildExportParams()` on the frontend, axios GET with `responseType: 'blob'`, and backend controller methods that use DomPDF (PDF) and PhpSpreadsheet (Excel).

## Architecture

The changes span three layers:
- **Backend (Laravel)**: New export controller methods + FormPermintaanExport class + Blade PDF template + routes
- **Frontend (Vue 3)**: Export dropdown UI + status column logic changes in FormPermintaanList.vue, RouterLink in FormPermintaanDetail.vue
- **No database changes** — uses existing schema and data

---

## Components and Interfaces

### Backend Components

#### 1. FormPermintaanController — Export Methods

Two new methods added to the existing `FormPermintaanController`:

```php
// app/Http/Controllers/FormPermintaanController.php

public function exportPdf(Request $request)
{
    // Validate optional filter params
    $request->validate([
        'branch_id' => 'nullable|exists:branches,id',
        'request_type' => 'nullable|in:pembelian_produk_baru,penggantian_produk_lama,servis,penggantian_part,jasa',
        'status' => 'nullable|in:progress,pending,approved,rejected',
        'start_date' => 'nullable|date',
        'end_date' => 'nullable|date|after_or_equal:start_date',
        'search' => 'nullable|string',
    ]);

    $filters = [
        'search' => $request->search,
        'branch_id' => $request->branch_id,
        'request_type' => $request->request_type,
        'status' => $request->status,
        'start_date' => $request->start_date,
        'end_date' => $request->end_date,
    ];

    $records = $this->getFilteredRecords($filters);

    $pdf = Pdf::loadView('form-permintaan.export-list', ['records' => $records]);
    $pdf->setPaper('a4', 'landscape');

    $filename = 'form_permintaan_' . now()->format('Y-m-d_His') . '.pdf';
    return $pdf->download($filename);
}

public function exportExcel(Request $request)
{
    // Same validation as exportPdf
    $filters = [
        'search' => $request->search,
        'branch_id' => $request->branch_id,
        'request_type' => $request->request_type,
        'status' => $request->status,
        'start_date' => $request->start_date,
        'end_date' => $request->end_date,
    ];

    $export = new FormPermintaanExport($filters);
    return $export->download();
}
```

#### 2. FormPermintaanExport Class

New export class following the `TicketExport` pattern using PhpSpreadsheet directly:

```php
// app/Exports/FormPermintaanExport.php

namespace App\Exports;

use App\Models\FormPermintaan;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FormPermintaanExport
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function download(): StreamedResponse
    {
        $records = $this->getRecords();
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Form Permintaan');

        // Headers: No, No Permintaan, Tanggal, Pemohon, Outlet, Jenis Permintaan, Prioritas, Status
        // ... (styled like TicketExport)

        $filename = 'form_permintaan_' . now()->format('Y-m-d_His') . '.xlsx';
        $writer = new Xlsx($spreadsheet);

        return new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    protected function getRecords()
    {
        $query = FormPermintaan::with(['user', 'branch', 'ticket'])
            ->orderBy('date', 'desc');

        // Apply same filtering logic as getAllPaginated (without pagination)
        if (!empty($this->filters['search'])) {
            $query->where('request_number', 'like', '%' . $this->filters['search'] . '%');
        }
        if (!empty($this->filters['branch_id'])) {
            $query->where('branch_id', $this->filters['branch_id']);
        }
        if (!empty($this->filters['request_type'])) {
            $query->where('request_type', $this->filters['request_type']);
        }
        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }
        if (!empty($this->filters['start_date'])) {
            $query->whereDate('date', '>=', $this->filters['start_date']);
        }
        if (!empty($this->filters['end_date'])) {
            $query->whereDate('date', '<=', $this->filters['end_date']);
        }

        return $query->get();
    }
}
```

#### 3. Blade View for PDF Export

```php
// resources/views/form-permintaan/export-list.blade.php

// Landscape A4 table layout with columns:
// No | No Permintaan | Tanggal | Pemohon | Outlet | Jenis Permintaan | Prioritas | Status
// Renders $records collection passed from controller
```

#### 4. Routes

```php
// routes/api.php (inside authenticated middleware group)
Route::get('form-permintaan/export/pdf', [FormPermintaanController::class, 'exportPdf']);
Route::get('form-permintaan/export/excel', [FormPermintaanController::class, 'exportExcel']);
```

These routes must be defined **before** the `form-permintaan/{id}` route to avoid route parameter conflicts.

#### 5. Middleware/Permission

Export endpoints use the existing `form-permintaan-list` permission, added to the controller's `middleware()` method:

```php
new Middleware(PermissionMiddleware::using(['form-permintaan-list']), only: ['index', 'show', 'downloadPdf', 'exportPdf', 'exportExcel']),
```

---

### Frontend Components

#### 1. FormPermintaanList.vue — Export Feature

Follows the exact TicketList.vue export pattern:

```javascript
// New refs
const showExportMenu = ref(false);
const exportLoading = ref(false);

// Build export params from active filters (same pattern as TicketList)
const buildExportParams = () => {
  const params = {};
  if (filters.value.status) params.status = filters.value.status;
  if (filters.value.branchId) params.branch_id = filters.value.branchId;
  if (filters.value.requestType) params.request_type = filters.value.requestType;
  if (filters.value.startDate) params.start_date = filters.value.startDate;
  if (filters.value.endDate) params.end_date = filters.value.endDate;
  if (searchQuery.value) params.search = searchQuery.value;
  return params;
};

const handleExportPdf = async () => {
  showExportMenu.value = false;
  exportLoading.value = true;
  try {
    const { axiosInstance } = await import('@/plugins/axios');
    const response = await axiosInstance.get('/form-permintaan/export/pdf', {
      params: buildExportParams(),
      responseType: 'blob',
    });
    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `form_permintaan_${new Date().toISOString().slice(0, 10)}.pdf`);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
  } catch (err) {
    formPermintaanStore.error = 'Gagal export PDF';
  } finally {
    exportLoading.value = false;
  }
};

const handleExportExcel = async () => {
  // Same pattern, endpoint: '/form-permintaan/export/excel', extension: .xlsx
};
```

#### 2. FormPermintaanList.vue — Export Dropdown UI

```html
<!-- Export button with dropdown, placed alongside "Buat Form Permintaan" and "Filter" buttons -->
<div class="relative" v-if="can('form-permintaan-list')">
  <button
    @click="showExportMenu = !showExportMenu"
    :disabled="exportLoading"
    class="inline-flex items-center px-4 py-2 border border-gray-200 text-gray-700 rounded-lg hover:bg-gray-50"
  >
    <Download :size="18" class="mr-2" />
    Export
    <span v-if="exportLoading" class="ml-2 animate-spin">...</span>
  </button>
  <div v-if="showExportMenu" class="absolute right-0 mt-2 w-40 bg-white rounded-lg shadow-lg border z-10">
    <button @click="handleExportPdf" class="w-full flex items-center px-4 py-2 hover:bg-gray-50">
      <FileText :size="18" class="mr-2 text-red-500" />
      Export PDF
    </button>
    <button @click="handleExportExcel" class="w-full flex items-center px-4 py-2 hover:bg-gray-50">
      <FileSpreadsheet :size="18" class="mr-2 text-green-500" />
      Export Excel
    </button>
  </div>
</div>
```

Click-outside close handled by adding a click listener or using `@click.self` on a backdrop overlay.

#### 3. FormPermintaanList.vue — Status Column Changes

```javascript
// Remove "completed" from status labels and options
const statusLabels = {
  progress: "Progress",
  pending: "Pending",
  approved: "Approved",
  rejected: "Rejected",
};

// Status filter options (also remove "completed")
const statusOptions = Object.entries(statusLabels).map(([value, label]) => ({ value, label }));
```

Status column template logic:

```html
<template #cell-status="{ value, item }">
  <!-- Read-only badge for "approved" and "rejected" -->
  <span
    v-if="value === 'approved' || value === 'rejected' || !can('form-permintaan-confirm')"
    class="px-2 py-0.5 text-xs rounded font-medium"
    :class="{
      'bg-yellow-100 text-yellow-800': value === 'progress',
      'bg-gray-100 text-gray-800': value === 'pending',
      'bg-blue-100 text-blue-800': value === 'approved',
      'bg-red-100 text-red-800': value === 'rejected',
    }"
  >
    {{ statusLabels[value] || value }}
  </span>

  <!-- Dropdown for "progress" and "pending" (admin with permission only) -->
  <select
    v-else
    :value="value"
    :disabled="updatingStatusId === item.id"
    class="px-2 py-1 text-xs font-medium rounded border focus:outline-none focus:ring-1 focus:ring-blue-500"
    :class="{
      'bg-yellow-50 text-yellow-800 border-yellow-200': value === 'progress',
      'bg-gray-50 text-gray-800 border-gray-200': value === 'pending',
    }"
    @change="handleStatusChange(item, $event.target.value)"
  >
    <option value="progress">Progress</option>
    <option value="approved">Approved</option>
    <option value="rejected">Rejected</option>
  </select>
</template>
```

#### 4. FormPermintaanDetail.vue — Ticket Hyperlink

```html
<!-- Replace plain text ticket code with RouterLink -->
<div v-if="formData.ticket">
  <label class="block text-sm font-medium text-gray-700 mb-2">
    <ClipboardList :size="16" class="inline mr-2" />
    Ticket Terkait
  </label>
  <RouterLink
    :to="{ name: `${routePrefix}.ticket.detail`, params: { id: formData.ticket.id } }"
    class="text-blue-600 hover:text-blue-800 hover:underline font-medium"
  >
    {{ formData.ticket.code || "-" }}
  </RouterLink>
  <p class="mt-1 text-sm text-gray-500">{{ formData.ticket.description || "-" }}</p>
</div>
```

Uses the existing `routePrefix` computed property that already resolves to `"admin"` or `"app"` based on route context.

---

## Data Models

No new database tables or columns are required. The feature uses existing models:

- **FormPermintaan** — existing model with fields: id, user_id, branch_id, ticket_id, request_number, date, priority, request_type, fa_number, reason, status, confirmed_by, confirmed_at, rejected_by, rejection_reason
- **Branch** — existing model (for branch_id filter)
- **Ticket** — existing model (for hyperlink, accessed via `formPermintaan.ticket` relationship)

Status enum values after this change: `progress`, `pending`, `approved`, `rejected` (removed: `completed`).

---

## Data Flow

### Export Flow

```
User clicks "Export PDF/Excel"
  → buildExportParams() collects active filter values
  → axios GET /form-permintaan/export/{pdf|excel} with params + responseType: 'blob'
  → Controller validates params, builds query (same logic as getAllPaginated, no pagination)
  → PDF: DomPDF renders blade view → download response
  → Excel: FormPermintaanExport builds spreadsheet → StreamedResponse
  → Frontend creates blob URL → triggers download → cleans up
```

### Status Rendering Flow

```
DataTable renders status cell
  → Check status value:
    - "approved" or "rejected" → render read-only badge (span with colored classes)
    - "progress" or "pending" → render dropdown select (if user has permission)
  → Dropdown options: Progress, Approved, Rejected (no "completed")
```

### Ticket Link Flow

```
Detail page loads formData
  → If formData.ticket exists → render RouterLink
  → RouterLink :to uses routePrefix computed:
    - admin context → { name: 'admin.ticket.detail', params: { id: ticket.id } }
    - app context → { name: 'app.ticket.detail', params: { id: ticket.id } }
```

---

## Interface Contracts

### Export PDF Endpoint

```
GET /form-permintaan/export/pdf
Permission: form-permintaan-list
Query Parameters (all optional):
  - search: string
  - branch_id: integer
  - request_type: enum(pembelian_produk_baru|penggantian_produk_lama|servis|penggantian_part|jasa)
  - status: enum(progress|pending|approved|rejected)
  - start_date: date (YYYY-MM-DD)
  - end_date: date (YYYY-MM-DD)
Response: application/pdf binary stream
```

### Export Excel Endpoint

```
GET /form-permintaan/export/excel
Permission: form-permintaan-list
Query Parameters: (same as PDF)
Response: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet binary stream
```

---

## Error Handling

| Scenario | Backend Response | Frontend Handling |
|----------|-----------------|-------------------|
| Invalid filter params | 422 validation error | Toast error notification |
| No permission | 403 Forbidden | Toast error notification |
| No matching records | Valid file with headers only | Normal download (empty report) |
| Server error (DomPDF/PhpSpreadsheet) | 500 with error message | Toast: "Gagal export PDF/Excel" |
| Network timeout | No response | Toast: "Gagal export PDF/Excel" |

---

## Testing Strategy

- **Unit tests (backend)**: Verify FormPermintaanExport filtering logic produces correct record sets for given filter combinations
- **Unit tests (frontend)**: Verify status column rendering logic (badge vs dropdown) and export param building
- **Integration tests**: Verify export endpoints return correct content-type headers and valid file responses
- **Property tests**: Validate universal filter consistency, status rendering rules, and ticket link resolution

---

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system — essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Export filter consistency

*For any* valid combination of filter parameters (search, branch_id, request_type, status, start_date, end_date), the set of records returned by the export query SHALL be exactly the set of records that match ALL applied filters, using the same filtering logic as the `getAllPaginated` method but without pagination limits.

**Validates: Requirements 1.2, 2.2, 4.3**

### Property 2: Status column rendering rules

*For any* form permintaan record displayed in the list, the status column rendering SHALL be determined by its status value: if status is "approved" or "rejected", it renders as a read-only badge element; if status is "progress" or "pending" (and user has confirm permission), it renders as a dropdown select with exactly the options "Progress", "Approved", and "Rejected".

**Validates: Requirements 5.1, 5.3, 5.4**

### Property 3: Ticket link route resolution

*For any* form permintaan that has a non-null related ticket, and *for any* route context (admin or app), the ticket code SHALL be rendered as a RouterLink whose target route name equals `${routePrefix}.ticket.detail` with the ticket's ID as parameter, where routePrefix is "admin" when the current route name starts with "admin." and "app" otherwise.

**Validates: Requirements 7.1, 7.2, 7.3, 7.4**
