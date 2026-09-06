<template>
  <img
    ref="imgRef"
    alt="VU SA logo"
    class="max-w-full h-auto dark:invert transition-opacity duration-300"
    :class="isLoaded ? 'opacity-100' : 'opacity-0'"
    v-bind="$attrs"
    :src="logoSrc"
    loading="eager"
    width="1200"
    height="428"
    @load="isLoaded = true"
  >
</template>

<script setup lang="ts">
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

import { getAppLogoSrc } from '@/Utils/AppLogo';

defineOptions({ inheritAttrs: false });

const page = usePage();

const logoSrc = computed(() => getAppLogoSrc(page.props.tenant?.alias, page.props.app.locale));

const imgRef = ref<HTMLImageElement | null>(null);
const isLoaded = ref(false);

// A cached image is already `complete` by the time the `load` listener attaches, so it never fires.
const checkAlreadyLoaded = (): void => {
  if (imgRef.value?.complete && imgRef.value.naturalWidth > 0) {
    isLoaded.value = true;
  }
};

watch(logoSrc, () => {
  isLoaded.value = false;
  nextTick(checkAlreadyLoaded);
}, { flush: 'post' });

onMounted(checkAlreadyLoaded);
</script>
