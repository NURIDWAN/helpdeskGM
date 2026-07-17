# Requirements Document

## Introduction

Form Permintaan (Request Form) is a digital version of the physical request form used in the helpdeskGM application. It enables regular users (staff) to submit purchase, replacement, service, part replacement, or service (jasa) requests for their outlet. The form captures request metadata (date, user, outlet, priority, request type), line items (product description, quantity, unit of measure, notes), and attachments. A unique request number is auto-generated based on a defined format. The feature is accessible from the App layout (regular user side).

## Glossary

- **Form_Permintaan_System**: The module responsible for creating, storing, validating, and displaying request forms (Form Permintaan)
- **Request_Form**: A completed form permintaan record consisting of header data (date, user, outlet, priority, request type) and one or more line items
- **Request_Number**: An auto-generated unique identifier for each request form following the format `{DD}/{OUTLET_CODE}/FP{YY}/{M}/{YYYY}`
- **Request_Type**: The category of the request, one of: Pembelian produk baru, Penggantian produk lama, Servis, Penggantian part, or Jasa
- **Line_Item**: A single row in the request form's item table containing product description, quantity, unit of measure, and notes
- **Outlet**: A branch/location from the branches table in the database
- **Priority**: The urgency level of a request (low, medium, high, urgent)
- **FA_Number**: Fixed Asset number used to reference existing equipment for replacement, service, or part replacement requests
- **UoM**: Unit of Measure, describing the unit type for the requested item (e.g., Unit, Pcs, Set, Meter)
- **Attachment**: An image or document file uploaded alongside the request form as supporting evidence

## Requirements

### Requirement 1: Create Request Form

**User Story:** As a staff user, I want to create a new request form (Form Permintaan), so that I can formally submit requests for purchases, replacements, services, or parts to the maintenance/purchasing team.

#### Acceptance Criteria

