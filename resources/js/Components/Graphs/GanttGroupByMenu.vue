<template>
  <SpotlightPopover
    :title="$t('visak.gantt.grouping_spotlight_title')"
    :description="$t('visak.gantt.grouping_spotlight_body')"
    :is-dismissed="spotlight.isDismissed.value"
    :show-badge="false"
    align="end"
    @dismiss="spotlight.dismiss()"
  >
    <DropdownMenu @update:open="(open: boolean) => open && spotlight.dismiss()">
      <DropdownMenuTrigger as-child>
        <!-- No notch (show-badge off): on a toolbar-sized trigger it covered the button and swallowed the click. -->
        <Button
          type="button"
          size="xs"
          variant="ghost"
          voice="sentence"
          class="h-6 gap-1 px-1.5 pointer-coarse:h-11"
          data-slot="gantt-group-by"
          data-tour="gantt-group-by"
          :aria-label="$t('visak.gantt.group_by_current', { option: label })"
        >
          <Group class="size-3.5" aria-hidden="true" />
          {{ label }}
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end" class="w-56">
        <DropdownMenuLabel>{{ $t('visak.gantt.group_by') }}</DropdownMenuLabel>
        <DropdownMenuSeparator />
        <DropdownMenuRadioGroup :model-value @update:model-value="value => emit('update:modelValue', value as GanttGroupBy)">
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
import { useFeatureSpotlight } from '@/Composables/useFeatureSpotlight';
import { GANTT_GROUP_BY_OPTIONS, type GanttGroupBy } from '@/Pages/Admin/Dashboard/Composables/useGanttSettings';

const props = defineProps<{
  modelValue: GanttGroupBy;
}>();

const emit = defineEmits<{
  'update:modelValue': [value: GanttGroupBy];
}>();

const spotlight = useFeatureSpotlight('gantt-grouping-v2');

const label = computed(() => $t(`visak.gantt.group_by_short.${props.modelValue}`));
</script>
