<template>
  <NTooltip
    :style="{ maxWidth: '600px' }"
    trigger="hover"
    :show-arrow="false"
    :show-delay="500"
    :hide-delay="100"
    ><template #trigger
      ><NButton text type="tertiary" size="tiny"
        ><template #icon
          ><NIcon :color="color" :component="Info24Regular"></NIcon></template
      ></NButton> </template
    ><slot></slot
  ></NTooltip>
</template>

<script setup lang="tsx">
import { Info24Regular } from "@vicons/fluent";
import { NButton, NIcon, NTooltip } from "naive-ui";

defineProps<{
  color?: string;
}>();
</script>
