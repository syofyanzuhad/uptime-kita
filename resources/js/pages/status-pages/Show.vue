<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
// Removed sortablejs-vue3 import - using native drag & drop
import AppLayout from '@/layouts/AppLayout.vue'
import Heading from '@/components/Heading.vue'
import Button from '@/components/ui/button/Button.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Label } from '@/components/ui/label'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Checkbox } from '@/components/ui/checkbox'
import Icon from '@/components/Icon.vue'

// --- INTERFACES ---
interface Monitor {
  id: number
  name: string
  url: string
  uptime_status: string
  uptime_check_enabled: boolean
  favicon?: string | null
  last_check_date?: string | null
  certificate_check_enabled: boolean
  certificate_status?: string | null
  certificate_expiration_date?: string | null
  down_for_events_count: number
  uptime_check_interval: number
  is_subscribed: boolean
  is_public: boolean
  today_uptime_percentage: number
  uptime_status_last_change_date?: string | null
  uptime_check_failure_reason?: string | null
  created_at: string
  updated_at: string
  histories?: MonitorHistory[]
}

interface StatusPage {
  id: number
  title: string
  description: string
  icon: string
  path: string
  created_at: string
  updated_at: string
  custom_domain?: string
  custom_domain_verified?: boolean
  force_https?: boolean
  monitors?: {
    data: Monitor[]
  }
}

interface MonitorHistory {
  id: number
  monitor_id: number
  status: string
  checked_at: string
  response_time?: number
  reason?: string | null
  created_at: string
  updated_at: string
}

interface Props {
  statusPage: StatusPage
}

const props = defineProps<Props>()

// --- REFS & STATE ---
const isModalOpen = ref(false)
const availableMonitors = ref<Monitor[]>([])
const selectedMonitors = ref<number[]>([])
const isLoading = ref(false)
const isDisassociateModalOpen = ref(false)
const monitorToDisassociate = ref<number | null>(null)

// State untuk daftar monitor yang bisa di-drag
const draggableMonitors = ref<Monitor[]>([])

// Drag and drop state
const draggedItem = ref<Monitor | null>(null)
const draggedOverIndex = ref<number>(-1)
const isUpdatingOrder = ref(false)
const lastOrderIds = ref<number[]>([]) // Track last saved order

// Gunakan 'watch' untuk mengisi dan menyinkronkan draggableMonitors dengan props
watch(() => props.statusPage.monitors?.data, (newMonitors) => {
  draggableMonitors.value = newMonitors ? [...newMonitors] : []
  // Initialize lastOrderIds when monitors load
  lastOrderIds.value = newMonitors ? newMonitors.map(m => m.id) : []
}, { immediate: true, deep: true })

// --- SEARCH STATE FOR MODAL ---
const searchQuery = ref('')
const filteredAvailableMonitors = computed(() => {
  if (!searchQuery.value.trim()) return availableMonitors.value
  const q = searchQuery.value.toLowerCase()
  return availableMonitors.value.filter(m =>
    m.name.toLowerCase().includes(q) ||
    m.url.toLowerCase().includes(q)
  )
})

// --- COMPUTED PROPERTIES ---
const baseUrl = computed(() => {
  if (typeof window !== 'undefined') {
    return window.location.origin
  }
  return ''
})

const isButtonDisabled = computed(() => {
  return selectedMonitors.value.length === 0 || isLoading.value
})

// --- HELPER FUNCTIONS ---
const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString()
}

const getStatusColor = (status?: string) => {
  switch (status?.toLowerCase()) {
    case 'up':
      return 'bg-green-500'
    case 'down':
      return 'bg-red-500'
    case 'warning':
      return 'bg-yellow-500'
    default:
      return 'bg-gray-400'
  }
}

// --- CORE LOGIC FUNCTIONS ---
const openAddMonitorModal = async () => {
  selectedMonitors.value = []
  isModalOpen.value = true
  await fetchAvailableMonitors()
}

const fetchAvailableMonitors = async () => {
  try {
    isLoading.value = true
    const response = await fetch(route('status-pages.monitors.available', props.statusPage.id))
    const data = await response.json()
    availableMonitors.value = data.data || data
  } catch (error) {
    console.error('Failed to fetch available monitors:', error)
  } finally {
    isLoading.value = false
  }
}

const handleMonitorSelection = (checked: boolean, monitorId: number) => {
  const index = selectedMonitors.value.indexOf(monitorId)
  console.log(checked, monitorId)

  if (checked) {
    if (index === -1) {
      selectedMonitors.value.push(monitorId)
    }
  } else {
    if (index > -1) {
      selectedMonitors.value.splice(index, 1)
    }
  }
}

