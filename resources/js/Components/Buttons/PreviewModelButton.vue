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
  // fix for permalinks when main org is vusa
  if(props.routeProps['padalinys'] == "vusa"){
    props.routeProps['padalinys'] = 'www';
  } 

  window.open(
    route(props.publicRoute, props.routeProps),
    "_blank",
    "noopener,noreferrer"
  );
};
</script>
