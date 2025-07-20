<template>
  <div class="w-full py-1">
    <div class="flex flex-col">
      <div class="flex items-center justify-between">
        <h4 class="text-base font-medium truncate max-w-[70%]">{{ primaryText }}</h4>
        <span class="text-xs font-medium text-zinc-400 dark:text-zinc-500 ml-2 flex-shrink-0">
          {{ category }}
        </span>
      </div>
      <div class="text-sm text-muted-foreground truncate">{{ secondaryText }}</div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface CompactListItemProps {
  primaryText: string
  secondaryText?: string
  category?: string
}

defineProps<CompactListItemProps>()
</script>