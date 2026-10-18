<template>
  <SpotlightPopover
    :title="$t('visak.gantt.grouping_spotlight_title')"
    :description="$t('visak.gantt.grouping_spotlight_body')"
    :is-dismissed="spotlight.isDismissed.value"
    align="end"
    @dismiss="spotlight.dismiss()"
  >
    <DropdownMenu @update:open="(open: boolean) => open && spotlight.dismiss()">
      <Tooltip>
        <TooltipTrigger as-child>
          <DropdownMenuTrigger as-child>
            <Button
              type="button"
              size="icon-xs"
              variant="ghost"
              class="pointer-coarse:size-11"
              data-slot="gantt-group-by"
              data-tour="gantt-group-by"
              :aria-label="currentLabel"
            >
              <Group class="size-3.5" />
            </Button>
          </DropdownMenuTrigger>
        </TooltipTrigger>
        <TooltipContent>{{ currentLabel }}</TooltipContent>
      </Tooltip>
      <DropdownMenuContent align="end" class="w-56">
        <DropdownMenuLabel>{{ $t('visak.gantt.group_by') }}</DropdownMenuLabel>
        <DropdownMenuSeparator />
        <DropdownMenuRadioGroup :model-value="modelValue" @update:model-value="value => emit('update:modelValue', value as GanttGroupBy)">
          <DropdownMenuRadioItem v-for="option in GANTT_GROUP_BY_OPTIONS" :key="option" :value="option">
            {{ $t(`visak.gantt.group_by_options.${option}`) }}
          </DropdownMenuRadioItem>
        </DropdownMenuRadioGroup>
      </DropdownMenuContent>
    </DropdownMenu>
  </SpotlightPopover>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { Group } from '@lucide/vue';

import SpotlightPopover from '@/Components/Onboarding/SpotlightPopover.vue';
import { Button } from '@/Components/ui/button';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuLabel,
  DropdownMenuRadioGroup,
  DropdownMenuRadioItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/Components/ui/tooltip';
import { useFeatureSpotlight } from '@/Composables/useFeatureSpotlight';
import { GANTT_GROUP_BY_OPTIONS, type GanttGroupBy } from '@/Pages/Admin/Dashboard/Composables/useGanttSettings';

const props = defineProps<{
  modelValue: GanttGroupBy;
}>();

const emit = defineEmits<{
  'update:modelValue': [value: GanttGroupBy];
}>();

const spotlight = useFeatureSpotlight('gantt-grouping-v1');

const currentLabel = computed(() => $t('visak.gantt.group_by_current', {
  option: $t(`visak.gantt.group_by_options.${props.modelValue}`),
}));
</script>
