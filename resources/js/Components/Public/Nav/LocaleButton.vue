<template>
  <NDropdown placement="top-end" :options :delay="750">
    <SmartLink title="Change language" :href="$page.props.otherLangURL ?? `/${otherLocale}`">
      <div class="flex gap-1">
        <img alt="Change language" :src="`https://hatscripts.github.io/circle-flags/flags/${locale === 'lt' ? 'lt' : 'gb'
          }.svg`" width="16">
        <span class="text-zinc-700 dark:text-zinc-300">
          {{ locale === "lt" ? "LT" : "EN" }}
        </span>
      </div>
    </SmartLink>
  </NDropdown>
</template>

<script setup lang="tsx">
import { NDropdown } from "naive-ui";
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
      icon: () => <img src={`https://hatscripts.github.io/circle-flags/flags/${props.locale === 'lt' ? 'gb' : 'lt'}.svg`} width="16" />
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
      icon: () => <img src={`https://hatscripts.github.io/circle-flags/flags/${props.locale === 'lt' ? 'gb' : 'lt'}.svg`} width="16" />
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
