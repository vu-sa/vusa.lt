<template>
  <img
    alt="VU SA logo"
    class="max-w-full h-auto dark:invert"
    v-bind="$attrs"
    :src="logoSrc"
    loading="eager"
    width="1200"
    height="428"
  >
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

import { getAppLogoSrc } from '@/Utils/AppLogo';

defineOptions({ inheritAttrs: false });

const page = usePage();

const logoSrc = computed(() => getAppLogoSrc(page.props.tenant?.alias, page.props.app.locale));
</script>
