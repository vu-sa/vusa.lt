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

import { toMixcloudEmbedUrl } from './embedUrl';

import type { SpotifyEmbed } from '@/Types/contentParts';

const props = defineProps<{
  element: SpotifyEmbed;
}>();

const isDark = useDark();

const embedUrl = computed(() => toMixcloudEmbedUrl(props.element.json_content.url, isDark.value));
</script>
