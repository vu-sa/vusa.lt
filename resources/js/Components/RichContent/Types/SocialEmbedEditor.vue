<template>
  <div v-if="modelValue && options" class="flex flex-col gap-4">
    <div class="space-y-2">
      <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
        {{ $t('Facebook arba Instagram įrašo nuoroda') }}
      </label>
      <input 
        v-model="modelValue.url" 
        type="url" 
        class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm placeholder:text-zinc-400 focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100 dark:placeholder:text-zinc-500"
        placeholder="https://www.facebook.com/... arba https://www.instagram.com/p/..."
        @input="detectPlatform"
      />
      <p class="text-xs text-zinc-500 dark:text-zinc-400">
        {{ $t('Įklijuokite Facebook arba Instagram įrašo nuorodą') }}
      </p>
    </div>

    <!-- Platform detection indicator -->
    <div v-if="detectedPlatform" class="flex items-center gap-2 text-sm">
      <div class="flex items-center gap-1.5 rounded-full px-2.5 py-1" :class="platformBadgeClass">
        <component :is="platformIcon" class="h-4 w-4" />
        <span class="font-medium">{{ platformLabel }}</span>
      </div>
      <span v-if="isValidUrl" class="text-emerald-600 dark:text-emerald-400">
        ✓ {{ $t('Nuoroda atpažinta') }}
      </span>
      <span v-else class="text-amber-600 dark:text-amber-400">
        {{ $t('Patikrinkite nuorodą') }}
      </span>
    </div>

    <!-- Options -->
    <div class="flex items-center gap-2">
      <input 
        id="showCaption" 
        v-model="options.showCaption" 
        type="checkbox"
        class="h-4 w-4 rounded border-zinc-300 text-zinc-600 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800"
      />
      <label for="showCaption" class="text-sm text-zinc-700 dark:text-zinc-300">
        {{ $t('Rodyti įrašo aprašymą') }}
      </label>
    </div>

    <!-- Live preview -->
    <div v-if="isValidUrl && detectedPlatform" class="mt-4 space-y-2">
      <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
        {{ $t('Peržiūra') }}
      </label>
      <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800/50">
        <SocialEmbedPreview 
          :url="modelValue.url" 
          :platform="detectedPlatform" 
          :show-caption="options.showCaption" 
        />
      </div>
    </div>

    <!-- Help text -->
    <div class="rounded-lg bg-zinc-50 p-3 dark:bg-zinc-800/50">
      <p class="text-xs text-zinc-600 dark:text-zinc-400">
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
import { computed, defineAsyncComponent } from 'vue';
import FacebookIcon from '~icons/mdi/facebook';
import InstagramIcon from '~icons/mdi/instagram';

const SocialEmbedPreview = defineAsyncComponent(() => import('./SocialEmbedPreview.vue'));

const modelValue = defineModel<{
  url: string;
  platform: 'facebook' | 'instagram' | null;
  postId?: string;
}>();

const options = defineModel<{
  showCaption?: boolean;
}>('options');

// URL patterns for platform detection
// Facebook URLs can be in many formats:
// - https://www.facebook.com/username/posts/pfbid...
// - https://www.facebook.com/photo/?fbid=...
// - https://www.facebook.com/permalink.php?story_fbid=...
// - https://fb.watch/...
const FACEBOOK_PATTERNS = [
  /facebook\.com\/[\w.-]+\/posts\/[\w]+/i,  // username/posts/id or pfbid
  /facebook\.com\/photo\/?\?fbid=/i,  // photo?fbid=
  /facebook\.com\/[\w.-]+\/photos\//i, // username/photos/
  /facebook\.com\/[\w.-]+\/videos\//i, // username/videos/
  /facebook\.com\/permalink\.php/i, // permalink.php
  /facebook\.com\/watch\//i, // watch/
  /facebook\.com\/reel\//i, // reel/
  /facebook\.com\/share\//i, // share/
  /fb\.watch\/[\w]+/i, // fb.watch short URLs
];

const INSTAGRAM_PATTERNS = [
  /instagram\.com\/p\/[\w-]+/i,  // posts
  /instagram\.com\/reel\/[\w-]+/i, // reels
  /instagram\.com\/tv\/[\w-]+/i, // IGTV
  /instagr\.am\/p\/[\w-]+/i, // short URL posts
];

// Detect platform from URL
const detectedPlatform = computed(() => {
  if (!modelValue.value?.url) return null;
  
  const url = modelValue.value.url;
  
  // Simple domain check first for broader matching
  if (/facebook\.com|fb\.watch/i.test(url)) {
    // Verify it looks like a post URL (not just the homepage)
    if (FACEBOOK_PATTERNS.some(pattern => pattern.test(url))) {
      return 'facebook';
    }
    // Also accept any facebook.com URL with a path longer than just /
    if (/facebook\.com\/\S+/.test(url)) {
      return 'facebook';
    }
  }
  
  if (INSTAGRAM_PATTERNS.some(pattern => pattern.test(url))) {
    return 'instagram';
  }
  
  return null;
});

// Check if URL is valid
const isValidUrl = computed(() => {
  if (!modelValue.value?.url) return false;
  try {
    new URL(modelValue.value.url);
    return detectedPlatform.value !== null;
  } catch {
    return false;
  }
});

// Platform-specific styling
const platformBadgeClass = computed(() => {
  if (detectedPlatform.value === 'facebook') {
    return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400';
  }
  if (detectedPlatform.value === 'instagram') {
    return 'bg-gradient-to-r from-purple-100 to-pink-100 text-pink-700 dark:from-purple-900/30 dark:to-pink-900/30 dark:text-pink-400';
  }
  return 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-400';
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

// Update platform in model when URL changes
function detectPlatform() {
  if (modelValue.value) {
    modelValue.value.platform = detectedPlatform.value;
  }
}
</script>
