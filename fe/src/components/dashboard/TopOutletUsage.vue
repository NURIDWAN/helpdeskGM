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
