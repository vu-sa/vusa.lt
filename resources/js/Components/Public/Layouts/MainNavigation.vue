<template>
  <nav
    class="fixed top-0 z-50 flex h-20 w-screen flex-row items-center justify-between gap-4 overflow-x-auto bg-white/80 p-2 text-gray-800 shadow-sm backdrop-blur-sm dark:bg-zinc-800/90 dark:text-white md:px-6 lg:px-12 xl:px-24"
  >
    <div class="flex flex-row items-center space-x-4">
      <!-- Hamburger -->
      <div class="block lg:hidden">
        <NButton size="small" strong quaternary @click="toggleMenu">
          <template #icon>
            <NIcon>
              <Navigation24Filled />
            </NIcon>
          </template>
        </NButton>
      </div>
      <a
        :href="`${$page.props.app.url}/${$page.props.app.locale}`"
        @click="resetPadalinys()"
      >
        <AppLogo :is-theme-dark="isThemeDark" class="w-24 md:w-32" />
      </a>
      <PadalinysSelector
        :size="smallerThanSm ? 'tiny' : 'small'"
        :padalinys="padalinys"
        @select:padalinys="handleSelectPadalinys"
      ></PadalinysSelector>
      <NButton
        v-if="$page.props.auth?.user"
        quaternary
        circle
        size="small"
        @click="router.visit(route('dashboard'))"
        ><NIcon :size="16" :component="AnimalTurtle24Filled"></NIcon
      ></NButton>
    </div>

    <div class="hidden items-center justify-center gap-x-2 md:gap-x-4 lg:flex">
      <MainMenu
        :options="navigation"
        mode="horizontal"
        class="grow"
        :dropdown-props="{ size: 'medium' }"
        :flat-navigation="$page.props.mainNavigation"
        :padalinys="padalinys"
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
      <LocaleButton :locale="locale" @change-locale="localeSelect" />
    </div>
    <NDrawer
      v-model:show="activeDrawer"
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
          :padalinys="padalinys"
          @close:drawer="activeDrawer = false"
        ></MainMenu>
      </NDrawerContent>
    </NDrawer>
  </nav>
</template>

<script setup lang="ts">
import { AnimalTurtle24Filled, Navigation24Filled } from "@vicons/fluent";
import { NButton, NDrawer, NDrawerContent, NIcon, NScrollbar } from "naive-ui";
import { breakpointsTailwind, useBreakpoints } from "@vueuse/core";
import { computed, reactive, ref } from "vue";
import { router, usePage } from "@inertiajs/vue3";
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
import StartFM from "@/Components/Public/Nav/StartFM.vue";

defineProps<{
  isThemeDark: boolean;
}>();

// map padaliniai to options_padaliniai

const padaliniai = usePage().props.padaliniai;
const locale = ref(usePage().props.app.locale);
const activeDrawer = ref(false);
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

const getPadalinys = (alias = usePage().props.alias) => {
  for (const padalinys of padaliniai) {
    if (padalinys.alias == alias) {
      return padalinys.shortname.split(" ").pop();
    }
  }
  return "Padaliniai";
};

const padalinys = ref(getPadalinys());

const handleSelectPadalinys = (key) => {
  console.log(key);

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

  // if subdomain is different the same as padalinys_alias, reload page
  // router.reload({
  //   data: {
  //     padalinys: padalinys_alias,
  //   },
  //   preserveScroll: false,
  //   preserveState: false,
  //   onSuccess: () => {
  //     padalinys.value = getPadalinys(padalinys_alias);
  //     activeDrawer.value = false;
  //   },
  // });
};

const resetPadalinys = () => {
  padalinys.value = "Padaliniai";
};

const localeSelect = (lang: LocaleEnum) => {
  if (lang !== "lt") {
    locale.value = LocaleEnum.EN;
  } else {
    locale.value = LocaleEnum.LT;
  }
  // update app logo button
  homeParams.lang = locale.value;
  // reset padalinys value if home
  padalinys.value = getPadalinys();
};

const breakpoints = useBreakpoints(breakpointsTailwind);
const smallerThanSm = breakpoints.smaller("sm");
</script>
