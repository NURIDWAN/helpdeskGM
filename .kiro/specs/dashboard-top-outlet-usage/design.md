# Technical Design Document: Dashboard Top Outlet Usage

## Overview

This feature adds a new section to the admin dashboard displaying the top 5 outlets (branches) ranked by electricity usage, water usage, gas usage, and customer count for the current month. The implementation follows the existing repository pattern in the Laravel backend and integrates into the existing Vue 3 admin dashboard.

## Architecture

### System Flow

```
┌─────────────────┐     GET /dashboard/top-outlet-usage     ┌──────────────────────────┐
│   Vue Frontend  │ ──────────────────────────────────────► │   DashboardController    │
│   Dashboard.vue │                                         │   getTopOutletUsage()    │
└─────────────────┘ ◄────────────────────────────────────── └──────────────────────────┘
        │                    JSON Response                            │
        │                                                            │
        ▼                                                            ▼
┌─────────────────┐                                         ┌──────────────────────────┐
│  Pinia Store    │                                         │  DashboardRepository     │
│  dashboard.js   │                                         │  getTopOutletUsage()     │
└─────────────────┘                                         └──────────────────────────┘
                                                                     │
                                                                     ▼
                                                            ┌──────────────────────────┐
                                                            │   Database Queries       │
                                                            │   - electricity_readings │
                                                            │   - utility_readings     │
                                                            │   - daily_records        │
                                                            │   - branches             │
                                                            └──────────────────────────┘
```

### Components

1. **Backend**
   - `DashboardRepositoryInterface` — new method signature `getTopOutletUsage(): array`
   - `DashboardRepository` — implementation with aggregation queries
   - `DashboardController` — new `getTopOutletUsage()` endpoint method
   - Route: `GET /api/dashboard/top-outlet-usage`

2. **Frontend**
   - `TopOutletUsage.vue` — new component in `fe/src/components/dashboard/`
   - `dashboard.js` store — new state property and fetch action
   - `Dashboard.vue` — integrate the new component

## Components and Interfaces

### API Endpoint

**Route:** `GET /api/dashboard/top-outlet-usage`

**Middleware:** `auth:sanctum` + `PermissionMiddleware::using(['dashboard-view-outlet-usage'])`

**Response (200 OK):**

```json
{
  "success": true,
  "message": "Top outlet usage berhasil diambil",
  "data": {
    "electricity": [
      { "branch_name": "Outlet A", "value": 15230.50 },
      { "branch_name": "Outlet B", "value": 12100.00 }
    ],
    "water": [
      { "branch_name": "Outlet C", "value": 8500.75 },
      { "branch_name": "Outlet A", "value": 7200.00 }
    ],
    "gas": [
      { "branch_name": "Outlet B", "value": 3200.00 },
      { "branch_name": "Outlet D", "value": 2800.50 }
    ],
    "customer": [
      { "branch_name": "Outlet A", "value": 4520 },
      { "branch_name": "Outlet E", "value": 3890 }
    ]
  }
}
```

Each category array contains at most 5 entries, sorted descending by `value`. The `value` field is a numeric type (decimal for utilities, integer for customers).

**Error Responses:**
- `401 Unauthorized` — unauthenticated request
- `403 Forbidden` — user lacks admin/superadmin role
- `500 Internal Server Error` — server-side failure

### Repository Interface

```php
interface DashboardRepositoryInterface
{
    // ... existing methods ...

    /**
     * Get top 5 outlet usage for electricity, water, gas, and customer count
     * for the current month.
     */
    public function getTopOutletUsage(): array;
}
```

### Repository Implementation

