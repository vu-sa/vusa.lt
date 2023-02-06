<template>
  <NPopover
    :style="{ maxWidth: '600px' }"
    trigger="hover"
    :show-delay="500"
    :hide-delay="100"
    ><template #trigger
      ><NButton text type="tertiary" size="tiny"
        ><template #icon><NIcon :component="Info24Regular"></NIcon></template
      ></NButton> </template
    ><slot></slot
  ></NPopover>
</template>

<script setup lang="tsx">
import { Info24Regular } from "@vicons/fluent";
import { NButton, NIcon, NPopover } from "naive-ui";
</script>
