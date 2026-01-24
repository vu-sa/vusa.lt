<template>
  <div v-if="agendaItems && agendaItems.length > 0" class="flex items-center gap-1.5">
    <TooltipProvider>
      <Tooltip v-for="item in agendaItems" :key="item.id">
        <TooltipTrigger>
          <span
            class="w-2.5 h-2.5 rounded-full"
            :class="getStatusMeta(item).dotClass"
          />
        </TooltipTrigger>
        <TooltipContent class="max-w-xs">
          <p class="font-medium text-xs mb-1">{{ item.order }}. {{ item.title }}</p>
          <p class="text-xs text-muted-foreground">
            {{ getStatusMeta(item).label }}
          </p>
        </TooltipContent>
      </Tooltip>
    </TooltipProvider>
  </div>
</template>

<script setup lang="ts">
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/Components/ui/tooltip';
import { getAgendaItemStatusMeta } from '@/Composables/useAgendaItemStyling';

defineProps<{
  agendaItems?: App.Entities.AgendaItem[];
}>();

const getStatusMeta = (item: App.Entities.AgendaItem) => {
  return getAgendaItemStatusMeta(item);
};
</script>
