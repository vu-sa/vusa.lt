<template>
  <nav
    class="fixed top-0 z-50 flex w-full flex-row items-center justify-between border bg-white/90 px-6 py-2 text-gray-700 shadow-sm backdrop-blur-sm lg:px-24"
  >
    <div class="flex flex-row items-center space-x-4">
      <NPopover>
        <template #trigger>
          <Link :href="route('main.home', { lang: locale })" preserve-state>
            <img
              class="min-w-[15vw] object-contain lg:min-w-[10vw]"
              src="/logos/vusa.lin.hor.svg"
            />
          </Link>
        </template>
        Grįžti į vusa.lt
      </NPopover>
      <!-- <Link
        class="text-gray-500 duration-200 hover:text-gray-900 hidden lg:block"
        :href="route('main.home')"
        >{{ $t("Grįžti į vusa.lt") }}</Link
      > -->
      <div>
        <NButton
          size="small"
          style="border-radius: 0.5rem"
          @click="goToAtaskaitaHome"
        >
          <!-- <NGradientText type="warning"> -->
          <strong
            ><NGradientText v-if="permalink == 'pradzia'" type="error">{{
              $t("Ataskaita 2022")
            }}</NGradientText
            ><template v-else>{{ $t("Ataskaita 2022") }}</template></strong
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
    <div class="hidden flex-row items-center space-x-4 lg:flex">
      <!-- <n-gradient-text type="error"> -->
      <!-- <Link :href="route('page', { permalink: 'apie' })">VU SA</Link> -->
      <!-- </n-gradient-text> -->
      <!-- <div>Studijos ir mokslas</div>
      <div>Saviraiška</div> -->

      <Link
        v-if="permalink === 'sveikinimai'"
        :href="
          route('main.ataskaita2022', {
            lang: locale,
            permalink: 'sveikinimai',
          })
        "
        ><NGradientText type="error">{{
          $t("Sveikinimai")
        }}</NGradientText></Link
      >

      <Link
        v-else
        class="duration-200 hover:text-vusa-red"
        :href="
          route('main.ataskaita2022', {
            lang: locale,
            permalink: 'sveikinimai',
          })
        "
        >{{ $t("Sveikinimai") }}</Link
      >

      <Link
        class="duration-200 hover:text-vusa-red"
        :href="
          route('main.ataskaita2022', { lang: locale, permalink: 'vu-sa' })
        "
      >
        <NGradientText v-if="permalink === 'vu-sa'" type="error">{{
          $t("VU SA")
        }}</NGradientText
        ><template v-else>{{ $t("VU SA") }}</template></Link
      >
      <Link
        class="duration-200 hover:text-vusa-red"
        :href="route('main.ataskaita2022', { lang: locale, permalink: 'mvp' })"
        ><NGradientText v-if="permalink === 'mvp'" type="error">{{
          $t("Metų veiklos planas")
        }}</NGradientText
        ><template v-else>{{ $t("Metų veiklos planas") }}</template></Link
      >
      <NDropdown
        :options="locale === 'lt' ? navigation : navigationEN"
        @select="handleSelectKryptis"
        ><div
          class="flex flex-row items-center duration-200 hover:text-vusa-red"
          role="button"
        >
          {{ $t("Strateginės kryptys") }}
          <NIcon class="ml-1" size="16"><ChevronDown20Filled /></NIcon></div
      ></NDropdown>
      <Link
        class="duration-200 hover:text-vusa-red"
        :href="
          route('main.ataskaita2022', { lang: locale, permalink: 'sritys' })
        "
        ><NGradientText v-if="permalink === 'sritys'" type="error">{{
          $t("Bendruomenė")
        }}</NGradientText
        ><template v-else>{{ $t("Bendruomenė") }}</template></Link
      >
      <Link
        class="duration-200 hover:text-vusa-red"
        :href="
          route('main.ataskaita2022', { lang: locale, permalink: 'padeka' })
        "
        ><NGradientText v-if="permalink === 'padeka'" type="error">{{
          $t("Padėka")
        }}</NGradientText
        ><template v-else>{{ $t("Padėka") }}</template></Link
      >
      <NDropdown
        v-if="locale == 'lt'"
        placement="top-end"
        :options="options_language_en"
        @select="handleSelectLanguage"
      >
        <NButton text
          ><img
            src="https://hatscripts.github.io/circle-flags/flags/gb.svg"
            width="16" /></NButton
      ></NDropdown>
      <NDropdown
        v-if="locale == 'en'"
        placement="top-end"
        :options="options_language_lt"
        @select="handleSelectLanguage"
      >
        <NButton text
          ><img
            src="https://hatscripts.github.io/circle-flags/flags/lt.svg"
            width="16" /></NButton
      ></NDropdown>
    </div>
    <NDrawer
      v-model:show="activeDrawer"
      :width="325"
      placement="left"
      :trap-focus="true"
    >
      <NDrawerContent closable :title="$t('Ataskaita 2022')">
        <NTree
          block-line
          :default-expand-all="true"
          :data="
            locale === 'lt' ? navigationTreeMobile : navigationTreeMobileEN
          "
          @update:selected-keys="handleSelectKryptis"
        />

        <div class="mt-4 flex flex-row items-center space-x-4">
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
            v-if="locale == 'lt'"
            placement="top-end"
            :options="options_language_en"
            @select="handleSelectLanguage"
          >
            <NButton text
              ><img
                src="https://hatscripts.github.io/circle-flags/flags/gb.svg"
                width="16" /></NButton
          ></NDropdown>
          <NDropdown
            v-if="locale == 'en'"
            placement="top-end"
            :options="options_language_lt"
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

