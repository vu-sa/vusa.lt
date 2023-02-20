<template>
  <NButton secondary size="small" @click="handleClick">
    <NIcon :component="PreviewLink20Filled"></NIcon>
  </NButton>
</template>

<script setup lang="ts">
import { NButton, NIcon } from "naive-ui";
import { PreviewLink20Filled } from "@vicons/fluent";

const props = defineProps<{
  publicRoute: string;
  routeProps: Record<string, string>;
}>();

const handleClick = () => {
  window.open(
    route(props.publicRoute, props.routeProps),
    "_blank",
    "noopener,noreferrer"
  );
};
</script>
