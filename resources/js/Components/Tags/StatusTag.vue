<template>
  <NTag :type="statusType(status)" round size="small" :bordered="false">
    {{ status }}
  </NTag>
</template>

<script setup lang="ts">
import { NTag } from "naive-ui";

defineProps<{
  status: string;
}>();

const statusType = (status) => {
  switch (status) {
    case "Sukurtas":
      return "info";
    case "Atnaujintas":
      return "warning";
    case "Pabaigtas":
      return "success";
    case "Atmestas":
      return "error";
    default:
      return "error";
  }
};
</script>
