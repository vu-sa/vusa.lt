<template>
  <NButton tag="a" :href="route(publicRoute, routePropsModified)" rel="noopener noreferrer" secondary size="small"
    target="_blank">
    <IFluentPreviwLink20Regular />
  </NButton>
</template>

<script setup lang="ts">
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
