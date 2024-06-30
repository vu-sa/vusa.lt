<template>
  <NButton size="small" text title="Tamsaus režimo perjungimas" @click="isDark = !isDark">
    <template #icon>
      <IFluentWeatherMoon24Regular v-if="isDark" />
      <IFluentWeatherSunny24Regular v-else />
    </template>
  </NButton>
</template>

<script setup lang="ts">
import { useDark } from "@vueuse/core";

const isDark = useDark();
</script>
