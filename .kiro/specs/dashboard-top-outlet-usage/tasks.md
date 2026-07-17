# Implementation Plan: Dashboard Top Outlet Usage

## Overview

Implement a new dashboard section displaying the top 5 outlets ranked by electricity, water, gas usage, and customer count for the current month. The backend uses Laravel's repository pattern with aggregation queries, and the frontend uses a new Vue 3 component integrated into the existing admin dashboard.

## Tasks

- [x] 1. Backend - Repository Interface and Implementation
  - [x] 1.1 Add `getTopOutletUsage()` method to DashboardRepositoryInterface
    - Add method signature `public function getTopOutletUsage(): array;` to `api/app/Interfaces/DashboardRepositoryInterface.php`
    - Include PHPDoc describing return structure (electricity, water, gas, customer arrays)
    - _Requirements: 1.1, 1.2, 1.3, 1.4_

  - [x] 1.2 Implement `getTopOutletUsage()` in DashboardRepository
    - Add the implementation in `api/app/Repositories/DashboardRepository.php`
    - Query `electricity_readings` joined with `daily_records` and `branches`, filter by current month date range, group by branch, sum `meter_value`, order descending, limit 5
    - Query `utility_readings` with category "water" joined with `daily_records` and `branches`, same date filter, group/sum/order/limit
    - Query `utility_readings` with category "gas" joined with `daily_records` and `branches`, same date filter, group/sum/order/limit
    - Query `daily_records` joined with `branches`, same date filter, group by branch, sum `total_customers`, order descending, limit 5
    - Return associative array with keys: electricity, water, gas, customer
    - Use `Carbon::now()->startOfMonth()->toDateString()` and `Carbon::today()->toDateString()` for date boundaries
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 1.7, 1.8, 1.9, 4.1_

  - [ ]* 1.3 Write unit tests for `getTopOutletUsage()` repository method
    - Test that each category returns max 5 results sorted descending by value
    - Test electricity aggregation sums `meter_value` from `electricity_readings`
    - Test water aggregation only includes `utility_readings` with category "water"
    - Test gas aggregation only includes `utility_readings` with category "gas"
    - Test customer aggregation sums `total_customers` from `daily_records`
    - Test date filtering only includes records within current month (start of month to today)
    - Test empty result when no data exists for current month
    - _Requirements: 1.1-1.9, 4.1, 4.2_

- [x] 2. Backend - Controller and Route
  - [x] 2.1 Add `getTopOutletUsage()` method to DashboardController
    - Add method in `api/app/Http/Controllers/DashboardController.php`
    - Call `$this->dashboardRepository->getTopOutletUsage()`
    - Return via `ResponseHelper::jsonResponse(true, 'Top outlet usage berhasil diambil', $data, 200)`
    - Wrap in try-catch, return 500 with generic error message on failure
    - _Requirements: 1.1-1.4, 5.1_

  - [x] 2.2 Add PermissionMiddleware for `getTopOutletUsage` in DashboardController
    - Add `new Middleware(PermissionMiddleware::using(['dashboard-view-outlet-usage']), only: ['getTopOutletUsage'])` to the `middleware()` method
    - _Requirements: 2.1, 2.3_

  - [x] 2.3 Register the API route
    - Add `Route::get('dashboard/top-outlet-usage', [DashboardController::class, 'getTopOutletUsage']);` in `api/routes/api.php` within the `auth:sanctum` middleware group, alongside existing dashboard routes
    - _Requirements: 2.1, 2.2_

  - [ ]* 2.4 Write unit tests for the controller endpoint
    - Test 401 response for unauthenticated requests
    - Test 403 response for authenticated user without admin/superadmin role
    - Test 200 response with correct JSON structure for admin user
    - Test response contains `success`, `message`, and `data` fields
    - Test `data` contains `electricity`, `water`, `gas`, `customer` arrays
    - _Requirements: 2.1, 2.2, 2.3, 1.9_

- [x] 3. Checkpoint - Backend verification
  - Ensure all tests pass, ask the user if questions arise.

- [x] 4. Frontend - Pinia Store Update
  - [x] 4.1 Add top outlet usage state and action to dashboard store
    - Add state properties: `topOutletUsage: null`, `topOutletUsageLoading: false`, `topOutletUsageError: null` to `fe/src/stores/dashboard.js`
    - Add `fetchTopOutletUsage()` action that calls `GET /dashboard/top-outlet-usage`
    - On success, set `this.topOutletUsage = response.data.data`
    - On error, set `this.topOutletUsageError = handleError(error)`
    - Use independent loading/error state (not the shared `this.loading`)
    - Call `fetchTopOutletUsage()` within `fetchDataByPermissions()` when user has `dashboard-view-outlet-usage` permission
    - _Requirements: 3.1, 5.1, 5.2, 5.3_

- [x] 5. Frontend - TopOutletUsage Component
  - [x] 5.1 Create `TopOutletUsage.vue` component
    - Create file at `fe/src/components/dashboard/TopOutletUsage.vue`
    - Accept props: `data` (Object), `loading` (Boolean), `error` (String)
    - Render a Card with header "Top 5 Outlet - Bulan Ini"
    - Display 4-column grid (responsive: 1 col mobile, 2 col tablet, 4 col desktop) for Listrik, Air, Gas, Customer
    - Each column shows icon, title, and numbered list of top 5 branch entries with name and value
    - Format numeric values using `toLocaleString('id-ID')`
    - Use lucide-vue-next icons: Zap (electricity), Droplets (water), Flame (gas), Users (customer)
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6_

  - [x] 5.2 Implement loading, error, and empty states
    - Show skeleton placeholders while `loading` is true
    - Show error message when `error` is non-null
    - Show empty state with message "Belum ada data untuk bulan ini" when data has all empty arrays
    - _Requirements: 4.2, 5.1, 5.2, 5.3_

- [x] 6. Frontend - Dashboard Integration
  - [x] 6.1 Integrate TopOutletUsage component into Dashboard.vue
    - Import and register `TopOutletUsage` component in `fe/src/views/admin/Dashboard.vue`
    - Place the component after existing chart sections
    - Conditionally render with `v-if` checking user has admin/superadmin role (use existing `isManagement` or equivalent computed)
    - Pass `topOutletUsage`, `topOutletUsageLoading`, `topOutletUsageError` from the dashboard store as props
    - _Requirements: 3.1, 3.7_

- [x] 7. Final Checkpoint
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- The backend follows the existing repository pattern (`DashboardRepositoryInterface` → `DashboardRepository` → `DashboardController`)
- The frontend follows existing store patterns with independent loading/error state per section
- Permission `dashboard-view-outlet-usage` must be seeded in the permissions table (or created via the existing permission system)
- No database migration needed — uses existing tables: `branches`, `daily_records`, `utility_readings`, `electricity_readings`

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1"] },
    { "id": 1, "tasks": ["1.2"] },
    { "id": 2, "tasks": ["1.3", "2.1"] },
    { "id": 3, "tasks": ["2.2", "2.3"] },
    { "id": 4, "tasks": ["2.4", "4.1"] },
    { "id": 5, "tasks": ["5.1"] },
    { "id": 6, "tasks": ["5.2"] },
    { "id": 7, "tasks": ["6.1"] }
  ]
}
```
