<template>
  <header class="fixed top-0 w-[100cqw] z-50">
    <section class="max-w-[84rem] mx-auto">
      <div class="group relative mt-4 mx-4 2xl:mx-0">
        <nav
          aria-label="Main navigation"
          class="relative z-10 flex bg-white py-0.5 pl-3 pr-6 text-zinc-800 transition duration-500 group-hover:rounded-b-none group-hover:rounded-t-2xl group-hover:delay-500 dark:bg-zinc-800 dark:text-white max-md:rounded-xl max-md:gap-2 md:grid md:grid-cols-[auto__1fr__auto] md:gap-8 md:px-8 md:rounded-t-2xl"
          :class="{
            // Desktop shadow logic (md and up)
            'md:rounded-2xl shadow-lg group-hover:shadow-none': hasScrolledDown && hasSecondMenu,
            'md:rounded-2xl shadow-lg': hasScrolledDown && !hasSecondMenu,
            'ease-in': !hasScrolledDown && hasSecondMenu,
            'ease-in shadow-md': !hasScrolledDown && !hasSecondMenu,
            // Mobile shadow logic (max-md)
            'max-md:shadow-lg': hasScrolledDown,
            'max-md:shadow-md max-md:ease-in': !hasScrolledDown,
          }">
          <div class="flex flex-row items-center space-x-4">
            <SmartLink prefetch title="Grįžti į pagrindinį puslapį" class="leading-3 w-32 h-12 md:w-36 md:h-14 p-1 inline-flex items-center justify-center rounded-md hover:bg-accent hover:text-accent-foreground transition-colors"
              :href="`${$page.props.app.url}/${$page.props.app.locale}`" target="_self">
              <AppLogo :is-theme-dark class="w-full h-full" />
            </SmartLink>
          </div>

          <div class="flex w-full items-center gap-x-2 md:gap-x-4">
            <MainMenu class="max-lg:hidden xl:ml-12">
              <template #additional>
                <PadalinysSelector :size="smallerThanSm ? 'tiny' : 'small'" />
              </template>
            </MainMenu>
            <div class="hidden max-lg:flex items-center">
              <PadalinysSelector :size="smallerThanSm ? 'tiny' : 'small'" />
            </div>
          </div>
          <div class="my-auto justify-self-end">
            <div class="hidden gap-2 lg:flex items-center">
              <LocaleButton :locale="$page.props.app.locale" size="sm" />
              <DarkModeSwitch size="icon" />
            </div>
            <div class="ml-auto lg:hidden flex items-center gap-2">
              <!-- Hide LocaleButton on very small screens (smaller than sm breakpoint) -->
              <LocaleButton v-if="!smallerThanSm" :locale="$page.props.app.locale" size="default" />
              <Drawer direction="left">
                <DrawerTrigger>
                  <Button variant="outline" :size="smallerThanSm ? 'sm' : 'default'" class="gap-2">
                    <LineHorizontal320Filled class="h-4 w-4" />
                    <span class="sr-only lg:not-sr-only">{{ $t('Menu') }}</span>
                  </Button>
                </DrawerTrigger>
                <DrawerContent class="p-6">
                  <Suspense>
                    <MainMenuMobile class="pb-4" />
                    <template #fallback>
                      <div class="animate-pulse h-32 bg-gray-200 rounded dark:bg-gray-700" />
                    </template>
                  </Suspense>
                </DrawerContent>
              </Drawer>
            </div>
          </div>
        </nav>
        <SecondMenu v-if="hasSecondMenu"
          class="bg-gradient-to-br from-nav-gradient-from-light to-nav-gradient-to-light dark:from-nav-gradient-from-dark dark:to-nav-gradient-to-dark border-nav-border-light dark:border-nav-border-dark border-t duration-300 ease-in-out group-hover:translate-y-0 group-hover:opacity-100 group-hover:shadow-md max-md:hidden"
          :class="{
            '-translate-y-full opacity-0': hasScrolledDown,
            'opacity-100 shadow-md': !hasScrolledDown,
          }" />
      </div>
    </section>
  </header>
</template>

<script setup lang="tsx">
import { breakpointsTailwind, useBreakpoints } from '@vueuse/core';
import { computed, defineAsyncComponent, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import LocaleButton from '../Nav/LocaleButton.vue';
import MainMenu from '../Nav/MainMenu.vue';
import PadalinysSelector from '../Nav/PadalinysSelector.vue';
import SecondMenu from '../Nav/SecondMenu.vue';
import SmartLink from '../SmartLink.vue';

import DarkModeSwitch from '@/Components/Buttons/DarkModeButton.vue';
import AppLogo from '@/Components/AppLogo.vue';
import { useSecondMenu } from '@/Composables/useSecondMenu';
import LineHorizontal320Filled from '~icons/fluent/line-horizontal-3-20-filled';
import { Drawer, DrawerContent, DrawerTrigger } from '@/Components/ui/drawer';
import { Button } from '@/Components/ui/button';

const MainMenuMobile = defineAsyncComponent(() => import('../Nav/MainMenuMobile.vue'));

defineProps<{
  isThemeDark: boolean;
}>();

const breakpoints = useBreakpoints(breakpointsTailwind);
const smallerThanSm = breakpoints.smaller('sm');

// Use shared composable for second menu visibility logic
const { hasSecondMenu, hasScrolledDown } = useSecondMenu();

const currentPath = computed(() => usePage().props.app.path);

// When the route changes, close the drawer
watch(
  () => currentPath.value,
  () => {
    // Drawer auto-closes when route changes
  },
);
</script>
