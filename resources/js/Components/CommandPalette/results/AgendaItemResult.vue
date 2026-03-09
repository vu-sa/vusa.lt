<template>
  <CommandItem
    :value="itemValue"
    class="group cursor-pointer rounded-lg px-3 py-2.5 transition-colors hover:bg-accent data-[highlighted]:bg-accent"
    @select="handleSelect"
  >
    <div class="flex items-center gap-3 w-full min-w-0">
      <!-- Icon container with background -->
      <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-violet-500/10 text-violet-600 dark:bg-violet-500/20 dark:text-violet-400 group-hover:bg-violet-500/15 dark:group-hover:bg-violet-500/25 transition-colors">
        <AgendaItemIcon class="size-4" />
      </div>

      <!-- Content -->
      <div class="flex-1 min-w-0">
        <div class="flex items-center gap-2">
          <span class="font-medium truncate text-sm group-hover:text-foreground transition-colors">
            {{ item.title || $t('Be pavadinimo') }}
          </span>
          <!-- Related institution indicator -->
          <span
            v-if="isRelated"
            class="inline-flex items-center gap-1 rounded-full px-1.5 py-0.5 text-[10px] font-medium bg-purple-500/10 text-purple-600 ring-1 ring-inset ring-purple-500/20 dark:bg-purple-500/20 dark:text-purple-400"
            :title="$t('Iš susijusios institucijos')"
          >
            <LinkIcon class="size-2.5" />
            {{ $t('Susiję') }}
          </span>
          <span
            v-if="item.vote_result"
            :class="[
              'inline-flex items-center rounded-full px-1.5 py-0.5 text-[10px] font-medium ring-1 ring-inset',
              voteColorClasses
            ]"
          >
            {{ item.vote_result }}
          </span>
        </div>
        <div class="flex items-center gap-1.5 text-xs text-muted-foreground mt-0.5">
          <span v-if="item.meeting_title" class="truncate">{{ item.meeting_title }}</span>
          <span v-if="item.meeting_title && formattedDate" class="text-muted-foreground/40">•</span>
          <span v-if="formattedDate" class="shrink-0 tabular-nums">{{ formattedDate }}</span>
        </div>
      </div>

      <!-- Arrow indicator -->
      <ChevronRight class="size-4 text-muted-foreground/40 opacity-0 group-hover:opacity-100 transition-opacity shrink-0" />
    </div>
  </CommandItem>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { trans as $t } from 'laravel-vue-i18n'
import { ChevronRight, Link as LinkIcon } from 'lucide-vue-next'
import { CommandItem } from '@/Components/ui/command'
import { AgendaItemIcon } from '@/Components/icons'
import { useCommandPalette, type RecentItem } from '@/Composables/useCommandPalette'
import type { AgendaItemSearchResult } from '@/Composables/useAdminSearch'

const props = defineProps<{
  item: AgendaItemSearchResult
  isRelated?: boolean
}>()

const { close, addRecentItem } = useCommandPalette()

const itemValue = computed(() => `agenda-item-${props.item.id}`)

const formattedDate = computed(() => {
  if (!props.item.meeting_start_time) return null
  const date = new Date(props.item.meeting_start_time * 1000)
  return date.toLocaleDateString('lt-LT', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
})

const voteColorClasses = computed(() => {
  const result = props.item.vote_result?.toLowerCase()
  if (result === 'adopted' || result === 'priimta') {
    return 'bg-emerald-500/10 text-emerald-600 ring-emerald-500/20 dark:bg-emerald-500/20 dark:text-emerald-400'
  }
  if (result === 'rejected' || result === 'atmesta') {
    return 'bg-red-500/10 text-red-600 ring-red-500/20 dark:bg-red-500/20 dark:text-red-400'
  }
  return 'bg-zinc-500/10 text-zinc-600 ring-zinc-500/20 dark:bg-zinc-500/20 dark:text-zinc-400'
})

const handleSelect = () => {
  // Add to recent items
  addRecentItem({
    id: props.item.id,
    type: 'agenda_item',
    title: props.item.title || $t('Be pavadinimo'),
    href: props.item.meeting_id ? route('meetings.show', props.item.meeting_id) : undefined
  } as Omit<RecentItem, 'timestamp'>)

  // Navigate to the meeting that contains this agenda item
  if (props.item.meeting_id) {
    close()
    router.visit(route('meetings.show', props.item.meeting_id))
  }
}
</script>
