<template>
  <nav
    class="flex flex-row fixed justify-between px-6 lg:px-24 border shadow-sm w-full backdrop-blur-sm bg-white/90 text-gray-700 items-center py-2 z-50 top-0"
  >
    <div class="flex flex-row space-x-4 items-center">
      <Link :href="route('main.home', { lang: locale })" preserve-state>
        <!-- <a :href="locale === 'lt' ? $page.props.app.url : `${$page.props.app.url}/en`"> -->
        <img
          class="object-contain min-w-[15vw] lg:min-w-[10vw]"
          src="/logos/vusa.lin.hor.svg"
        />
        <!-- </a> -->
      </Link>
      <div>
        <NDropdown
          :options="options_padaliniai"
          placement="top-start"
          size="small"
          @select="handleSelectPadalinys"
          style="overflow: auto; max-height: 600px"
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
    </div>
    <!-- Hamburger -->
    <div class="block md:hidden">
      <NButton style="border-radius: 0.5rem" @click="toggleMenu">
        <NIcon><Navigation24Filled /></NIcon>
      </NButton>
    </div>
    <div class="hidden md:flex flex-row space-x-4 items-center">
      <!-- <n-gradient-text type="error"> -->
      <!-- <Link :href="route('page', { permalink: 'apie' })">VU SA</Link> -->
      <!-- </n-gradient-text> -->
      <!-- <div>Studijos ir mokslas</div>
      <div>Saviraiška</div> -->
      <template v-for="item in navigation" :key="item.key">
        <div>
          <NDropdown
            @select="handleSelectNavigation"
            :options="item.children"
            :render-label="renderNavigacijaLabel"
          >
            <NButton text>{{ item.label }}</NButton>
          </NDropdown>
        </div>
      </template>
      <!-- <Link
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
      </Link> -->
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
      <!-- <NBadge dot processing> -->
      <NButton text @click="changeShowSearch"
        ><NIcon color="#000000" size="22"><Search20Filled /></NIcon
      ></NButton>
      <!-- </NBadge> -->
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
    <NDrawer v-model:show="activeDrawer" :width="325" placement="left" :trap-focus="true">
      <NDrawerContent
        closable
        :title="padalinys == 'Padaliniai' ? 'VU SA' : __(padalinys)"
      >
        <NCollapse
          ><NCollapseItem title="Padaliniai"
            ><NTree
              block-line
              :data="options_padaliniai"
              @update:selected-keys="handleSelectPadalinys"
            >
            </NTree></NCollapseItem
        ></NCollapse>

        <NDivider></NDivider>
        <NTree
          block-line
          :data="navigation"
          :expanded-keys="expandedKeys"
          :selected-keys="selectedKeys"
          @update:selected-keys="handleSelectNavigation"
        />
        <!-- <Link
          v-if="locale === 'lt'"
          class="ml-7 mt-1"
          :data="{ padalinys: usePage().props.value.alias }"
          :href="route('contacts')"
          ><NButton text>
            <NGradientText type="error" v-if="route().current('*contacts')"
              >Kontaktai</NGradientText
            >
            <template v-else>Kontaktai</template>
          </NButton>
        </Link> -->
        <div class="flex flex-row space-x-4 items-center mt-4">
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
          <!-- <NBadge dot processing> -->
          <NButton text @click="changeShowSearch"
            ><NIcon color="#000000" size="22"><Search20Filled /></NIcon
          ></NButton>
          <!-- </NBadge> -->
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
      </NDrawerContent>
    </NDrawer>
  </nav>
  <NModal v-model:show="showSearch">
    <div
      class="w-3/4 md:w-1/2 md:h-1/2 overflow-auto p-4 bg-white/95 rounded-md border-2 border-gray-100 shadow-lg md:fixed md:inset-x-0 md:top-40"
    >
      <!-- <h3 class="mb-2">Paieška</h3> -->
      <NInput
        @input="handleSearchInput"
        :loading="searchInputLoading"
        round
        type="text"
        size="large"
        :placeholder="__('Ieškoti...')"
        class="mb-4"
      />
      <div v-if="$page.props.search.pages.length !== 0">
        <h3>Puslapiai</h3>
        <Link
          v-for="page in $page.props.search.pages"
          :href="route('page', { lang: page.lang, permalink: page.permalink })"
        >
          <div class="bg-white/95 py-2 px-4 border border-gray-200 rounded-lg mb-2">
            <p>{{ page.title }}</p>
          </div>
        </Link>
      </div>
      <div v-if="$page.props.search.news.length !== 0">
        <h3 v-if="$page.props.search.news">Naujienos</h3>
        <Link
          v-for="news in $page.props.search.news"
          :href="
            route('news', {
              lang: news.lang,
              newsString: 'naujiena',
              permalink: news.permalink,
            })
          "
        >
          <div class="bg-white/95 p-4 border border-gray-200 rounded-lg mb-2">
            <p>{{ news.title }}</p>
            <p class="text-sm text-gray-500">{{ news.publish_time }}</p>
          </div>
        </Link>
      </div>
      <div v-if="$page.props.search.calendar.length !== 0">
        <h3 v-if="$page.props.search.calendar">Kalendoriaus įrašai</h3>
        <div
          v-for="calendar in $page.props.search.calendar"
          class="bg-white/95 p-4 border border-gray-200 rounded-lg mb-2"
        >
          <p>{{ calendar.title }}</p>
          <p class="text-sm text-gray-500">{{ calendar.date }}</p>
        </div>
      </div>
    </div>
  </NModal>
