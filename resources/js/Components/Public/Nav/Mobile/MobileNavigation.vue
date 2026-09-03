<template>
  <!--
    A full-viewport panel, not a sheet.

    The design opens the mobile menu as the page itself: the header stays, everything below it is
    the menu, and sections expand in place. A side sheet with a drill-down stack hid the rest of
    the menu behind a back button and read as a different surface from the site around it.
  -->
  <Button
    variant="ghost"
    size="icon"
    :class="props.class"
    :aria-label="$t('navigation.menu')"
    :aria-expanded="open"
    @click="open = true"
  >
    <LineHorizontal320Filled class="size-4" />
    <span class="sr-only">{{ $t('navigation.menu') }}</span>
  </Button>

  <Teleport to="body">
    <div
      v-if="open"
      class="fixed inset-0 z-[60] flex flex-col bg-background lg:hidden [[data-a11y-font-scale=xl]_&]:!flex"
      role="dialog"
      aria-modal="true"
      :aria-label="$t('navigation.menu')"
    >
      <div class="flex h-16 shrink-0 items-center justify-between border-b border-border px-5 sm:px-6">
        <!-- `inline-flex items-center`: see the same fix in `MainNavigation.vue` — a plain `<a>`
             is taller than the logo it wraps unless it's a flex container itself. -->
        <SmartLink
          prefetch
          target="_self"
          :href="`${page.props.app.url}/${page.props.app.locale}`"
          :title="$t('Grįžti į pagrindinį puslapį')"
          class="inline-flex items-center"
          @click="close"
        >
          <HeaderWordmark />
        </SmartLink>

        <Button
          ref="closeButtonRef"
          variant="ghost"
          size="icon"
          class="border border-border text-foreground/70 transition-colors hover:border-brand hover:bg-transparent hover:text-brand dark:hover:bg-transparent dark:hover:text-brand"
          :aria-label="$t('navigation.close_menu')"
          @click="close"
        >
          <IFluentDismiss24Regular class="size-4" />
        </Button>
      </div>

      <div class="flex-1 overflow-y-auto overscroll-contain">
        <MobileNavRootPanel @close="close" />

        <div class="flex items-center gap-3 p-4 pb-[max(1rem,env(safe-area-inset-bottom))]">
          <LocaleButton :locale="page.props.app.locale" size="public-sm" class="h-9 flex-1 border border-border" />
          <SearchButton size="public-sm" :class="cn(mobileNavButtonClass, 'h-9 flex-1')" @click="close">
            {{ $t('Paieška') }}
          </SearchButton>
          <AccessibilityMenu :class="mobileNavButtonClass" />
          <DarkModeButton size="icon" :class="mobileNavButtonClass" />
        </div>

        <SmartLink
          :href="page.props.auth?.user ? route('dashboard') : route('login')"
          target="_self"
          class="plain flex h-11 items-center gap-1.5 border-t border-border px-4 text-xs font-medium text-muted-foreground transition-colors hover:text-brand"
          :title="page.props.auth?.user ? page.props.auth.user?.name : $t('auth.login')"
        >
          <IFluentPerson24Filled v-if="page.props.auth?.user" class="size-4" aria-hidden="true" />
          <IFluentPerson24Regular v-else class="size-4" aria-hidden="true" />
          <span>{{ $t('Mano VU SA') }}</span>
        </SmartLink>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { computed, nextTick, ref, watch, type HTMLAttributes } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import SmartLink from '../../SmartLink.vue';

import MobileNavRootPanel from './MobileNavRootPanel.vue';

import LocaleButton from '@/Components/Public/Nav/LocaleButton.vue';
import SearchButton from '@/Components/Public/Nav/SearchButton.vue';
import { AccessibilityMenu, HeaderWordmark } from '@/Components/Public/Base';
import DarkModeButton from '@/Components/Buttons/DarkModeButton.vue';
import { Button } from '@/Components/ui/button';
import { cn } from '@/Utils/Shadcn/utils';
import LineHorizontal320Filled from '~icons/fluent/line-horizontal-3-20-filled';
import IFluentDismiss24Regular from '~icons/fluent/dismiss-24-regular';
import IFluentPerson24Filled from '~icons/fluent/person-24-filled';
import IFluentPerson24Regular from '~icons/fluent/person-24-regular';

const props = defineProps<{
  class?: HTMLAttributes['class'];
}>();

const mobileNavButtonClass = 'border border-border text-foreground/70 transition-colors duration-200 '
  + 'hover:border-brand hover:bg-transparent hover:text-brand '
  + 'dark:hover:bg-transparent dark:hover:text-brand';

const page = usePage();

const open = ref(false);
const closeButtonRef = ref<{ $el?: HTMLElement } | null>(null);

const close = () => {
  open.value = false;
};

// The panel covers the page, so the page behind it must not scroll under it.
watch(open, (isOpen) => {
  if (typeof document === 'undefined') return;

  document.body.style.overflow = isOpen ? 'hidden' : '';

  if (isOpen) {
    nextTick(() => closeButtonRef.value?.$el?.focus());
  }
});

const currentPath = computed(() => page.props.app.path);

watch(currentPath, close);
</script>
