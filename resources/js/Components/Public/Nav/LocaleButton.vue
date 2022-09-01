<template>
  <!-- <NDropdown
    v-if="locale == 'lt'"
    placement="top-end"
    :options="options"
    @select="handleSelectLanguage"
  >
    <NButton text
      ><img
        src="https://hatscripts.github.io/circle-flags/flags/gb.svg"
        width="16"
    /></NButton>
  </NDropdown>
  <NDropdown
    v-if="locale == 'en'"
    placement="top-end"
    :options="lt_options"
    @select="handleSelectLanguage"
  >
    <NButton text
      ><img
        src="https://hatscripts.github.io/circle-flags/flags/lt.svg"
        width="16"
    /></NButton>
  </NDropdown> -->
  <NBadge dot processing :show="!!otherLanguagePage">
    <NDropdown :options="options" @select="handleSelectLanguage">
      <NButton text><img :src="icon" width="16" /></NButton>
    </NDropdown>
  </NBadge>
</template>

<script setup lang="ts">
import { Inertia } from "@inertiajs/inertia";
import { NBadge, NButton, NDropdown } from "naive-ui";
import { computed, onMounted } from "vue";
import { loadLanguageAsync } from "laravel-vue-i18n";
import { usePage } from "@inertiajs/inertia-vue3";
import route from "ziggy-js";

const props = defineProps<{
  locale: string;
}>();

const emit = defineEmits<{
  (e: "changeLocale", lang: string): void;
}>();

const locales = ["lt", "en"];

const otherLanguagePage = computed(() => {
  if (usePage().props.value.otherLangPage) {
    return {
      lang: usePage().props.value.otherLangPage.lang,
      newsString:
        usePage().props.value.otherLangPage.lang === "lt" ? "naujiena" : "news",
      padalinys: usePage().props.value.alias,
      permalink: usePage().props.value.otherLangPage.permalink,
    };
  }

  return false;
});

const en_options = computed(() => [
  {
    label: "Change page language",
    key: "page",
    disabled: !otherLanguagePage.value,
  },
  {
    label: "Go to main page",
    key: "home",
  },
]);

const lt_options = computed(() => [
  {
    label: "Pakeisti puslapio kalbą",
    key: "page",
    disabled: !otherLanguagePage.value,
  },
  {
    label: "Eiti į pagrindinį",
    key: "home",
  },
]);

const options = computed(() => {
  if (props.locale !== "lt") {
    return lt_options.value;
  } else {
    return en_options.value;
  }
});

const icon = computed(() => {
  if (props.locale !== "lt") {
    return "https://hatscripts.github.io/circle-flags/flags/lt.svg";
  } else {
    return "https://hatscripts.github.io/circle-flags/flags/gb.svg";
  }
});

const handleSelectLanguage = (key) => {
  const newLang = locales.filter((l) => {
    return l !== props.locale;
  });

  if (key === "home") {
    Inertia.visit(
      route("main.home", {
        lang: newLang,
      }),
      {
        onSuccess: () => {
          emit("changeLocale", newLang[0]);
          loadLanguageAsync(newLang[0]);
        },
      }
    );
    // Inertia.visit(route("main.page", { lang: "lt" }));
    // message.info("Navigating to " + key);
  } else if (key === "page") {
    Inertia.visit(
      route("main.page", {
        lang: newLang,
        padalinys: usePage().props.value.alias,
        permalink: usePage().props.value.otherLangPage.permalink,
      }),
      {
        onSuccess: () => {
          emit("changeLocale", newLang[0]);
          loadLanguageAsync(newLang[0]);
        },
      }
    );
  }
};
</script>
