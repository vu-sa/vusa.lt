<template>
  <nav
    class="flex flex-row fixed justify-between px-6 lg:px-24 border shadow-sm w-full backdrop-blur-sm bg-white/90 text-gray-700 items-center py-2 z-50 top-0"
  >
    <div class="flex flex-row space-x-4 items-center">
      <Link :href="route('main.home', { lang: locale })" preserve-state>
        <img
          class="object-contain min-w-[15vw] lg:min-w-[10vw]"
          src="/logos/vusa.lin.hor.svg"
        />
      </Link>
      <Link
        class="text-gray-500 duration-200 hover:text-gray-900 hidden lg:block"
        :href="route('main.home')"
        >{{ __("Grįžti į vusa.lt") }}</Link
      >
      <div>
        <NButton size="small" style="border-radius: 0.5rem" @click="goToAtaskaitaHome">
          <!-- <NGradientText type="warning"> -->
          <strong
            ><NGradientText v-if="permalink == 'pradzia'" type="error">{{
              __("Ataskaita 2022")
            }}</NGradientText
            ><template v-else>{{ __("Ataskaita 2022") }}</template></strong
          >
          <!-- </NGradientText> -->
        </NButton>
      </div>
    </div>
    <!-- Hamburger -->
    <div class="block lg:hidden">
      <NButton style="border-radius: 0.5rem" @click="toggleMenu">
        <NIcon><Navigation24Filled /></NIcon>
      </NButton>
    </div>
    <div class="hidden lg:flex flex-row space-x-4 items-center">
      <!-- <n-gradient-text type="error"> -->
      <!-- <Link :href="route('page', { permalink: 'apie' })">VU SA</Link> -->
      <!-- </n-gradient-text> -->
      <!-- <div>Studijos ir mokslas</div>
      <div>Saviraiška</div> -->

      <Link
        v-if="permalink === 'sveikinimai'"
        :href="route('main.ataskaita2022', { lang: locale, permalink: 'sveikinimai' })"
        ><NGradientText type="error">{{ __("Sveikinimai") }}</NGradientText></Link
      >

      <Link
        class="hover:text-red-600 duration-200"
        v-else
        :href="route('main.ataskaita2022', { lang: locale, permalink: 'sveikinimai' })"
        >Sveikinimai</Link
      >

      <Link
        class="hover:text-red-600 duration-200"
        :href="route('main.ataskaita2022', { lang: locale, permalink: 'vu-sa' })"
      >
        <NGradientText type="error" v-if="permalink === 'vu-sa'">{{
          __("VU SA")
        }}</NGradientText
        ><template v-else>{{ __("VU SA") }}</template></Link
      >
      <Link
        class="hover:text-red-600 duration-200"
        :href="route('main.ataskaita2022', { lang: locale, permalink: 'mvp' })"
        ><NGradientText type="error" v-if="permalink === 'mvp'">{{
          __("Metų veiklos planas")
        }}</NGradientText
        ><template v-else>{{ __("Metų veiklos planas") }}</template></Link
      >
      <NDropdown :options="navigation" @select="handleSelectKryptis"
        ><div
          class="flex flex-row items-center hover:text-red-600 duration-200"
          role="button"
        >
          VU SA strateginės kryptys
          <NIcon class="ml-1" size="16"><ChevronDown20Filled /></NIcon></div
      ></NDropdown>
      <Link
        class="hover:text-red-600 duration-200"
        :href="route('main.ataskaita2022', { lang: locale, permalink: 'sritys' })"
        ><NGradientText type="error" v-if="permalink === 'sritys'">{{
          __("Bendruomenė")
        }}</NGradientText
        ><template v-else>{{ __("Bendruomenė") }}</template></Link
      >
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
      <NDrawerContent closable :title="__('Ataskaita 2022')">
        <NTree
          block-line
          :data="navigationTreeMobile"
          @update:selected-keys="handleSelectKryptis"
        />

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
</template>

<script setup>
import { FacebookF, Instagram } from "@vicons/fa";
import { ChevronDown20Filled, Navigation24Filled } from "@vicons/fluent";
import {
  NIcon,
  NDropdown,
  NButton,
  NDrawer,
  NDrawerContent,
  NTree,
  NGradientText,
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
const activeDrawer = ref(false);
const toggleMenu = () => {
  activeDrawer.value = !activeDrawer.value;
};

// const message = useMessage();

// get permalink from url, last part after /
const getPermalink = () => {
  const url = usePage().url;
  const urlParts = url.value.split("/");
  const permalink = urlParts[urlParts.length - 1];
  // also trim hashtags
  return permalink.split("#")[0];
};

const permalink = getPermalink();

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
  },
  {
    label: "Go to main report page",
    key: "home",
  },
];

const options_language_lt = [
  {
    label: "Pakeisti puslapio kalbą",
    key: "page",
  },
  {
    label: "Eiti į ataskaitos pagrindinį",
    key: "home",
  },
];

const navigation = [
  {
    label: "Kokybiškos studijos ir joms pritaikyta aplinka",
    key: "studijos",
  },
  { label: "Stipri organizacija", key: "organizacija" },
  { label: "Darni universitetinė bendruomenė", key: "bendruomene" },
];

const navigationTreeMobile = [
  { label: "Sveikinimai", key: "sveikinimai" },
  {
    label: "VU SA",
    key: "vu-sa",
  },
  {
    label: "MVP",
    key: "mvp",
  },
  {
    label: "Strateginės kryptys",
    key: "strategines",
    children: [
      {
        label: "Kokybiškos studijos ir joms pritaikyta aplinka",
        key: "studijos",
      },
      { label: "Stipri organizacija", key: "organizacija" },
      { label: "Darni universitetinė bendruomenė", key: "bendruomene" },
    ],
  },
  {
    label: "Sritys",
    key: "sritys",
  },
];

// const handleSelectNavigation = (id) => {
//   // message.info("Navigating to " + key);
//   // get url from id from mainNavigation array
//   let url = "";
//   for (let item of Object.entries(mainNavigation)) {
//     if (item[1].id == id) {
//       url = item[1].url;
//     }
//   }
//   Inertia.visit(route("main.page", { lang: locale.value, permalink: url }));
// };

const handleSelectKryptis = (url) => {
  Inertia.visit(
    route("main.ataskaita2022", {
      lang: locale.value,
      permalink: url,
    })
  );
};

const goToAtaskaitaHome = () => {
  Inertia.visit(
    route("main.ataskaita2022", {
      lang: locale.value,
      permalink: "pradzia",
    })
  );
};

const handleSelectLanguage = (key) => {
  let otherLocale = locales.filter((l) => {
    return l !== locale.value;
  });

  if (key === "home") {
    Inertia.visit(
      route("main.ataskaita2022", {
        lang: otherLocale[0],
        permalink: "pradzia",
      })
    );
    // Inertia.visit(route("main.page", { lang: "lt" }));
    // message.info("Navigating to " + key);
  } else if (key === "page") {
    Inertia.visit(
      route("main.ataskaita2022", {
        lang: otherLocale[0],
        permalink: permalink,
      })
    );
  }
};
</script>