const associateMonitors = async () => {
  if (selectedMonitors.value.length === 0) return

  try {
    isLoading.value = true
    await router.post(route('status-pages.monitors.associate', props.statusPage.id), {
      monitor_ids: selectedMonitors.value
    }, {
      onSuccess: () => {
        selectedMonitors.value = []
        isModalOpen.value = false
        router.reload()
      }
    })
  } catch (error) {
    console.error('Failed to associate monitors:', error)
  } finally {
    isLoading.value = false
  }
}

const openDisassociateModal = (monitorId: number) => {
  monitorToDisassociate.value = monitorId
  isDisassociateModalOpen.value = true
}

const confirmDisassociateMonitor = async () => {
  if (monitorToDisassociate.value === null) return
  try {
    await router.delete(route('status-pages.monitors.disassociate', [props.statusPage.id, monitorToDisassociate.value]), {
      onSuccess: () => router.reload()
    })
  } catch (error) {
    console.error('Failed to disassociate monitor:', error)
  } finally {
    isDisassociateModalOpen.value = false
    monitorToDisassociate.value = null
  }
}

// Native drag & drop handlers
const handleDragStart = (event: DragEvent, monitor: Monitor) => {
  draggedItem.value = monitor
  event.dataTransfer!.effectAllowed = 'move'
  event.dataTransfer!.setData('text/html', '')
}

const handleDragOver = (event: DragEvent, index: number) => {
  event.preventDefault()
  draggedOverIndex.value = index
  event.dataTransfer!.dropEffect = 'move'
}

const handleDragLeave = () => {
  draggedOverIndex.value = -1
}

const handleDrop = (event: DragEvent, targetIndex: number) => {
  event.preventDefault()
  
  if (!draggedItem.value) return
  
  const draggedIndex = draggableMonitors.value.findIndex(m => m.id === draggedItem.value!.id)
  
  if (draggedIndex !== -1 && draggedIndex !== targetIndex) {
    // Remove item from old position
    const [movedItem] = draggableMonitors.value.splice(draggedIndex, 1)
    // Insert at new position
    draggableMonitors.value.splice(targetIndex, 0, movedItem)
    
    // Debounce the update call to prevent multiple rapid calls
    setTimeout(() => {
      updateMonitorOrder()
    }, 100)
  }
  
  // Reset drag state
  draggedItem.value = null
  draggedOverIndex.value = -1
}

const handleDragEnd = () => {
  draggedItem.value = null
  draggedOverIndex.value = -1
}

// Function to update monitor order on the server
const updateMonitorOrder = async () => {
  // The list is automatically updated by sortablejs-vue3
  const orderedMonitorIds = draggableMonitors.value.map(monitor => monitor.id)

  router.post(route('status-page-monitor.reorder', props.statusPage.id), {
    monitor_ids: orderedMonitorIds
  }, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      console.log('Monitor order updated successfully!')
    },
    onError: (errors) => {
      console.error('Failed to update monitor order:', errors)
      draggableMonitors.value = props.statusPage.monitors?.data ? [...props.statusPage.monitors.data] : []
    }
  })
}
</script>

