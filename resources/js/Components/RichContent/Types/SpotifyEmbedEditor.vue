<template>
  <div class="flex flex-col gap-4">
    <Field>
      <FieldLabel>Spotify URL</FieldLabel>
      <Input
        v-model="modelValue.url"
        type="url"
        placeholder="https://open.spotify.com/playlist/..."
      />
      <FieldDescription>{{ $t('rich-content.spotify_url_hint') }}</FieldDescription>
    </Field>

    <!-- Preview if URL is valid -->
    <div v-if="isValidSpotifyUrl" class="rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-700 dark:bg-zinc-800/50">
      <p class="mb-2 text-xs font-medium text-zinc-500 dark:text-zinc-400">
        {{ $t('rich-content.preview') }}
      </p>
      <div class="flex items-center gap-2 text-sm text-emerald-600 dark:text-emerald-400">
        <IFluentCheckmark24Regular class="h-4 w-4" />
        {{ $t('rich-content.valid_url') }}
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

import type { SpotifyEmbed } from '@/Types/contentParts';
import { Field, FieldLabel, FieldDescription } from '@/Components/ui/field';
import { Input } from '@/Components/ui/input';
import IFluentCheckmark24Regular from '~icons/fluent/checkmark24-regular';

const modelValue = defineModel<SpotifyEmbed['json_content']>();

const isValidSpotifyUrl = computed(() => {
  const url = modelValue.value?.url;
  if (!url) return false;
  return /^https?:\/\/open\.spotify\.com\/(playlist|album|track|episode|show)\/[\w]+/.test(url);
});
</script>
