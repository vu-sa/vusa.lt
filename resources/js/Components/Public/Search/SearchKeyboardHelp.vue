<template>
  <div class="px-6 py-3 border-b border-zinc-200 dark:border-zinc-700 bg-red-50 dark:bg-red-950/30">
    <div class="space-y-3">
      <h4 class="text-sm font-medium text-blue-900 dark:text-blue-100">{{ $t('search.keyboard_shortcuts') }}</h4>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
        <div class="space-y-2">
          <div class="flex items-center justify-between">
            <span class="text-muted-foreground">{{ $t('search.open_close_search') }}</span>
            <kbd class="px-1.5 py-0.5 bg-white dark:bg-zinc-800 border rounded text-xs">{{ isMac ? '⌘K' : 'Ctrl+K' }}</kbd>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-muted-foreground">{{ $t('search.toggle_filters') }}</span>
            <kbd class="px-1.5 py-0.5 bg-white dark:bg-zinc-800 border rounded text-xs">{{ isMac ? '⌘F' : 'Ctrl+F' }}</kbd>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-muted-foreground">{{ $t('search.toggle_view_mode') }}</span>
            <kbd class="px-1.5 py-0.5 bg-white dark:bg-zinc-800 border rounded text-xs">{{ isMac ? '⌘G' : 'Ctrl+G' }}</kbd>
          </div>
        </div>
        <div class="space-y-2">
          <div class="flex items-center justify-between">
            <span class="text-muted-foreground">{{ $t('search.toggle_news') }}</span>
            <kbd class="px-1.5 py-0.5 bg-white dark:bg-zinc-800 border rounded text-xs">Alt+1</kbd>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-muted-foreground">{{ $t('search.toggle_pages') }}</span>
            <kbd class="px-1.5 py-0.5 bg-white dark:bg-zinc-800 border rounded text-xs">Alt+2</kbd>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-muted-foreground">{{ $t('search.toggle_documents') }}</span>
            <kbd class="px-1.5 py-0.5 bg-white dark:bg-zinc-800 border rounded text-xs">Alt+3</kbd>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-muted-foreground">{{ $t('search.toggle_events') }}</span>
            <kbd class="px-1.5 py-0.5 bg-white dark:bg-zinc-800 border rounded text-xs">Alt+4</kbd>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { trans as $t } from 'laravel-vue-i18n'

const isMac = computed(() => {
  if (typeof navigator === 'undefined') return false
  return navigator.platform?.includes('Mac') || navigator.userAgent?.includes('Mac') || false
})
</script>