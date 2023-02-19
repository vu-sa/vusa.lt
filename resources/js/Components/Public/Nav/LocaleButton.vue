<template>
  <NDropdown :options="options" @select="handleSelectLanguage">
    <NButton text>
      <div class="flex gap-1">
        <NBadge dot processing :show="!!otherLanguagePage"
          ><img :src="icon" width="16"
        /></NBadge>
        <NIcon :component="ChevronDown20Filled" />
      </div>
    </NButton>
  </NDropdown>
</template>

<script setup lang="ts">
import { ChevronDown20Filled } from "@vicons/fluent";
import { NBadge, NButton, NDropdown, NIcon } from "naive-ui";
import { computed } from "vue";
import { loadLanguageAsync } from "laravel-vue-i18n";
import { router, usePage } from "@inertiajs/vue3";

import { LocaleEnum } from "@/Types/enums";

const props = defineProps<{
  locale: LocaleEnum;
}>();

const emit = defineEmits<{
  (e: "changeLocale", lang: string): void;
}>();

const otherLanguagePage = computed(() => {
  if (usePage().props.otherLangPage) {
    return {
      lang: usePage().props.otherLangPage.lang,
      newsString:
        usePage().props.otherLangPage.lang === "lt" ? "naujiena" : "news",
      padalinys: usePage().props.alias,
      permalink: usePage().props.otherLangPage.permalink,
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
  return props.locale === "lt" ? lt_options.value : en_options.value;
});

const icon = computed(() => {
  return `https://hatscripts.github.io/circle-flags/flags/${
    props.locale === "lt" ? "lt" : "gb"
  }.svg`;
});

const handleSelectLanguage = (key: "home" | "page") => {
  const newLang = Object.values(LocaleEnum).filter((l) => {
    return l !== props.locale;
  })[0];

  let alias = usePage().props.alias;
  let padalinys = alias === "vusa" ? "www" : alias ?? "www";

  const routes = {
    home: () => route("home", { lang: newLang, padalinys }),
    page: () =>
      route("page", {
        lang: newLang,
        padalinys,
        permalink: usePage().props?.otherLangPage?.permalink,
      }),
  };

  router.visit(routes[key](), {
    onSuccess: () => {
      emit("changeLocale", newLang);
      loadLanguageAsync(newLang);
    },
  });
};
</script>
