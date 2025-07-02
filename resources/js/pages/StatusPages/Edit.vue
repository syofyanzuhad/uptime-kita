<template>
  <AppLayout>
    <template #header>
      <Heading title="Edit Status Page" />
    </template>

    <div class="max-w-2xl mx-auto">
      <Card>
        <CardHeader>
          <CardTitle>Edit Status Page</CardTitle>
          <CardDescription>
            Update your status page information and settings.
          </CardDescription>
        </CardHeader>
        <CardContent>
          <form @submit.prevent="submit" class="space-y-6">
            <div class="space-y-2">
              <Label for="title">Title</Label>
              <Input
                id="title"
                v-model="form.title"
                type="text"
                placeholder="My Service Status"
                required
              />
              <InputError :message="form.errors.title" />
            </div>

            <div class="space-y-2">
              <Label for="description">Description</Label>
              <textarea
                id="description"
                v-model="form.description"
                rows="3"
                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="Brief description of your service"
                required
              />
              <InputError :message="form.errors.description" />
            </div>

            <div class="space-y-2">
              <Label for="icon">Icon</Label>
              <Input
                id="icon"
                v-model="form.icon"
                type="text"
                placeholder="globe"
                required
              />
              <p class="text-sm text-gray-500">
                Use icon names from Lucide React (e.g., globe, server, database)
              </p>
              <InputError :message="form.errors.icon" />
            </div>

            <div class="space-y-2">
              <Label for="path">URL Path</Label>
              <Input
                id="path"
                v-model="form.path"
                type="text"
                placeholder="my-service"
                required
              />
              <p class="text-sm text-gray-500">
                Your status page is available at /status/{{ form.path }}
              </p>
              <InputError :message="form.errors.path" />
            </div>

            <div class="flex justify-end space-x-3">
              <Button type="button" variant="outline" @click="router.visit(route('status-pages.show', statusPage.id))">
                Cancel
              </Button>
              <Button type="submit" :disabled="form.processing">
                <Icon v-if="form.processing" name="loader-2" class="w-4 h-4 mr-2 animate-spin" />
                Update Status Page
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import Heading from '@/components/Heading.vue'
import Button from '@/components/ui/button/Button.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import InputError from '@/components/InputError.vue'
import Icon from '@/components/Icon.vue'

interface StatusPage {
  id: number
  title: string
  description: string
  icon: string
  path: string
}

interface Props {
  statusPage: StatusPage
}

const props = defineProps<Props>()

const form = useForm({
  title: props.statusPage.title,
  description: props.statusPage.description,
  icon: props.statusPage.icon,
  path: props.statusPage.path,
})

const submit = () => {
  form.put(route('status-pages.update', props.statusPage.id))
}
</script>