</template>

<script setup>
import { FacebookF, Instagram } from "@vicons/fa";
import { Search20Filled, ChevronDown20Filled, Navigation24Filled } from "@vicons/fluent";
import {
  NIcon,
  NDropdown,
  NButton,
  NGradientText,
  // NBadge,
  NModal,
  NInput,
  NDrawer,
  NDrawerContent,
  NTree,
  NDivider,
  NCollapse,
  NCollapseItem,
  // useMessage,
} from "naive-ui";
import { usePage, Link } from "@inertiajs/inertia-vue3";
import { Inertia } from "@inertiajs/inertia";
import { ref } from "vue";

// map padaliniai to options_padaliniai

const padaliniai = usePage().props.value.padaliniai;
const mainNavigation = usePage().props.value.mainNavigation;
const locale = ref(usePage().props.value.locale);
const locales = ["lt", "en"];
const showSearch = ref(false);
const searchInputLoading = ref(false);
const activeDrawer = ref(false);
const toggleMenu = () => {
  activeDrawer.value = !activeDrawer.value;
};

const expandedKeys = ref([]);
const selectedKeys = ref([]);

const searchResults = ref(false);

const changeShowSearch = () => {
  showSearch.value = !showSearch.value;
};

// const message = useMessage();

// after half a second input delay, use Inertiapost request to fetch search results
const handleSearchInput = _.debounce((input) => {
  if (input.length > 2) {
    searchInputLoading.value = true;
    Inertia.post(
      route("search"),
      {
        data: { input: input },
      },
      {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
          searchInputLoading.value = false;
        },
      }
    );
  }
}, 500);

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
        key: item[1].id,
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
  let i = key;
  // if padalinys is array, get first element (for mobile)
  if (Array.isArray(i)) {
    i = key[0];
  }

  Inertia.reload({
    data: {
      padalinys: i,
    },
    preserveScroll: true,
    only: ["alias", "news", "banners", "main_page"],
    onSuccess: () => {
      padalinys.value = getPadalinys(i);
      activeDrawer.value = false;
    },
  });
};

const handleSelectNavigation = (id) => {
  // message.info("Navigating to " + key);
  // get url from id from mainNavigation array
  let url = "";
  for (let item of Object.entries(mainNavigation)) {
    if (item[1].id == id) {
      // if url has https or http, use it
      if (item[1].url.includes("https://") || item[1].url.includes("http://")) {
        window.open(item[1].url, "_blank");
      } else if (item[1].url === "#") {
        // if url is #, add id to checked keys
        // if id is in expandedKeys, remove it
        if (expandedKeys.value.includes(item[1].id)) {
          expandedKeys.value = expandedKeys.value.filter((key) => key !== item[1].id);
        } else {
          expandedKeys.value.push(item[1].id);
        }
      } else {
        url = item[1].url;
        // message.info("Navigating to " + url);
        Inertia.visit(route("main.page", { lang: locale.value, permalink: url }));
      }
      selectedKeys.value = [];
    }
  }
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
    // message.info("Navigating to " + key);
  } else if (key === "page") {
    // message.info("Navigating to " + key);
  }
};
</script>
