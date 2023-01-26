<template>
  <NTag
    class="mb-4 shadow-sm"
    :bordered="false"
    size="small"
    :type="stateTypeMap[props.doing.state].tagType"
    >{{ stateTypeMap[props.doing.state].text }}</NTag
  >
</template>

<script setup lang="tsx">
import { NTag } from "naive-ui";

const props = defineProps<{
  doing: App.Entities.Doing;
}>();

const stateTypeMap: Record<string, Record<"tagType" | "text", string>> = {
  draft: {
    tagType: "default",
    text: "Šablonas",
  },
  pending_padalinys_approval: {
    tagType: "info",
    text: "Laukia padalinio pritarimo",
  },
  pending_final_approval: {
    tagType: "info",
    text: "Laukia galutinio pritarimo",
  },
  pending_changes: {
    tagType: "warning",
    text: "Laukia pakeitimų",
  },
  approved: {
    tagType: "success",
    text: "Patvirtintas",
  },
  canceled: {
    tagType: "error",
    text: "Atšauktas",
  },
  pending_completion: {
    tagType: "info",
    text: "Laukia užbaigimo",
  },
  completed: {
    tagType: "success",
    text: "Užbaigtas",
  },
};
</script>
