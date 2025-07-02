
<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3'
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
  statusPages: StatusPage[]
}

defineProps<Props>()

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString()
}

const deleteStatusPage = (statusPage: StatusPage) => {
  if (confirm(`Are you sure you want to delete "${statusPage.title}"?`)) {
    router.delete(route('status-pages.destroy', statusPage.id))
  }
}
</script>

<template>
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
      <div v-if="statusPages.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <Card v-for="statusPage in statusPages" :key="statusPage.id" class="hover:shadow-lg transition-shadow">
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
                    <Link :href="route('status-page.public', statusPage.path)" target="_blank">
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

      <!-- Empty State -->
      <div v-else class="text-center py-12">
        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
          <Icon name="globe" class="w-8 h-8 text-gray-400" />
        </div>
        <h3 class="text-lg font-medium text-gray-900 mb-2">No status pages yet</h3>
        <p class="text-gray-600 mb-6">Create your first status page to share service updates with your users.</p>
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
