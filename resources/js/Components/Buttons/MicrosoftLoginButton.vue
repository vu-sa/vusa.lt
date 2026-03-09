<template>
  <Button
    size="lg"
    class="w-full bg-gradient-to-r from-vusa-red to-red-600 hover:from-vusa-red/90 hover:to-red-600/90 text-white font-medium shadow-lg transition-all duration-200 focus:ring-2 focus:ring-vusa-red/20 disabled:opacity-70 disabled:cursor-not-allowed"
    :disabled="loading"
    @click="handleLogin"
  >
    <span v-if="loading" class="flex items-center">
      <div class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin mr-2" />
      {{ $t("auth.continue_microsoft") }}
    </span>
    <span v-else class="flex items-center">
      <IMdiMicrosoft class="w-5 h-5 mr-2" />
      {{ $t("auth.continue_microsoft") }}
    </span>
  </Button>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from "vue";
import { router } from "@inertiajs/vue3";
import { Button } from "@/Components/ui/button";
import { usePWA } from "@/Composables/usePWA";
import IMdiMicrosoft from "~icons/mdi/microsoft";

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
      replace: true 
    });
  } else if (event.data?.type === 'oauth-error') {
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
  } else {
    // Browser mode: standard redirect
    window.location.href = route("microsoft.redirect");
  }
};

const openPopupLogin = () => {
  const width = 500;
  const height = 700;
  const left = window.screenX + (window.outerWidth - width) / 2;
  const top = window.screenY + (window.outerHeight - height) / 2;
  
  const popup = window.open(
    route("microsoft.redirect") + "?popup=1",
    "microsoft-oauth",
    `width=${width},height=${height},left=${left},top=${top},popup=1`
  );
  
  // If popup was blocked, fall back to redirect
  if (!popup || popup.closed) {
    console.log('[OAuth] Popup blocked, falling back to redirect');
    window.location.href = route("microsoft.redirect");
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
