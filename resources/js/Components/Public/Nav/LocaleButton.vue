<template>
  <NDropdown placement="top-end" :options="options">
    <NButton text>
      <div class="flex gap-1">
        <img
          :src="`https://hatscripts.github.io/circle-flags/flags/${
            locale === 'lt' ? 'lt' : 'gb'
          }.svg`"
          width="16"
        />
        <NIcon :component="ChevronDown20Filled" />
      </div>
    </NButton>
  </NDropdown>
</template>

<script setup lang="tsx">
import { trans as $t } from "laravel-vue-i18n";
import { ChevronDown20Filled, Home16Regular } from "@vicons/fluent";
import { NButton, NDropdown, NIcon } from "naive-ui";
import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";

import SmartLink from "@/Components/Public/SmartLink.vue";
import type { LocaleEnum } from "@/Types/enums";

defineProps<{
  locale: LocaleEnum;
}>();

const options = computed(() => {
  return [
    {
      label() {
        return (
          <SmartLink target="_self" href={usePage().props.otherLangURL}>
            {$t("Pakeisti puslapio kalbą")}
          </SmartLink>
        );
      },
      key: "page",
      disabled: !usePage().props.otherLangURL,
    },
    {
      label() {
        return <SmartLink href="/">{$t("Eiti į pagrindinį")}</SmartLink>;
      },
      key: "home",
      icon: () => <NIcon size={16} component={Home16Regular} />,
    },
  ];
});
</script>
