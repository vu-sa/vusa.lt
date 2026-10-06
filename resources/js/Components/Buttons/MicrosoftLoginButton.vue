<template>
  <button
    type="button"
    :class="[
      'flex w-full items-center justify-center gap-3',
      'border border-foreground bg-foreground px-4 py-3',
      'text-sm font-bold text-background transition-colors',
      'hover:bg-foreground/90 disabled:opacity-70 rounded-none min-h-[44px]',
      'cursor-pointer disabled:cursor-not-allowed',
    ]"
    :disabled="loading"
    @click="handleLogin"
  >
    <Loader2 v-if="loading" class="size-5 animate-spin" />
    <svg
      v-else
      viewBox="0 0 21 21"
      class="size-5 shrink-0"
      aria-hidden="true"
      fill="none"
    >
      <rect x="1" y="1" width="9" height="9" fill="#F25022" />
      <rect x="11" y="1" width="9" height="9" fill="#7FBA00" />
      <rect x="1" y="11" width="9" height="9" fill="#00A4EF" />
      <rect x="11" y="11" width="9" height="9" fill="#FFB900" />
    </svg>
    <span>{{ $t('auth.continue_microsoft') }}</span>
  </button>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { Loader2 } from 'lucide-vue-next';

import { usePWA } from '@/Composables/usePWA';

const loading = ref(false);
const { isPWA } = usePWA();

// Handle popup message from OAuth callback
const handlePopupMessage = (event: MessageEvent) => {
  // Verify origin for security
  if (event.origin !== window.location.origin) return;

  if (event.data?.type === 'oauth-success') {
    loading.value = false;
    // Reload the page to get authenticated state
    router.visit(event.data.redirectUrl || route('dashboard'), {
      preserveState: false,
      replace: true,
    });
  }
  else if (event.data?.type === 'oauth-error') {
    loading.value = false;
    // Could show error message here
    console.error('[OAuth] Login failed:', event.data.message);
  }
};

onMounted(() => {
  window.addEventListener('message', handlePopupMessage);
});

onUnmounted(() => {
  window.removeEventListener('message', handlePopupMessage);
});

const handleLogin = () => {
  loading.value = true;

  if (isPWA.value) {
    // PWA mode: use popup to stay in app shell
    openPopupLogin();
  }
  else {
    // Browser mode: standard redirect
    window.location.href = route('microsoft.redirect');
  }
};

const openPopupLogin = () => {
  const width = 500;
  const height = 700;
  const left = window.screenX + (window.outerWidth - width) / 2;
  const top = window.screenY + (window.outerHeight - height) / 2;

  const popup = window.open(
    `${route('microsoft.redirect')}?popup=1`,
    'microsoft-oauth',
    `width=${width},height=${height},left=${left},top=${top},popup=1`,
  );

  // If popup was blocked, fall back to redirect
  if (!popup || popup.closed) {
    window.location.href = route('microsoft.redirect');
    return;
  }

  // Check if popup was closed manually (user cancelled)
  const checkClosed = setInterval(() => {
    if (popup.closed) {
      clearInterval(checkClosed);
      loading.value = false;
    }
  }, 500);
};
</script>
