<template>
  <DialogRoot v-model:open="open">
    <!-- Keep navigation full-screen, with sections expanded in place. -->
    <DialogTrigger as-child>
      <Button
        voice="brand"
        variant="ghost"
        size="icon"
        data-slot="mobile-navigation-trigger"
        :class="props.class"
        :aria-label="$t('navigation.menu')"
      >
        <LineHorizontal320Filled class="size-4" />
        <span class="sr-only">{{ $t('navigation.menu') }}</span>
      </Button>
    </DialogTrigger>

    <!-- After mount only: SSR drops teleported markup, so hydrating a body teleport eats <body>'s first node. -->
    <Teleport v-if="mounted" to="body">
      <DialogOverlay v-if="open" class="fixed inset-0 z-[59]" />
      <MobileDialogContent
        v-if="open"
        as-child
        :aria-describedby="undefined"
        @open-auto-focus="focusCloseButton"
        @close-auto-focus="restoreFocus"
      >
        <div
          class="fixed inset-0 z-[60] flex flex-col bg-background lg:hidden [[data-a11y-font-scale=xl]_&]:!flex"
          data-slot="mobile-navigation"
          aria-modal="true"
        >
          <MobileDialogTitle class="sr-only">
            {{ $t('navigation.menu') }}
          </MobileDialogTitle>
          <div class="flex h-16 shrink-0 items-center justify-between border-b border-border px-5 sm:px-6">
            <!-- `inline-flex items-center`: see the same fix in `MainNavigation.vue` — a plain `<a>`
                 is taller than the logo it wraps unless it's a flex container itself. -->
            <SmartLink
              prefetch
              target="_self"
              :href="page.props.tenant?.homeUrl ?? `${page.props.app.url}/${page.props.app.locale}`"
              :title="$t('Grįžti į pagrindinį puslapį')"
              class="inline-flex items-center"
              @click="close"
            >
              <HeaderWordmark />
            </SmartLink>

            <Button
              ref="closeButtonRef"
              voice="brand"
              variant="ghost"
              size="icon"
              :class="[
                'border border-border text-foreground/70 transition-colors',
                'hover:border-brand hover:bg-transparent hover:text-brand dark:hover:bg-transparent dark:hover:text-brand',
              ]"
              :aria-label="$t('navigation.close_menu')"
              @click="close"
            >
              <IFluentDismiss24Regular class="size-4" />
            </Button>
          </div>

          <div class="flex-1 overflow-y-auto overscroll-contain">
            <MobileNavRootPanel @close="close" />

            <div class="flex items-center gap-3 p-4 pb-[max(1rem,env(safe-area-inset-bottom))]">
              <LocaleButton :locale="page.props.app.locale" size="sm" class="h-9 flex-1 border border-border" />
              <SearchButton size="sm" :class="cn(mobileNavButtonClass, 'h-9 flex-1')" @click="close">
                {{ $t('Paieška') }}
              </SearchButton>
              <AccessibilityMenu :class="mobileNavButtonClass" />
              <DarkModeButton size="icon" :class="mobileNavButtonClass" />
            </div>

            <div class="border-t border-border p-4">
              <Button voice="brand" as-child variant="outline" size="sm" class="h-11 w-full text-foreground/70">
                <a
                  :href="page.props.auth?.user ? route('dashboard') : route('login')"
                  :title="page.props.auth?.user ? page.props.auth.user?.name : $t('auth.login')"
                >
                  <IFluentGrid24Filled
                    v-if="page.props.auth?.user"
                    data-slot="mano-vusa-button-icon"
                    class="size-4 text-brand"
                    aria-hidden="true"
                  />
                  <IFluentGrid24Regular
                    v-else
                    data-slot="mano-vusa-button-icon"
                    class="size-4"
                    aria-hidden="true"
                  />
                  <span>{{ $t('Mano VU SA') }}</span>
                </a>
              </Button>
            </div>
          </div>
        </div>
      </MobileDialogContent>
    </Teleport>
  </DialogRoot>
</template>

<script setup lang="ts">
import { computed, ref, watch, type HTMLAttributes } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useMediaQuery, useMounted } from '@vueuse/core';
import { trans as $t } from 'laravel-vue-i18n';
import { DialogContent as MobileDialogContent, DialogOverlay, DialogRoot, DialogTitle as MobileDialogTitle, DialogTrigger } from 'reka-ui';

import SmartLink from '../../SmartLink.vue';

import MobileNavRootPanel from './MobileNavRootPanel.vue';

import LocaleButton from '@/Components/Public/Nav/LocaleButton.vue';
import SearchButton from '@/Components/Public/Nav/SearchButton.vue';
import { AccessibilityMenu, HeaderWordmark } from '@/Components/Public/Base';
import DarkModeButton from '@/Components/Buttons/DarkModeButton.vue';
import { Button } from '@/Components/ui/button';
import { cn } from '@/Utils/Shadcn/utils';
import { useAccessibilityPreferences } from '@/Composables/useAccessibilityPreferences';
import LineHorizontal320Filled from '~icons/fluent/line-horizontal-3-20-filled';
import IFluentDismiss24Regular from '~icons/fluent/dismiss-24-regular';
import IFluentGrid24Filled from '~icons/fluent/grid-24-filled';
import IFluentGrid24Regular from '~icons/fluent/grid-24-regular';

const props = defineProps<{
  class?: HTMLAttributes['class'];
}>();

const mobileNavButtonClass = 'border border-border text-foreground/70 transition-colors duration-200 '
  + 'hover:border-brand hover:bg-transparent hover:text-brand '
  + 'dark:hover:bg-transparent dark:hover:text-brand';

const page = usePage();

const open = ref(false);
const mounted = useMounted();
const desktop = useMediaQuery('(min-width: 64rem)');
const { fontScale } = useAccessibilityPreferences();
const desktopNavigationVisible = computed(() => desktop.value && fontScale.value !== 'xl');
const closeButtonRef = ref<{ $el?: HTMLElement } | null>(null);

const close = () => {
  open.value = false;
};

function focusCloseButton(event: Event): void {
  event.preventDefault();
  closeButtonRef.value?.$el?.focus();
}

function restoreFocus(event: Event): void {
  if (!desktopNavigationVisible.value) return;
  event.preventDefault();
  document.querySelector<HTMLElement>('header a[href]')?.focus();
}

watch(desktopNavigationVisible, (visible) => {
  if (visible) close();
});

const currentPath = computed(() => page.props.app.path);

watch(currentPath, close);
</script>
