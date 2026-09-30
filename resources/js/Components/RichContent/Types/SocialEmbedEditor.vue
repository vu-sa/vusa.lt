<template>
  <div v-if="modelValue && options" class="flex flex-col gap-4">
    <div class="space-y-2">
      <label :for="urlId" class="text-sm font-medium text-foreground">
        {{ $t('Facebook arba Instagram įrašo nuoroda') }}
      </label>
      <Input
        :id="urlId"
        v-model="modelValue.url"
        type="url"
        variant="surface"
        placeholder="https://www.facebook.com/... arba https://www.instagram.com/p/..."
      />
      <p class="text-xs text-muted-foreground">
        {{ $t('Įklijuokite Facebook arba Instagram įrašo nuorodą') }}
      </p>
    </div>

    <div v-if="detectedPlatform" class="flex items-center gap-2 text-sm">
      <div class="flex items-center gap-1.5 border border-border bg-secondary px-2.5 py-1 text-foreground">
        <component :is="platformIcon" class="h-4 w-4" />
        <span class="font-medium">{{ platformLabel }}</span>
      </div>
      <span v-if="isValidUrl" class="inline-flex items-center gap-1 text-status-success">
        <CircleCheck class="size-4" /> {{ $t('Nuoroda atpažinta') }}
      </span>
      <span v-else class="inline-flex items-center gap-1 text-status-attention">
        <TriangleAlert class="size-4" /> {{ $t('Patikrinkite nuorodą') }}
      </span>
    </div>

    <div class="flex min-h-11 items-center gap-2">
      <Checkbox
        :id="captionId"
        v-model="options.showCaption"
      />
      <label :for="captionId" class="text-sm text-foreground">
        {{ $t('Rodyti įrašo aprašymą') }}
      </label>
    </div>

    <div v-if="isValidUrl && detectedPlatform" class="mt-4 space-y-2">
      <p class="text-sm font-medium text-foreground">
        {{ $t('Peržiūra') }}
      </p>
      <div class="border border-border bg-secondary/50 p-4">
        <SocialEmbedPreview
          :url="modelValue.url"
          :platform="detectedPlatform"
          :show-caption="options.showCaption"
        />
      </div>
    </div>

    <div class="border-l border-border bg-secondary/50 p-3">
      <p class="text-xs text-muted-foreground">
        <strong>{{ $t('Kaip gauti nuorodą') }}:</strong><br>
        <span class="mt-1 block">
          <strong>Facebook:</strong> {{ $t('Paspauskite ant įrašo datos arba "..." → "Embed" → kopijuokite nuorodą') }}
        </span>
        <span class="mt-1 block">
          <strong>Instagram:</strong> {{ $t('Paspauskite "..." → "Copy link" ant įrašo') }}
        </span>
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, defineAsyncComponent, useId, watch } from 'vue';
import { CircleCheck, TriangleAlert } from 'lucide-vue-next';

import { Checkbox } from '@/Components/ui/checkbox';
import { Input } from '@/Components/ui/input';
import type { SocialEmbed } from '@/Types/contentParts';
import FacebookIcon from '~icons/simple-icons/facebook';
import InstagramIcon from '~icons/simple-icons/instagram';

const SocialEmbedPreview = defineAsyncComponent(() => import('./SocialEmbedPreview.vue'));
const urlId = useId();
const captionId = useId();

const modelValue = defineModel<SocialEmbed['json_content']>();

const options = defineModel<SocialEmbed['options']>('options');

// Anchor supported hosts to reject lookalike domains.
const FACEBOOK_PATTERNS = [
  /^https?:\/\/(?:www\.)?facebook\.com\/[\w.-]+\/posts\/[\w]+/i,
  /^https?:\/\/(?:www\.)?facebook\.com\/photo\/?\?fbid=/i,
  /^https?:\/\/(?:www\.)?facebook\.com\/[\w.-]+\/photos\//i,
  /^https?:\/\/(?:www\.)?facebook\.com\/[\w.-]+\/videos\//i,
  /^https?:\/\/(?:www\.)?facebook\.com\/permalink\.php/i,
  /^https?:\/\/(?:www\.)?facebook\.com\/watch\//i,
  /^https?:\/\/(?:www\.)?facebook\.com\/reel\//i,
  /^https?:\/\/(?:www\.)?facebook\.com\/share\//i,
  /^https?:\/\/fb\.watch\/[\w]+/i,
];

const INSTAGRAM_PATTERNS = [
  /^https?:\/\/(?:www\.)?instagram\.com\/p\/[\w-]+/i,
  /^https?:\/\/(?:www\.)?instagram\.com\/reel\/[\w-]+/i,
  /^https?:\/\/(?:www\.)?instagram\.com\/tv\/[\w-]+/i,
  /^https?:\/\/instagr\.am\/p\/[\w-]+/i,
];

const detectedPlatform = computed(() => {
  if (!modelValue.value?.url) return null;

  const { url } = modelValue.value;

  if (FACEBOOK_PATTERNS.some(pattern => pattern.test(url))) {
    return 'facebook';
  }
  if (/^https?:\/\/(?:www\.)?facebook\.com\/\S+/.test(url)) {
    return 'facebook';
  }

  if (INSTAGRAM_PATTERNS.some(pattern => pattern.test(url))) {
    return 'instagram';
  }

  return null;
});

const isValidUrl = computed(() => {
  if (!modelValue.value?.url) return false;
  try {
    new URL(modelValue.value.url);
    return detectedPlatform.value !== null;
  }
  catch {
    return false;
  }
});

const platformIcon = computed(() => {
  if (detectedPlatform.value === 'facebook') return FacebookIcon;
  if (detectedPlatform.value === 'instagram') return InstagramIcon;
  return null;
});

const platformLabel = computed(() => {
  if (detectedPlatform.value === 'facebook') return 'Facebook';
  if (detectedPlatform.value === 'instagram') return 'Instagram';
  return '';
});

watch(detectedPlatform, (platform) => {
  if (modelValue.value) {
    modelValue.value.platform = platform;
  }
});
</script>
