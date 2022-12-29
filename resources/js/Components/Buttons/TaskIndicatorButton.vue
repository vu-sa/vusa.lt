<template>
  <NBadge type="info" :offset="[0, -4]" :value="2">
    <NButton text circle
      ><template #icon
        ><NIcon :size="24" :component="TasksApp24Regular"></NIcon></template
    ></NButton>
  </NBadge>
</template>

<script setup lang="tsx">
import { NBadge, NButton, NIcon } from "naive-ui";
import { TasksApp24Regular } from "@vicons/fluent";
</script>
