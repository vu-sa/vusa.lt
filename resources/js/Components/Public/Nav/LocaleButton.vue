<template>
  <NDropdown
    placement="top-end"
    :options="options"
    @select="handleSelectLanguage"
  >
    <NButton text>
      <div class="flex gap-1">
        <NBadge dot processing :show="!!otherLanguagePage"
          ><img
            :src="`https://hatscripts.github.io/circle-flags/flags/${
              locale === 'lt' ? 'lt' : 'gb'
            }.svg`"
            width="16"
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

const pageProps = usePage().props;

const emit = defineEmits<{
  (e: "changeLocale", lang: string): void;
}>();

const otherLanguagePage = computed(() => {
  if (pageProps.otherLangPage) {
    return {
      lang: pageProps.otherLangPage.lang,
      newsString: pageProps.otherLangPage.lang === "lt" ? "naujiena" : "news",
      subdomain: pageProps.padalinys?.subdomain ?? "www",
      permalink: pageProps.otherLangPage.permalink,
    };
  }

  return false;
});

const options = computed(() => {
  return [
    {
      label:
        props.locale === LocaleEnum.LT
          ? "Change page language"
          : "Pakeisti puslapio kalbą",
      key: "page",
      disabled: !hasChangeableLocale.value,
    },
    {
      label:
        props.locale === LocaleEnum.LT
          ? "Go to main page"
          : "Eiti į pagrindinį",
      key: "home",
    },
  ];
});

const hasChangeableLocale = computed(() => {
  if (otherLanguagePage.value) {
    return true;
  }

  // check if current page url has /kontaktai or /contacts
  if (
    window.location.pathname.includes("kontaktai") ||
    window.location.pathname.includes("contacts") ||
    window.location.pathname.includes("kuratoriu-registracija") ||
    window.location.pathname.includes("individualios-studijos")
  ) {
    return true;
  }

  return false;
});

const routerMethod = (key: "home" | "page") => {
  if (otherLanguagePage.value) {
    return "visit";
  }

  if (key === "home") {
    return "visit";
  }

  return "reload";
};

const handleSelectLanguage = (key: "home" | "page") => {
  const newLang = Object.values(LocaleEnum).filter((l) => {
    return l !== props.locale;
  })[0];

  if (routerMethod(key) === "reload") {
    // if first 3 chars of url are '/lt' or '/en', replace them with new lang and visit
    let url = window.location.pathname.replace(
      window.location.pathname.substr(0, 3),
      `/${newLang}`,
    );

    router.visit(url, {
      onSuccess: () => {
        emit("changeLocale", newLang);
        loadLanguageAsync(newLang);
      },
    });

    return;
  }

  let subdomain = pageProps.padalinys?.subdomain ?? "www";

  const routes = {
    home: () => route("home", { lang: newLang, subdomain }),
    page: () =>
      route("page", {
        lang: newLang,
        subdomain,
        permalink: pageProps?.otherLangPage?.permalink,
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
