<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import Heading from '@/components/Heading.vue'
import Button from '@/components/ui/button/Button.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import Icon from '@/components/Icon.vue'
import {
  PaginationRoot,
  PaginationList,
  PaginationListItem,
  PaginationFirst,
  PaginationLast,
  PaginationPrev,
  PaginationNext,
  PaginationEllipsis,
} from 'reka-ui'

interface StatusPage {
  id: number
  title: string
  description: string
  icon: string
  path: string
  created_at: string
  updated_at: string
}

interface Props {
  statusPages: {
    data: StatusPage[]
    meta: {
      current_page: number
      last_page: number
      per_page: number
      total: number
      from: number
      to: number
      links: any[]
    }
    links: any
  }
}

const props = defineProps<Props>()

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString()
}

const deleteStatusPage = (statusPage: StatusPage) => {
  if (confirm(`Are you sure you want to delete "${statusPage.title}"?`)) {
    router.delete(route('status-pages.destroy', statusPage.id))
  }
}

const goToPage = (page: number) => {
  router.visit(route('status-pages.index', { page }), {
    preserveScroll: true,
    preserveState: true,
  })
}
</script>

<template>
  <Head title="Status Pages" />

  <AppLayout>
    <template #header>
      <Heading title="Status Pages" />
    </template>

    <div class="space-y-6 p-4">
      <!-- Header with Create Button -->
      <div class="flex justify-between items-center">
        <div>
          <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Your Status Pages</h2>
          <p class="text-sm text-gray-600 dark:text-gray-400">Manage and monitor your service status pages</p>
        </div>
        <Button as-child>
          <Link :href="route('status-pages.create')" class="flex items-center">
            <Icon name="plus" class="w-4 h-4 mr-2" />
            Create Status Page
          </Link>
        </Button>
      </div>

      <!-- Status Pages Grid -->
      <div v-if="props.statusPages.data.length > 0">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <Card v-for="statusPage in props.statusPages.data" :key="statusPage.id" class="hover:shadow-lg transition-shadow">
            <CardHeader>
              <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                  <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                    <Icon :name="statusPage.icon" class="w-5 h-5 text-blue-600" />
                  </div>
                  <div>
                    <CardTitle class="text-lg">{{ statusPage.title }}</CardTitle>
                    <p class="text-sm text-gray-500">{{ statusPage.path }}</p>
                  </div>
                </div>
                <DropdownMenu>
                  <DropdownMenuTrigger as-child>
                    <Button variant="ghost" size="sm">
                      <Icon name="more-horizontal" class="w-4 h-4" />
                    </Button>
                  </DropdownMenuTrigger>
                  <DropdownMenuContent align="end">
                    <DropdownMenuItem as-child>
                      <Link :href="route('status-page.public', statusPage.path)" target="_blank" rel="noopener noreferrer">
                        <Icon name="external-link" class="w-4 h-4 mr-2" />
                        View Public
                      </Link>
                    </DropdownMenuItem>
                    <DropdownMenuItem as-child>
                      <Link :href="route('status-pages.show', statusPage.id)">
                        <Icon name="eye" class="w-4 h-4 mr-2" />
                        View Details
                      </Link>
                    </DropdownMenuItem>
                    <DropdownMenuItem as-child>
                      <Link :href="route('status-pages.edit', statusPage.id)">
                        <Icon name="edit" class="w-4 h-4 mr-2" />
                        Edit
                      </Link>
                    </DropdownMenuItem>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem @click="deleteStatusPage(statusPage)" class="text-red-600">
                      <Icon name="trash" class="w-4 h-4 mr-2" />
                      Delete
                    </DropdownMenuItem>
                  </DropdownMenuContent>
                </DropdownMenu>
              </div>
            </CardHeader>
            <CardContent>
              <p class="text-sm text-gray-600 mb-4">{{ statusPage.description }}</p>
              <div class="flex items-center justify-between text-sm">
                <span class="text-gray-500">Created {{ formatDate(statusPage.created_at) }}</span>
                <Button variant="outline" size="sm" as-child>
                  <Link :href="route('status-pages.show', statusPage.id)">
                    Manage
                  </Link>
                </Button>
              </div>
            </CardContent>
          </Card>
        </div>
        <!-- Pagination Controls -->
        <div v-if="props.statusPages.meta && props.statusPages.meta.last_page > 1" class="flex justify-center mt-6">
          <PaginationRoot
            :total="props.statusPages.meta.total"
            :items-per-page="props.statusPages.meta.per_page"
            :default-page="props.statusPages.meta.current_page"
            @update:page="goToPage"
          >
            <PaginationList v-slot="{ items }" class="flex items-center gap-1">
              <PaginationFirst class="w-9 h-9 flex items-center justify-center bg-transparent cursor-pointer hover:bg-white dark:hover:bg-stone-700/70 transition disabled:opacity-50 rounded-lg text-gray-500 dark:text-gray-400">
                <Icon name="chevronsLeft" class="text-gray-500 dark:text-gray-400" />
              </PaginationFirst>
              <PaginationPrev class="w-9 h-9 flex items-center justify-center bg-transparent cursor-pointer hover:bg-white dark:hover:bg-stone-700/70 transition disabled:opacity-50 rounded-lg text-gray-500 dark:text-gray-400">
                <Icon name="chevronLeft" class="text-gray-500 dark:text-gray-400" />
              </PaginationPrev>
              <template v-for="(item, index) in items" :key="index">
                <PaginationListItem
                  v-if="item.type === 'page'"
                  :value="item.value"
                  :is-active="item.value === props.statusPages.meta.current_page"
                  class="w-9 h-9 flex items-center justify-center bg-transparent cursor-pointer hover:bg-white dark:hover:bg-stone-700/70 transition disabled:opacity-50 rounded-lg text-gray-500 dark:text-gray-400"
                >
                  {{ item.value }}
                </PaginationListItem>
                <PaginationEllipsis v-else :index="index">…</PaginationEllipsis>
              </template>
              <PaginationNext class="w-9 h-9 flex items-center justify-center bg-transparent cursor-pointer hover:bg-white dark:hover:bg-stone-700/70 transition disabled:opacity-50 rounded-lg text-gray-500 dark:text-gray-400">
                <Icon name="chevronRight" class="text-gray-500 dark:text-gray-400" />
              </PaginationNext>
              <PaginationLast class="w-9 h-9 flex items-center justify-center bg-transparent cursor-pointer hover:bg-white dark:hover:bg-stone-700/70 transition disabled:opacity-50 rounded-lg text-gray-500 dark:text-gray-400">
                <Icon name="chevronsRight" class="text-gray-500 dark:text-gray-400" />
              </PaginationLast>
            </PaginationList>
          </PaginationRoot>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="text-center py-12">
        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
          <Icon name="globe" class="w-8 h-8 text-gray-400 dark:text-gray-400" />
        </div>
        <h3 class="text-lg font-medium text-gray-900 mb-2 dark:text-gray-100">No status pages yet</h3>
        <p class="text-gray-600 mb-6 dark:text-gray-400">Create your first status page to share service updates with your users.</p>
        <Button as-child>
          <Link :href="route('status-pages.create')" class="flex items-center">
            <Icon name="plus" class="w-4 h-4 mr-2" />
            Create Status Page
          </Link>
        </Button>
      </div>
    </div>
  </AppLayout>
</template>
