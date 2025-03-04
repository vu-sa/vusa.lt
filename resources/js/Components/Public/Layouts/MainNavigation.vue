<template>
  <div class="fixed top-0 w-[100cqw] z-50">
    <section class="max-w-[84rem] mx-auto">
      <div class="group relative mt-4 mx-4 2xl:mx-0">
        <nav
          class="relative z-10 flex bg-white py-0.5 pl-3 pr-6 text-zinc-800 shadow-md transition duration-500 group-hover:rounded-b-none group-hover:rounded-t-2xl group-hover:delay-500 dark:bg-zinc-800 dark:text-white max-md:rounded-xl max-md:gap-2 md:grid md:grid-cols-[auto__1fr__auto] md:gap-12 md:px-10 md:rounded-t-2xl"
          :class="{
            'md:rounded-2xl md:shadow-lg': hasScrolledDown,
            'ease-in md:shadow-none': !hasScrolledDown,
          }">
          <div class="flex flex-row items-center space-x-4">
            <SmartLink prefetch title="Grįžti į pagrindinį puslapį" class="leading-3"
              :href="`${$page.props.app.url}/${$page.props.app.locale}`" target="_self">
              <button
                class="w-24 rounded-lg px-2 py-1 transition hover:bg-zinc-400/10 cursor-pointer dark:hover:bg-zinc-100/10 md:w-28">
                <AppLogo :is-theme-dark />
              </button>
            </SmartLink>
          </div>

          <div class="flex w-full items-center gap-x-2 md:gap-x-4">
            <MainMenu class="max-lg:hidden xl:ml-12">
              <template #additional>
                <PadalinysSelector :size="smallerThanSm ? 'tiny' : 'small'" />
              </template>
            </MainMenu>
            <div class="hidden max-lg:block">
              <PadalinysSelector :size="smallerThanSm ? 'tiny' : 'small'" />
            </div>
          </div>
          <div class="my-auto justify-self-end">
            <div class="hidden gap-4 lg:flex">
              <LocaleButton :locale="$page.props.app.locale" />
              <DarkModeSwitch />
            </div>
            <div class="ml-auto lg:hidden">
              <NButton text @click="isDrawerActive = true">
                Menu
                <template #icon>
                  <LineHorizontal320Filled />
                </template>
              </NButton>
            </div>
          </div>
        </nav>
        <SecondMenu v-if="
          $page.props.tenant?.links &&
          $page.props.tenant?.links.length > 0
        " class="bg-linear-to-br from-stone-50 to-neutral-100 shadow-md duration-300 ease-in-out group-hover:translate-y-0 group-hover:opacity-100 dark:from-zinc-800 dark:to-[rgb(23,_23,_25)] max-md:hidden "
          :class="{
            '-translate-y-full opacity-0 shadow-xs': hasScrolledDown,
            'opacity-100': !hasScrolledDown,
          }" />
      </div>
      <NDrawer v-model:show="isDrawerActive" placement="left" :width="400">
        <NDrawerContent closable>
          <template #header>
            Navigacija
          </template>
          <MainMenuMobile class="pb-4" />
        </NDrawerContent>
      </NDrawer>
    </section>
  </div>
</template>

<script setup lang="tsx">
import { breakpointsTailwind, useBreakpoints } from "@vueuse/core";
import { computed, defineAsyncComponent, ref, watch } from "vue";
import { usePage } from "@inertiajs/vue3";

import AppLogo from "@/Components/AppLogo.vue";
import DarkModeSwitch from "@/Components/Buttons/DarkModeButton.vue";
import LocaleButton from "../Nav/LocaleButton.vue";
import MainMenu from "../Nav/MainMenu.vue";
import PadalinysSelector from "../Nav/PadalinysSelector.vue";
import SecondMenu from "../Nav/SecondMenu.vue";
import SmartLink from "../SmartLink.vue";

import LineHorizontal320Filled from "~icons/fluent/line-horizontal-3-20-filled";

const MainMenuMobile = defineAsyncComponent(() => import("../Nav/MainMenuMobile.vue"));

defineProps<{
  isThemeDark: boolean;
}>();

const breakpoints = useBreakpoints(breakpointsTailwind);
const smallerThanSm = breakpoints.smaller("sm");

const isDrawerActive = ref(false);
const hasScrolledDown = ref(false);

const currentPath = computed(() => usePage().props.app.path);

// When the route changes, close the drawer
watch(
  () => currentPath.value,
  () => {
    isDrawerActive.value = false;
  },
);

// When scrolling down, hide the second menu only when scrolled down 50px from the top
window.addEventListener(
  "scroll",
  () => {
    if (window.scrollY > 50) {
      hasScrolledDown.value = true;
    } else {
      hasScrolledDown.value = false;
    }
  },
);
</script>
