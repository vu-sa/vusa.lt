<template>
  <NButton size="small" text title="Tamsaus režimo perjungimas" @click="isDark = !isDark">
    <template #icon>
      <NIcon
        :component="isDark ? WeatherMoon24Regular : WeatherSunny24Regular"
      />
    </template>
  </NButton>
</template>

<script setup lang="ts">
import { NButton, NIcon } from "naive-ui";
import { WeatherMoon24Regular, WeatherSunny24Regular } from "@vicons/fluent";
import { useDark } from "@vueuse/core";

const isDark = useDark();
</script>
