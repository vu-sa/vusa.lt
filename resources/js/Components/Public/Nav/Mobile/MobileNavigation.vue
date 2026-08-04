<template>
  <Drawer v-model:open="open" direction="right">
    <DrawerTrigger as-child>
      <Button variant="outline" :size="smallerThanSm ? 'sm' : 'default'" :class="cn('gap-2', props.class)">
        <LineHorizontal320Filled class="h-4 w-4" />
        <span class="sr-only lg:not-sr-only">{{ $t('navigation.menu') }}</span>
      </Button>
    </DrawerTrigger>

    <DrawerContent class="flex flex-col overflow-hidden p-0 data-[vaul-drawer-direction=right]:w-full">
      <DrawerTitle class="sr-only">
        {{ $t('navigation.menu') }}
      </DrawerTitle>
      <DrawerDescription class="sr-only">
        {{ $t('navigation.menu') }}
      </DrawerDescription>

      <header class="flex shrink-0 items-center gap-1 border-b border-zinc-200 p-2 dark:border-zinc-700">
        <Button
          variant="ghost"
          size="icon"
          :class="{ invisible: stack.length <= 1 }"
          :aria-hidden="stack.length <= 1"
          :tabindex="stack.length <= 1 ? -1 : undefined"
          :aria-label="$t('navigation.back')"
          @click="back"
        >
          <IFluentChevronLeft24Regular class="size-5" />
        </Button>
        <h2 ref="titleRef" tabindex="-1" class="flex-1 truncate px-1 text-base font-semibold outline-none">
          {{ panelTitle }}
        </h2>
        <DrawerClose as-child>
          <Button variant="ghost" size="icon" :aria-label="$t('navigation.close_menu')">
            <IFluentDismiss24Regular class="size-5" />
          </Button>
        </DrawerClose>
      </header>

      <div class="relative flex-1 overflow-hidden">
        <Transition
          enter-active-class="transition-transform duration-200 ease-out motion-reduce:transition-none"
          leave-active-class="transition-transform duration-200 ease-in motion-reduce:transition-none"
          :enter-from-class="direction === 'forward' ? 'translate-x-full' : '-translate-x-full'"
          :leave-to-class="direction === 'forward' ? '-translate-x-full' : 'translate-x-full'"
        >
          <div :key="panelKey" class="absolute inset-0 h-full overflow-y-auto overscroll-contain">
            <MobileNavRootPanel
              v-if="current.type === 'root'"
              @open-section="openSection"
              @open-tenants="openTenants"
              @close="close"
            />
            <MobileNavSectionPanel
              v-else-if="current.type === 'section' && sectionItem"
              :item="sectionItem!"
              @close="close"
            />
            <MobileTenantPanel v-else-if="current.type === 'tenants'" @close="close" />
          </div>
        </Transition>
      </div>

      <footer class="flex shrink-0 items-center justify-between gap-2 border-t border-zinc-200 p-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] dark:border-zinc-700">
        <a
          href="/login"
          class="flex min-h-11 items-center gap-2 rounded-md px-3 py-2 text-sm font-medium transition-colors hover:bg-zinc-100 dark:hover:bg-zinc-800"
          :title="page.props.auth?.user ? page.props.auth.user?.name : $t('auth.login')"
        >
          <IFluentPerson24Filled v-if="page.props.auth?.user" class="size-5" aria-hidden="true" />
          <IFluentPerson24Regular v-else class="size-5" aria-hidden="true" />
          <span>{{ page.props.auth?.user ? page.props.auth.user?.name : $t('Mano VU SA') }}</span>
        </a>
        <div class="flex items-center gap-1">
          <LocaleButton :locale="page.props.app.locale" size="sm" />
          <DarkModeButton size="icon" />
        </div>
      </footer>
    </DrawerContent>
  </Drawer>
</template>

<script setup lang="ts">
import { breakpointsTailwind, useBreakpoints } from '@vueuse/core';
import { computed, nextTick, ref, watch, type HTMLAttributes } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import MobileNavRootPanel from './MobileNavRootPanel.vue';
import MobileNavSectionPanel from './MobileNavSectionPanel.vue';
import MobileTenantPanel from './MobileTenantPanel.vue';

import LocaleButton from '@/Components/Public/Nav/LocaleButton.vue';
import DarkModeButton from '@/Components/Buttons/DarkModeButton.vue';
import { Drawer, DrawerClose, DrawerContent, DrawerDescription, DrawerTitle, DrawerTrigger } from '@/Components/ui/drawer';
import { Button } from '@/Components/ui/button';
import { cn } from '@/Utils/Shadcn/utils';
import LineHorizontal320Filled from '~icons/fluent/line-horizontal-3-20-filled';
import IFluentChevronLeft24Regular from '~icons/fluent/chevron-left-24-regular';
import IFluentDismiss24Regular from '~icons/fluent/dismiss-24-regular';
import IFluentPerson24Filled from '~icons/fluent/person-24-filled';
import IFluentPerson24Regular from '~icons/fluent/person-24-regular';

type Panel = { type: 'root' } | { type: 'section'; index: number } | { type: 'tenants' };

const props = defineProps<{
  class?: HTMLAttributes['class'];
}>();

const page = usePage();
const breakpoints = useBreakpoints(breakpointsTailwind);
const smallerThanSm = breakpoints.smaller('sm');

const open = ref(false);
const stack = ref<Panel[]>([{ type: 'root' }]);
const direction = ref<'forward' | 'back'>('forward');
const titleRef = ref<HTMLElement | null>(null);

const current = computed(() => stack.value.at(-1)!);
const panelKey = computed(() => `${current.value.type}-${current.value.type === 'section' ? current.value.index : ''}`);

const mainNavigation = computed(() => page.props.mainNavigation ?? []);
const sectionItem = computed(() => current.value.type === 'section' ? mainNavigation.value[current.value.index] : undefined);

const panelTitle = computed(() => {
  if (current.value.type === 'tenants') {
    return $t('navigation.choose_tenant');
  }
  if (current.value.type === 'section') {
    return sectionItem.value?.name ?? '';
  }
  return $t('navigation.menu');
});

const openSection = (index: number) => {
  direction.value = 'forward';
  stack.value.push({ type: 'section', index });
};

const openTenants = () => {
  direction.value = 'forward';
  stack.value.push({ type: 'tenants' });
};

const back = () => {
  direction.value = 'back';
  if (stack.value.length > 1) {
    stack.value.pop();
  }
};

const close = () => {
  open.value = false;
};

// Reopening the sheet should always start at the root panel, never mid-drill.
watch(open, (isOpen) => {
  if (!isOpen) {
    stack.value = [{ type: 'root' }];
  }
});

// Move focus to the panel title on every drill/back step, for screen readers and keyboard users.
watch(current, () => {
  nextTick(() => titleRef.value?.focus());
});

const currentPath = computed(() => page.props.app.path);

watch(currentPath, () => {
  open.value = false;
});
</script>
