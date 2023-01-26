<template>
  <nav
    class="fixed top-0 z-50 flex max-h-24 w-full flex-row items-center justify-between bg-white/80 px-4 py-2 text-gray-800 shadow-sm backdrop-blur-sm dark:bg-zinc-800/60 dark:text-white lg:px-12 xl:px-24"
  >
    <div
      class="flex flex-row items-center space-x-4"
      :class="{ 'mx-auto': isMobile }"
    >
      <!-- Hamburger -->
      <div v-if="isMobile" class="block">
        <NButton style="border-radius: 0.5rem" @click="toggleMenu">
          <NIcon>
            <Navigation24Filled />
          </NIcon>
        </NButton>
      </div>
      <Link :href="route('main.home', homeParams)" @click="resetPadalinys()">
        <AppLogo class="w-36" />
      </Link>
      <NScrollbar>
        <NDropdown
          :options="options_padaliniai"
          placement="top-start"
          size="small"
          style="overflow: auto; max-height: 600px"
          @select="handleSelectPadalinys"
        >
          <NButton
            :disabled="route().current('*page')"
            size="small"
            style="border-radius: 0.5rem"
          >
            {{ $t(padalinys) }}
            <NIcon class="ml-1" size="18">
              <ChevronDown20Filled />
            </NIcon>
          </NButton>
        </NDropdown>
      </NScrollbar>
      <NButton
        v-if="$page.props.user"
        quaternary
        circle
        size="small"
        @click="Inertia.visit(route('dashboard'))"
        ><NIcon :size="16" :component="AnimalTurtle24Filled"></NIcon
      ></NButton>
    </div>

    <div v-if="!isMobile" class="flex items-center gap-x-4">
      <NMenu
        v-model:value="activeMenuKey"
        :icon-size="16"
        mode="horizontal"
        :options="navigation"
        :dropdown-props="{ size: 'medium' }"
        @update:value="handleSelectNavigation"
      />
      <div class="flex flex-wrap items-center gap-4">
        <FacebookButton />
        <InstagramButton />
        <SearchButton />
        <StartFM />
        <NDivider vertical></NDivider>
        <DarkModeSwitch />
        <LocaleButton :locale="locale" @change-locale="localeSelect" />
      </div>
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
          <span>{{
            padalinys == "Padaliniai" ? $t("VU SA") : $t(padalinys)
          }}</span>
          <div class="mt-4 flex flex-row gap-4">
            <FacebookButton />
            <InstagramButton />
            <SearchButton />
            <StartFM />
            <LocaleButton :locale="locale" @change-locale="localeSelect" />
            <div class="flex items-center justify-center">
              <DarkModeSwitch />
            </div>
          </div>
        </template>
        <template v-if="!route().current('*page')">
          <NCollapse>
            <NCollapseItem :title="$t('Padaliniai')">
              <NTree
                block-line
                :data="options_padaliniai"
                @update:selected-keys="handleSelectPadalinys"
              >
              </NTree>
            </NCollapseItem>
          </NCollapse>
        </template>
        <NDivider></NDivider>
        <NTree
          block-line
          :data="navigation"
          :expanded-keys="expandedKeys"
          :selected-keys="selectedKeys"
          @update:selected-keys="handleSelectNavigation"
        />
      </NDrawerContent>
    </NDrawer>
  </nav>
</template>

<script setup lang="ts">
import { trans as $t } from "laravel-vue-i18n";
import {
  AnimalTurtle24Filled,
  ChevronDown20Filled,
  Navigation24Filled,
} from "@vicons/fluent";
import { Inertia } from "@inertiajs/inertia";
import { Link, usePage } from "@inertiajs/inertia-vue3";
import {
  NButton,
  NCollapse,
  NCollapseItem,
  NDivider,
  NDrawer,
  NDrawerContent,
  NDropdown,
  NIcon,
  NMenu,
  NScrollbar,
  NTree,
} from "naive-ui";
import { computed, reactive, ref } from "vue";
import { split } from "lodash";
import route, { RouteParamsWithQueryOverload } from "ziggy-js";

