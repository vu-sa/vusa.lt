<template>
  <DropdownMenu>
    <DropdownMenuTrigger as-child>
      <button
        type="button"
        :title="$t('Tvarkyti greitus veiksmus')"
        :aria-label="$t('Tvarkyti greitus veiksmus')"
        class="p-0.5 rounded hover:bg-sidebar-accent/50 text-muted-foreground hover:text-foreground transition-colors"
      >
        <Settings2 class="h-3.5 w-3.5" />
      </button>
    </DropdownMenuTrigger>
    <DropdownMenuContent class="w-56" align="end" :side-offset="4">
      <DropdownMenuLabel>{{ $t('Greiti veiksmai') }}</DropdownMenuLabel>
      <DropdownMenuSeparator />
      <DropdownMenuCheckboxItem
        v-for="meta in availableQuickActions"
        :id="`popover-qa-${meta.key}`"
        :key="meta.key"
        :model-value="isQuickActionVisible(meta.key as QuickActionKey)"
        @update:model-value="(value: boolean) => setQuickActionVisibility(meta.key as QuickActionKey, value)"
      >
        <span class="flex items-center gap-2">
          <component :is="meta.icon" class="size-3.5 shrink-0 text-muted-foreground" />
          {{ meta.title }}
        </span>
      </DropdownMenuCheckboxItem>
      <DropdownMenuSeparator />
      <p class="px-2 py-1.5 text-[10px] text-muted-foreground/70 leading-tight">
        {{ $t('Kai kurie pasirinkimai taip pat veikia komandų paletėje (Ctrl+K / Cmd+K).') }}
      </p>
    </DropdownMenuContent>
  </DropdownMenu>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { Settings2 } from 'lucide-vue-next';

import { useUIPreferences, type QuickActionKey } from '@/Composables/useUIPreferences';
import { useAvailableQuickActions } from '@/Composables/useQuickActions';
import {
  DropdownMenu,
  DropdownMenuCheckboxItem,
  DropdownMenuContent,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';

const { isQuickActionVisible, setQuickActionVisibility } = useUIPreferences();
const { available: availableQuickActions } = useAvailableQuickActions();
</script>
