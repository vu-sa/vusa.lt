<template>
  <Button
    variant="ghost"
    :size
    :title="$t('Tamsaus režimo perjungimas')"
    @click="isDark = !isDark"
  >
    <IFluentWeatherMoon24Filled v-if="isDark" class="h-4 w-4" />
    <IFluentWeatherSunny24Filled v-else class="h-4 w-4" />
    <span class="sr-only">
      {{ $t('Tamsaus režimo perjungimas') }} - {{ isDark ? $t('Dabar tamsus režimas') : $t('Dabar šviesus režimas') }}
    </span>
  </Button>
</template>

<script setup lang="ts">
import { useDark } from '@vueuse/core';
import { trans as $t } from 'laravel-vue-i18n';

import { Button } from '@/Components/ui/button';
import type { ButtonVariants } from '@/Components/ui/button';

interface Props {
  /** Button size variant - affects padding and height */
  size?: ButtonVariants['size'];
}

defineProps<Props>();

const isDark = useDark();
</script>
