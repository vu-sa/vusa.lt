<template>
  <NButton
    tag="a"
    :href="route(publicRoute, routePropsModified)"
    rel="noopener noreferrer"
    secondary
    size="small"
  >
    <NIcon :component="PreviewLink20Filled" />
  </NButton>
</template>

<script setup lang="ts">
import { NButton, NIcon } from "naive-ui";
import { PreviewLink20Filled } from "@vicons/fluent";
import { computed } from "vue";

const props = defineProps<{
  publicRoute: string;
  routeProps: Record<string, string>;
}>();

const routePropsModified = computed(() => {
  let routeProps = props.routeProps;

  if (routeProps["subdomain"] === "vusa") {
    routeProps["subdomain"] = "www";
  }

  return routeProps;
});
</script>
