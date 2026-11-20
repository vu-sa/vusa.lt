<template>
  <Button
    variant="ghost"
    :size
    :title="$t('Tamsaus režimo perjungimas')"
    @click="toggleDarkMode"
  >
    <IFluentWeatherMoon24Regular v-if="shownDark" class="h-4 w-4" />
    <IFluentWeatherSunny24Regular v-else class="h-4 w-4" />
    <span class="sr-only">
      {{ $t('Tamsaus režimo perjungimas') }} - {{ shownDark ? $t('Dabar tamsus režimas') : $t('Dabar šviesus režimas') }}
    </span>
  </Button>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useDark, useMounted } from '@vueuse/core';
import { trans as $t } from 'laravel-vue-i18n';

import { Button } from '@/Components/ui/button';
import type { ButtonVariants } from '@/Components/ui/button';
import { applyThemeImmediately } from '@/Composables/useThemeColor';

interface Props {
  /** Button size variant - affects padding and height */
  size?: ButtonVariants['size'];
}

defineProps<Props>();

const isDark = useDark();
// The stored theme is unknown to the server; show it only once hydration has matched the SSR icon.
const mounted = useMounted();
const shownDark = computed(() => mounted.value && isDark.value);

function toggleDarkMode(): void {
  const nextIsDark = !isDark.value;

  applyThemeImmediately(nextIsDark);
  isDark.value = nextIsDark;
}
</script>
