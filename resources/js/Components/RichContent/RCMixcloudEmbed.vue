<template>
  <!-- Hairline frame, square corners — same treatment as the Spotify embed beside it, so an
       audio player reads as a ruled block on the page rather than a floating rounded card. -->
  <div class="my-8 w-full border border-border">
    <iframe
      :src="embedUrl"
      frameborder="0"
      allow="encrypted-media; fullscreen; autoplay; idle-detection; speaker-selection; web-share;"
      title="Mixcloud Embed"
      class="block w-full h-[120px] min-h-[120px]"
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

  try {
    const parsedUrl = new URL(url);

    // If already a widget URL, update the light param to match dark mode
    if (parsedUrl.hostname === 'player-widget.mixcloud.com') {
      parsedUrl.searchParams.set('light', lightParam);
      return parsedUrl.toString();
    }

    // Convert regular Mixcloud URL to widget iframe URL
    const path = parsedUrl.pathname;
    return `https://player-widget.mixcloud.com/widget/iframe/?hide_cover=1&light=${lightParam}&feed=${encodeURIComponent(path)}`;
  }
  catch {
    return url;
  }
});
</script>
