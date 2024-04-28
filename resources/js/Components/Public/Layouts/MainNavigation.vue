<template>
  <section class="fixed top-0 z-50 w-full">
    <div class="group relative">
      <nav
        class="relative z-10 grid h-20 grid-cols-[auto,_1fr,_100px] gap-12 bg-white px-6 text-zinc-800 shadow-sm dark:bg-zinc-800 dark:text-white lg:px-12 xl:px-24 2xl:px-36">
        <div class="flex flex-row items-center space-x-4">
          <SmartLink title="Grįžti į pagrindinį puslapį" :href="`${$page.props.app.url}/${$page.props.app.locale}`"
            target="_self">
            <AppLogo :is-theme-dark="isThemeDark" class="w-24 md:w-32" />
          </SmartLink>
          <NButton v-if="$page.props.auth?.user" class="hidden lg:inline-flex" quaternary tag="a" circle size="small"
            :href="route('dashboard')">
            <NIcon :size="16" :component="AnimalTurtle24Filled" />
          </NButton>
        </div>

        <div class="flex w-full items-center gap-x-2 max-md:justify-between md:gap-x-6 lg:gap-x-8">
          <!-- <MainMenu :options="navigation" mode="horizontal" class="grow" :dropdown-props="{ size: 'medium' }"
@close:drawer="activeDrawer = false" /> -->
          <MainMenuUpdated class="max-md:hidden">
            <template #additional>
              <PadalinysSelector :size="smallerThanSm ? 'tiny' : 'small'" @select:padalinys="handleSelectPadalinys" />
            </template>
          </MainMenuUpdated>
          <div class="hidden max-md:block">
            <PadalinysSelector :size="smallerThanSm ? 'tiny' : 'small'" @select:padalinys="handleSelectPadalinys" />
          </div>
          <div class="flex items-center gap-4">
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
        <MainMenuUpdated class="mx-auto hidden max-md:block" />
      </nav>
      <SecondMenu v-if="
        $page.props.padalinys?.links &&
        $page.props.padalinys?.links.length > 0
      " class="duration-300 ease-in-out group-hover:translate-y-0" :class="{
        '-translate-y-full': hasScrolledDown,
      }" :links="$page.props.padalinys?.links" />
    </div>
  </section>
</template>

<script setup lang="tsx">
import { AnimalTurtle24Filled, Navigation24Filled } from "@vicons/fluent";
import { NButton, NDrawer, NDrawerContent, NIcon } from "naive-ui";
import { breakpointsTailwind, useBreakpoints } from "@vueuse/core";
import { computed, ref } from "vue";
import { usePage } from "@inertiajs/vue3";

import AppLogo from "@/Components/AppLogo.vue";
import DarkModeSwitch from "@/Components/Buttons/DarkModeButton.vue";
import LocaleButton from "../Nav/LocaleButton.vue";
import MainMenu from "../Nav/MainMenu.vue";
import MainMenuUpdated from "../Nav/MainMenuUpdated.vue";
import PadalinysSelector from "../Nav/PadalinysSelector.vue";
import SearchButton from "../Nav/SearchButton.vue";
import SecondMenu from "../Nav/SecondMenu.vue";
import SmartLink from "../SmartLink.vue";
import StartFM from "@/Components/Public/Nav/StartFM.vue";

defineProps<{
  isThemeDark: boolean;
}>();

const activeDrawer = ref(false);

const toggleMenu = () => {
  activeDrawer.value = !activeDrawer.value;
};

const parseNavigation = (array: App.Entities.Navigation[], id: number) => {
  const result: Record<string, any>[] = [];
  array.forEach((item) => {
    if (item.parent_id === id) {
      // if item href matches usepage.props.app.url, then use self link
      // else use external link

      let target: string | undefined = undefined;
      let appUrl = usePage().props.app.url;

      if (item.url.startsWith(appUrl)) {
        target = "_self";
      }

      result.push({
        key: item.id,
        label() {
          return (
            <SmartLink target={target} href={item.url}>
              {item.name}
            </SmartLink>
          );
        },
        children: parseNavigation(array, item.id),
      });
      if (result[result.length - 1].children.length === 0) {
        delete result[result.length - 1].children;
        delete result[result.length - 1].icon;
      } else {
        // change label to simple span
        result[result.length - 1].label = item.name;
      }
    }
  });
  return result;
};

const navigation = computed(() => {
  if (usePage().props.mainNavigation === undefined) {
    return [];
  }

  return parseNavigation(usePage().props.mainNavigation, 0);
});

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
const smallerThanLg = breakpoints.smaller("lg");

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
