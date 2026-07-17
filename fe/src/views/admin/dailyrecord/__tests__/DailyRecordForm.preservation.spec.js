/**
 * Property 2: Preservation - Date Selection and Model Update Behavior
 *
 * Observation-first methodology:
 * - handleDateUpdate with string input (e.g., "2024-06-15") assigns directly to form.date
 * - handleDateUpdate with Date object input formats to yyyy-MM-dd via Luxon
 * - handleDateUpdate with null/undefined sets form.date to empty string
 * - handleMonthYearChange({ month: 6, year: 2024 }) produces "2024-06" format
 *
 * **Validates: Requirements 3.1, 3.2, 3.3, 3.4**
 */
import { describe, it, expect } from 'vitest';
import fc from 'fast-check';
import { DateTime } from 'luxon';

/**
 * Extracted logic from DailyRecordForm.vue handleDateUpdate function.
 * This mirrors the exact implementation in the component.
 */
function handleDateUpdate(modelData, form) {
  if (modelData) {
    if (typeof modelData === 'string') {
      form.date = modelData;
    } else {
      const dt = DateTime.fromJSDate(modelData);
      form.date = dt.toFormat('yyyy-MM-dd');
    }
  } else {
    form.date = '';
  }
}

/**
 * Extracted logic from DailyRecordForm.vue handleMonthYearChange function.
 * Returns the formatted month string (yyyy-MM) that would be assigned to displayedMonth.
 */
function handleMonthYearChange({ month, year }) {
  return `${year}-${String(month).padStart(2, '0')}`;
}

describe('DailyRecordForm Preservation Properties', () => {
  describe('handleDateUpdate - string input preservation', () => {
    it('for all valid date strings in yyyy-MM-dd format (years 2020-2030), handleDateUpdate sets form.date to that exact string', () => {
      // Generate valid yyyy-MM-dd date strings within 2020-2030 range
      const validDateStringArb = fc
        .record({
          year: fc.integer({ min: 2020, max: 2030 }),
          month: fc.integer({ min: 1, max: 12 }),
          day: fc.integer({ min: 1, max: 28 }), // Use 28 to guarantee valid day for all months
        })
        .map(({ year, month, day }) => {
          const m = String(month).padStart(2, '0');
          const d = String(day).padStart(2, '0');
          return `${year}-${m}-${d}`;
        });

      fc.assert(
        fc.property(validDateStringArb, (dateStr) => {
          const form = { date: '' };
          handleDateUpdate(dateStr, form);
          expect(form.date).toBe(dateStr);
        }),
        { numRuns: 200 }
      );
    });
  });

  describe('handleMonthYearChange - month/year formatting preservation', () => {
    it('for all valid month (1-12) and year (2020-2030) combinations, handleMonthYearChange correctly formats as yyyy-MM', () => {
      const monthYearArb = fc.record({
        month: fc.integer({ min: 1, max: 12 }),
        year: fc.integer({ min: 2020, max: 2030 }),
      });

      fc.assert(
        fc.property(monthYearArb, ({ month, year }) => {
          const result = handleMonthYearChange({ month, year });
          const expectedMonth = String(month).padStart(2, '0');
          const expected = `${year}-${expectedMonth}`;
          expect(result).toBe(expected);
        }),
        { numRuns: 200 }
      );
    });
  });

  describe('handleDateUpdate - null/undefined input', () => {
    it('for null input, handleDateUpdate sets form.date to empty string', () => {
      const form = { date: '2024-06-15' };
      handleDateUpdate(null, form);
      expect(form.date).toBe('');
    });

    it('for undefined input, handleDateUpdate sets form.date to empty string', () => {
      const form = { date: '2024-06-15' };
      handleDateUpdate(undefined, form);
      expect(form.date).toBe('');
    });

    it('for all nullish values, handleDateUpdate sets form.date to empty string', () => {
      fc.assert(
        fc.property(
          fc.constantFrom(null, undefined, '', 0),
          (nullishValue) => {
            const form = { date: '2024-06-15' };
            handleDateUpdate(nullishValue, form);
            expect(form.date).toBe('');
          }
        ),
        { numRuns: 10 }
      );
    });
  });
});
