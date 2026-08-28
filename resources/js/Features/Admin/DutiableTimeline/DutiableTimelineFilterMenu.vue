<template>
  <DropdownMenu>
    <DropdownMenuTrigger as-child>
      <Button type="button" size="xs" variant="outline" :disabled="options.length === 0 && !$slots.extra">
        {{ label }}
        <Badge v-if="modelValue.length > 0" variant="secondary" class="ml-1 text-[10px]">
          {{ modelValue.length }}
        </Badge>
        <!-- Whatever the `extra` switches are set to. They persist between visits, so their
             state has to be readable without opening the menu that holds them. -->
        <slot name="indicator" />
        <ChevronDown class="size-3" />
      </Button>
    </DropdownMenuTrigger>

    <DropdownMenuContent align="start" class="max-h-72 w-56 overflow-y-auto">
      <DropdownMenuLabel class="text-xs">
        {{ label }}
      </DropdownMenuLabel>
      <DropdownMenuSeparator />

      <DropdownMenuCheckboxItem
        v-for="option in options"
        :key="option.value"
        :model-value="modelValue.includes(option.value)"
        class="text-xs"
        @select="(event: Event) => event.preventDefault()"
        @update:model-value="toggle(option.value)"
      >
        <span class="truncate">{{ option.label }}</span>
        <span class="ml-auto pl-2 text-[10px] text-muted-foreground">{{ option.count }}</span>
      </DropdownMenuCheckboxItem>

      <!-- View switches that belong to the same question the filter answers, e.g. whether
           ended assignments are listed at all. -->
      <template v-if="$slots.extra">
        <DropdownMenuSeparator />
        <slot name="extra" />
      </template>

      <template v-if="modelValue.length > 0">
        <DropdownMenuSeparator />
        <DropdownMenuItem class="text-xs" @select="emit('update:modelValue', [])">
          {{ $t('dutiables.timeline.filters.clear') }}
        </DropdownMenuItem>
      </template>
    </DropdownMenuContent>
  </DropdownMenu>
</template>

<script setup lang="ts">
import { ChevronDown } from 'lucide-vue-next';

import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import {
  DropdownMenu,
  DropdownMenuCheckboxItem,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';

export interface FilterOption {
  value: string;
  label: string;
  count: number;
}

const props = defineProps<{
  label: string;
  options: FilterOption[];
  modelValue: string[];
}>();

const emit = defineEmits<{
  'update:modelValue': [value: string[]];
}>();

function toggle(value: string): void {
  emit(
    'update:modelValue',
    props.modelValue.includes(value)
      ? props.modelValue.filter(entry => entry !== value)
      : [...props.modelValue, value],
  );
}
</script>
