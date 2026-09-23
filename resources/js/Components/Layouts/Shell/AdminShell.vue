<template>
  <!-- `--shell-bottom-bar` lifts the fixed save bars above the bottom bar on phones. -->
  <div
    data-slot="admin-shell"
    :data-focused="focused || undefined"
    :class="[
      'flex h-svh flex-col bg-background text-foreground',
      !focused && 'max-md:[--shell-bottom-bar:calc(3.5rem_+_env(safe-area-inset-bottom,0px))]',
    ]"
  >
    <StagingBanner />
    <ImpersonateBanner />

    <ShellTopBar
      :workspaces
      :active-workspace
      :active-section
      :show-all-sections
      :can-create
      :focused
      @create="actionWindow.open()"
    />
    <template v-if="!focused">
      <SectionTabs :workspace="activeWorkspace" :active-section />
      <ShellBreadcrumbs :active-section />
    </template>
    <SystemAnnouncement :message="systemMessage" />

    <main class="min-h-0 flex-1 overflow-auto">
      <div class="mx-auto min-h-full w-full max-w-[100rem] p-4 md:p-6">
        <slot />
      </div>
    </main>

    <MobileBottomBar
      v-if="!focused"
      :primary
      :active-workspace
      :active-section
      :can-create
      :menu-open
      @create="actionWindow.open()"
      @menu="menuOpen = true"
    />
    <MobileMenuPanel
      v-model:open="menuOpen"
      :workspaces
      :active-workspace
      :active-section
      :show-all-sections
    />
  </div>
</template>

<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import MobileBottomBar from './MobileBottomBar.vue';
import MobileMenuPanel from './MobileMenuPanel.vue';
import SectionTabs from './SectionTabs.vue';
import ShellBreadcrumbs from './ShellBreadcrumbs.vue';
import ShellTopBar from './ShellTopBar.vue';
import SystemAnnouncement from './SystemAnnouncement.vue';

import ImpersonateBanner from '@/Components/ImpersonateBanner.vue';
import StagingBanner from '@/Components/StagingBanner.vue';
import { useActionWindow } from '@/Composables/useActionWindow';
import { useAdminNavigation } from '@/Composables/useAdminNavigation';
import { useShellFocus } from '@/Composables/useShellFocus';

const page = usePage<PageProps>();
const { workspaces, activeWorkspace, activeSection, primaryWorkspace: primary } = useAdminNavigation();
const actionWindow = useActionWindow();

const shellFocus = useShellFocus();
const focused = computed(() => shellFocus?.isFocused.value ?? false);

const menuOpen = ref(false);
const canCreate = computed(() => workspaces.value.some(workspace => workspace.createActions.length > 0));
// Visi skyriai lists only what the user may open, so it is never a dead end and is offered to everyone.
const showAllSections = true;
const systemMessage = computed(() => page.props.app?.systemMessage ?? null);
</script>
