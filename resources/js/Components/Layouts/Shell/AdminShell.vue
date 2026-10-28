<template>
  <!-- `--shell-bottom-bar` lifts the fixed save bars above the bottom bar on phones. -->
  <div
    data-slot="admin-shell"
    :data-focused="focused || undefined"
    :class="[
      'flex h-dvh flex-col bg-background text-foreground',
      !focused && 'max-md:[--shell-bottom-bar:calc(3.5rem_+_env(safe-area-inset-bottom,0px))]',
    ]"
  >
    <!-- Skip to main content link - positioned first for keyboard navigation -->
    <a
      href="#main-content"
      :class="[
        'sr-only focus:not-sr-only focus:fixed focus:top-4 focus:left-4 focus:z-[9999]',
        'focus:bg-background focus:text-foreground focus:px-4 focus:py-2 focus:border-2 focus:border-brand',
      ]"
    >
      {{ $t('accessibility.skip_to_main_content') }}
    </a>

    <StagingWatermark />
    <StagingBanner v-model:dismissed="stagingDismissed" />
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
      <!-- A view-transition name creates a backdrop root, so the chrome itself owns the blur. -->
      <div
        ref="shellChrome"
        data-slot="shell-chrome"
        class="sticky top-0 z-40 shrink-0 backdrop-blur-sm [view-transition-name:admin-chrome] [.a11y-contrast_&]:backdrop-blur-none"
      >
        <ShellTopBar
          :workspaces
          :active-workspace
          :active-section
          :show-all-sections
          :can-create
          :focused
          @create="actionWindow.open()"
        >
          <template #staging-warning>
            <StagingBanner v-model:dismissed="stagingDismissed" compact />
          </template>
        </ShellTopBar>
        <template v-if="!focused">
          <MobileContextBar :active-workspace :active-section @menu="menuOpen = true">
            <template #staging-warning>
              <StagingBanner v-model:dismissed="stagingDismissed" compact />
            </template>
          </MobileContextBar>
          <SectionTabs :workspace="activeWorkspace" :active-section class="max-md:hidden" />
        </template>
        <SystemAnnouncement :message="systemMessage" />
      </div>

      <main id="main-content" tabindex="-1" class="min-w-0 flex-1 outline-none">
        <div class="mx-auto min-h-full min-w-0 w-full max-w-7xl px-4 pt-4 pb-24 sm:px-6 lg:px-8 md:pt-6 md:pb-32" data-slot="admin-page-measure">
          <slot />
        </div>
      </main>
    </div>

    <div ref="bottomStack" class="shrink-0 md:contents" data-slot="shell-bottom-stack">
      <StartFmPlayer surface="admin" />
      <div :id="SHELL_MOBILE_ACTIONS_ID" class="md:hidden" />
      <MobileBottomBar
        v-if="!focused"
        :active-workspace
        :active-section
        :can-create
        :menu-open
        @create="actionWindow.open()"
        @menu="menuOpen = true"
      />
    </div>
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
import { useElementSize, useEventListener, useLocalStorage } from '@vueuse/core';
import { computed, ref, watch } from 'vue';

import MobileBottomBar from './MobileBottomBar.vue';
import MobileContextBar from './MobileContextBar.vue';
import MobileMenuPanel from './MobileMenuPanel.vue';
import SectionTabs from './SectionTabs.vue';
import ShellTopBar from './ShellTopBar.vue';
import SystemAnnouncement from './SystemAnnouncement.vue';

import ImpersonateBanner from '@/Components/ImpersonateBanner.vue';
import StagingBanner from '@/Components/StagingBanner.vue';
import StagingWatermark from '@/Components/StagingWatermark.vue';
import { useActionWindow } from '@/Composables/useActionWindow';
import { useAdminNavigation } from '@/Composables/useAdminNavigation';
import StartFmPlayer from '@/Components/StartFmPlayer.vue';
import { useStartFm } from '@/Composables/useStartFm';
import { SHELL_MOBILE_ACTIONS_ID, useShellFocus } from '@/Composables/useShellFocus';

const radio = useStartFm();
const bottomStack = ref<HTMLElement | null>(null);
const { height: bottomHeight } = useElementSize(bottomStack, undefined, { box: 'border-box' });
watch(bottomHeight, (height) => {
  radio.bottomInset.value = height;
});

const page = usePage<PageProps>();
const stagingDismissed = useLocalStorage(`admin-staging-warning:${page.props.auth?.user?.id ?? 'guest'}`, false, { writeDefaults: false });
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

// iOS can leave the window scrolled after the keyboard closes, even with overflow hidden.
const resetWindowScroll = () => {
  if (window.scrollY !== 0) {
    window.scrollTo(0, 0);
  }
};
useEventListener(typeof document === 'undefined' ? null : document, 'focusout', resetWindowScroll);
useEventListener(typeof window === 'undefined' ? null : window.visualViewport, 'resize', resetWindowScroll);
</script>
