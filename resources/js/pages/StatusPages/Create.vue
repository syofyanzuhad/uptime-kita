<template>
  <Head title="Create Status Page" />
  <AppLayout>
    <template #header>
      <Heading title="Create Status Page" />
    </template>

    <div class="max-w-2xl mx-auto">
      <Card>
        <CardHeader>
          <CardTitle>New Status Page</CardTitle>
          <CardDescription>
            Create a new status page to share service updates with your users.
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
              <Label for="path">URL Path (Optional)</Label>
              <div class="flex space-x-2">
                <Input
                  id="path"
                  v-model="form.path"
                  type="text"
                  placeholder="my-service"
                />
                <Button type="button" variant="secondary" @click="generateRandomSlug" title="Generate random slug">
                  Generate
                </Button>
              </div>
              <p class="text-sm text-gray-500">
                Leave empty to auto-generate from title. Your status page will be available at /status/{path}
              </p>
              <InputError :message="form.errors.path" />
            </div>

            <div class="flex justify-end space-x-3">
              <Button type="button" variant="outline" @click="router.visit(route('status-pages.index'))">
                Cancel
              </Button>
              <Button type="submit" :disabled="form.processing">
                <Icon v-if="form.processing" name="loader-2" class="w-4 h-4 mr-2 animate-spin" />
                Create Status Page
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
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import Heading from '@/components/Heading.vue'
import Button from '@/components/ui/button/Button.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import InputError from '@/components/InputError.vue'
import Icon from '@/components/Icon.vue'

const form = useForm({
  title: '',
  description: '',
  icon: '',
  path: '',
})

const submit = () => {
  form.post(route('status-pages.store'))
}

function generateRandomSlug() {
  // Generates a slug: word-word-string (e.g., 'alpha-bravo-1a2')
  const words = [
    'alpha', 'bravo', 'charlie', 'delta', 'echo', 'foxtrot',
    'golf', 'hotel', 'india', 'juliet', 'kilo', 'lima',
    'mike', 'november', 'oscar', 'papa', 'quebec', 'romeo',
    'sierra', 'tango', 'uniform', 'victor', 'whiskey', 'xray',
    'yankee', 'zulu'
  ];
  const chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
  const first = words[Math.floor(Math.random() * words.length)];
  let second;
  do {
    second = words[Math.floor(Math.random() * words.length)];
  } while (second === first);
  let randStr = '';
  for (let i = 0; i < 5; i++) {
    randStr += chars.charAt(Math.floor(Math.random() * chars.length));
  }
  form.path = `${first}-${second}-${randStr}`;
}
</script>
