# Requirements Document

## Introduction

This feature adds granular reset capability for electricity sub-items (turunan) in the "Reset Daily Usage" dialog. Currently, when a superadmin resets electricity usage, all ElectricityReading records for the selected branch are reset to zero. This enhancement allows the superadmin to selectively choose specific electricity meters to reset, providing more precise control over the reset operation. The UI introduces a two-step flow: first select the category, then when "Listrik" is selected, a second step appears with checkboxes for individual meters belonging to the selected branch.

## Glossary

- **Reset_Dialog**: The modal dialog in the frontend that allows superadmin to reset daily usage data for a selected branch and category
- **Meter_Selector**: The second-step UI panel within the Reset_Dialog that displays checkboxes for individual electricity meters when the "Listrik" category is selected
- **ElectricityMeter**: A database model representing a physical electricity meter assigned to a branch, with attributes meter_name, meter_number, location, branch_id, and is_active
- **ElectricityReading**: A database model representing a meter reading record linked to a DailyRecord and an ElectricityMeter via electricity_meter_id
- **Reset_API**: The backend endpoint POST /daily-records/report/daily-usage/reset that performs the reset operation
- **Superadmin**: A user with the superadmin role who has exclusive permission to perform reset operations
- **Branch**: An outlet or branch location that owns one or more ElectricityMeter records

## Requirements

### Requirement 1: Meter Selection UI in Reset Dialog

**User Story:** As a superadmin, I want to see and select specific electricity meters when resetting electricity usage, so that I can reset only the meters that need correction without affecting others.

#### Acceptance Criteria

1. WHEN the superadmin selects "Listrik" as the category in the Reset_Dialog, THE Meter_Selector SHALL display a list of electricity meters belonging to the currently selected branch.
2. THE Meter_Selector SHALL display each ElectricityMeter with its meter_name and location as the label text.
3. THE Meter_Selector SHALL provide a checkbox input for each active ElectricityMeter in the list.
4. THE Meter_Selector SHALL provide a "Pilih Semua" (Select All) checkbox that toggles selection of all listed meters simultaneously.
5. WHEN the "Pilih Semua" checkbox is checked, THE Meter_Selector SHALL mark all individual meter checkboxes as selected.
6. WHEN the "Pilih Semua" checkbox is unchecked, THE Meter_Selector SHALL clear all individual meter checkboxes.
7. WHEN all individual meter checkboxes are manually selected, THE Meter_Selector SHALL automatically mark the "Pilih Semua" checkbox as checked.
8. WHEN any individual meter checkbox is unchecked after all were selected, THE Meter_Selector SHALL automatically unmark the "Pilih Semua" checkbox.
9. THE Meter_Selector SHALL only display meters where is_active equals true.
10. IF the branch has no active electricity meters, THEN THE Meter_Selector SHALL display a message indicating no meters are available for the selected branch.

### Requirement 2: Two-Step Reset Flow

**User Story:** As a superadmin, I want the reset dialog to guide me through a two-step process for electricity, so that the interaction is clear and I can confirm my meter selection before resetting.

#### Acceptance Criteria

1. THE Reset_Dialog SHALL present category selection (gas, water, electricity) as the first step.
2. WHEN the superadmin selects "Listrik" category, THE Reset_Dialog SHALL display the Meter_Selector as a second step before allowing the reset action.
3. WHEN the superadmin selects "Gas" or "Air" category, THE Reset_Dialog SHALL proceed directly without showing meter selection.
4. WHILE the Meter_Selector is visible with no meters selected, THE Reset_Dialog SHALL disable the reset confirmation button.
5. WHEN at least one meter is selected in the Meter_Selector, THE Reset_Dialog SHALL enable the reset confirmation button.

### Requirement 3: Backend API Accepts Meter Filter

**User Story:** As a superadmin, I want the reset API to accept an optional list of electricity meter IDs, so that only readings from those specific meters are reset.

#### Acceptance Criteria

1. THE Reset_API SHALL accept an optional parameter electricity_meter_ids as an array of integer values in the request body.
2. WHEN electricity_meter_ids is provided and the category is "electricity", THE Reset_API SHALL reset only ElectricityReading records whose electricity_meter_id is in the provided array.
3. WHEN electricity_meter_ids is not provided and the category is "electricity", THE Reset_API SHALL reset all ElectricityReading records for the matching daily records (preserving current behavior).
4. WHEN electricity_meter_ids is provided as an empty array, THE Reset_API SHALL reset all ElectricityReading records for the matching daily records (treating empty array same as omitted).
5. THE Reset_API SHALL validate that each value in electricity_meter_ids exists in the electricity_meters table.
6. IF any electricity_meter_id in the array does not exist, THEN THE Reset_API SHALL return a 422 validation error with a descriptive message.
7. THE Reset_API SHALL include the count of reset electricity readings in the response payload.

### Requirement 4: Backward Compatibility

**User Story:** As a developer, I want the existing reset behavior to remain unchanged when no meter IDs are specified, so that current integrations and workflows continue to function correctly.

#### Acceptance Criteria

1. WHEN a reset request is sent without the electricity_meter_ids parameter, THE Reset_API SHALL reset all ElectricityReading records for the matching daily records (identical to current behavior).
2. WHEN a reset request is sent for category "gas" or "water", THE Reset_API SHALL ignore the electricity_meter_ids parameter.
3. THE Reset_API SHALL continue to require the branch_id parameter as mandatory.
4. THE Reset_API SHALL continue to restrict reset operations to users with the superadmin role.

### Requirement 5: Meter Data Fetching

**User Story:** As a superadmin, I want the reset dialog to automatically load the meters for the selected branch, so that I can see which meters are available without manual lookup.

#### Acceptance Criteria

1. WHEN the superadmin selects "Listrik" as the category, THE Reset_Dialog SHALL fetch meter data from the existing GET /branches/{branchId}/electricity-meters endpoint.
2. THE Reset_Dialog SHALL filter the fetched meters to display only those where is_active equals true.
3. WHILE meter data is being loaded, THE Meter_Selector SHALL display a loading indicator.
4. IF the meter data fetch fails, THEN THE Reset_Dialog SHALL display an error message and disable the reset confirmation button.
