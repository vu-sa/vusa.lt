<template>
  <section class="fixed top-0 z-50 w-full">
    <div class="group relative">
      <nav
        class="relative z-10 grid grid-cols-[auto,_1fr,_80px] bg-white px-6 text-zinc-800 shadow-sm dark:bg-zinc-800 dark:text-white max-md:h-16 max-md:gap-4 md:h-20 md:gap-12 lg:px-12 xl:px-24 2xl:px-36">
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

        <div class="flex w-full items-center justify-center gap-x-2 max-md:justify-between md:gap-x-4 lg:gap-x-12">
          <MainMenu class="max-md:hidden">
            <template #additional>
              <PadalinysSelector :size="smallerThanSm ? 'tiny' : 'small'" @select:padalinys="handleSelectPadalinys" />
            </template>
          </MainMenu>
          <div class="hidden max-md:block">
            <PadalinysSelector :size="smallerThanSm ? 'tiny' : 'small'" @select:padalinys="handleSelectPadalinys" />
          </div>
          <div class="hidden items-center gap-4 md:flex">
            <SearchButton />
            <StartFM />
          </div>
        </div>
        <div class="flex items-center gap-4">
          <LocaleButton :locale="$page.props.app.locale" />
          <DarkModeSwitch />
        </div>
      </nav>
      <nav class="relative z-50 w-full bg-white text-center shadow-sm dark:bg-zinc-800 dark:text-white">
        <MainMenu class="mx-auto hidden pb-1 max-md:block" />
      </nav>
      <SecondMenu v-if="
        $page.props.tenant?.links &&
        $page.props.tenant?.links.length > 0
      " class="duration-300 ease-in-out group-hover:translate-y-0" :class="{
        '-translate-y-full': hasScrolledDown,
      }" :links="$page.props.tenant?.links" />
    </div>
  </section>
</template>

<script setup lang="tsx">
import { breakpointsTailwind, useBreakpoints } from "@vueuse/core";
import { ref } from "vue";
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

defineProps<{
  isThemeDark: boolean;
}>();

const handleSelectPadalinys = (key) => {
  let padalinys_alias = key;

  // if padalinys is array, get first element (for mobile)
  // because tree component returns array of selected keys
  if (Array.isArray(padalinys_alias)) {
    padalinys_alias = key[0];
  }

  // get last two elements of host and join them with dot
  const hostWithoutSubdomain = window.location.host
    .split(".")
    .slice(-2)
    .join(".");

  window.location.href = `${window.location.protocol
    }//${padalinys_alias}.${hostWithoutSubdomain}${usePage().url}`;
};

const breakpoints = useBreakpoints(breakpointsTailwind);
const smallerThanSm = breakpoints.smaller("sm");

const hasScrolledDown = ref(false);

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
