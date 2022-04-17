<template>
  <nav
    class="flex flex-row fixed justify-between px-12 lg:px-24 border shadow-sm w-full backdrop-blur-sm bg-white/90 text-gray-700 items-center py-2 z-50 top-0"
  >
    <div class="flex flex-row space-x-4 items-center">
      <!-- <Link :href="route('main.home')"> -->
      <a :href="locale === 'lt' ? $page.props.app.url : `${$page.props.app.url}/en`">
        <img class="object-contain min-w-[10vw]" src="/logos/vusa.lin.hor.svg"
      /></a>
      <!-- </Link> -->
      <NDropdown
        :options="options_padaliniai"
        placement="top-start"
        size="small"
        @select="handleSelectPadalinys"
      >
        <NButton
          :disabled="!route().current('*home')"
          size="small"
          style="border-radius: 0.5rem"
        >
          <!-- <NGradientText type="warning"> -->
          {{ __(padalinys) }}
          <!-- </NGradientText> -->
          <NIcon class="ml-1" size="18"><ChevronDown20Filled /></NIcon></NButton
      ></NDropdown>
    </div>
    <div class="flex flex-row space-x-4 items-center">
      <!-- <n-gradient-text type="error"> -->
      <!-- <Link :href="route('page', { permalink: 'apie' })">VU SA</Link> -->
      <!-- </n-gradient-text> -->
      <!-- <div>Studijos ir mokslas</div>
      <div>Saviraiška</div> -->
      <template v-for="item in navigation" :key="item.key">
        <div class="hidden md:block">
          <NDropdown @select="handleSelectNavigation" :options="item.children">
            <NButton text>{{ item.label }}</NButton>
          </NDropdown>
        </div>
      </template>
      <Link
        v-if="locale === 'lt'"
        class="hidden md:block"
        :data="{ padalinys: usePage().props.value.alias }"
        :href="route('contacts')"
        ><NButton text>
          <NGradientText type="error" v-if="route().current('*contacts')"
            >Kontaktai</NGradientText
          >
          <template v-else>Kontaktai</template>
        </NButton>
      </Link>
      <NButton
        text
        target="_blank"
        tag="a"
        href="https://www.facebook.com/VUstudentuatstovybe"
        ><NIcon size="18"><FacebookF /></NIcon
      ></NButton>
      <NButton
        text
        target="_blank"
        tag="a"
        href="https://www.instagram.com/vustudentuatstovybe/"
        ><NIcon size="18"><Instagram /></NIcon
      ></NButton>
      <NBadge dot processing
        ><NIcon color="#000000" size="22"><Search20Filled /></NIcon
      ></NBadge>
      <NDropdown
        placement="top-end"
        :options="options_language_en"
        v-if="locale == 'lt'"
        @select="handleSelectLanguage"
      >
        <NButton text
          ><img
            src="https://hatscripts.github.io/circle-flags/flags/gb.svg"
            width="16" /></NButton
      ></NDropdown>
      <NDropdown
        placement="top-end"
        :options="options_language_lt"
        v-if="locale == 'en'"
        @select="handleSelectLanguage"
      >
        <NButton text
          ><img
            src="https://hatscripts.github.io/circle-flags/flags/lt.svg"
            width="16" /></NButton
      ></NDropdown>
    </div>
  </nav>
</template>

<script setup>
import { FacebookF, Instagram } from "@vicons/fa";
import { Search20Filled, ChevronDown20Filled } from "@vicons/fluent";
import {
  NIcon,
  NDropdown,
  NButton,
  NGradientText,
  NBadge,
  NScrollbar,
  useMessage,
} from "naive-ui";
import { usePage, Link } from "@inertiajs/inertia-vue3";
import { Inertia } from "@inertiajs/inertia";
import { ref, h } from "vue";

// map padaliniai to options_padaliniai

const padaliniai = usePage().props.value.padaliniai;
const mainNavigation = usePage().props.value.mainNavigation;
const locale = ref(usePage().props.value.locale);
const locales = ["lt", "en"];

const message = useMessage();

const options_padaliniai = padaliniai.map((padalinys) => ({
  label: _.split(padalinys.fullname, "atstovybė ")[1],
  key: padalinys.alias,
}));

const options_language_en = [
  {
    label: "Change page language",
    key: "page",
    disabled: true,
  },
  {
    label: "Go to main page",
    key: "home",
  },
];

const options_language_lt = [
  {
    label: "Pakeisti puslapio kalbą",
    key: "page",
    disabled: true,
  },
  {
    label: "Eiti į pagrindinį",
    key: "home",
  },
];

const parseNavigation = (array, id) => {
  // console.log(array);
  const result = [];
  array.forEach((item) => {
    if (item[1].parent_id === id) {
      result.push({
        key: item[1].url.replace(/^\/|\/$/g, ""),
        label: item[1].name,
        children: parseNavigation(array, item[1].id),
        // trim url of slashes
        url: item[1].url.replace(/^\/|\/$/g, ""),
        // suffix: () =>
        //   h(NButton, { quartenary: true, circle: true },
        //     h('template', { slot: "icon" },
        //       h(NIcon, { size: "tiny" },
        //         h(Edit16Regular)))),
      });
      if (result[result.length - 1].children.length === 0) {
        // console.log(result[result.length - 1].children, 1);
        delete result[result.length - 1].children;
      }
      // console.log(result[result.length - 1].children, 2);
    }
  });
  return result;
};

const navigation = parseNavigation(Object.entries(mainNavigation), 0);

const getPadalinys = (alias = usePage().props.value.alias) => {
  for (let padalinys of padaliniai) {
    if (padalinys.alias == alias) {
      return padalinys.shortname.split(" ").pop();
    }
  }
  return "Padaliniai";
};

// const getMainHost = () => {
//   if (window.location.host.includes("localhost")) {
//     return "localhost";
//   }
//   let host = window.location.host.split(".");
//   let last = host.pop();
//   let second_to_last = host.pop();
//   return `${second_to_last}.${last}`;
// };

const padalinys = ref("");
padalinys.value = getPadalinys();

// const mainHost = "http://" + getMainHost();

const handleSelectPadalinys = (key) => {
  Inertia.reload({
    data: {
      padalinys: key,
    },
    preserveScroll: true,
    only: ["alias", "news"],
    onSuccess: () => {
      padalinys.value = getPadalinys(key);
    },
  });
};

const handleSelectNavigation = (url) => {
  // message.info("Navigating to " + key);
  Inertia.visit(route("main.page", { lang: locale.value, permalink: url }));
};

const handleSelectLanguage = (key) => {
  if (key === "home") {
    Inertia.visit(
      route("main.home", {
        lang: locales.filter((l) => {
          return l !== locale.value;
        }),
      })
    );
    // Inertia.visit(route("main.page", { lang: "lt" }));
    message.info("Navigating to " + key);
  } else if (key === "page") {
    message.info("Navigating to " + key);
  }
};
</script>
