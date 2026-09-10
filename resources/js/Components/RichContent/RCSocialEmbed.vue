<template>
  <div class="social-embed-container my-8 flex w-full justify-center">
    <!-- Loading state - only show initially before any render -->
    <div v-if="isLoading && !hasRendered" class="flex items-center justify-center py-8">
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
    <div v-else-if="platform === 'facebook'" ref="fbContainer" :class="['facebook-embed w-full max-w-lg', editable && 'pointer-events-none']">
      <div id="fb-root" />
      <div
        class="fb-post"
        :data-href="element.json_content.url"
        data-width="500"
        :data-show-text="showCaption ? 'true' : 'false'"
      />
    </div>

    <!-- Instagram embed -->
    <div v-else-if="platform === 'instagram'" ref="igContainer" :class="['instagram-embed w-full max-w-lg', editable && 'pointer-events-none']">
      <blockquote
        class="instagram-media"
        :data-instgrm-permalink="instagramEmbedUrl"
        data-instgrm-version="14"
        style="max-width:540px; min-width:326px; width:100%;"
      >
        <div class="flex items-center justify-center py-8 text-muted-foreground">
          Kraunamas Instagram įrašas...
        </div>
      </blockquote>
    </div>

    <!-- Fallback for invalid embeds -->
    <div v-else-if="element.json_content.url" class="border border-border bg-secondary/40 p-4 text-center text-sm text-muted-foreground">
      <a
        :href="element.json_content.url"
        target="_blank"
        rel="noopener noreferrer"
        class="text-muted-foreground underline hover:text-brand"
      >
        Atidaryti įrašą naujame lange →
      </a>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, nextTick } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import SocialIcon from '~icons/fluent/share-24-regular';
import type { SocialEmbed } from '@/Types/contentParts';

const props = defineProps<{
  element: SocialEmbed;
  editable?: boolean;
  blockKey?: string;
}>();

defineEmits<(e: 'update:element', value: SocialEmbed) => void>();

const isLoading = ref(true);
const hasRendered = ref(false);
const fbContainer = ref<HTMLElement | null>(null);
const igContainer = ref<HTMLElement | null>(null);

// Detect platform from stored value or URL
const platform = computed(() => {
  if (props.element.json_content.platform) {
    return props.element.json_content.platform;
  }
  // Fallback: detect from URL
  const { url } = props.element.json_content;
  if (!url) return null;

  if (/facebook\.com|fb\.watch/i.test(url)) return 'facebook';
  if (/instagram\.com|instagr\.am/i.test(url)) return 'instagram';
  return null;
});

const showCaption = computed(() => props.element.options?.showCaption !== false);

// Extract clean Instagram URL for embedding
const instagramEmbedUrl = computed(() => {
  if (!props.element.json_content.url || platform.value !== 'instagram') return '';
  const cleanUrl = props.element.json_content.url.split('?')[0] || '';
  if (!cleanUrl.endsWith('/')) {
    return `${cleanUrl}/`;
  }
  return cleanUrl;
});

interface FacebookSDK {
  init: (options: { xfbml: boolean; version: string }) => void;
  XFBML?: {
    parse: (element?: HTMLElement | null) => void;
  };
}

interface InstagramSDK {
  Embeds?: {
    process: () => void;
  };
}

// Load Facebook SDK lazily
async function loadFacebookSDK(): Promise<FacebookSDK> {
  if (window.FB) {
    return window.FB;
  }

  return new Promise((resolve, reject) => {
    // Check if script already exists
    if (document.getElementById('facebook-jssdk')) {
      const checkFB = setInterval(() => {
        if (window.FB) {
          clearInterval(checkFB);
          resolve(window.FB);
        }
      }, 100);
      // Timeout after 10 seconds
      setTimeout(() => {
        clearInterval(checkFB);
        reject(new Error('Facebook SDK load timeout'));
      }, 10000);
      return;
    }

    window.fbAsyncInit = function () {
      window.FB.init({
        xfbml: true,
        version: 'v21.0',
      });
      resolve(window.FB);
    };

    const script = document.createElement('script');
    script.id = 'facebook-jssdk';
    script.src = 'https://connect.facebook.net/en_US/sdk.js#xfbml=1&version=v21.0';
    script.async = true;
    script.defer = true;
    script.crossOrigin = 'anonymous';
    script.onerror = () => reject(new Error('Failed to load Facebook SDK'));
    document.body.appendChild(script);
  });
}

// Load Instagram embed script lazily
async function loadInstagramEmbed(): Promise<InstagramSDK> {
  if (window.instgrm) {
    return window.instgrm;
  }

  return new Promise((resolve, reject) => {
    // Check if script already exists
    if (document.getElementById('instagram-embed-js')) {
      const checkIG = setInterval(() => {
        if (window.instgrm) {
          clearInterval(checkIG);
          resolve(window.instgrm);
        }
      }, 100);
      setTimeout(() => {
        clearInterval(checkIG);
        reject(new Error('Instagram embed load timeout'));
      }, 10000);
      return;
    }

    const script = document.createElement('script');
    script.id = 'instagram-embed-js';
    script.src = 'https://www.instagram.com/embed.js';
    script.async = true;
    script.onload = () => {
      const checkIG = setInterval(() => {
        if (window.instgrm) {
          clearInterval(checkIG);
          resolve(window.instgrm);
        }
      }, 50);
      setTimeout(() => {
        clearInterval(checkIG);
        if (window.instgrm) {
          resolve(window.instgrm);
        }
        else {
          reject(new Error('Instagram embed init timeout'));
        }
      }, 5000);
    };
    script.onerror = () => reject(new Error('Failed to load Instagram embed'));
    document.body.appendChild(script);
  });
}

// Initialize embed on mount
async function initEmbed() {
  if (!props.element.json_content.url || !platform.value) {
    isLoading.value = false;
    return;
  }

  try {
    // Wait for Vue to render the DOM
    await nextTick();
    // Additional delay to ensure DOM is ready
    await new Promise(resolve => setTimeout(resolve, 200));

    if (platform.value === 'facebook') {
      const FB = await loadFacebookSDK();
      if (fbContainer.value && FB?.XFBML) {
        FB.XFBML.parse(fbContainer.value);
        hasRendered.value = true;
      }
    }
    else if (platform.value === 'instagram') {
      const instgrm = await loadInstagramEmbed();
      if (instgrm?.Embeds) {
        instgrm.Embeds.process();
        hasRendered.value = true;
      }
    }
  }
  catch (error) {
    console.error('Error loading social embed:', error);
  }
  finally {
    isLoading.value = false;
  }
}

onMounted(() => {
  initEmbed();
});

// Type declarations for global SDK objects
declare global {
  interface Window {
    FB: FacebookSDK;
    fbAsyncInit: () => void;
    instgrm: InstagramSDK;
  }
}
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
