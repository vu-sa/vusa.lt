<template>
  <header data-slot="shell-top-bar" class="shrink-0 border-b border-border bg-background">
    <div class="flex h-14 items-center gap-2 px-4 md:gap-3 md:px-6">
      <Link
        :href="route('dashboard')"
        prefetch
        :cache-for="SHELL_PREFETCH_CACHE_FOR"
        :class="[
          'mr-1 shrink-0 items-baseline gap-1 text-sm font-bold uppercase tracking-wide md:mr-3',
          focused ? 'hidden lg:flex' : 'flex',
        ]"
      >
        <span>Mano</span>
        <span class="text-brand">VU SA</span>
      </Link>

      <!-- A form teleports its editor bar (back, save state, actions, save) in here. Always
           rendered: on a hard load the shell mounts before the form can switch focus on. -->
      <div :id="SHELL_FORM_BAR_ID" :class="focused ? 'flex min-w-0 flex-1 items-center gap-2' : 'contents'" />

      <template v-if="!focused">
        <WorkspacePicker
          data-tour="workspace-picker"
          class="hidden md:block"
          :workspaces
          :active-workspace
          :active-section
          :show-all-sections
        />

        <div data-tour="command-palette" class="flex min-w-0 flex-1 items-center justify-end md:justify-start">
          <PaletteField />
        </div>

        <Button
          v-if="canCreate"
          data-tour="action-create"
          variant="brand"
          class="hidden text-xs font-bold uppercase tracking-wide md:inline-flex"
          @click="emit('create')"
        >
          <Plus class="size-4" />
          {{ $t('shell.chrome.create') }}
        </Button>
      </template>

      <NotificationsIndicator />
      <div data-tour="account-menu" class="hidden md:block">
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
import { SHELL_PREFETCH_CACHE_FOR, type AdminSection, type AdminWorkspace } from '@/Composables/useAdminNavigation';
import { SHELL_FORM_BAR_ID } from '@/Composables/useShellFocus';

defineProps<{
  workspaces: AdminWorkspace[];
  activeWorkspace?: AdminWorkspace;
  activeSection?: AdminSection;
  showAllSections?: boolean;
  /** Hidden, never disabled, when the catalog holds no create action for this user. */
  canCreate?: boolean;
  /** A form is open: navigation chrome gives way to the form's own bar. */
  focused?: boolean;
}>();

const emit = defineEmits<{ create: [] }>();
</script>
