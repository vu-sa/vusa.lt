<template>
  <StatusBadge v-if="!editable" :status />
  <DropdownMenu v-else v-model:open="open">
    <DropdownMenuTrigger as-child>
      <button
        type="button"
        :disabled="pending"
        :aria-label="`${$t('Keisti būseną')}: ${$t(status.label)}`"
        :class="[
          'group/status inline-flex items-center gap-1 outline-none',
          'focus-visible:ring-[3px] focus-visible:ring-ring/40 pointer-coarse:min-h-11',
          'disabled:cursor-progress disabled:opacity-60',
        ]"
        data-slot="collection-status-menu"
      >
        <StatusBadge :status />
        <ChevronDown class="size-3.5 text-muted-foreground transition-transform group-data-[state=open]/status:rotate-180" aria-hidden="true" />
      </button>
    </DropdownMenuTrigger>
    <DropdownMenuContent align="start" class="w-48">
      <DropdownMenuRadioGroup :model-value @update:model-value="choose">
        <DropdownMenuRadioItem
          v-for="option in options"
          :key="option.value"
          :value="option.value"
          class="pointer-coarse:min-h-11"
        >
          <StatusBadge :status="option.status" />
        </DropdownMenuRadioItem>
      </DropdownMenuRadioGroup>
    </DropdownMenuContent>
  </DropdownMenu>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { ChevronDown } from 'lucide-vue-next';
import { ref, watch } from 'vue';

import { StatusBadge } from '@/Components/Patterns';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuRadioGroup,
  DropdownMenuRadioItem,
  DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import type { StatusPresentation } from '@/Constants/statuses';

export interface CollectionStatusOption {
  value: string;
  status: StatusPresentation;
}

const props = defineProps<{
  /** What the badge shows; may be finer than the chosen value ("Suplanuota" is still active). */
  status: StatusPresentation;
  modelValue: string;
  options: CollectionStatusOption[];
  /** Without it the badge is read-only. */
  editable?: boolean;
  pending?: boolean;
}>();

const emit = defineEmits<{
  'update:modelValue': [value: string];
  'open': [];
}>();

const open = ref(false);
watch(open, (isOpen) => {
  if (isOpen) {
    emit('open');
  }
});

function choose(value: unknown): void {
  if (typeof value === 'string' && value !== props.modelValue) {
    emit('update:modelValue', value);
  }
}
</script>
