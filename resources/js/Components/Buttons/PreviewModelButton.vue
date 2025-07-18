<template>
  <Button as="a" size="sm" variant="secondary" target="_blank" :href="route(publicRoute, routePropsModified)"
    rel="noopener noreferrer">
    <IFluentPreviewLink20Regular />
    <span class="hidden">{{ $t("Preview") }}</span>
  </Button>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { Button } from "../ui/button";

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
