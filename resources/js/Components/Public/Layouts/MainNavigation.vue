<template>
  <section class="fixed top-0 z-50">
    <div class="relative">
      <nav
        class="relative z-10 flex h-20 w-screen flex-row items-center justify-between gap-4 bg-white p-2 text-gray-800 shadow-sm dark:bg-zinc-800 dark:text-white md:px-6 lg:px-12 xl:px-24"
      >
        <div class="flex flex-row items-center space-x-4">
          <!-- Hamburger -->
          <div v-if="smallerThanLg" class="block">
            <NButton size="small" strong quaternary @click="toggleMenu">
              <template #icon>
                <NIcon :component="Navigation24Filled" />
              </template>
            </NButton>
          </div>
          <a :href="`${$page.props.app.url}/${$page.props.app.locale}`">
            <AppLogo :is-theme-dark="isThemeDark" class="w-24 md:w-32" />
          </a>
          <PadalinysSelector
            :size="smallerThanSm ? 'tiny' : 'small'"
            @select:padalinys="handleSelectPadalinys"
          ></PadalinysSelector>
          <NButton
            v-if="$page.props.auth?.user"
            class="hidden lg:inline-flex"
            quaternary
            tag="a"
            circle
            size="small"
            :href="route('dashboard')"
            ><NIcon :size="16" :component="AnimalTurtle24Filled"></NIcon
          ></NButton>
        </div>

        <div
          class="hidden items-center justify-center gap-x-2 md:gap-x-4 lg:flex"
        >
          <MainMenu
            :options="navigation"
            mode="horizontal"
            class="grow"
            :dropdown-props="{ size: 'medium' }"
            :flat-navigation="$page.props.mainNavigation"
            @close:drawer="activeDrawer = false"
          ></MainMenu>
          <div class="flex items-center gap-4">
            <FacebookButton />
            <InstagramButton />
            <SearchButton />
            <StartFM />
          </div>
        </div>
        <div class="flex items-center gap-4">
          <DarkModeSwitch />
          <LocaleButton
            :locale="$page.props.app.locale"
            @change-locale="localeSelect"
          />
        </div>
        <NDrawer
          v-model:show="activeDrawer"
          display-directive="show"
          :width="325"
          placement="left"
          :trap-focus="true"
        >
          <NDrawerContent closable>
            <template #header>
              <div class="flex gap-4">
                <FacebookButton />
                <InstagramButton />
                <SearchButton />
                <StartFM />
              </div>
            </template>
            <MainMenu
              :options="navigation"
              :flat-navigation="$page.props.mainNavigation"
              @close:drawer="activeDrawer = false"
            ></MainMenu>
          </NDrawerContent>
        </NDrawer>
      </nav>
      <Transition v-if="$page.props.padalinys?.links">
        <SecondMenu v-if="showSecondMenu" :links="$page.props.padalinys?.links">
        </SecondMenu>
      </Transition>
    </div>
  </section>
</template>

<script setup lang="ts">
import { AnimalTurtle24Filled, Navigation24Filled } from "@vicons/fluent";
import { NButton, NDrawer, NDrawerContent, NIcon } from "naive-ui";
import { breakpointsTailwind, useBreakpoints } from "@vueuse/core";
import { computed, reactive, ref } from "vue";
import { usePage } from "@inertiajs/vue3";
import type { RouteParamsWithQueryOverload } from "ziggy-js";

import { LocaleEnum } from "@/Types/enums";
import AppLogo from "@/Components/AppLogo.vue";
import DarkModeSwitch from "@/Components/Buttons/DarkModeSwitch.vue";
import FacebookButton from "../Nav/FacebookButton.vue";
import InstagramButton from "../Nav/InstagramButton.vue";
import LocaleButton from "../Nav/LocaleButton.vue";
import MainMenu from "../Nav/MainMenu.vue";
import PadalinysSelector from "../Nav/PadalinysSelector.vue";
import SearchButton from "../Nav/SearchButton.vue";
import SecondMenu from "../Nav/SecondMenu.vue";
import StartFM from "@/Components/Public/Nav/StartFM.vue";

defineProps<{
  isThemeDark: boolean;
}>();

const activeDrawer = ref(false);

const locale = ref(usePage().props.app.locale);
const toggleMenu = () => {
  activeDrawer.value = !activeDrawer.value;
};

const homeParams: RouteParamsWithQueryOverload = reactive({
  lang: locale.value,
});

const parseNavigation = (array: App.Entities.Navigation[], id: number) => {
  const result: Record<string, any>[] = [];
  array.forEach((item) => {
    if (item.parent_id === id) {
      result.push({
        key: item.id,
        label: item.name,
        children: parseNavigation(array, item.id),
        url: item.url.replace(/^\/|\/$/g, ""),
      });
      if (result[result.length - 1].children.length === 0) {
        delete result[result.length - 1].children;
        delete result[result.length - 1].icon;
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

  window.location.href = `${
    window.location.protocol
  }//${padalinys_alias}.${hostWithoutSubdomain}${usePage().url}`;
};

const localeSelect = (lang: LocaleEnum) => {
  if (lang !== "lt") {
    locale.value = LocaleEnum.EN;
  } else {
    locale.value = LocaleEnum.LT;
  }
  // update app logo button
  homeParams.lang = locale.value;
};

const breakpoints = useBreakpoints(breakpointsTailwind);
const smallerThanSm = breakpoints.smaller("sm");
const smallerThanLg = breakpoints.smaller("lg");

const showSecondMenu = ref(true);

// on 50px scroll down hide second menu, but on scroll up show it
// only hide when scrolled down from last scroll up 50px

let lastScrollTop = 0;

window.addEventListener(
  // when scrolling down, hide only when scrolled down 50px from previous scroll up
  "scroll",
  () => {
    const st = window.scrollY || document.documentElement.scrollTop;
    if (st > lastScrollTop) {
      // downscroll code
      showSecondMenu.value = false;
    } else {
      // upscroll code
      showSecondMenu.value = true;
    }
    lastScrollTop = st <= 0 ? 0 : st; // For Mobile or negative scrolling
  },
);
</script>

<style scoped>
/* add vue transform y to second menu */

.v-enter-from,
.v-leave-to {
  transform: translateY(-100%);
}

.v-enter-active,
.v-leave-active {
  transition: all 0.3s ease;
}

/* https://css-tricks.com/books/greatest-css-tricks/scroll-shadows/ */
/* horizontal scroll shadow */
</style>
