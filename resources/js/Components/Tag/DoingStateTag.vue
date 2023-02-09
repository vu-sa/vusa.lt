<template>
  <NTag
    :bordered="false"
    size="small"
    :type="stateTypeMap[props.doing.state].tagType"
    >{{ stateTypeMap[props.doing.state].text }}</NTag
  >
</template>

<script setup lang="tsx">
import { trans as $t } from "laravel-vue-i18n";
import { NTag } from "naive-ui";

const props = defineProps<{
  doing: App.Entities.Doing;
}>();

const stateTypeMap: Record<string, Record<"tagType" | "text", string>> = {
  draft: {
    tagType: "default",
    text: $t("Šablonas"),
  },
  pending_padalinys_approval: {
    tagType: "info",
    text: $t("Laukia padalinio pritarimo"),
  },
  pending_final_approval: {
    tagType: "info",
    text: $t("Laukia galutinio pritarimo"),
  },
  pending_changes: {
    tagType: "warning",
    text: $t("Laukia pakeitimų"),
  },
  approved: {
    tagType: "success",
    text: $t("Patvirtintas"),
  },
  canceled: {
    tagType: "error",
    text: $t("Atšauktas"),
  },
  pending_completion: {
    tagType: "info",
    text: $t("Laukia užbaigimo"),
  },
  completed: {
    tagType: "success",
    text: $t("Užbaigtas"),
  },
};
</script>
