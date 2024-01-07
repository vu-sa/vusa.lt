<template>
  <NDropdown placement="top-end" :options="options">
    <SmartLink :href="$page.props.otherLangURL ?? `/${otherLocale}`">
      <div class="flex gap-1">
        <img
          :src="`https://hatscripts.github.io/circle-flags/flags/${
            locale === 'lt' ? 'lt' : 'gb'
          }.svg`"
          width="16"
        />
        <!-- <NIcon :component="ChevronDown20Filled" /> -->
      </div>
    </SmartLink>
  </NDropdown>
</template>

<script setup lang="tsx">
import { Home16Regular } from "@vicons/fluent";
import { NDropdown, NIcon } from "naive-ui";
import { computed, watch } from "vue";
import { loadLanguageAsync } from "laravel-vue-i18n";
import { usePage } from "@inertiajs/vue3";

import { LocaleEnum } from "@/Types/enums";
import SmartLink from "@/Components/Public/SmartLink.vue";

const props = defineProps<{
  locale: LocaleEnum;
}>();

const otherLocale = computed(() => {
  return props.locale === "lt" ? "en" : "lt";
});

const options = computed(() => {
  return [
    {
      label() {
        return (
          <SmartLink target="_self" href={usePage().props.otherLangURL}>
            {props.locale === LocaleEnum.LT
              ? "Change page language"
              : "Pakeisti puslapio kalbą"}
          </SmartLink>
        );
      },
      key: "page",
      disabled: !usePage().props.otherLangURL,
    },
    {
      label() {
        return (
          <SmartLink href={`/${otherLocale.value}`}>
            {props.locale === LocaleEnum.LT
              ? "Go to main page"
              : "Eiti į pagrindinį"}
          </SmartLink>
        );
      },
      key: "home",
      icon: () => <NIcon size={16} component={Home16Regular} />,
    },
  ];
});

watch(
  () => props.locale,
  () => {
    loadLanguageAsync(props.locale);
  },
);
</script>
