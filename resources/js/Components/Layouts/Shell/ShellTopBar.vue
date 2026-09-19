<template>
  <header data-slot="shell-top-bar" class="shrink-0 border-b border-border bg-background">
    <div class="flex h-14 items-center gap-2 px-4 md:gap-3 md:px-6">
      <Link
        :href="route('dashboard')"
        class="mr-1 flex shrink-0 items-baseline gap-1 text-sm font-bold uppercase tracking-wide md:mr-3"
      >
        <span>Mano</span>
        <span class="text-brand">VU SA</span>
      </Link>

      <WorkspacePicker
        class="hidden md:block"
        :workspaces
        :active-workspace
        :active-section
        :show-all-sections
      />

      <div class="flex min-w-0 flex-1 items-center justify-end md:justify-start">
        <PaletteField />
      </div>

      <Button
        v-if="canCreate"
        variant="brand"
        class="hidden text-xs font-bold uppercase tracking-wide md:inline-flex"
        @click="emit('create')"
      >
        <Plus class="size-4" />
        {{ $t('shell.chrome.create') }}
      </Button>

      <NotificationsIndicator />
      <div class="hidden md:block">
        <ShellAccountMenu />
      </div>
    </div>
  </header>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Plus } from 'lucide-vue-next';

import PaletteField from './PaletteField.vue';
import ShellAccountMenu from './ShellAccountMenu.vue';
import WorkspacePicker from './WorkspacePicker.vue';

import NotificationsIndicator from '@/Components/NotificationsIndicator.vue';
import { Button } from '@/Components/ui/button';
import type { AdminSection, AdminWorkspace } from '@/Composables/useAdminNavigation';

defineProps<{
  workspaces: AdminWorkspace[];
  activeWorkspace?: AdminWorkspace;
  activeSection?: AdminSection;
  showAllSections?: boolean;
  /** Hidden, never disabled, when the catalog holds no create action for this user. */
  canCreate?: boolean;
}>();

const emit = defineEmits<{ create: [] }>();
</script>
