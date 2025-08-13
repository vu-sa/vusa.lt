<template>
  <div class="space-y-3">
    <!-- Header Actions -->
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-4">
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
          {{ $t('Darbotvarkės punktai') }}
        </h2>
        <Badge variant="secondary" class="text-xs">
          {{ localItems.length }} {{ localItems.length === 1 ? $t('punktas') : $t('punktai') }}
        </Badge>
      </div>
      
      <div class="flex items-center gap-2">
        <!-- Vote Options Toggle -->
        <div class="flex items-center gap-2 text-sm">
          <Switch 
            :checked="showVoteOptions" 
            @update:checked="$emit('update:showVoteOptions', $event)" 
          />
          <Label class="text-zinc-600 dark:text-zinc-400">
            {{ $t('Išsamūs balsavimo duomenys') }}
          </Label>
        </div>
        
        <Separator orientation="vertical" class="h-6" />
        
        <!-- Add Button -->
        <Button @click="$emit('add')" size="sm">
          <Plus class="h-4 w-4 mr-2" />
          {{ $t('Pridėti klausimų') }}
        </Button>
      </div>
    </div>
    
    <!-- Sortable Container -->
    <div 
      ref="sortableContainer" 
      class="space-y-3"
      :class="{ 'min-h-32': localItems.length === 0 }"
    >
      <AgendaItemCard
        v-for="(item, index) in localItems"
        :key="item.id"
        :item="item"
        :order="index + 1"
        :show-vote-options="showVoteOptions"
        :data-id="item.id"
        @edit="$emit('edit', $event)"
        @delete="$emit('delete', $event)"
        @update="handleItemUpdate"
      />
    </div>
    
    <!-- Empty State -->
    <div 
      v-if="localItems.length === 0" 
      class="flex flex-col items-center justify-center py-12 text-center border-2 border-dashed border-zinc-300 dark:border-zinc-600 rounded-lg"
    >
      <FileText class="h-12 w-12 text-zinc-400 dark:text-zinc-500 mb-4" />
      <h3 class="text-lg font-medium text-zinc-900 dark:text-zinc-100 mb-2">
        {{ $t('Darbotvarkės punktų nėra') }}
      </h3>
      <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-4 max-w-sm">
        {{ $t('Sukurkite darbotvarkės punktus, kad galėtumėte pradėti posėdžio valdymą.') }}
      </p>
      <Button @click="$emit('add')" variant="default">
        <Plus class="h-4 w-4 mr-2" />
        {{ $t('Pridėti pirmą klausimą') }}
      </Button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, nextTick } from 'vue'
import { useSortable } from '@vueuse/integrations/useSortable'
import { router } from '@inertiajs/vue3'
import { trans as $t } from 'laravel-vue-i18n'

// UI Components
import { Button } from '@/Components/ui/button'
import { Badge } from '@/Components/ui/badge'
import { Label } from '@/Components/ui/label'
import { Switch } from '@/Components/ui/switch'
import { Separator } from '@/Components/ui/separator'

// Custom Components
import AgendaItemCard from './AgendaItemCard.vue'

// Icons
import { Plus, FileText } from 'lucide-vue-next'

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
  (e: 'add'): void
  (e: 'edit', item: AgendaItem): void
  (e: 'delete', item: AgendaItem): void
  (e: 'update:showVoteOptions', value: boolean): void
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
  ghostClass: 'sortable-ghost',
  chosenClass: 'sortable-chosen',
  dragClass: 'sortable-drag',
  animation: 200,
  fallbackOnBody: true,
  swapThreshold: 0.65,
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

const handleItemUpdate = (item: AgendaItem, field: string, value: any) => {
  router.patch(route("agendaItems.update", item.id), {
    [field]: value
  }, {
    preserveState: true,
    preserveScroll: true
  })
}
</script>

<style scoped>
/* Sortable styles are handled by individual cards */
</style>