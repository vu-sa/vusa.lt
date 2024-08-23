<template>
  <section class="fixed top-0 z-50 w-full">
    <div class="group relative">
      <nav
        class="relative z-10 grid grid-cols-[120px,_1fr,_auto] bg-white px-6 text-zinc-800 shadow-sm dark:bg-zinc-800 dark:text-white max-md:h-16 max-md:gap-4 md:h-20 md:grid-cols-[auto,_1fr,_120px] md:gap-12 lg:px-12 xl:px-24 2xl:px-36">
        <div class="flex flex-row items-center space-x-4">
          <SmartLink title="Grįžti į pagrindinį puslapį" :href="`${$page.props.app.url}/${$page.props.app.locale}`"
            target="_self">
            <AppLogo :is-theme-dark="isThemeDark" class="w-24 md:w-32" />
          </SmartLink>
          <NButton v-if="$page.props.auth?.user" class="hidden lg:inline-flex" quaternary tag="a" circle size="small"
            :href="route('dashboard')">
            <IFluentAnimalTurtle24Filled />
          </NButton>
        </div>

        <div class="flex w-full items-center gap-x-2 md:gap-x-4 lg:gap-x-12">
          <MainMenu class="max-lg:hidden xl:ml-12">
            <template #additional>
              <PadalinysSelector :size="smallerThanSm ? 'tiny' : 'small'" />
            </template>
          </MainMenu>
          <div class="hidden max-lg:block">
            <PadalinysSelector :size="smallerThanSm ? 'tiny' : 'small'" />
          </div>
          <div class="hidden items-center gap-4 lg:flex">
            <SearchButton />
            <StartFM />
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
      " class="duration-300 ease-in-out group-hover:translate-y-0 max-lg:hidden" :class="{
        '-translate-y-full': hasScrolledDown,
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
</template>

<script setup lang="tsx">
import { breakpointsTailwind, useBreakpoints } from "@vueuse/core";
import { computed, ref, watch } from "vue";
import { usePage } from "@inertiajs/vue3";

import AppLogo from "@/Components/AppLogo.vue";
import DarkModeSwitch from "@/Components/Buttons/DarkModeButton.vue";
import LocaleButton from "../Nav/LocaleButton.vue";
import MainMenu from "../Nav/MainMenu.vue";
import PadalinysSelector from "../Nav/PadalinysSelector.vue";
import SearchButton from "../Nav/SearchButton.vue";
import SecondMenu from "../Nav/SecondMenu.vue";
import SmartLink from "../SmartLink.vue";
import StartFM from "@/Components/Public/Nav/StartFM.vue";

import LineHorizontal320Filled from "~icons/fluent/line-horizontal-3-20-filled.vue";
import MainMenuMobile from "../Nav/MainMenuMobile.vue";

defineProps<{
  isThemeDark: boolean;
}>();

const breakpoints = useBreakpoints(breakpointsTailwind);
const smallerThanSm = breakpoints.smaller("sm");

const isDrawerActive = ref(false);

const hasScrolledDown = ref(false);

const currentPath = computed(() => usePage().props.app.path);

watch(
  () => currentPath.value,
  () => {
    isDrawerActive.value = false;
  },
);

window.addEventListener(
  // when scrolling down, hide only when scrolled down 50px from previous scroll up
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
