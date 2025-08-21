<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import Icon from '@/components/Icon.vue';

interface PaginationLink {
  url: string | null;
  label: string;
  active: boolean;
}

interface PaginationData {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number;
  to: number;
  links: PaginationLink[];
}

// For monitor-style pagination with meta object
interface PaginatorMeta {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number;
  to: number;
  links: PaginationLink[];
}

interface PaginatorData {
  meta: PaginatorMeta;
}

interface Props {
  data: PaginationData | PaginatorData;
  showInfo?: boolean;
  className?: string;
  onLinkClick?: (link: PaginationLink) => void;
  preserveParams?: string[];
}

const props = withDefaults(defineProps<Props>(), {
  showInfo: true,
  className: '',
  preserveParams: () => ['per_page'],
});

const handleLinkClick = (link: PaginationLink, event: Event) => {
  if (props.onLinkClick) {
    // If custom handler is provided, use it
    event.preventDefault();
    props.onLinkClick(link);
  } else if (link.url) {
    // Default behavior: preserve specified parameters
    event.preventDefault();
    const url = new URL(link.url, window.location.origin);

    // Preserve specified parameters from current URL
    const currentUrl = new URL(window.location.href);
    props.preserveParams.forEach(param => {
      const value = currentUrl.searchParams.get(param);
      if (value) {
        url.searchParams.set(param, value);
      }
    });

    router.visit(url.pathname + url.search, {
      preserveState: true,
      only: ['monitors', 'users', 'search', 'statusFilter', 'perPage', 'visibilityFilter', 'tagFilter']
    });
  } else {
    // Disabled link
    event.preventDefault();
  }
};

// Clean up pagination labels
const cleanLabel = (label: string): string => {
  return label
    .replace('&laquo;', '')
    .replace('&raquo;', '')
    .replace('Previous', '')
    .replace('Next', '')
    .trim();
};

// Check if link is previous/next
const isPrevious = (label: string): boolean => {
  return label.includes('Previous') || label.includes('&laquo;');
};

const isNext = (label: string): boolean => {
  return label.includes('Next') || label.includes('&raquo;');
};

// Helper to get pagination info from either structure
const getPaginationInfo = () => {
  const rawData = 'meta' in props.data ? props.data.meta : props.data;

  // Handle case where values might be arrays (duplicate values from backend)
  // Extract first value if it's an array
  const cleanData = {
    current_page: Array.isArray(rawData.current_page) ? rawData.current_page[0] : rawData.current_page,
    last_page: Array.isArray(rawData.last_page) ? rawData.last_page[0] : rawData.last_page,
    per_page: Array.isArray(rawData.per_page) ? rawData.per_page[0] : rawData.per_page,
    total: Array.isArray(rawData.total) ? rawData.total[0] : rawData.total,
    from: Array.isArray(rawData.from) ? rawData.from[0] : rawData.from,
    to: Array.isArray(rawData.to) ? rawData.to[0] : rawData.to,
    links: rawData.links,
  };

  return cleanData;
};

// Get display text for pagination info
const getInfoText = (): string => {
  const info = getPaginationInfo();

  if (info.total === 0) {
    return 'No results found';
  }

  if (info.total === 1) {
    return 'Showing 1 result';
  }

  if (info.from === info.to) {
    return `Showing ${info.from} of ${info.total} results`;
  }

  return `Showing ${info.from} to ${info.to} of ${info.total} results`;
};
</script>

<template>
  <div :class="['flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4', className]">
    <!-- Pagination Info -->
    <div v-if="showInfo" class="text-sm text-gray-700 dark:text-gray-300">
      {{ getInfoText() }}
    </div>

    <!-- Pagination Links -->
    <nav v-if="getPaginationInfo().links && getPaginationInfo().links.length > 3" class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
      <component
        :is="onLinkClick ? 'button' : Link"
        v-for="(link, index) in getPaginationInfo().links"
        :key="`${link.label}-${index}`"
        :href="onLinkClick ? undefined : (link.url || '')"
        @click="handleLinkClick(link, $event)"
        :class="[
          'relative inline-flex items-center cursor-pointer px-3 py-2 text-sm font-medium transition-colors duration-200',
          // First link styling
          index === 0 ? 'rounded-l-md' : '',
          // Last link styling
          index === getPaginationInfo().links.length - 1 ? 'rounded-r-md' : '',
          // Active state
          link.active
            ? 'z-10 bg-blue-600 text-white border-blue-600'
            : 'bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-300 border-gray-300 dark:border-gray-600',
          // Disabled state
          !link.url
            ? 'pointer-events-none opacity-50 cursor-default'
            : 'hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-700 dark:hover:text-gray-200',
          // Border
          'border'
        ]"
        :aria-current="link.active ? 'page' : undefined"
        :aria-disabled="!link.url"
      >
        <!-- Previous arrow -->
        <Icon
          v-if="isPrevious(link.label)"
          name="chevronLeft"
          class="h-4 w-4"
          aria-hidden="true"
        />

        <!-- Page number or label -->
        <span v-if="!isPrevious(link.label) && !isNext(link.label)">
          {{ cleanLabel(link.label) }}
        </span>

        <!-- Next arrow -->
        <Icon
          v-if="isNext(link.label)"
          name="chevronRight"
          class="h-4 w-4"
          aria-hidden="true"
        />

        <!-- Screen reader text for prev/next -->
        <span v-if="isPrevious(link.label)" class="sr-only">Previous page</span>
        <span v-if="isNext(link.label)" class="sr-only">Next page</span>
      </component>
    </nav>
  </div>
</template>
