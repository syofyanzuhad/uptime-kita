<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import Heading from '@/components/Heading.vue'
import Button from '@/components/ui/button/Button.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
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
  monitors?: {
    data: Monitor[]
  }
}

interface MonitorHistory {
  id: number
  monitor_id: number
  status: string
  checked_at: string // ISO date string
  response_time?: number // in milliseconds, optional
  reason?: string | null // reason for failure, if any
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

const copyToClipboard = async (text: string) => {
  try {
    await navigator.clipboard.writeText(text)
    // Anda bisa menambahkan notifikasi toast di sini
  } catch (err) {
    console.error('Failed to copy text: ', err)
  }
}

// --- CORE LOGIC FUNCTIONS ---
const openAddMonitorModal = async () => {
  selectedMonitors.value = [] // Reset pilihan saat modal dibuka
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

// ** INI BAGIAN YANG DIPERBAIKI **
// Logika dipindahkan ke method tersendiri untuk kebersihan kode.
const handleMonitorSelection = (checked: boolean, monitorId: number) => {
  const index = selectedMonitors.value.indexOf(monitorId)
  console.log(checked, monitorId)

  if (checked) {
    // Jika dicentang dan belum ada di array, tambahkan
    if (index === -1) {
      selectedMonitors.value.push(monitorId)
    }
  } else {
    // Jika tidak dicentang dan ada di array, hapus
    if (index > -1) {
      selectedMonitors.value.splice(index, 1)
    }
  }
}

const associateMonitors = async () => {
  if (selectedMonitors.value.length === 0) return

  try {
    isLoading.value = true
    // Lebih efisien mengirim semua ID sekaligus jika backend mendukung
    await router.post(route('status-pages.monitors.associate', props.statusPage.id), {
      monitor_ids: selectedMonitors.value // Kirim sebagai array
    }, {
      onSuccess: () => {
        // Reset dan tutup modal
        selectedMonitors.value = []
        isModalOpen.value = false
        router.reload() // Refresh halaman untuk menampilkan monitor terbaru
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
          <CardTitle>Status Page Information</CardTitle>
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
            <Button variant="outline" size="sm" @click="openAddMonitorModal">
              <span class="flex items-center">
                <Icon name="plus" class="w-4 h-4 mr-2" />
                Add Monitor
              </span>
            </Button>
          </div>
        </CardHeader>
        <CardContent>
          <div v-if="statusPage.monitors && statusPage.monitors.data.length > 0" class="space-y-3">
            <div v-for="monitor in statusPage.monitors.data" :key="monitor.id" class="flex items-center justify-between p-3 border rounded-lg">
              <div class="flex items-center space-x-3">
                <img v-if="monitor.favicon" :src="monitor.favicon" alt="favicon" class="w-5 h-5 mr-2 rounded" />
                <div class="w-3 h-3 rounded-full" :class="getStatusColor(monitor.uptime_status)"></div>
                <div>
                  <p class="font-medium">{{ monitor.name }}</p>
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
              <div class="flex items-center space-x-2">
                <Button variant="ghost" size="sm" @click="openDisassociateModal(monitor.id)">
                  <Icon name="x" class="w-4 h-4" />
                </Button>
              </div>
            </div>
          </div>
          <div v-else class="text-center py-8">
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
        </CardContent>
      </Card>

      <!-- <Card>
        <CardHeader>
          <CardTitle>Embed Code</CardTitle>
          <CardDescription>
            Embed this status page on your website or share the link with your users.
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div class="space-y-4">
            <div>
              <Label class="text-sm font-medium text-gray-700 dark:text-gray-300">Direct Link</Label>
              <div class="flex mt-1">
                <Input :value="`${baseUrl}/status/${statusPage.path}`" readonly class="rounded-r-none" />
                <Button variant="outline" class="rounded-l-none" @click="copyToClipboard(`${baseUrl}/status/${statusPage.path}`)">
                  <Icon name="copy" class="w-4 h-4" />
                </Button>
              </div>
            </div>
            <div>
              <Label class="text-sm font-medium text-gray-700 dark:text-gray-300">Embed iframe</Label>
              <div class="flex mt-1">
                <Input
                  :value="`<iframe src='${baseUrl}/status/${statusPage.path}' width='100%' height='600' frameborder='0'></iframe>`"
                  readonly
                  class="rounded-r-none"
                />
                <Button variant="outline" class="rounded-l-none" @click="copyToClipboard(`<iframe src='${baseUrl}/status/${statusPage.path}' width='100%' height='600' frameborder='0'></iframe>`)">
                  <Icon name="copy" class="w-4 h-4" />
                </Button>
              </div>
            </div>
          </div>
        </CardContent>
      </Card> -->

      <Dialog v-model:open="isModalOpen">
        <DialogContent class="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>Add Monitors to Status Page</DialogTitle>
            <DialogDescription>
              Select monitors to display on this status page. Only monitors you own can be added.
            </DialogDescription>
          </DialogHeader>

          <div class="space-y-4">
            <div v-if="isLoading" class="text-center py-4">
              <p class="text-gray-500 dark:text-gray-400">Loading available monitors...</p>
            </div>

            <div v-else-if="availableMonitors.length === 0" class="text-center py-4">
              <p class="text-gray-500 dark:text-gray-400">No available monitors to add.</p>
              <p class="text-sm text-gray-400 dark:text-gray-400 mt-2">Create a monitor first to add it to this status page.</p>
            </div>

            <div v-else class="space-y-3 max-h-60 overflow-y-auto">
              <div v-for="monitor in availableMonitors" :key="monitor.id" class="flex items-center space-x-3 p-3 border rounded-lg">
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
