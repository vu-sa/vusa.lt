<template>
  <div class="mt-4 flex flex-col gap-4">
    <NFormItem label="Spotify URL" :show-feedback="false">
      <NInput v-model:value="modelValue.url" type="text" placeholder="https://open.spotify.com/..." />
    </NFormItem>
  </div>
</template>

<script setup lang="ts">
import type { SpotifyEmbed } from '@/Types/contentParts';

const modelValue = defineModel<SpotifyEmbed['json_content']>();
</script>