import AppLogo from "@/Components/AppLogo.vue";
import DarkModeSwitch from "@/Components/DarkModeSwitch.vue";
import FacebookButton from "../Nav/FacebookButton.vue";
import InstagramButton from "../Nav/InstagramButton.vue";
import LocaleButton from "../Nav/LocaleButton.vue";
import SearchButton from "../Nav/SearchButton.vue";
import StartFM from "@/Components/Public/Nav/StartFM.vue";

// map padaliniai to options_padaliniai

const padaliniai = usePage().props.value.padaliniai;
const mainNavigation = ref(usePage().props.value.mainNavigation);
const locale = ref(usePage().props.value.locale);
const activeDrawer = ref(false);
const toggleMenu = () => {
  activeDrawer.value = !activeDrawer.value;
};

const homeParams: RouteParamsWithQueryOverload = reactive({
  lang: locale.value,
});

const activeMenuKey = ref(usePage().props.value.navigationItemId);

const expandedKeys = ref([]);
const selectedKeys = ref([]);

const options_padaliniai = computed(() => {
  return padaliniai.map((padalinys) => ({
    label: $t(split(padalinys.fullname, "atstovybė ")[1]),
    key: padalinys.alias,
  }));
});

const parseNavigation = (array, id: number) => {
  const result: Record<string, any>[] = [];
  array.forEach((item) => {
    if (item[1].parent_id === id) {
      result.push({
        key: item[1].id,
        label: item[1].name,
        children: parseNavigation(array, item[1].id),
        url: item[1].url.replace(/^\/|\/$/g, ""),
      });
      if (result[result.length - 1].children.length === 0) {
        delete result[result.length - 1].children;
        delete result[result.length - 1].icon;
      }
    }
  });
  return result;
};

const navigation = computed(() =>
  parseNavigation(Object.entries(mainNavigation.value), 0)
);

const getPadalinys = (alias = usePage().props.value.alias) => {
  for (const padalinys of padaliniai) {
    if (padalinys.alias == alias) {
      return padalinys.shortname.split(" ").pop();
    }
  }
  return "Padaliniai";
};

const padalinys = ref("");
padalinys.value = getPadalinys();

const handleSelectPadalinys = (key) => {
  let i = key;

  // if padalinys is array, get first element (for mobile)
  if (Array.isArray(i)) {
    i = key[0];
  }

  Inertia.reload({
    data: {
      padalinys: i,
    },
    preserveScroll: false,
    preserveState: false,
    onSuccess: () => {
      padalinys.value = getPadalinys(i);
      activeDrawer.value = false;
    },
  });
};

const resetPadalinys = () => {
  padalinys.value = "Padaliniai";
};

const handleSelectNavigation = (id: number) => {
  // message.info("Navigating to " + key);
  // get url from id from mainNavigation array
  let url = "";
  for (const item of Object.entries(mainNavigation.value)) {
    if (item[1].id == id) {
      // if url has https or http, use it
      if (item[1].url.includes("https://") || item[1].url.includes("http://")) {
        window.open(item[1].url, "_blank");
      } else if (item[1].url === "#") {
        // if url is #, add id to checked keys
        // if id is in expandedKeys, remove it
        if (expandedKeys.value.includes(item[1].id)) {
          expandedKeys.value = expandedKeys.value.filter(
            (key) => key !== item[1].id
          );
        } else {
          expandedKeys.value.push(item[1].id);
        }
      } else {
        url = item[1].url;
        // message.info("Navigating to " + url);
        Inertia.visit(
          route("main.page", { lang: locale.value, permalink: url }),
          {
            preserveScroll: false,
          }
        );
      }
      selectedKeys.value = [];
    }
  }
};

const localeSelect = (lang: string) => {
  if (lang !== "lt") {
    locale.value = "en";
  } else {
    locale.value = "lt";
  }
  // update navigation
  mainNavigation.value = usePage().props.value.mainNavigation;
  // update app logo button
  homeParams.lang = locale.value;
  // reset padalinys value if home
  padalinys.value = getPadalinys();
};

// check if 1024px or less with resize
const isMobile = ref(false);
const handleResize = () => {
  if (window.innerWidth <= 1024) {
    isMobile.value = true;
  } else {
    isMobile.value = false;
  }
};

window.addEventListener("resize", handleResize);
handleResize();
</script>
