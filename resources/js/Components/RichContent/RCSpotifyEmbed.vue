<template>
  <RCSpotifyPromoDisplay v-if="isPromo" :element :anchor-id="anchorId" />

  <!-- Single root — a multi-root/fragment component can't auto-inherit the width/spacing
       class RichContentParser passes via :class (no single target to fall through to). -->
  <div v-else>
    <RCMixcloudEmbed v-if="isMixcloud" :element />
    <!-- Hairline frame, square corners: an embed is still a block on this surface, so it is
         ruled off like every other one rather than floating as a rounded card. -->
    <div v-else class="my-8 w-full border border-border">
      <iframe class="block w-full h-[352px]" :src="embedUrl" frameborder="0" allowtransparency="true"
        allow="encrypted-media" title="Spotify Embed" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useDark } from '@vueuse/core';

import RCMixcloudEmbed from './RCMixcloudEmbed.vue';
import RCSpotifyPromoDisplay from './RCSpotifyPromoDisplay.vue';
import { isMixcloudUrl, toSpotifyEmbedUrl } from './embedUrl';

import type { SpotifyEmbed } from '@/Types/contentParts';

const props = defineProps<{
  element: SpotifyEmbed;
  anchorId?: number | null;
}>();

const isPromo = computed(() => props.element.options?.variant === 'promo');

const isMixcloud = computed(() => isMixcloudUrl(props.element.json_content.url));

const isDark = useDark();

const embedUrl = computed(() => toSpotifyEmbedUrl(props.element.json_content.url, isDark.value));
</script>
