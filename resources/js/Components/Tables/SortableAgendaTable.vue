<template>
  <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
    <!-- Header -->
    <div class="agenda-header grid gap-4 border-b p-3 text-sm font-medium text-zinc-600 dark:text-zinc-400" 
         :class="showVoteOptions ? 'grid-cols-12' : 'grid-cols-8'">
      <div class="col-span-1">{{ $t('No.') }}</div>
      <div :class="showVoteOptions ? 'col-span-4' : 'col-span-3'">{{ $t('forms.fields.title') }}</div>
      <div class="col-span-2">{{ $t('Būsena') }}</div>
      <div v-if="showVoteOptions" class="col-span-2">{{ $t('Kaip balsavo studentai') }}</div>
      <div v-if="showVoteOptions" class="col-span-2">{{ $t('Ar palanku studentams') }}?</div>
      <div class="col-span-1">{{ $t('forms.fields.description') }}</div>
      <div class="col-span-1">{{ $t('Veiksmai') }}</div>
    </div>

    <!-- Sortable Items -->
    <div ref="sortableContainer">
      <div
        v-for="(item, index) in localItems" 
        :key="item.id" 
        :data-id="item.id"
        class="grid gap-4 border-b p-3 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors"
        :class="showVoteOptions ? 'grid-cols-12' : 'grid-cols-8'"
      >
        <!-- Drag Handle + Number -->
        <div class="col-span-1 flex items-center gap-2">
          <div class="drag-handle cursor-move p-1 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
              <path d="M8 6h2v2H8zm0 4h2v2H8zm0 4h2v2H8zm6-8h2v2h-2zm0 4h2v2h-2zm0 4h2v2h-2z"/>
            </svg>
          </div>
          <span class="text-sm text-zinc-500">{{ index + 1 }}</span>
        </div>

        <!-- Title -->
        <div :class="showVoteOptions ? 'col-span-4' : 'col-span-3'" class="flex items-center">
          <span class="font-medium">{{ item.title }}</span>
        </div>

        <!-- Status Column (Always visible) -->
        <div class="col-span-2 flex items-center">
          <TriStateButton 
            :state="item.decision" 
            size="tiny" 
            :positive-text="'Sprendimas priimtas / klausimas patvirtintas'"
            :negative-text="'Klausimui / sprendimui nepritarta'"
            :neutral-text="'Joks sprendimas (teigiamas ar neigiamas) nepriimtas / susilaikyta'"
            :row="item" 
            :show-options="showVoteOptions" 
            @enable-options="$emit('enableVoteOptions')"
            @change-state="(state) => updateAgendaItem(item, 'decision', state)"
          />
        </div>

        <!-- Additional Vote Options (shown when enabled) -->

        <div v-if="showVoteOptions" class="col-span-2 flex items-center">
          <TriStateButton 
            :state="item.student_vote" 
            size="tiny"
            :positive-text="'Visi pritarė'"
            :negative-text="'Visi nepritarė'" 
            :neutral-text="'Visi susilaikė'"
            :row="item"
            :show-options="showVoteOptions"
            @enable-options="$emit('enableVoteOptions')"
            @change-state="(state) => updateAgendaItem(item, 'student_vote', state)"
          />
        </div>

        <div v-if="showVoteOptions" class="col-span-2 flex items-center">
          <TriStateButton 
            :state="item.student_benefit" 
            size="tiny"
            :positive-text="'Palanku'"
            :negative-text="'Nepalanku'"
            :neutral-text="'Sprendimas neturi tiesioginės ar netiesioginės įtakos studentams / dar nėra aišku'"
            :row="item"
            :show-options="showVoteOptions"
            @enable-options="$emit('enableVoteOptions')"
            @change-state="(state) => updateAgendaItem(item, 'student_benefit', state)"
          />
        </div>

        <!-- Description -->
        <div class="col-span-1 flex items-center justify-center">
          <TooltipProvider>
            <Tooltip>
              <TooltipTrigger asChild>
                <Button 
                  size="sm" 
                  variant="ghost"
                  @click="$emit('editItem', item)"
                >
                  <component 
                    :is="!item.description ? FileDocumentPlus : FileDocument"
                    class="h-4 w-4"
                  />
                </Button>
              </TooltipTrigger>
              <TooltipContent>
                <p>{{ !item.description ? $t('Pridėti aprašymą') : item.description }}</p>
              </TooltipContent>
            </Tooltip>
          </TooltipProvider>
        </div>

        <!-- Actions -->
        <div class="col-span-1 flex items-center justify-center">
          <MoreOptionsButton
            edit
            delete
            small
            @edit-click="$emit('editItem', item)"
            @delete-click="$emit('deleteItem', item)"
          />
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-if="localItems.length === 0" class="p-8 text-center text-zinc-500">
      <p>{{ $t('Darbotvarkės punktų nėra') }}</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, nextTick } from 'vue'
import { useSortable } from '@vueuse/integrations/useSortable'
import { router } from '@inertiajs/vue3'
import { trans as $t } from 'laravel-vue-i18n'

import { Button } from '@/Components/ui/button'
import { Tooltip, TooltipContent, TooltipTrigger, TooltipProvider } from '@/Components/ui/tooltip'
import MoreOptionsButton from '@/Components/Buttons/MoreOptionsButton.vue'
import TriStateButton from '@/Components/Buttons/TriStateButton.vue'

// Icons
import FileDocument from '~icons/mdi/file-document'
import FileDocumentPlus from '~icons/mdi/file-document-plus'

interface AgendaItem {
  id: string
  title: string
  description?: string | null
  order: number
  decision?: string | null
  student_vote?: string | null
  student_benefit?: string | null
}

interface Props {
  items: AgendaItem[]
  meetingId: string
  showVoteOptions?: boolean
}

interface Emits {
  (e: 'editItem', item: AgendaItem): void
  (e: 'deleteItem', item: AgendaItem): void
  (e: 'enableVoteOptions'): void
}

const props = withDefaults(defineProps<Props>(), {
  showVoteOptions: false
})

const emit = defineEmits<Emits>()

// Local reactive copy of items for drag and drop
const localItems = ref<AgendaItem[]>([...props.items])

// Keep local items in sync with props
watch(
  () => props.items, 
  (newItems) => {
    localItems.value = [...newItems].sort((a, b) => a.order - b.order)
  },
  { immediate: true, deep: true }
)

const sortableContainer = ref<HTMLElement>()

// Setup sortable
const sortable = useSortable(sortableContainer, localItems, {
  handle: '.drag-handle',
  ghostClass: 'opacity-50',
  chosenClass: 'shadow-lg',
  dragClass: 'rotate-2',
  animation: 150,
  onEnd: async () => {
    await nextTick()
    await updateOrder()
  }
})

const updateOrder = async () => {
  const reorderedItems = localItems.value.map((item, index) => ({
    id: item.id,
    order: index + 1
  }))

  router.post(route('agendaItems.reorder'), {
    meeting_id: props.meetingId,
    agenda_items: reorderedItems
  }, {
    preserveState: true,
    preserveScroll: true,
    onError: (errors) => {
      console.error('Failed to reorder agenda items:', errors)
      // Revert to original order on error
      localItems.value = [...props.items].sort((a, b) => a.order - b.order)
    }
  })
}

const updateAgendaItem = (item: AgendaItem, field: string, value: any) => {
  router.patch(route("agendaItems.update", item.id), {
    [field]: value
  }, {
    preserveState: true,
    preserveScroll: true
  })
}
</script>

