<template>
  <div v-if="platform || (editable && !element.json_content.url)" class="social-embed-container my-8 flex w-full justify-center">
    <!-- Loading state - only show initially before any render -->
    <div v-if="platform && isLoading && !hasRendered" class="flex items-center justify-center py-8">
      <div class="h-6 w-6 animate-spin rounded-full border-2 border-border border-t-brand" />
    </div>

    <!-- Placeholder when editable and no URL -->
    <div
      v-if="editable && !element.json_content.url"
      class="flex w-full max-w-lg flex-col items-center justify-center border border-dashed border-border bg-secondary/20 p-8 text-center"
      data-rc-interactive
    >
      <SocialIcon class="mb-2 size-8 text-muted-foreground" />
      <p class="text-sm font-medium text-foreground">
        {{ $t('rich-content.enter_social_url') }}
      </p>
      <p class="mt-1 text-xs text-muted-foreground">
        Facebook / Instagram
      </p>
    </div>

    <!-- Facebook embed -->
    <div
      v-else-if="platform === 'facebook'"
      :key="`fb-${element.json_content.url}-${showCaption}`"
      ref="fbContainer"
      :class="['facebook-embed w-full max-w-lg', editable && 'pointer-events-none']"
    >
      <div id="fb-root" />
      <div
        class="fb-post"
        :data-href="element.json_content.url"
        data-width="500"
        :data-show-text="showCaption ? 'true' : 'false'"
      />
    </div>

    <!-- Instagram embed -->
    <div
      v-else-if="platform === 'instagram'"
      :key="`ig-${instagramEmbedUrl}-${showCaption}`"
      ref="igContainer"
      :class="['instagram-embed w-full max-w-lg', editable && 'pointer-events-none']"
    >
      <blockquote
        class="instagram-media w-full min-w-[326px] max-w-[540px]"
        :data-instgrm-permalink="instagramEmbedUrl"
        data-instgrm-version="14"
      >
        <div class="flex items-center justify-center py-8 text-muted-foreground">
          {{ $t('Kraunamas Instagram įrašas...') }}
        </div>
      </blockquote>
    </div>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';

import type { SocialEmbed } from '@/Types/contentParts';

import { detectSocialPlatform, toInstagramEmbedUrl } from './embedUrl';
import { loadFacebookSdk, loadInstagramSdk } from './socialEmbedSdk';

import SocialIcon from '~icons/fluent/share-24-regular';

const props = defineProps<{
  element: SocialEmbed;
  editable?: boolean;
  blockKey?: string;
}>();

defineEmits<(e: 'update:element', value: SocialEmbed) => void>();

const isLoading = ref(false);
const hasRendered = ref(false);
const fbContainer = ref<HTMLElement | null>(null);
const igContainer = ref<HTMLElement | null>(null);

const platform = computed(() => detectSocialPlatform(props.element.json_content.url));

const showCaption = computed(() => props.element.options?.showCaption !== false);

const instagramEmbedUrl = computed(() => {
  if (!props.element.json_content.url || platform.value !== 'instagram') {
    return '';
  }
  return toInstagramEmbedUrl(props.element.json_content.url);
});

let renderToken = 0;
let cancelPendingDelay: (() => void) | null = null;

function waitForDomReady(): Promise<void> {
  cancelPendingDelay?.();
  return new Promise((resolve) => {
    const timer = setTimeout(() => {
      cancelPendingDelay = null;
      resolve();
    }, 200);
    cancelPendingDelay = () => {
      clearTimeout(timer);
      cancelPendingDelay = null;
      resolve();
    };
  });
}

async function renderEmbed(): Promise<void> {
  const token = ++renderToken;
  const currentPlatform = platform.value;

  if (!props.element.json_content.url || !currentPlatform) {
    cancelPendingDelay?.();
    isLoading.value = false;
    return;
  }

  isLoading.value = true;

  try {
    await nextTick();
    await waitForDomReady();

    if (token !== renderToken) {
      return;
    }

    if (currentPlatform === 'facebook') {
      const FB = await loadFacebookSdk();
      if (token === renderToken && fbContainer.value && FB.XFBML) {
        FB.XFBML.parse(fbContainer.value);
        hasRendered.value = true;
      }
    }
    else if (currentPlatform === 'instagram') {
      const instgrm = await loadInstagramSdk();
      if (token === renderToken && instgrm.Embeds) {
        instgrm.Embeds.process();
        hasRendered.value = true;
      }
    }
  }
  catch (error) {
    if (token === renderToken) {
      console.error('Error loading social embed:', error);
    }
  }
  finally {
    if (token === renderToken) {
      isLoading.value = false;
    }
  }
}

watch([() => props.element.json_content.url, showCaption], () => {
  hasRendered.value = false;
  void renderEmbed();
});

onMounted(() => {
  void renderEmbed();
});

onUnmounted(() => {
  renderToken += 1;
  cancelPendingDelay?.();
});
</script>

<style scoped>
.social-embed-container {
  contain: layout;
}

.facebook-embed,
.instagram-embed {
  display: flex;
  justify-content: center;
}

/* Instagram embed responsive styles */
.instagram-embed :deep(.instagram-media) {
  margin: 0 auto !important;
}
</style>
