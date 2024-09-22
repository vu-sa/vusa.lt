<template>
  <NButton size="small" text title="Tamsaus režimo perjungimas" @click="isDark = !isDark">
    <template #icon>
      <IFluentWeatherMoon24Filled v-if="isDark" />
      <IFluentWeatherSunny24Filled v-else />
    </template>
  </NButton>
</template>

<script setup lang="ts">
import { useDark } from "@vueuse/core";

const isDark = useDark();
</script>
