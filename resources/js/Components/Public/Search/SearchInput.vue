<template>
  <div class="flex-shrink-0 px-6 py-3 border-b border-zinc-200 dark:border-zinc-700">
    <AisSearchBox>
      <template #="{ refine, currentRefinement, isSearchStalled }">
        <span style="display: none">{{ refineFunction = refine }}</span>
        <div class="relative w-full">
          <Input ref="searchInputRef" role="search" :model-value="currentRefinement"
            :placeholder="$t('search.search_placeholder')"
            class="w-full h-12 text-lg pl-4 pr-24 rounded-md border border-zinc-300 dark:border-zinc-700 focus-visible:ring-2 focus-visible:ring-primary dark:bg-zinc-900"
            @input="(e) => { refine(e.target.value); handleUpdateValue(e.target.value); }" />
          <div class="absolute right-3 top-1/2 -translate-y-1/2 flex items-center gap-2">
            <kbd v-if="!currentRefinement"
              class="hidden sm:inline-flex h-6 select-none items-center gap-1 rounded border bg-muted px-1.5 font-mono text-xs font-medium text-muted-foreground opacity-70">
              <span class="text-xs">{{ isMac ? '⌘' : 'Ctrl' }}</span>K
            </kbd>
            <Button v-if="currentRefinement" type="button" variant="ghost" size="icon"
              class="h-6 w-6 hover:bg-zinc-100 dark:hover:bg-zinc-800" @click="clearSearch">
              <span class="sr-only">{{ $t('search.clear_search') }}</span>
              <IconDismiss class="w-3.5 h-3.5" />
            </Button>
          </div>
        </div>

        <!-- Loading indicator -->
        <div v-if="isSearchStalled" class="flex items-center justify-center mt-2">
          <div class="flex items-center gap-2 text-sm text-muted-foreground">
            <div class="animate-spin rounded-full h-4 w-4 border-2 border-primary border-t-transparent" />
            {{ $t('search.searching') }}
          </div>
        </div>
      </template>
    </AisSearchBox>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, nextTick } from 'vue'
import { trans as $t } from 'laravel-vue-i18n'
import { debounce } from 'lodash-es'
import { AisSearchBox } from 'vue-instantsearch/vue3/es'
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'
import { useSearchUtils } from '@/Composables/useSearchUtils'
import IconDismiss from '~icons/fluent/dismiss20-regular'

interface SearchInputProps {
  searchQuery: string
}

const props = defineProps<SearchInputProps>()

const emit = defineEmits<{
  (e: 'update:searchQuery', value: string): void
  (e: 'clear'): void
}>()

const { trackSearchInteraction } = useSearchUtils()

const searchInputRef = ref<HTMLInputElement | null>(null)
const refineFunction = ref<((query: string) => void) | null>(null)

const isMac = computed(() => {
  if (typeof navigator === 'undefined') return false
  return navigator.platform?.includes('Mac') || navigator.userAgent?.includes('Mac') || false
})

// Search input handling with debouncing and minimum length check
const debouncedSearchUpdate = debounce((value: string) => {
  // Only update search if query is long enough or empty (for clearing)
  if (value.length >= 3 || value.length === 0) {
    emit('update:searchQuery', value)
  }
}, 300)

const handleUpdateValue = (value: string) => {
  // Update UI immediately for responsiveness, but debounce the actual search
  debouncedSearchUpdate(value)
}

const clearSearch = () => {
  const previousQuery = props.searchQuery
  
  // Clear the input field by calling refine with empty string
  if (refineFunction.value) {
    refineFunction.value('')
  }
  
  emit('update:searchQuery', '')
  emit('clear')

  if (previousQuery) {
    trackSearchInteraction('search_cleared', {
      previous_query: previousQuery
    })
  }
}

const focusSearchInput = async () => {
  await nextTick()
  searchInputRef.value?.focus()
}

defineExpose({
  focusSearchInput,
  refineFunction
})
</script>
