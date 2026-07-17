# Requirements Document

## Introduction

Fitur ini menambahkan sebuah section/card baru di dashboard admin yang menampilkan ranking top 5 outlet (cabang) berdasarkan pemakaian listrik, air, gas, dan jumlah customer terbanyak pada bulan berjalan. Section ini hanya terlihat oleh pengguna dengan role admin atau superadmin.

## Glossary

- **Dashboard_System**: Sistem dashboard admin yang menampilkan metrik, chart, dan ranking operasional
- **Top_Outlet_Section**: Section/card baru pada dashboard yang menampilkan ranking outlet berdasarkan pemakaian utilitas dan jumlah customer
- **Branch**: Entitas outlet/cabang dalam sistem, direpresentasikan oleh model Branch
- **DailyRecord**: Catatan harian per cabang yang menyimpan informasi tanggal dan total customer
- **UtilityReading**: Pembacaan meter utilitas (gas, water, electricity) yang terkait dengan DailyRecord
- **ElectricityReading**: Pembacaan meter listrik multi-meter yang terkait dengan DailyRecord
- **Current_Month**: Periode bulan berjalan dari tanggal 1 sampai hari ini
- **API_Endpoint**: Endpoint REST pada backend Laravel yang menyediakan data ranking outlet

## Requirements

### Requirement 1: Backend API Endpoint for Top Outlet Usage

**User Story:** As an admin, I want an API endpoint that provides ranked outlet usage data, so that the dashboard can display the top 5 outlets by utility consumption and customer count.

#### Acceptance Criteria

1. WHEN the API_Endpoint receives a GET request, THE Dashboard_System SHALL return the top 5 Branch entities ranked by total electricity meter value for the Current_Month.
2. WHEN the API_Endpoint receives a GET request, THE Dashboard_System SHALL return the top 5 Branch entities ranked by total water meter value for the Current_Month.
3. WHEN the API_Endpoint receives a GET request, THE Dashboard_System SHALL return the top 5 Branch entities ranked by total gas meter value for the Current_Month.
4. WHEN the API_Endpoint receives a GET request, THE Dashboard_System SHALL return the top 5 Branch entities ranked by total customer count from DailyRecord for the Current_Month.
5. THE Dashboard_System SHALL calculate electricity usage by summing meter_value from ElectricityReading records associated with DailyRecord entries within the Current_Month per Branch.
6. THE Dashboard_System SHALL calculate water usage by summing meter_value from UtilityReading records with category "water" associated with DailyRecord entries within the Current_Month per Branch.
7. THE Dashboard_System SHALL calculate gas usage by summing meter_value from UtilityReading records with category "gas" associated with DailyRecord entries within the Current_Month per Branch.
8. THE Dashboard_System SHALL calculate customer count by summing total_customers from DailyRecord entries within the Current_Month per Branch.
9. THE Dashboard_System SHALL return each ranking entry containing the Branch name and the aggregated value.

### Requirement 2: Access Control

**User Story:** As a system administrator, I want the top outlet usage data to be restricted to admin and superadmin roles, so that sensitive operational data is protected.

#### Acceptance Criteria

1. THE Dashboard_System SHALL restrict access to the API_Endpoint to authenticated users with admin or superadmin role.
2. WHEN an unauthenticated user requests the API_Endpoint, THE Dashboard_System SHALL return HTTP status 401.
3. WHEN an authenticated user without admin or superadmin role requests the API_Endpoint, THE Dashboard_System SHALL return HTTP status 403.

### Requirement 3: Frontend Top Outlet Section Display

**User Story:** As an admin, I want to see a combined section on the dashboard showing the top 5 outlets for electricity, water, gas, and customer count, so that I can quickly identify the highest-consuming branches.

#### Acceptance Criteria

1. WHEN the dashboard page loads, THE Top_Outlet_Section SHALL display four ranking lists within a single combined card.
2. THE Top_Outlet_Section SHALL display a ranking list titled "Listrik" showing top 5 Branch names with electricity usage values.
3. THE Top_Outlet_Section SHALL display a ranking list titled "Air" showing top 5 Branch names with water usage values.
4. THE Top_Outlet_Section SHALL display a ranking list titled "Gas" showing top 5 Branch names with gas usage values.
5. THE Top_Outlet_Section SHALL display a ranking list titled "Customer" showing top 5 Branch names with customer count values.
6. THE Top_Outlet_Section SHALL display each ranking entry with a rank number, the Branch name, and the corresponding aggregated value.
7. THE Top_Outlet_Section SHALL only be visible to users with admin or superadmin role.

### Requirement 4: Data Period Scope

**User Story:** As an admin, I want the outlet rankings to reflect the current month data, so that I see up-to-date operational insights.

#### Acceptance Criteria

1. THE Dashboard_System SHALL filter DailyRecord entries where the date column falls within the first day of the current month up to and including the current date.
2. WHEN no DailyRecord data exists for the Current_Month, THE Top_Outlet_Section SHALL display an empty state indicating no data is available.

### Requirement 5: Error Handling

**User Story:** As an admin, I want the dashboard to handle errors gracefully, so that a failure in loading rankings does not break the overall dashboard experience.

#### Acceptance Criteria

1. IF the API_Endpoint returns an error response, THEN THE Top_Outlet_Section SHALL display an error message without affecting other dashboard sections.
2. IF the API_Endpoint request times out, THEN THE Top_Outlet_Section SHALL display a loading failure indicator.
3. WHILE the Top_Outlet_Section is fetching data from the API_Endpoint, THE Top_Outlet_Section SHALL display a loading indicator.
