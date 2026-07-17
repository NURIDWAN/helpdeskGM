# Requirements Document

## Introduction

Perbaikan dan peningkatan fitur pada modul Form Permintaan di aplikasi Helpdesk GM. Terdapat tiga area utama: (1) fitur export PDF/Excel dari halaman admin list yang meneruskan filter aktif, (2) penyesuaian status dropdown agar "approved" menjadi status final tanpa opsi "completed", dan (3) hyperlink ke ticket terkait di halaman detail form permintaan.

## Glossary

- **Form_Permintaan_List_Page**: Halaman admin daftar form permintaan (`FormPermintaanList.vue`) yang menampilkan tabel paginated dengan filter
- **Form_Permintaan_Detail_Page**: Halaman detail form permintaan (`FormPermintaanDetail.vue`) yang menampilkan informasi lengkap satu record
- **Export_Service**: Komponen backend Laravel yang meng-generate file PDF atau Excel dari data form permintaan berdasarkan filter yang diterima
- **Status_Dropdown**: Elemen `<select>` pada kolom status di Form_Permintaan_List_Page yang memungkinkan admin mengubah status
- **Active_Filters**: Kumpulan parameter filter yang sedang diterapkan di Form_Permintaan_List_Page meliputi status, outlet (branch_id), tanggal mulai, tanggal akhir, jenis permintaan, dan search query
- **Ticket_Link**: Elemen navigasi berupa `<RouterLink>` yang mengarahkan pengguna ke halaman detail ticket terkait
- **Route_Prefix**: Computed property yang menentukan apakah konteks saat ini berada di admin route atau app route

## Requirements

### Requirement 1: Export PDF dari List Form Permintaan

**User Story:** As an admin, I want to export the form permintaan list as a PDF file with the active filters applied, so that I can generate filtered reports for documentation or review purposes.

#### Acceptance Criteria

1. WHEN the admin clicks the "Export PDF" button on the Form_Permintaan_List_Page, THE Export_Service SHALL generate a PDF file containing form permintaan records that match the Active_Filters.
2. WHEN the export PDF request is received, THE Export_Service SHALL accept filter parameters: search, branch_id, request_type, status, start_date, and end_date.
3. WHEN the PDF is generated successfully, THE Form_Permintaan_List_Page SHALL trigger a browser file download with filename format `form_permintaan_{timestamp}.pdf`.
4. IF the export PDF request fails, THEN THE Form_Permintaan_List_Page SHALL display an error notification to the admin.
5. WHILE the export PDF process is in progress, THE Form_Permintaan_List_Page SHALL disable the export button and show a loading indicator.

### Requirement 2: Export Excel dari List Form Permintaan

**User Story:** As an admin, I want to export the form permintaan list as an Excel file with the active filters applied, so that I can analyze data in a spreadsheet.

#### Acceptance Criteria

1. WHEN the admin clicks the "Export Excel" button on the Form_Permintaan_List_Page, THE Export_Service SHALL generate an Excel file containing form permintaan records that match the Active_Filters.
2. WHEN the export Excel request is received, THE Export_Service SHALL accept filter parameters: search, branch_id, request_type, status, start_date, and end_date.
3. WHEN the Excel file is generated successfully, THE Form_Permintaan_List_Page SHALL trigger a browser file download with filename format `form_permintaan_{timestamp}.xlsx`.
4. IF the export Excel request fails, THEN THE Form_Permintaan_List_Page SHALL display an error notification to the admin.
5. WHILE the export Excel process is in progress, THE Form_Permintaan_List_Page SHALL disable the export button and show a loading indicator.

### Requirement 3: Export Button UI pada List Page

**User Story:** As an admin, I want to access export options from the list page header, so that I can quickly generate reports without leaving the page.

#### Acceptance Criteria

1. THE Form_Permintaan_List_Page SHALL display an "Export" button in the page header area alongside the existing "Buat Form Permintaan" and "Filter" buttons.
2. WHEN the admin clicks the "Export" button, THE Form_Permintaan_List_Page SHALL show a dropdown menu with two options: "Export PDF" and "Export Excel".
3. WHEN the admin clicks outside the export dropdown menu, THE Form_Permintaan_List_Page SHALL close the dropdown menu.

### Requirement 4: Backend Export Endpoints

**User Story:** As a system, I need API endpoints that generate filtered export files, so that the frontend can request exports with the current filter state.

#### Acceptance Criteria

1. THE Export_Service SHALL expose a GET endpoint at `/form-permintaan/export/pdf` that returns a PDF file response.
2. THE Export_Service SHALL expose a GET endpoint at `/form-permintaan/export/excel` that returns an Excel file response.
3. WHEN the export endpoints receive filter parameters, THE Export_Service SHALL apply the same filtering logic as the existing `getAllPaginated` method without pagination limits.
4. THE Export_Service SHALL require the `form-permintaan-list` permission to access the export endpoints.
5. IF no records match the provided filters, THEN THE Export_Service SHALL return a valid file with only headers and no data rows.

### Requirement 5: Status Dropdown — Approved sebagai Status Final

**User Story:** As an admin, I want the status to display as a read-only badge when it is "approved", so that I understand approved is the final state and cannot accidentally change it.

#### Acceptance Criteria

1. WHILE the form permintaan status is "approved", THE Form_Permintaan_List_Page SHALL render the status as a read-only badge element instead of a dropdown.
2. THE Form_Permintaan_List_Page SHALL remove the "completed" option from the status dropdown options list.
3. WHILE the form permintaan status is "progress" or "pending", THE Form_Permintaan_List_Page SHALL render the status as a dropdown with options: "Progress", "Approved", and "Rejected".
4. WHILE the form permintaan status is "rejected", THE Form_Permintaan_List_Page SHALL render the status as a read-only badge element.
5. THE Form_Permintaan_List_Page SHALL style the read-only status badge consistent with the badge styling used in the ticket list page (background color and text color matching the status).

### Requirement 6: Status Filter Options Update

**User Story:** As an admin, I want the status filter to reflect the actual available statuses without "completed", so that filtering is accurate.

#### Acceptance Criteria

1. THE Form_Permintaan_List_Page SHALL remove "Completed" from the status filter dropdown options.
2. THE Form_Permintaan_List_Page SHALL include these status filter options: "Progress", "Pending", "Approved", and "Rejected".

### Requirement 7: Hyperlink ke Ticket Terkait di Detail Page

**User Story:** As a user, I want to click on the ticket code in the form permintaan detail page to navigate directly to the related ticket detail, so that I can quickly access the ticket information.

#### Acceptance Criteria

1. WHEN a form permintaan has a related ticket, THE Form_Permintaan_Detail_Page SHALL render the ticket code as a clickable RouterLink instead of plain text.
2. WHILE the current route is within the admin context, THE Ticket_Link SHALL navigate to the `admin.ticket.detail` route with the ticket ID as parameter.
3. WHILE the current route is within the app context, THE Ticket_Link SHALL navigate to the `app.ticket.detail` route with the ticket ID as parameter.
4. THE Ticket_Link SHALL use the existing computed `routePrefix` property to determine the correct route target.
5. THE Ticket_Link SHALL be visually distinguishable as a link using blue text color and underline on hover, consistent with other links in the application.
