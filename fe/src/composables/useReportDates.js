import { ref } from 'vue'
import { axiosInstance } from '@/plugins/axios'
import { DateTime } from 'luxon'

/**
 * Composable untuk mengambil dan mengecek tanggal-tanggal yang sudah memiliki laporan harian.
 * Menggunakan Set untuk O(1) lookup performance.
 *
 * @param {import('vue').Ref<string|number>} branchId - Reactive ref branch_id
 * @returns {Object} - reportDates, loading, fetchReportDates, hasReport
 */
export function useReportDates(branchId) {
  const reportDates = ref(new Set())
  const loading = ref(false)

  /**
   * Fetch tanggal-tanggal yang memiliki laporan untuk branch dan bulan tertentu.
   * Jika branchId kosong, skip fetch dan clear reportDates.
   *
   * @param {string} [month] - Format YYYY-MM, default bulan ini
   */
  const fetchReportDates = async (month) => {
    if (!branchId.value) {
      reportDates.value = new Set()
      return
    }

    const monthStr = month || DateTime.now().toFormat('yyyy-MM')

    loading.value = true
    try {
      const response = await axiosInstance.get('/daily-records/report-dates', {
        params: {
          branch_id: branchId.value,
          month: monthStr,
        },
      })

      if (response.data.success) {
        reportDates.value = new Set(response.data.data)
      } else {
        reportDates.value = new Set()
      }
    } catch (error) {
      console.error('Failed to fetch report dates:', error)
      reportDates.value = new Set()
    } finally {
      loading.value = false
    }
  }

  /**
   * Cek apakah tanggal tertentu memiliki laporan.
   *
   * @param {string} dateStr - Format YYYY-MM-DD
   * @returns {boolean}
   */
  const hasReport = (dateStr) => {
    return reportDates.value.has(dateStr)
  }

  return {
    reportDates,
    loading,
    fetchReportDates,
    hasReport,
  }
}
