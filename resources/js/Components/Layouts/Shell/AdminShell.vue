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

    <div
      ref="scrollArea"
      scroll-region
      data-slot="admin-scroll-area"
      class="min-h-0 flex flex-1 flex-col overflow-auto overscroll-y-contain"
      :style="scrollAreaHeight ? {
        '--shell-chrome-height': `${shellChromeHeight}px`,
        '--shell-scroll-height': `${scrollAreaHeight}px`,
      } : undefined"
    >
      <div ref="shellChrome" class="sticky top-0 z-40 shrink-0">
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
          <MobileContextBar :active-workspace :active-section @menu="menuOpen = true" />
          <SectionTabs :workspace="activeWorkspace" :active-section class="max-md:hidden" />
        </template>
        <SystemAnnouncement :message="systemMessage" />
      </div>

      <main class="flex-1">
        <div class="mx-auto min-h-full w-full max-w-7xl px-4 pt-4 pb-24 sm:px-6 lg:px-8 md:pt-6 md:pb-32" data-slot="admin-page-measure">
          <slot />
        </div>
      </main>
    </div>

    <MobileBottomBar
      v-if="!focused"
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
    />
  </div>
</template>

<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { useElementSize } from '@vueuse/core';
import { computed, ref } from 'vue';

import MobileBottomBar from './MobileBottomBar.vue';
import MobileContextBar from './MobileContextBar.vue';
import MobileMenuPanel from './MobileMenuPanel.vue';
import SectionTabs from './SectionTabs.vue';
import ShellTopBar from './ShellTopBar.vue';
import SystemAnnouncement from './SystemAnnouncement.vue';

import ImpersonateBanner from '@/Components/ImpersonateBanner.vue';
import StagingBanner from '@/Components/StagingBanner.vue';
import { useActionWindow } from '@/Composables/useActionWindow';
import { useAdminNavigation } from '@/Composables/useAdminNavigation';
import { useShellFocus } from '@/Composables/useShellFocus';

const page = usePage<PageProps>();
const scrollArea = ref<HTMLElement | null>(null);
const shellChrome = ref<HTMLElement | null>(null);
const { height: scrollAreaHeight } = useElementSize(scrollArea);
const { height: shellChromeHeight } = useElementSize(shellChrome);
const { workspaces, activeWorkspace, activeSection } = useAdminNavigation();
const actionWindow = useActionWindow();

const shellFocus = useShellFocus();
const focused = computed(() => shellFocus?.isFocused.value ?? false);

const menuOpen = ref(false);
const canCreate = computed(() => workspaces.value.some(workspace => workspace.createActions.length > 0));
// Visi skyriai lists only what the user may open, so it is never a dead end and is offered to everyone.
const showAllSections = true;
const systemMessage = computed(() => page.props.app?.systemMessage ?? null);
</script>
