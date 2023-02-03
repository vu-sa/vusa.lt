<template>
  <NButton size="small"
    ><template #icon
      ><NIcon :component="PeopleTeamAdd24Filled"></NIcon></template
    >Pranešti?</NButton
  >
</template>

<script setup lang="ts">
import { NButton, NIcon } from "naive-ui";
import { PeopleTeamAdd24Filled } from "@vicons/fluent";
</script>