```php
public function getTopOutletUsage(): array
{
    $startOfMonth = Carbon::now()->startOfMonth()->toDateString();
    $today = Carbon::today()->toDateString();

    // Top 5 by electricity (from electricity_readings table)
    $electricity = DB::table('electricity_readings')
        ->join('daily_records', 'electricity_readings.daily_record_id', '=', 'daily_records.id')
        ->join('branches', 'daily_records.branch_id', '=', 'branches.id')
        ->whereBetween('daily_records.date', [$startOfMonth, $today])
        ->select('branches.name as branch_name', DB::raw('SUM(electricity_readings.meter_value) as value'))
        ->groupBy('branches.id', 'branches.name')
        ->orderByDesc('value')
        ->limit(5)
        ->get()
        ->map(fn($item) => [
            'branch_name' => $item->branch_name,
            'value' => (float) $item->value,
        ])
        ->toArray();

    // Top 5 by water (from utility_readings with category='water')
    $water = DB::table('utility_readings')
        ->join('daily_records', 'utility_readings.daily_record_id', '=', 'daily_records.id')
        ->join('branches', 'daily_records.branch_id', '=', 'branches.id')
        ->where('utility_readings.category', 'water')
        ->whereBetween('daily_records.date', [$startOfMonth, $today])
        ->select('branches.name as branch_name', DB::raw('SUM(utility_readings.meter_value) as value'))
        ->groupBy('branches.id', 'branches.name')
        ->orderByDesc('value')
        ->limit(5)
        ->get()
        ->map(fn($item) => [
            'branch_name' => $item->branch_name,
            'value' => (float) $item->value,
        ])
        ->toArray();

    // Top 5 by gas (from utility_readings with category='gas')
    $gas = DB::table('utility_readings')
        ->join('daily_records', 'utility_readings.daily_record_id', '=', 'daily_records.id')
        ->join('branches', 'daily_records.branch_id', '=', 'branches.id')
        ->where('utility_readings.category', 'gas')
        ->whereBetween('daily_records.date', [$startOfMonth, $today])
        ->select('branches.name as branch_name', DB::raw('SUM(utility_readings.meter_value) as value'))
        ->groupBy('branches.id', 'branches.name')
        ->orderByDesc('value')
        ->limit(5)
        ->get()
        ->map(fn($item) => [
            'branch_name' => $item->branch_name,
            'value' => (float) $item->value,
        ])
        ->toArray();

    // Top 5 by customer count (from daily_records)
    $customer = DB::table('daily_records')
        ->join('branches', 'daily_records.branch_id', '=', 'branches.id')
        ->whereBetween('daily_records.date', [$startOfMonth, $today])
        ->select('branches.name as branch_name', DB::raw('SUM(daily_records.total_customers) as value'))
        ->groupBy('branches.id', 'branches.name')
        ->orderByDesc('value')
        ->limit(5)
        ->get()
        ->map(fn($item) => [
            'branch_name' => $item->branch_name,
            'value' => (int) $item->value,
        ])
        ->toArray();

    return [
        'electricity' => $electricity,
        'water' => $water,
        'gas' => $gas,
        'customer' => $customer,
    ];
}
```

### Controller Method

```php
/**
 * Get top outlet usage data for the current month
 */
public function getTopOutletUsage()
{
    try {
        $data = $this->dashboardRepository->getTopOutletUsage();
        return ResponseHelper::jsonResponse(true, 'Top outlet usage berhasil diambil', $data, 200);
    } catch (\Throwable $e) {
        return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan', null, 500);
    }
}
```

### Route Registration

```php
// In routes/api.php, within the auth:sanctum middleware group
Route::get('dashboard/top-outlet-usage', [DashboardController::class, 'getTopOutletUsage']);
```

### Middleware Configuration

```php
// In DashboardController::middleware()
new Middleware(PermissionMiddleware::using(['dashboard-view-outlet-usage']), only: ['getTopOutletUsage']),
```

## Data Models

### Existing Models (No Changes Required)

| Model | Table | Relevant Fields |
|-------|-------|-----------------|
| Branch | branches | id, name |
| DailyRecord | daily_records | id, branch_id, date, total_customers |
| UtilityReading | utility_readings | id, daily_record_id, category, meter_value |
| ElectricityReading | electricity_readings | id, daily_record_id, meter_value |

### Data Relationships for Aggregation

```
Branch (1) ──► (N) DailyRecord (1) ──► (N) UtilityReading (category: water|gas)
                                   (1) ──► (N) ElectricityReading
```

### Date Filtering