<template>
  <Head title="Status Page" />

  <AppLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
          <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
            <Icon :name="statusPage.icon" class="w-6 h-6 text-blue-600" />
          </div>
          <div>
            <Heading :title="statusPage.title" />
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ statusPage.description }}</p>
          </div>
        </div>
        <div class="flex items-center space-x-3">
          <Button variant="outline" as-child>
            <a :href="route('status-page.public', statusPage.path)" target="_blank">
              <Icon name="external-link" class="w-4 h-4 mr-2" />
              View Public
            </a>
          </Button>
          <Button as-child>
            <Link :href="route('status-pages.edit', statusPage.id)">
              <Icon name="edit" class="w-4 h-4 mr-2" />
              Edit
            </Link>
          </Button>
        </div>
      </div>
    </template>

    <div class="space-y-6 p-4">
      <Card>
        <CardHeader>
          <div class="flex items-center justify-between">
            <CardTitle>Status Page Information</CardTitle>
            <Button 
              variant="ghost" 
              size="sm"
              @click="router.visit(route('status-pages.edit', statusPage.id))"
              title="Edit Status Page"
            >
              <Icon name="pencil" class="w-4 h-4" />
            </Button>
          </div>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <Label class="text-sm font-medium text-gray-700 dark:text-gray-300">Public URL</Label>
              <p class="text-sm text-gray-900 dark:text-gray-100 mt-1">
                <a :href="route('status-page.public', statusPage.path)" target="_blank" class="text-blue-600 hover:underline">
                  {{ baseUrl }}/status/{{ statusPage.path }}
                </a>
              </p>
              <div v-if="statusPage.custom_domain" class="mt-2">
                <p class="text-sm text-gray-600 dark:text-gray-400">Custom Domain:</p>
                <div class="flex items-center gap-2">
                  <a :href="`https://${statusPage.custom_domain}`" target="_blank" class="text-blue-600 hover:underline text-sm">
                    {{ statusPage.custom_domain }}
                  </a>
                  <span v-if="statusPage.custom_domain_verified" class="inline-flex items-center px-2 py-0.5 text-xs font-medium text-green-700 bg-green-100 rounded-full dark:bg-green-900 dark:text-green-300">
                    <Icon name="check-circle" class="w-3 h-3 mr-1" />
                    Verified
                  </span>
                  <span v-else class="inline-flex items-center px-2 py-0.5 text-xs font-medium text-yellow-700 bg-yellow-100 rounded-full dark:bg-yellow-900 dark:text-yellow-300">
                    <Icon name="alert-circle" class="w-3 h-3 mr-1" />
                    Pending
                  </span>
                </div>
              </div>
            </div>
            <div>
              <Label class="text-sm font-medium text-gray-700 dark:text-gray-300">Created</Label>
              <p class="text-sm text-gray-900 dark:text-gray-100 mt-1">{{ formatDate(statusPage.created_at) }}</p>
            </div>
            <div>
              <Label class="text-sm font-medium text-gray-700 dark:text-gray-300">Last Updated</Label>
              <p class="text-sm text-gray-900 dark:text-gray-100 mt-1">{{ formatDate(statusPage.updated_at) }}</p>
            </div>
            <div>
              <Label class="text-sm font-medium text-gray-700 dark:text-gray-300">Monitors</Label>
              <p class="text-sm text-gray-900 dark:text-gray-100 mt-1">{{ statusPage.monitors?.data.length || 0 }} monitors</p>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <div class="flex items-center justify-between">
            <CardTitle>Associated Monitors</CardTitle>
            <Button @click="openAddMonitorModal" v-if="draggableMonitors && draggableMonitors.length > 0">
              <span class="flex items-center">
                <Icon name="plus" class="w-4 h-4 mr-2" />
                Add Monitor
              </span>
            </Button>
          </div>
        </CardHeader>
        <CardContent>
          <div v-if="!draggableMonitors || draggableMonitors.length === 0" class="text-center py-8">
            <Icon name="monitor" class="w-12 h-12 text-gray-300 mx-auto mb-4" />
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">No monitors added</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-4">Add monitors to display their status on this page.</p>
            <Button @click="openAddMonitorModal">
              <span class="flex items-center">
                <Icon name="plus" class="w-4 h-4 mr-2" />
                Add Monitor
              </span>
            </Button>
          </div>

          <!-- Native HTML5 drag & drop implementation -->
          <div v-else class="space-y-3">
            <div
              v-for="(monitor, index) in draggableMonitors"
              :key="monitor.id"
              :draggable="true"
              @dragstart="handleDragStart($event, monitor)"
              @dragover="handleDragOver($event, index)"
              @dragleave="handleDragLeave"
              @drop="handleDrop($event, index)"
              @dragend="handleDragEnd"
              :class="[
                'flex items-center justify-between p-3 border rounded-lg bg-white dark:bg-gray-800 transition-all duration-200',
                {
                  'opacity-50 scale-95': draggedItem?.id === monitor.id,
                  'border-blue-400 bg-blue-50 dark:bg-blue-900/20': draggedOverIndex === index && draggedItem?.id !== monitor.id,
                  'hover:bg-gray-50 dark:hover:bg-gray-700': draggedItem?.id !== monitor.id,
                  'cursor-grabbing': draggedItem?.id === monitor.id,
                  'cursor-grab': !draggedItem
                }
              ]"
            >
              <div class="flex items-center space-x-3">
                <div class="text-gray-400 hover:text-gray-600 transition-colors">
                  <Icon name="gripVertical" class="w-5 h-5" />
                </div>
                <div class="flex items-center space-x-2">
                  <img 
                    v-if="monitor.favicon" 
                    :src="monitor.favicon" 
                    alt="favicon" 
                    class="w-4 h-4 rounded"
                  />
                  <span 
                    :class="getStatusColor(monitor.uptime_status)" 
                    class="w-3 h-3 rounded-full"
                  ></span>
                </div>
                <div>
                  <p class="font-medium text-gray-900 dark:text-gray-100">{{ monitor.name }}</p>
                  <p class="text-sm text-gray-500 dark:text-gray-400">{{ monitor.url }}</p>
                </div>
              </div>
              <div class="flex items-center space-x-2">
                <span class="text-sm text-gray-500 dark:text-gray-400">
                  {{ monitor.today_uptime_percentage?.toFixed(1) }}%
                </span>
                <Button 
                  variant="ghost" 
                  size="sm" 
                  @click="openDisassociateModal(monitor.id)"
                  :disabled="!!draggedItem || isUpdatingOrder"
                >
                  <Icon name="x" class="w-4 h-4" />
                </Button>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Add Monitor Modal -->
      <Dialog v-model:open="isModalOpen">
        <DialogContent class="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>Add Monitors to Status Page</DialogTitle>
            <DialogDescription>
              Select monitors to display on this status page. Only monitors you own can be added.
            </DialogDescription>
          </DialogHeader>

          <div class="space-y-4">
            <!-- Search input -->
            <div v-if="availableMonitors.length > 0 && !isLoading" class="mb-2">
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Search monitors by name or URL..."
                class="w-full px-3 py-2 border rounded focus:outline-none focus:ring"
              />
            </div>
            <div v-if="isLoading" class="text-center py-4">
              <p class="text-gray-500 dark:text-gray-400">Loading available monitors...</p>
            </div>

            <div v-else-if="availableMonitors.length === 0" class="text-center py-4">
              <p class="text-gray-500 dark:text-gray-400">No available monitors to add.</p>
              <p class="text-sm text-gray-400 dark:text-gray-400 mt-2">Create a monitor first to add it to this status page.</p>
            </div>

            <div v-else class="space-y-3 max-h-60 overflow-y-auto">
              <div v-if="filteredAvailableMonitors.length === 0" class="text-center text-gray-400 py-4">
                No monitors match your search.
              </div>
              <div v-for="monitor in filteredAvailableMonitors" :key="monitor.id" class="flex items-center space-x-3 p-3 border rounded-lg">
                <Checkbox
                  :id="`monitor-${monitor.id}`"
                  :checked="selectedMonitors.includes(monitor.id)"
                  @update:modelValue="(v) => { handleMonitorSelection(!!v, monitor.id) }"
                />
                <img v-if="monitor.favicon" :src="monitor.favicon" alt="favicon" class="w-5 h-5 mr-2 rounded" />
                <div class="flex-1">
                  <Label :for="`monitor-${monitor.id}`" class="font-medium cursor-pointer">
                    {{ monitor.name }}
                  </Label>
                  <p class="text-sm text-gray-500">{{ monitor.url }}</p>
                  <div class="flex flex-wrap gap-2 mt-1 text-xs text-gray-500">
                    <span>Uptime: {{ monitor.today_uptime_percentage?.toFixed(2) }}%</span>
                    <span>Status: <span :class="getStatusColor(monitor.uptime_status)"></span> {{ monitor.uptime_status || 'Unknown' }}</span>
                    <span v-if="monitor.last_check_date">Last Check: {{ formatDate(monitor.last_check_date) }}</span>
                    <span v-if="monitor.down_for_events_count > 0">Down Events: {{ monitor.down_for_events_count }}</span>
                    <span v-if="monitor.certificate_check_enabled">Cert: {{ monitor.certificate_status || 'N/A' }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <DialogFooter>
            <Button variant="outline" @click="isModalOpen = false">
              Cancel
            </Button>
            <Button @click="associateMonitors" :disabled="isButtonDisabled">
              <span class="flex items-center">
                <Icon name="plus" class="w-4 h-4 mr-2" />
                Add Selected ({{ selectedMonitors.length }})
              </span>
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <!-- Disassociate Modal -->
      <Dialog v-model:open="isDisassociateModalOpen">
        <DialogContent class="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>Remove Monitor from Status Page?</DialogTitle>
            <DialogDescription>
              Are you sure you want to remove this monitor from the status page? This action cannot be undone.
            </DialogDescription>
          </DialogHeader>
          <DialogFooter>
            <Button variant="outline" @click="isDisassociateModalOpen = false">
              Cancel
            </Button>
            <Button variant="destructive" @click="confirmDisassociateMonitor">
              Remove
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  </AppLayout>
</template>
