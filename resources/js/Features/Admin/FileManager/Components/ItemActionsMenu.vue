<template>
  <DropdownMenu v-if="actions.length > 0 || note">
    <DropdownMenuTrigger as-child>
      <Button
        variant="ghost"
        size="icon-sm"
        :class="triggerClass"
        :aria-label="$t('files.ui.actions_for', { name: label })"
        :title="$t('files.ui.actions')"
        @click.stop
        @dblclick.stop
      >
        <MoreHorizontal class="size-4" aria-hidden="true" />
      </Button>
    </DropdownMenuTrigger>
    <DropdownMenuContent align="end" class="w-56 rounded-none" @click.stop>
      <template v-for="action in actions" :key="action.key">
        <DropdownMenuSeparator v-if="action.destructive" />
        <DropdownMenuItem
          :variant="action.destructive ? 'destructive' : 'default'"
          class="min-h-9 cursor-pointer pointer-coarse:min-h-11"
          @select="emit('select', action.key)"
        >
          <component :is="action.icon" aria-hidden="true" />
          {{ action.label }}
        </DropdownMenuItem>
      </template>
      <template v-if="note">
        <DropdownMenuSeparator v-if="actions.length > 0" />
        <p class="px-2 py-1.5 text-xs text-muted-foreground">
          {{ note }}
        </p>
      </template>
    </DropdownMenuContent>
  </DropdownMenu>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { MoreHorizontal } from '@lucide/vue';

import type { ItemAction } from '../types';

import { Button } from '@/Components/ui/button';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';

defineProps<{
  /** Name of the file or folder, for the trigger's accessible label. */
  label: string;
  actions: ItemAction[];
  /** Explains a missing action (e.g. why a folder cannot be deleted yet). */
  note?: string;
  triggerClass?: string;
}>();

const emit = defineEmits<{
  select: [key: string];
}>();
</script>