The `daily_records.date` column is used to filter records within the current month:
- Start: First day of current month (`Carbon::now()->startOfMonth()`)
- End: Today's date (`Carbon::today()`)

## Frontend Components

### TopOutletUsage.vue Component

```vue
<script setup>
import { computed } from 'vue'
import { Zap, Droplets, Flame, Users } from 'lucide-vue-next'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Skeleton } from '@/components/ui/skeleton'

const props = defineProps({
  data: {
    type: Object,
    default: () => ({ electricity: [], water: [], gas: [], customer: [] })
  },
  loading: {
    type: Boolean,
    default: false
  },
  error: {
    type: String,
    default: null
  }
})

const categories = computed(() => [
  { key: 'electricity', title: 'Listrik', icon: Zap, iconClass: 'text-yellow-600', bgClass: 'bg-yellow-50' },
  { key: 'water', title: 'Air', icon: Droplets, iconClass: 'text-blue-600', bgClass: 'bg-blue-50' },
  { key: 'gas', title: 'Gas', icon: Flame, iconClass: 'text-orange-600', bgClass: 'bg-orange-50' },
  { key: 'customer', title: 'Customer', icon: Users, iconClass: 'text-green-600', bgClass: 'bg-green-50' },
])

const hasData = computed(() => {
  if (!props.data) return false
  return Object.values(props.data).some(arr => arr && arr.length > 0)
})

const formatValue = (key, value) => {
  if (key === 'customer') return value.toLocaleString('id-ID')
  return value.toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 })
}
</script>

<template>
  <Card>
    <CardHeader class="border-b border-slate-100">
      <CardTitle>Top 5 Outlet - Bulan Ini</CardTitle>
    </CardHeader>
    <CardContent class="p-6">
      <!-- Loading State -->
      <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <Skeleton v-for="i in 4" :key="i" class="h-48" />
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="text-center py-8 text-red-500">
        <p class="text-sm">{{ error }}</p>
      </div>

      <!-- Empty State -->
      <div v-else-if="!hasData" class="text-center py-8 text-gray-500">
        <Users :size="32" class="mx-auto mb-2 text-gray-300" />
        <p class="text-sm">Belum ada data untuk bulan ini</p>
      </div>

      <!-- Data Display -->
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div v-for="cat in categories" :key="cat.key">
          <div class="flex items-center gap-2 mb-3">
            <div class="rounded-lg p-2" :class="cat.bgClass">
              <component :is="cat.icon" :size="16" :class="cat.iconClass" />
            </div>
            <h4 class="text-sm font-semibold text-gray-700">{{ cat.title }}</h4>
          </div>
          <div class="space-y-2">
            <div
              v-for="(item, index) in (data[cat.key] || [])"
              :key="index"
              class="flex items-center justify-between p-2 bg-gray-50 rounded-lg"
            >
              <div class="flex items-center gap-2">
                <span class="w-5 h-5 rounded-full flex items-center justify-center text-xs font-bold"
                  :class="cat.bgClass + ' ' + cat.iconClass">
                  {{ index + 1 }}
                </span>
                <span class="text-sm text-gray-700 truncate max-w-[100px]">{{ item.branch_name }}</span>
              </div>
              <span class="text-xs font-semibold" :class="cat.iconClass">
                {{ formatValue(cat.key, item.value) }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </CardContent>
  </Card>
</template>
```

### Pinia Store Addition

```javascript
// In fe/src/stores/dashboard.js

// Add to state:
topOutletUsage: null,
topOutletUsageLoading: false,
topOutletUsageError: null,

// Add action:
async fetchTopOutletUsage() {
    this.topOutletUsageLoading = true;
    this.topOutletUsageError = null;

    try {
        const response = await axiosInstance.get('/dashboard/top-outlet-usage');
        if (response.data.success) {
            this.topOutletUsage = response.data.data;
        }
    } catch (error) {
        this.topOutletUsageError = handleError(error);
        console.error('Top outlet usage fetch error:', error);
    } finally {
        this.topOutletUsageLoading = false;
    }
}
```

### Dashboard.vue Integration

The `TopOutletUsage` component is placed after the charts row, visible only to admin/superadmin users:

