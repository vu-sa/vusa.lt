<template>
  <div class="w-full my-8">
    <iframe class="block w-full h-[352px] rounded-xl" :src="embedUrl" frameborder="0" allowtransparency="true"
      allow="encrypted-media" title="Spotify Embed" />
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useDark } from '@vueuse/core';

import type { SpotifyEmbed } from '@/Types/contentParts';

const props = defineProps<{
  element: SpotifyEmbed;
}>();

const isDark = useDark();

const embedUrl = computed(() => {
  const { url } = props.element.json_content;
  try {
    const parsedUrl = new URL(url, window.location.origin);
    parsedUrl.searchParams.set('theme', isDark.value ? '0' : '1');
    return parsedUrl.toString();
  }
  catch {
    const themeParam = `theme=${isDark.value ? '0' : '1'}`;
    return url.includes('?') ? `${url}&${themeParam}` : `${url}?${themeParam}`;
  }
});
</script>
