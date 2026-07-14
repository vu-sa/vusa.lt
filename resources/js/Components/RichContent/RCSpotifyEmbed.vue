<template>
  <RCMixcloudEmbed v-if="isMixcloud" :element />
  <div v-else class="w-full my-8">
    <iframe class="block w-full h-[352px] rounded-xl" :src="embedUrl" frameborder="0" allowtransparency="true"
      allow="encrypted-media" title="Spotify Embed" />
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useDark } from '@vueuse/core';

import RCMixcloudEmbed from './RCMixcloudEmbed.vue';

import type { SpotifyEmbed } from '@/Types/contentParts';

const props = defineProps<{
  element: SpotifyEmbed;
}>();

const isMixcloud = computed(() => {
  const { url } = props.element.json_content;

  try {
    const parsedUrl = new URL(url, window.location.origin);
    const host = parsedUrl.hostname.toLowerCase();

    return host === 'mixcloud.com'
      || host === 'www.mixcloud.com'
      || host === 'player-widget.mixcloud.com';
  }
  catch {
    return false;
  }
});

const isDark = useDark();

const embedUrl = computed(() => {
  const { url } = props.element.json_content;

  // Only append theme param for Spotify URLs
  const isSpotify = /^https?:\/\/(open\.)?spotify\.com\//.test(url);
  if (!isSpotify) {
    return url;
  }

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
