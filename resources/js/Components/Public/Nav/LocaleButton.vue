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
  <NDropdown :options="options" @select="handleSelectLanguage">
    <NButton text><img :src="icon" width="16" /></NButton>
  </NDropdown>
</template>

<script setup lang="ts">
import { Inertia } from "@inertiajs/inertia";
import { NButton, NDropdown } from "naive-ui";
import { computed } from "vue";
import { loadLanguageAsync } from "laravel-vue-i18n";
import route from "ziggy-js";

const props = defineProps<{
  locale: string;
}>();

const emit = defineEmits<{
  (e: "changeLocale", lang: string): void;
}>();

const locales = ["lt", "en"];

const en_options = [
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

const lt_options = [
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

const options = computed(() => {
  if (props.locale == "lt") {
    return lt_options;
  } else {
    return en_options;
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
  const lang = locales.filter((l) => {
    return l !== props.locale;
  });

  if (key === "home") {
    Inertia.visit(
      route("main.home", {
        lang: lang,
      }),
      {
        onSuccess: () => {
          console.log("success");
          emit("changeLocale", lang[0]);
          loadLanguageAsync(lang[0]);
        },
      }
    );
    // Inertia.visit(route("main.page", { lang: "lt" }));
    // message.info("Navigating to " + key);
  } else if (key === "page") {
    // message.info("Navigating to " + key);
  }
};
</script>