```vue
<!-- Top Outlet Usage Section - Only for Admin/SuperAdmin -->
<TopOutletUsage
  v-if="isManagement"
  :data="topOutletUsage"
  :loading="topOutletUsageLoading"
  :error="topOutletUsageError"
/>
```

The `fetchTopOutletUsage()` action is called alongside existing dashboard data in `loadDashboardData()` when the user is an admin/superadmin.

## Error Handling

| Scenario | Backend Behavior | Frontend Behavior |
|----------|-----------------|-------------------|
| Unauthenticated request | Return 401 via middleware | Handled by global axios interceptor (redirect to login) |
| Unauthorized role | Return 403 via PermissionMiddleware | Component not rendered for non-admin users |
| Database error | Catch Throwable, return 500 with generic message | Display error message in component |
| Request timeout | N/A (client-side) | Display loading failure indicator |
| No data for current month | Return empty arrays | Display empty state message |

The component uses independent loading/error state (`topOutletUsageLoading`, `topOutletUsageError`) to ensure failures in this section do not affect other dashboard sections.

## Testing Strategy

### Unit Tests (Example-Based)
- Verify 401 for unauthenticated requests (Req 2.2)
- Verify UI renders four category sections with correct titles: "Listrik", "Air", "Gas", "Customer" (Req 3.1-3.5)
- Verify empty state when no data exists (Req 4.2)
- Verify error state display on API failure (Req 5.1)
- Verify timeout handling with loading failure indicator (Req 5.2)
- Verify loading indicator during fetch (Req 5.3)

### Property-Based Tests
- Ranking sort order and limit (Req 1.1-1.4)
- Aggregation correctness for electricity, water, gas, and customers (Req 1.5-1.8)
- Category filtering isolation for utility readings (Req 1.6-1.7)
- Response structure invariant (Req 1.9)
- Role-based access control (Req 2.1, 2.3)
- Date filtering boundary correctness (Req 4.1)
- Rendered entry completeness (Req 3.6)

### Integration Tests
- Full API call from Vue component through Pinia store to verify end-to-end data flow
- Permission seeding and middleware verification with real authentication

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system — essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Ranking results are sorted descending and limited to 5

*For any* set of branches with associated reading data in the current month, *for any* category (electricity, water, gas, customer), the returned ranking array SHALL be sorted in descending order by aggregated value and contain at most 5 entries.

**Validates: Requirements 1.1, 1.2, 1.3, 1.4**

### Property 2: Electricity aggregation correctness

*For any* branch with ElectricityReading records linked to DailyRecord entries within the current month, the reported electricity value SHALL equal the sum of all `meter_value` fields from those ElectricityReading records.

**Validates: Requirements 1.5**

### Property 3: Utility category filtering

*For any* branch with UtilityReading records of mixed categories (water, gas, electricity) linked to DailyRecord entries within the current month, the water ranking SHALL include only records with category "water" and the gas ranking SHALL include only records with category "gas" in their aggregation.

**Validates: Requirements 1.6, 1.7**

### Property 4: Customer count aggregation correctness

*For any* branch with DailyRecord entries within the current month, the reported customer count value SHALL equal the sum of all `total_customers` fields from those DailyRecord entries.

**Validates: Requirements 1.8**

### Property 5: Response structure invariant

*For any* non-empty ranking result, every entry SHALL contain a `branch_name` string field and a numeric `value` field.

**Validates: Requirements 1.9**

### Property 6: Access control by role

*For any* authenticated user, the endpoint SHALL return a successful response (200) if and only if the user has admin or superadmin role. Users without these roles SHALL receive HTTP 403.

**Validates: Requirements 2.1, 2.3**

### Property 7: Date filtering correctness

*For any* set of DailyRecord entries with dates both inside and outside the current month, the aggregation SHALL include only records where the `date` column falls within the first day of the current month up to and including today's date.

**Validates: Requirements 4.1**

### Property 8: Ranking entry display completeness

*For any* ranking entry rendered in the TopOutletUsage component, the display SHALL include a rank number (1-5), the branch name, and the corresponding aggregated value.

**Validates: Requirements 3.6**
