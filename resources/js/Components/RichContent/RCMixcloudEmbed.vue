<template>
  <div class="w-full my-8">
    <iframe
      :src="embedUrl"
      frameborder="0"
      allow="encrypted-media; fullscreen; autoplay; idle-detection; speaker-selection; web-share;"
      title="Mixcloud Embed"
      class="block w-full rounded-xl h-[120px] min-h-[120px]"
    />
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

  const lightParam = isDark.value ? '0' : '1';

  // If already a widget URL, update the light param to match dark mode
  if (url.includes('player-widget.mixcloud.com')) {
    try {
      const parsedUrl = new URL(url);
      parsedUrl.searchParams.set('light', lightParam);
      return parsedUrl.toString();
    }
    catch {
      return url;
    }
  }

  // Convert regular Mixcloud URL to widget iframe URL
  try {
    const parsedUrl = new URL(url);
    const path = parsedUrl.pathname;
    return `https://player-widget.mixcloud.com/widget/iframe/?hide_cover=1&light=${lightParam}&feed=${encodeURIComponent(path)}`;
  }
  catch {
    return url;
  }
});
</script>