1. WHEN a staff user navigates to the form permintaan creation page, THE Form_Permintaan_System SHALL display a multi-step wizard form with Step 1 (Form Information) and Step 2 (Attachments).
2. THE Form_Permintaan_System SHALL display the following fields on Step 1: Tanggal (date, auto-filled with current date), User (auto-filled from the logged-in user's department/name), Outlet (dropdown), Prioritas (priority selector with options: Low, Medium, High, Urgent), and Jenis Permintaan (request type selector).
3. WHEN a staff user interacts with the Outlet field, THE Form_Permintaan_System SHALL populate the outlet dropdown with data fetched from the `/branches` API endpoint.
4. THE Form_Permintaan_System SHALL provide the following request type options: Pembelian produk (unit) baru, Penggantian produk (unit) lama, Servis, Penggantian part, and Jasa.
5. WHEN a user selects request type "Penggantian produk (unit) lama", "Servis", or "Penggantian part", THE Form_Permintaan_System SHALL display an additional input field for the FA Number (No FA).
6. WHEN a user selects request type "Pembelian produk (unit) baru", THE Form_Permintaan_System SHALL display an additional input field for the reason (Alasan).
7. THE Form_Permintaan_System SHALL provide a line items table with columns: No (auto-numbered), Deskripsi Produk (Merk & Tipe), QTY (integer, minimum value of 1, maximum value of 9999), UoM, and Catatan.
8. THE Form_Permintaan_System SHALL allow the user to add one or more line items to the request form, up to a maximum of 20 line items.
9. THE Form_Permintaan_System SHALL allow the user to remove any line item from the request form.
10. WHEN the user submits the form on Step 1, THE Form_Permintaan_System SHALL validate that Outlet, Prioritas, Jenis Permintaan, and at least one line item with a non-empty Deskripsi Produk and a QTY of at least 1 are provided before proceeding to Step 2.
11. IF the `/branches` API endpoint fails to respond or returns an error, THEN THE Form_Permintaan_System SHALL display an error message indicating that outlet data could not be loaded and SHALL prevent form submission until outlet data is successfully retrieved.
12. IF the user attempts to add a line item when 20 line items already exist, THEN THE Form_Permintaan_System SHALL disable the add action and display a message indicating the maximum number of line items has been reached.

### Requirement 2: Auto-Generate Request Number

**User Story:** As a staff user, I want the system to auto-generate a unique request number, so that each request form is uniquely identifiable and traceable.

#### Acceptance Criteria

1. WHEN a request form is successfully submitted, THE Form_Permintaan_System SHALL generate a unique request number following the format `{DD}/{OUTLET_CODE}/FP{YY}/{M}/{YYYY}` where DD is the two-digit day derived from the server date at the time of submission, OUTLET_CODE is the branch code from the selected outlet, YY is the two-digit sequence number (zero-padded, starting from 01), M is the numeric month (1-12), and YYYY is the four-digit year.
2. THE Form_Permintaan_System SHALL ensure each generated request number is unique across all request forms by atomically reserving the next sequence number during the submission transaction.
3. THE Form_Permintaan_System SHALL increment the sequence number (YY portion) by assigning the value of the highest existing sequence number for the same outlet, month, and year, plus one, and SHALL reset the sequence to 01 at the start of each new month.
4. IF the sequence number for a given outlet, month, and year exceeds 99, THEN THE Form_Permintaan_System SHALL reject the submission and display an error message indicating the maximum number of requests for that outlet in the current month has been reached.
5. IF two or more submissions for the same outlet occur concurrently, THEN THE Form_Permintaan_System SHALL serialize sequence number generation to prevent duplicate request numbers.
6. WHEN the request number is successfully generated, THE Form_Permintaan_System SHALL persist the request number in the request form record and display it to the user on the confirmation or detail view.

### Requirement 3: Attachment Upload

**User Story:** As a staff user, I want to upload images or documents as attachments to my request form, so that I can provide visual evidence or supporting documentation for the requested items.

#### Acceptance Criteria

1. WHEN the user proceeds to Step 2 (Attachments), THE Form_Permintaan_System SHALL display a file upload area that accepts image files (JPG, PNG) and document files (PDF).
2. THE Form_Permintaan_System SHALL allow the user to upload a maximum of 10 files per request form.
3. WHEN the user uploads an image file (JPG, PNG), THE Form_Permintaan_System SHALL display a preview thumbnail for the uploaded file.
4. THE Form_Permintaan_System SHALL enforce a maximum file size of 10MB per individual file.
5. IF a user uploads a file exceeding 10MB, THEN THE Form_Permintaan_System SHALL display a validation error message indicating the file size limit and SHALL NOT add the file to the upload list.
6. IF a user uploads a file with an unsupported format (not JPG, PNG, or PDF), THEN THE Form_Permintaan_System SHALL display a validation error message indicating the accepted file formats and SHALL NOT add the file to the upload list.
7. THE Form_Permintaan_System SHALL allow the user to remove any selected file before final submission.
8. THE Form_Permintaan_System SHALL allow the user to skip the attachment step and submit the form without attachments.
9. IF a file upload fails due to a server or network error, THEN THE Form_Permintaan_System SHALL display an error message indicating the upload failure and SHALL allow the user to retry the upload.

### Requirement 4: Form Validation

**User Story:** As a staff user, I want the system to validate my input, so that I can submit a complete and correct request form.

#### Acceptance Criteria

1. IF the Outlet field is empty upon submission, THEN THE Form_Permintaan_System SHALL display a validation error on the Outlet field indicating it is required.
2. IF the Prioritas field is empty upon submission, THEN THE Form_Permintaan_System SHALL display a validation error on the Prioritas field indicating it is required.
3. IF the Jenis Permintaan field is empty upon submission, THEN THE Form_Permintaan_System SHALL display a validation error on the Jenis Permintaan field indicating it is required.
4. IF no line items are added upon submission, THEN THE Form_Permintaan_System SHALL display a validation error indicating at least one line item is required.
5. IF a line item has an empty Deskripsi Produk field or a QTY value that is empty, not a positive integer, or less than 1, THEN THE Form_Permintaan_System SHALL display a validation error on the invalid line item field indicating the accepted constraint.
6. IF the request type is "Penggantian produk (unit) lama", "Servis", or "Penggantian part" and the FA Number field is empty upon submission, THEN THE Form_Permintaan_System SHALL display a validation error on the FA Number field indicating it is required.
7. IF the request type is "Pembelian produk (unit) baru" and the Alasan field is empty upon submission, THEN THE Form_Permintaan_System SHALL display a validation error on the Alasan field indicating it is required.
8. WHEN the backend returns validation errors, THE Form_Permintaan_System SHALL display each error message next to its corresponding field, and display any error not mapped to a visible field in a summary error area at the top of the form.
9. IF validation fails upon submission, THEN THE Form_Permintaan_System SHALL preserve all user-entered data in the form fields so that the user can correct errors without re-entering information.

### Requirement 5: Request Form Listing

**User Story:** As a staff user, I want to view a list of my submitted request forms, so that I can track and manage my requests.

#### Acceptance Criteria

1. WHEN a staff user navigates to the form permintaan list page, THE Form_Permintaan_System SHALL display a paginated table showing the columns: No. Permintaan, Tanggal, Outlet, Jenis Permintaan, Prioritas, and Status, sorted by Tanggal in descending order (newest first).
2. THE Form_Permintaan_System SHALL display only the request forms belonging to the logged-in user (for regular staff users).
3. THE Form_Permintaan_System SHALL display 10 request forms per page and provide pagination controls (page navigation and current page indicator) for navigating through the list.
4. WHEN a staff user clicks on a request form entry, THE Form_Permintaan_System SHALL navigate to the detail view of that request form.
5. IF the logged-in user has no submitted request forms, THEN THE Form_Permintaan_System SHALL display an empty state message indicating no request forms exist.
6. IF the system fails to load the request form list from the API, THEN THE Form_Permintaan_System SHALL display an error message indicating the data could not be loaded.

### Requirement 6: Request Form Detail View

**User Story:** As a staff user, I want to view the details of a submitted request form, so that I can review the complete information including items and attachments.

#### Acceptance Criteria

1. WHEN a staff user navigates to the detail view of a request form, THE Form_Permintaan_System SHALL display all header information: No. Permintaan, Tanggal, User, Outlet, Prioritas, and Jenis Permintaan. IF the request type is "Penggantian produk (unit) lama", "Servis", or "Penggantian part", THEN THE Form_Permintaan_System SHALL additionally display the FA Number field. IF the request type is "Pembelian produk (unit) baru", THEN THE Form_Permintaan_System SHALL additionally display the Alasan field.
2. WHEN a staff user navigates to the detail view of a request form, THE Form_Permintaan_System SHALL display the complete line items table with columns: No (row number), Deskripsi Produk, QTY, UoM, and Catatan, showing all line items associated with the request form.
3. WHEN a staff user navigates to the detail view of a request form, THE Form_Permintaan_System SHALL display all attachments associated with the request form, showing clickable thumbnails for image files (JPG, PNG) and clickable file name links for non-image files (PDF).
4. WHEN a user clicks an attachment thumbnail or file name link, THE Form_Permintaan_System SHALL open the attachment in a preview dialog displaying the file content.
5. IF the requested form does not exist or the user does not have permission to view it, THEN THE Form_Permintaan_System SHALL display an error message indicating the form is not accessible and prevent display of any form data.

### Requirement 7: Backend API for Form Permintaan

**User Story:** As a developer, I want a RESTful API for form permintaan CRUD operations, so that the frontend can create, list, and retrieve request form data.

#### Acceptance Criteria

1. THE Form_Permintaan_System SHALL provide a `POST /form-permintaan` endpoint that accepts request form data (outlet_id, priority, request_type, fa_number, reason, line items as an array of objects each containing product_description, quantity, uom, and notes) and creates a new request form record, returning the created record with a 201 status code.
2. THE Form_Permintaan_System SHALL provide a `GET /form-permintaan` endpoint that accepts a required `row_per_page` parameter (integer, 1 to 100) and an optional `search` parameter, and returns a paginated list of request forms belonging to the authenticated user.
3. THE Form_Permintaan_System SHALL provide a `GET /form-permintaan/{id}` endpoint that returns the complete details of a specific request form including header data, line items, and attachments.
4. IF the `GET /form-permintaan/{id}` endpoint is called with an ID that does not exist, THEN THE Form_Permintaan_System SHALL return a 404 response with an error message indicating the resource was not found.
5. THE Form_Permintaan_System SHALL store request form data using the Repository pattern consistent with the existing codebase.
6. THE Form_Permintaan_System SHALL validate incoming request data using a Laravel FormRequest class, and IF validation fails, THEN THE Form_Permintaan_System SHALL return a 422 response containing field-level error messages.
7. THE Form_Permintaan_System SHALL protect all endpoints with `auth:sanctum` middleware and Spatie permission middleware using `form-permintaan-create` for the POST endpoint and `form-permintaan-list` for the GET endpoints.
8. IF an unauthenticated request is made to any form-permintaan endpoint, THEN THE Form_Permintaan_System SHALL return a 401 response.
9. IF an authenticated user without the required permission accesses a form-permintaan endpoint, THEN THE Form_Permintaan_System SHALL return a 403 response.

### Requirement 8: Data Persistence

**User Story:** As a developer, I want the request form data to be properly stored in the database, so that request forms and their line items are persisted reliably.

#### Acceptance Criteria

1. THE Form_Permintaan_System SHALL store request form header data in a `form_permintaan` table with columns: id, user_id (foreign key to users), branch_id (foreign key to branches), request_number (string, max 50 characters, unique), date (date), priority (enum: low, medium, high, urgent), request_type (string, max 100 characters), fa_number (string, nullable, max 100 characters), reason (text, nullable), status (string, default "pending"), timestamps.
2. THE Form_Permintaan_System SHALL store line items in a `form_permintaan_items` table with columns: id, form_permintaan_id (foreign key), product_description (string, max 255 characters), quantity (integer, minimum value 1), uom (string, max 50 characters), notes (text, nullable), timestamps.
3. THE Form_Permintaan_System SHALL store attachments in a `form_permintaan_attachments` table with columns: id, form_permintaan_id (foreign key), file_path (string, max 255 characters), file_name (string, max 255 characters), file_type (string, max 100 characters), file_size (unsigned integer, in bytes), timestamps.
4. THE Form_Permintaan_System SHALL establish a belongs-to relationship between Request_Form and Branch with a restrict-on-delete constraint, preventing deletion of a Branch that has associated request forms.
5. THE Form_Permintaan_System SHALL establish a belongs-to relationship between Request_Form and User with a restrict-on-delete constraint, preventing deletion of a User that has associated request forms.
6. THE Form_Permintaan_System SHALL establish a has-many relationship between Request_Form and Line_Item with a cascade-on-delete constraint, so that deleting a Request_Form removes all associated line items.
7. THE Form_Permintaan_System SHALL establish a has-many relationship between Request_Form and Attachment with a cascade-on-delete constraint, so that deleting a Request_Form removes all associated attachments.
8. WHEN a request form is submitted, THE Form_Permintaan_System SHALL persist the header record, all line items, and all attachments within a single database transaction, rolling back all changes if any part of the operation fails.
9. THE Form_Permintaan_System SHALL create an index on the `form_permintaan` table for columns user_id and branch_id to support efficient listing queries.

### Requirement 9: Navigation and Routing

**User Story:** As a staff user, I want to access the form permintaan feature from the application menu, so that I can find and use the feature easily.

#### Acceptance Criteria

1. THE Form_Permintaan_System SHALL register the following routes under the App layout with route names prefixed by `app.`: `/form-permintaan` (name: `app.form-permintaan`, list view, permission: `form-permintaan-list`), `/form-permintaan/create` (name: `app.form-permintaan.create`, create form, permission: `form-permintaan-create`), and `/form-permintaan/{id}` (name: `app.form-permintaan.detail`, detail view, permission: `form-permintaan-list`).
2. THE Form_Permintaan_System SHALL add a navigation link labeled "Form Permintaan" in the App layout sidebar that navigates to the form permintaan list route and displays an active visual state when the current route name starts with `app.form-permintaan`.
3. THE Form_Permintaan_System SHALL protect all form permintaan routes with the `requiresAuth: true` meta flag, so that unauthenticated users are redirected to the login page.
4. IF an authenticated user navigates to a form permintaan route and does not have the route's required permission, THEN THE Form_Permintaan_System SHALL redirect the user to the forbidden error page.
5. IF the authenticated user does not have the `form-permintaan-list` permission, THEN THE Form_Permintaan_System SHALL hide the "Form Permintaan" navigation link from the App layout sidebar.
