<template>
  <div class="space-y-4 p-4">
    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">External Link Examples</h2>
    
    <!-- Basic external link -->
    <div>
      <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Basic External Link:</h3>
      <ExternalLink href="https://example.com" label="Visit Example.com" />
    </div>
    
    <!-- External link with custom styling -->
    <div>
      <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Custom Styled Link:</h3>
      <ExternalLink
        href="https://github.com"
        label="GitHub"
        class-name="text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300 font-medium"
        icon-class-name="text-green-500"
      />
    </div>
    
    <!-- External link without icon -->
    <div>
      <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Link Without Icon:</h3>
      <ExternalLink
        href="https://stackoverflow.com"
        label="Stack Overflow"
        :show-icon="false"
      />
    </div>
    
    <!-- External link with large icon -->
    <div>
      <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Large Icon:</h3>
      <ExternalLink
        href="https://laravel.com"
        label="Laravel Framework"
        icon-size="lg"
        class-name="text-orange-600 hover:text-orange-800 dark:text-orange-400 dark:hover:text-orange-300"
      />
    </div>
    
    <!-- Force external link (even for same domain) -->
    <div>
      <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Forced External Link:</h3>
      <ExternalLink
        href="/dashboard"
        label="Dashboard (forced external)"
        :force-external="true"
        class-name="text-purple-600 hover:text-purple-800 dark:text-purple-400 dark:hover:text-purple-300"
      />
    </div>
    
    <!-- Custom aria label -->
    <div>
      <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Custom Aria Label:</h3>
      <ExternalLink
        href="https://vuejs.org"
        label="Vue.js"
        aria-label="Learn Vue.js framework (opens in new window)"
        class-name="text-emerald-600 hover:text-emerald-800 dark:text-emerald-400 dark:hover:text-emerald-300"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import ExternalLink from '@/components/ExternalLink.vue'
</script>
