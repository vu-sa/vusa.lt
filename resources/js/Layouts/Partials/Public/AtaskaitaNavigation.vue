<template>
  <nav
    class="flex flex-row fixed justify-between px-6 lg:px-24 border shadow-sm w-full backdrop-blur-sm bg-white/90 text-gray-700 items-center py-2 z-50 top-0"
  >
    <div class="flex flex-row space-x-4 items-center">
      <NPopover>
        <template #trigger>
          <Link :href="route('main.home', { lang: locale })" preserve-state>
            <img
              class="object-contain min-w-[15vw] lg:min-w-[10vw]"
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
    <div class="hidden lg:flex flex-row space-x-4 items-center">
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
        class="hover:text-vusa-red duration-200"
        :href="
          route('main.ataskaita2022', {
            lang: locale,
            permalink: 'sveikinimai',
          })
        "
        >{{ $t("Sveikinimai") }}</Link
      >

      <Link
        class="hover:text-vusa-red duration-200"
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
        class="hover:text-vusa-red duration-200"
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
          class="flex flex-row items-center hover:text-vusa-red duration-200"
          role="button"
        >
          {{ $t("Strateginės kryptys") }}
          <NIcon class="ml-1" size="16"><ChevronDown20Filled /></NIcon></div
      ></NDropdown>
      <Link
        class="hover:text-vusa-red duration-200"
        :href="
          route('main.ataskaita2022', { lang: locale, permalink: 'sritys' })
        "
        ><NGradientText v-if="permalink === 'sritys'" type="error">{{
          $t("Bendruomenė")
        }}</NGradientText
        ><template v-else>{{ $t("Bendruomenė") }}</template></Link
      >
      <Link
        class="hover:text-vusa-red duration-200"
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

<script setup>
import { ChevronDown20Filled, Navigation24Filled } from "@vicons/fluent";
import { FacebookF, Instagram } from "@vicons/fa";
import { Inertia } from "@inertiajs/inertia";
import { Link, usePage } from "@inertiajs/inertia-vue3";
import {
  NButton,
  NDrawer,
  NDrawerContent,
  NDropdown,
  NGradientText,
  NIcon,
  NPopover,
  NTree,
  // useMessage,
} from "naive-ui";
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
