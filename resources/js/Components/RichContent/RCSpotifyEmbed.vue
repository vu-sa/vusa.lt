<template>
  <div class="w-full my-8">
    <iframe class="rounded-xl" :src="element.json_content.url" width="100%" height="352" frameborder="0" allowtransparency="true"
      allow="encrypted-media" title="Spotify Embed" />
  </div>
</template>

<script setup lang="ts">
import type { SpotifyEmbed } from '@/Types/contentParts';

defineProps<{
  element: SpotifyEmbed;
}>();
</script>
