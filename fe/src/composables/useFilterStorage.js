/**
 * Composable untuk menyimpan dan memuat filter state dari sessionStorage
 * Digunakan untuk mempertahankan filter saat navigasi ke halaman detail/edit
 *
 * @param {string} storageKey - Key unik untuk sessionStorage
 * @param {Object} defaultFilters - Default values untuk filter
 * @param {Object} defaultPagination - Default values untuk pagination
 */
export function useFilterStorage(storageKey, defaultFilters = {}, defaultPagination = {}) {
  /**
   * Simpan filter dan pagination ke sessionStorage
   * @param {Object} filters - Filter values
   * @param {Object} pagination - Pagination values (current_page, per_page)
   */
  const saveState = (filters, pagination = null) => {
    try {
      const state = {
        filters: { ...filters },
        pagination: pagination ? { ...pagination } : null,
        timestamp: Date.now(),
      };
      sessionStorage.setItem(storageKey, JSON.stringify(state));
    } catch (error) {
      console.warn(`[useFilterStorage] Failed to save state for ${storageKey}:`, error);
    }
  };

  /**
   * Muat filter dan pagination dari sessionStorage
   * @returns {Object} { filters, pagination } atau default values jika tidak ada
   */
  const loadState = () => {
    try {
      const stored = sessionStorage.getItem(storageKey);
      if (stored) {
        const parsed = JSON.parse(stored);
        return {
          filters: { ...defaultFilters, ...parsed.filters },
          pagination: parsed.pagination
            ? { ...defaultPagination, ...parsed.pagination }
            : { ...defaultPagination },
          hasStoredState: true,
        };
      }
    } catch (error) {
      console.warn(`[useFilterStorage] Failed to load state for ${storageKey}:`, error);
    }

    return {
      filters: { ...defaultFilters },
      pagination: { ...defaultPagination },
      hasStoredState: false,
    };
  };

  /**
   * Hapus state dari sessionStorage
   */
  const clearState = () => {
    try {
      sessionStorage.removeItem(storageKey);
    } catch (error) {
      console.warn(`[useFilterStorage] Failed to clear state for ${storageKey}:`, error);
    }
  };

  /**
   * Cek apakah ada state tersimpan
   * @returns {boolean}
   */
  const hasState = () => {
    try {
      return sessionStorage.getItem(storageKey) !== null;
    } catch {
      return false;
    }
  };

  return {
    saveState,
    loadState,
    clearState,
    hasState,
  };
}
