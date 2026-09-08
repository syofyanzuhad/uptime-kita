<script setup lang="ts">
import { onMounted, onUnmounted, ref, watch } from 'vue';

interface Tag {
    id?: number;
    name: string;
    type?: string;
}

interface Props {
    modelValue: string[];
    placeholder?: string;
    maxTags?: number;
    debounceMs?: number;
}

const props = withDefaults(defineProps<Props>(), {
    placeholder: 'Add tags...',
    maxTags: 10,
    debounceMs: 300,
});

const emit = defineEmits<{
    'update:modelValue': [value: string[]];
}>();

const inputValue = ref('');
const suggestions = ref<Tag[]>([]);
const showSuggestions = ref(false);
const selectedIndex = ref(-1);
const inputRef = ref<HTMLInputElement>();
const tags = ref<string[]>(props.modelValue || []);
const isLoading = ref(false);
let debounceTimer: ReturnType<typeof setTimeout> | null = null;

// Fetch tag suggestions
const fetchSuggestions = async (search: string) => {
    if (search.length < 1) {
        suggestions.value = [];
        isLoading.value = false;
        return;
    }

    isLoading.value = true;
    try {
        const response = await fetch(`/tags/search?search=${encodeURIComponent(search)}`);
        const data = await response.json();
        suggestions.value = data.tags || [];
        showSuggestions.value = suggestions.value.length > 0 || isLoading.value;
    } catch (error) {
        console.error('Error fetching tag suggestions:', error);
        suggestions.value = [];
    } finally {
        isLoading.value = false;
    }
};

// Debounced fetch function
const debouncedFetchSuggestions = (search: string) => {
    // Clear existing timer
    if (debounceTimer) {
        clearTimeout(debounceTimer);
    }

    // Show loading state immediately for better UX
    showSuggestions.value = true;

    // Set new timer
    debounceTimer = setTimeout(() => {
        fetchSuggestions(search);
    }, props.debounceMs);
};

// Handle input changes
const handleInput = (event: Event) => {
    const target = event.target as HTMLInputElement;
    inputValue.value = target.value;
    selectedIndex.value = -1;

    if (inputValue.value.length > 0) {
        debouncedFetchSuggestions(inputValue.value);
    } else {
        // Clear any pending debounce
        if (debounceTimer) {
            clearTimeout(debounceTimer);
        }
        suggestions.value = [];
        showSuggestions.value = false;
    }
};

// Add a tag
const addTag = (tagName: string) => {
    const normalizedTag = tagName.trim().toLowerCase();

    if (normalizedTag && !tags.value.includes(normalizedTag) && tags.value.length < props.maxTags) {
        tags.value.push(normalizedTag);
        emit('update:modelValue', tags.value);
        inputValue.value = '';
        suggestions.value = [];
        showSuggestions.value = false;
        selectedIndex.value = -1;
    }
};

// Remove a tag
const removeTag = (index: number) => {
    tags.value.splice(index, 1);
    emit('update:modelValue', tags.value);
};

// Handle keyboard navigation
const handleKeydown = (event: KeyboardEvent) => {
    if (event.key === 'Enter') {
        event.preventDefault();

        if (selectedIndex.value >= 0 && suggestions.value[selectedIndex.value]) {
            addTag(suggestions.value[selectedIndex.value].name);
        } else if (inputValue.value) {
            addTag(inputValue.value);
        }
    } else if (event.key === 'Backspace' && !inputValue.value && tags.value.length > 0) {
        removeTag(tags.value.length - 1);
    } else if (event.key === 'ArrowDown') {
        event.preventDefault();
        if (selectedIndex.value < suggestions.value.length - 1) {
            selectedIndex.value++;
        }
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        if (selectedIndex.value > 0) {
            selectedIndex.value--;
        }
    } else if (event.key === 'Escape') {
        showSuggestions.value = false;
        selectedIndex.value = -1;
    }
};

// Handle suggestion click
const selectSuggestion = (tag: Tag) => {
    addTag(tag.name);
    inputRef.value?.focus();
};

// Handle click outside
const handleClickOutside = (event: MouseEvent) => {
    const target = event.target as HTMLElement;
    if (!target.closest('.tag-input-container')) {
        showSuggestions.value = false;
    }
};

// Watch for external changes to modelValue
watch(
    () => props.modelValue,
    (newValue) => {
        tags.value = newValue || [];
    },
);

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    // Clean up event listener
    document.removeEventListener('click', handleClickOutside);

    // Clear any pending debounce timer
    if (debounceTimer) {
        clearTimeout(debounceTimer);
    }
});
</script>

<template>
    <div class="tag-input-container relative">
        <div
            class="border-input dark:bg-input/30 focus-within:border-ring focus-within:ring-ring/50 flex flex-wrap gap-2 rounded-md border bg-transparent p-2 shadow-xs transition-[color,box-shadow] focus-within:ring-[3px] focus-within:outline-none"
        >
            <!-- Tags -->
            <span
                v-for="(tag, index) in tags"
                :key="index"
                class="bg-secondary text-secondary-foreground inline-flex items-center gap-1 rounded px-2 py-0.5 text-xs font-medium"
            >
                {{ tag }}
                <button type="button" @click="removeTag(index)" class="text-muted-foreground hover:text-foreground inline-flex items-center justify-center">
                    <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                        <path
                            fill-rule="evenodd"
                            d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                            clip-rule="evenodd"
                        />
                    </svg>
                </button>
            </span>

            <!-- Input -->
            <input
                ref="inputRef"
                type="text"
                v-model="inputValue"
                @input="handleInput"
                @keydown="handleKeydown"
                :placeholder="tags.length === 0 ? placeholder : ''"
                class="placeholder:text-muted-foreground text-foreground min-w-[120px] flex-1 border-0 bg-transparent p-0 text-sm focus:outline-none focus:ring-0"
                :disabled="tags.length >= maxTags"
            />
        </div>

        <!-- Suggestions dropdown -->
        <div
            v-if="showSuggestions || isLoading"
            class="border-border bg-popover text-popover-foreground absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md border shadow-md"
        >
            <!-- Loading indicator -->
            <div v-if="isLoading" class="text-muted-foreground px-3 py-2 text-sm">
                <svg class="mr-2 inline-block h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path
                        class="opacity-75"
                        fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                    ></path>
                </svg>
                Loading suggestions...
            </div>

            <!-- Suggestions list -->
            <ul v-else-if="suggestions.length > 0" class="py-1">
                <li
                    v-for="(suggestion, index) in suggestions"
                    :key="suggestion.id || index"
                    @click="selectSuggestion(suggestion)"
                    :class="[
                        'cursor-pointer px-3 py-1.5 text-sm transition-colors',
                        selectedIndex === index
                            ? 'bg-accent text-accent-foreground'
                            : 'text-foreground hover:bg-muted',
                    ]"
                >
                    {{ suggestion.name }}
                </li>
            </ul>

            <!-- No results message -->
            <div v-else class="text-muted-foreground px-3 py-2 text-sm">
                No matching tags found. Press Enter to create "{{ inputValue }}"
            </div>
        </div>

        <!-- Helper text -->
        <p v-if="tags.length >= maxTags" class="text-destructive mt-1 text-xs">Maximum {{ maxTags }} tags allowed</p>
    </div>
</template>
