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
import type { LocaleEnum } from "@/Types/enums";

const props = defineProps<{
  locale: LocaleEnum;
}>();

const emit = defineEmits<{
  (e: "changeLocale", lang: string): void;
}>();

const locales = ["lt", "en"];

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
  if (props.locale !== "lt") {
    return lt_options.value;
  } else {
    return en_options.value;
  }
});

const icon = computed(() => {
  if (props.locale !== "en") {
    return "https://hatscripts.github.io/circle-flags/flags/lt.svg";
  } else {
    return "https://hatscripts.github.io/circle-flags/flags/gb.svg";
  }
});

const handleSelectLanguage = (key) => {
  const newLang = locales.filter((l) => {
    return l !== props.locale;
  })[0];

  if (key === "home") {
    router.visit(
      route("home", {
        lang: newLang,
        padalinys:
          usePage().props.alias === "vusa"
            ? "www"
            : usePage().props.alias ?? "www",
      }),
      {
        onSuccess: () => {
          emit("changeLocale", newLang);
          console.log("changeLocale", newLang);
          loadLanguageAsync(newLang);
        },
      }
    );
  } else if (key === "page") {
    router.visit(
      route("page", {
        lang: newLang,
        padalinys:
          usePage().props.alias === "vusa"
            ? "www"
            : usePage().props.alias ?? "www",
        permalink: usePage().props?.otherLangPage?.permalink,
      }),
      {
        onSuccess: () => {
          emit("changeLocale", newLang);
          // padalinysSelector needs to be updated in this way
          loadLanguageAsync(newLang);
        },
      }
    );
  }
};
</script>
