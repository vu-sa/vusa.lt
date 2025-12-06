<template>
  <div v-if="agendaItems && agendaItems.length > 0" class="flex items-center gap-1.5">
    <TooltipProvider>
      <Tooltip v-for="(item, index) in agendaItems" :key="item.id">
        <TooltipTrigger>
          <component
            :is="getOutcomeIcon(item)"
            class="h-3.5 w-3.5"
            :class="getOutcomeColor(item)"
          />
        </TooltipTrigger>
        <TooltipContent class="max-w-xs">
          <p class="font-medium text-xs mb-1">{{ item.order }}. {{ item.title }}</p>
          <p class="text-xs text-muted-foreground">
            {{ getOutcomeLabel(item) }}
          </p>
        </TooltipContent>
      </Tooltip>
    </TooltipProvider>
  </div>
</template>

<script setup lang="ts">
import { CheckIcon, XIcon, MinusIcon } from 'lucide-vue-next';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/Components/ui/tooltip';
import { trans as $t } from 'laravel-vue-i18n';

const props = defineProps<{
  agendaItems?: App.Entities.AgendaItem[];
}>();

const getOutcomeIcon = (item: App.Entities.AgendaItem) => {
  if (!item.student_vote || !item.decision) {
    return MinusIcon;
  }

  return item.student_vote === item.decision ? CheckIcon : XIcon;
};

const getOutcomeColor = (item: App.Entities.AgendaItem) => {
  if (!item.student_vote || !item.decision) {
    return 'text-zinc-400 dark:text-zinc-500';
  }

  return item.student_vote === item.decision
    ? 'text-green-600 dark:text-green-400'
    : 'text-amber-600 dark:text-amber-400';
};

const getOutcomeLabel = (item: App.Entities.AgendaItem) => {
  if (!item.student_vote || !item.decision) {
    return $t('Nėra balsavimo duomenų');
  }

  return item.student_vote === item.decision
    ? $t('Studentų pozicija priimta')
    : $t('Studentų pozicija nesutampa su sprendimu');
};
</script>
