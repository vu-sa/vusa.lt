<template>
  <NSwitch
    v-model:value="isDark"
    :loading="disabledSwitch"
    size="small"
    @update:value="toggleDark()"
  >
    <template #checked-icon>
      <NIcon :component="WeatherMoon28Regular" />
    </template>
    <template #unchecked-icon>
      <NIcon :component="WeatherSunny24Regular" />
    </template>
  </NSwitch>
</template>

<script setup lang="ts">
import { NIcon, NSwitch } from "naive-ui";
import { WeatherMoon28Regular, WeatherSunny24Regular } from "@vicons/fluent";
import { ref } from "vue";
import { useDark, useToggle } from "@vueuse/core";

const isDark = useDark();

const disabledSwitch = ref(false);

const toggleDark = () => {
  // disableSwitch for 0.5s to prevent double click
  disabledSwitch.value = true;
  useToggle(isDark);
  setTimeout(() => {
    disabledSwitch.value = false;
  }, 250);
};
</script>
