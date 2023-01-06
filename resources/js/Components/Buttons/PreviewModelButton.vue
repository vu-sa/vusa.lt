<template>
  <NButton secondary size="small" @click="handleClick">
    <NIcon :component="PreviewLink20Filled"></NIcon>
  </NButton>
</template>

<script setup lang="ts">
import { NButton, NIcon } from "naive-ui";
import { PreviewLink20Filled } from "@vicons/fluent";


const props = defineProps<{
  mainRoute: string;
  mainProps: object;
  padalinysRoute: string;
  padalinysProps: object;
  padalinysShortname?: string | null;
}>();

const handleClick = () => {
  if (props.padalinysShortname == "VU SA") {
    window.open(route(props.mainRoute, props.mainProps), "_blank");
  } else {
    window.open(route(props.padalinysRoute, props.padalinysProps), "_blank");
  }
};
</script>