<script setup lang="ts">
import { ChevronDown20Filled, Navigation24Filled } from "@vicons/fluent";
import { FacebookF, Instagram } from "@vicons/fa";
import { Link, router, usePage } from "@inertiajs/vue3";
import {
  NButton,
  NDrawer,
  NDrawerContent,
  NDropdown,
  NGradientText,
  NIcon,
  NPopover,
  NTree,
} from "naive-ui";
import { ref } from "vue";

// map padaliniai to options_padaliniai

const locale = ref(usePage().props.locale);
const locales = ["lt", "en"];
const activeDrawer = ref(false);
const toggleMenu = () => {
  activeDrawer.value = !activeDrawer.value;
};

// get permalink from url, last part after /
const getPermalink = () => {
  const url = usePage().url;
  const urlParts = url.value.split("/");
  const permalink = urlParts[urlParts.length - 1];
  // also trim hashtags
  return permalink.split("#")[0];
};

const permalink = getPermalink();

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

const navigationEN = [
  {
    label: "High quality studies and their environment",
    key: "studijos",
  },
  { label: "Strong organization", key: "organizacija" },
  { label: "Sustainable University community", key: "bendruomene" },
];

const navigationTreeMobile = [
  { label: "Sveikinimai", key: "sveikinimai" },
  {
    label: "VU SA",
    key: "vu-sa",
  },
  {
    label: "Metų veiklos planas",
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
    label: "Bendruomenė",
    key: "sritys",
  },
  {
    label: "Padėka",
    key: "padeka",
  },
];

const navigationTreeMobileEN = [
  { label: "Congratulations", key: "sveikinimai" },
  {
    label: "VU SR",
    key: "vu-sa",
  },
  {
    label: "Year plan",
    key: "mvp",
  },
  {
    label: "Strategic directions",
    key: "strategines",
    children: [
      {
        label: "High quality studies and their environment",
        key: "studijos",
      },
      { label: "Strong organization", key: "organizacija" },
      { label: "Sustainable University community", key: "bendruomene" },
    ],
  },
  {
    label: "Community",
    key: "sritys",
  },
  {
    label: "Acknowledgements",
    key: "padeka",
  },
];

const handleSelectKryptis = (url) => {
  router.visit(
    route("main.ataskaita2022", {
      lang: locale.value,
      permalink: url,
    })
  );
};

const goToAtaskaitaHome = () => {
  router.visit(
    route("main.ataskaita2022", {
      lang: locale.value,
      permalink: "pradzia",
    })
  );
};

const handleSelectLanguage = (key) => {
  const otherLocale = locales.filter((l) => {
    return l !== locale.value;
  });

  if (key === "home") {
    router.visit(
      route("main.ataskaita2022", {
        lang: otherLocale[0],
        permalink: "pradzia",
      })
    );
  } else if (key === "page") {
    router.visit(
      route("main.ataskaita2022", {
        lang: otherLocale[0],
        permalink: permalink,
      })
    );
  }
};
</script>
