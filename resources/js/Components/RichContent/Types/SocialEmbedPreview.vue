<template>
  <div class="social-embed-preview">
    <!-- Loading state -->
    <div v-if="isLoading && !hasRendered" class="flex items-center justify-center py-8">
      <div class="h-6 w-6 animate-spin rounded-full border-2 border-zinc-300 border-t-zinc-600"></div>
    </div>

    <!-- Facebook embed -->
    <div v-if="platform === 'facebook'" ref="fbContainer" class="facebook-embed" :key="'fb-' + url">
      <div id="fb-root"></div>
      <div 
        class="fb-post" 
        :data-href="url" 
        data-width="500"
        :data-show-text="showCaption ? 'true' : 'false'"
      ></div>
    </div>

    <!-- Instagram embed -->
    <div v-else-if="platform === 'instagram'" ref="igContainer" class="instagram-embed" :key="'ig-' + url">
      <blockquote 
        class="instagram-media" 
        :data-instgrm-permalink="instagramEmbedUrl"
        data-instgrm-version="14"
        style="max-width:540px; min-width:326px; width:100%;"
      >
        <div class="flex items-center justify-center py-8 text-zinc-500">
          {{ $t('Kraunamas Instagram įrašas...') }}
        </div>
      </blockquote>
    </div>

    <!-- Error/unsupported state -->
    <div v-else-if="!platform && url" class="rounded-lg bg-zinc-100 p-4 text-center text-sm text-zinc-500 dark:bg-zinc-800">
      {{ $t('Įveskite galiojančią Facebook arba Instagram nuorodą') }}
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted, computed, nextTick } from 'vue';

const props = defineProps<{
  url: string;
  platform: 'facebook' | 'instagram' | null;
  showCaption?: boolean;
}>();

const isLoading = ref(false);
const hasRendered = ref(false);
const fbContainer = ref<HTMLElement | null>(null);
const igContainer = ref<HTMLElement | null>(null);

// Extract clean Instagram URL for embedding
const instagramEmbedUrl = computed(() => {
  if (!props.url || props.platform !== 'instagram') return '';
  // Ensure URL ends with proper format
  const cleanUrl = props.url.split('?')[0] || ''; // Remove query params
  if (!cleanUrl.endsWith('/')) {
    return cleanUrl + '/';
  }
  return cleanUrl;
});

// Load Facebook SDK
async function loadFacebookSDK(): Promise<any> {
  if (window.FB) {
    return window.FB;
  }

  return new Promise((resolve, reject) => {
    // Check if script already exists
    if (document.getElementById('facebook-jssdk')) {
      // SDK script exists, wait for it to load
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

    window.fbAsyncInit = function() {
      window.FB.init({
        xfbml: true,
        version: 'v21.0'
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

// Load Instagram embed script
async function loadInstagramEmbed(): Promise<any> {
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
      // Timeout after 10 seconds
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
      // Give Instagram time to initialize
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
        } else {
          reject(new Error('Instagram embed init timeout'));
        }
      }, 5000);
    };
    script.onerror = () => reject(new Error('Failed to load Instagram embed'));
    document.body.appendChild(script);
  });
}

// Process embeds when URL/platform changes
async function processEmbed() {
  if (!props.url || !props.platform) return;

  isLoading.value = true;

  try {
    // Wait for Vue to render the DOM
    await nextTick();
    // Additional delay to ensure DOM is ready
    await new Promise(resolve => setTimeout(resolve, 200));

    if (props.platform === 'facebook') {
      const FB = await loadFacebookSDK();
      if (fbContainer.value && FB?.XFBML) {
        FB.XFBML.parse(fbContainer.value);
        hasRendered.value = true;
      }
    } else if (props.platform === 'instagram') {
      const instgrm = await loadInstagramEmbed();
      if (instgrm?.Embeds) {
        instgrm.Embeds.process();
        hasRendered.value = true;
      }
    }
  } catch (error) {
    console.error('Error loading social embed:', error);
  } finally {
    isLoading.value = false;
  }
}

// Watch for changes and re-process
watch(() => [props.url, props.platform, props.showCaption], () => {
  hasRendered.value = false;
  processEmbed();
}, { immediate: false });

onMounted(() => {
  if (props.url && props.platform) {
    processEmbed();
  }
});

// Type declarations for global SDK objects
declare global {
  interface Window {
    FB: any;
    fbAsyncInit: () => void;
    instgrm: any;
  }
}
</script>

<style scoped>
.social-embed-preview {
  width: 100%;
  overflow: hidden;
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
