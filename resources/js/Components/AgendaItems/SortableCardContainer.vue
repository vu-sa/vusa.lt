<template>
  <div class="space-y-3">
    <!-- Header Actions - Simplified -->
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-2">
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
          {{ $t('Darbotvarkės punktai') }}
        </h2>
        <Badge v-if="localItems.length > 0" variant="secondary" class="text-xs">
          {{ localItems.length }}
        </Badge>
      </div>

      <div class="flex items-center gap-2">
        <!-- Help Button -->
        <AdminVotingHelpButton />

        <!-- Add Button with Dropdown -->
        <DropdownMenu>
          <DropdownMenuTrigger as-child>
            <Button size="sm">
              <Plus class="h-4 w-4 mr-1" />
              <span class="hidden sm:inline">{{ $t('Pridėti') }}</span>
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end">
            <DropdownMenuItem @click="$emit('add')">
              <Plus class="h-4 w-4 mr-2" />
              {{ $t('Pridėti vieną punktą') }}
            </DropdownMenuItem>
            <DropdownMenuItem @click="$emit('add-bulk')">
              <ListPlus class="h-4 w-4 mr-2" />
              {{ $t('Pridėti kelis punktus') }}
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      </div>
    </div>

    <!-- Sortable Container -->
    <div ref="sortableContainer" class="space-y-2.5">
      <InlineAgendaItemCard
        v-for="(item, index) in localItems"
        :key="item.id"
        :item
        :order="index + 1"
        :expanded="expandedItems.has(item.id)"
        :data-id="item.id"
        @delete="$emit('delete', $event)"
        @update="handleItemUpdate"
        @toggle-expand="handleToggleExpand"
      />
    </div>

    <!-- Empty State -->
    <div
      v-if="localItems.length === 0"
      class="flex flex-col items-center justify-center py-16 text-center border-2 border-dashed border-zinc-200 dark:border-zinc-700 rounded-xl bg-zinc-50/50 dark:bg-zinc-800/30"
    >
      <div class="w-16 h-16 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center mb-4">
        <FileText class="h-8 w-8 text-zinc-400 dark:text-zinc-500" />
      </div>
      <h3 class="text-lg font-medium text-zinc-900 dark:text-zinc-100 mb-2">
        {{ $t('Darbotvarkės punktų nėra') }}
      </h3>
      <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-6 max-w-sm">
        {{ $t('Sukurkite darbotvarkės punktus, kad galėtumėte pradėti posėdžio valdymą.') }}
      </p>
      <Button size="lg" @click="$emit('add')">
        <Plus class="h-4 w-4 mr-2" />
        {{ $t('Pridėti pirmą klausimą') }}
      </Button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, nextTick } from 'vue';
import { useSortable } from '@vueuse/integrations/useSortable';
import { router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

// UI Components
import { Plus, FileText, ListPlus } from 'lucide-vue-next';

import InlineAgendaItemCard from './InlineAgendaItemCard.vue';
import AdminVotingHelpButton from './AdminVotingHelpButton.vue';

import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';

// Custom Components

// Icons

interface AgendaItem {
  id: string;
  title: string;
  description?: string | null;
  order: number;
  brought_by_students?: boolean;
  type?: 'voting' | 'informational' | 'deferred' | null;
  student_position?: string | null;
  votes?: App.Entities.Vote[];
}

interface Props {
  items: AgendaItem[];
  meetingId: string;
}

interface Emits {
  (e: 'add'): void;
  (e: 'add-bulk'): void;
  (e: 'delete', item: AgendaItem): void;
}

const props = defineProps<Props>();

const emit = defineEmits<Emits>();

// Track which items are expanded
const expandedItems = ref<Set<string>>(new Set());

const handleToggleExpand = (id: string) => {
  // Create a new Set to ensure reactivity
  const newSet = new Set(expandedItems.value);
  if (newSet.has(id)) {
    newSet.delete(id);
  }
  else {
    newSet.add(id);
  }
  expandedItems.value = newSet;
};

// Expose expandItem method for parent components
const expandItem = (id: string) => {
  // Create a new Set to ensure reactivity
  const newSet = new Set(expandedItems.value);
  newSet.add(id);
  expandedItems.value = newSet;
  // Scroll to the item after a short delay
  nextTick(() => {
    const element = document.querySelector(`[data-id="${id}"]`);
    element?.scrollIntoView({ behavior: 'smooth', block: 'center' });
  });
};

defineExpose({ expandItem });

// Local reactive copy of items for drag and drop
const localItems = ref<AgendaItem[]>([...props.items]);

// Keep local items in sync with props
watch(
  () => props.items,
  (newItems) => {
    localItems.value = [...newItems].sort((a, b) => a.order - b.order);
  },
  { immediate: true, deep: true },
);

const sortableContainer = ref<HTMLElement>();

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
    await nextTick();
    await updateOrder();
  },
});

const updateOrder = async () => {
  const reorderedItems = localItems.value.map((item, index) => ({
    id: item.id,
    order: index + 1,
  }));

  router.post(route('agendaItems.reorder'), {
    meeting_id: props.meetingId,
    agenda_items: reorderedItems,
  }, {
    preserveState: true,
    preserveScroll: true,
    onError: (errors) => {
      console.error('Failed to reorder agenda items:', errors);
      // Revert to original order on error
      localItems.value = [...props.items].sort((a, b) => a.order - b.order);
    },
  });
};

const handleItemUpdate = (item: AgendaItem) => {
  // Updates are now handled directly by InlineAgendaItemCard
  // This is kept for compatibility but the card handles its own saves
};
</script>

<style scoped>
.sortable-ghost {
  opacity: 0.4;
}

.sortable-chosen {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}
</style>
