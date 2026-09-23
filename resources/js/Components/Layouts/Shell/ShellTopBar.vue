<template>
  <header
    data-slot="shell-top-bar"
    :class="[
      'shrink-0 border-b border-(--border-opaque) bg-background/90 backdrop-blur-sm',
      '[.a11y-contrast_&]:bg-background [.a11y-contrast_&]:backdrop-blur-none',
    ]"
  >
    <div class="mx-auto flex h-14 w-full max-w-7xl items-center gap-2 px-4 sm:px-6 md:h-16 md:gap-3 lg:px-8">
      <Link
        v-if="!focused"
        :href="route('dashboard')"
        prefetch
        :cache-for="SHELL_PREFETCH_CACHE_FOR"
        class="mr-1 flex shrink-0 items-center gap-2 text-foreground transition-colors hover:text-foreground md:mr-3"
      >
        <img :src="logoSrc" alt="VU SA" width="1200" height="428" class="h-10 w-auto dark:invert">
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
          size="sm"
          class="hidden h-8 md:inline-flex pointer-coarse:h-11"
          @click="emit('create')"
        >
          <Plus class="size-4" />
          {{ $t('shell.chrome.create') }}
        </Button>

        <!-- Phones get notifications from the bottom bar. -->
        <div class="hidden md:block">
          <NotificationsIndicator />
        </div>
        <div data-tour="account-menu" class="hidden md:block">
          <ShellAccountMenu />
        </div>
      </template>
    </div>
  </header>
</template>

<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Plus } from 'lucide-vue-next';
import { computed } from 'vue';

import PaletteField from './PaletteField.vue';
import ShellAccountMenu from './ShellAccountMenu.vue';
import WorkspacePicker from './WorkspacePicker.vue';

import NotificationsIndicator from '@/Components/NotificationsIndicator.vue';
import { Button } from '@/Components/ui/button';
import { SHELL_PREFETCH_CACHE_FOR, type AdminSection, type AdminWorkspace } from '@/Composables/useAdminNavigation';
import { SHELL_FORM_BAR_ID } from '@/Composables/useShellFocus';
import { getAppLogoSrc } from '@/Utils/AppLogo';

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
const page = usePage<PageProps>();
const logoSrc = computed(() => getAppLogoSrc('vusa', page.props.app.locale));
</script>
