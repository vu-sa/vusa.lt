<template>
  <div class="space-y-3" data-slot="onboarding-push">
    <p role="status" class="text-sm font-medium">{{ $t(statusKey) }}</p>
    <p v-if="needsInstallation" class="text-sm text-muted-foreground">{{ $t('learning.path.push.install_first') }}</p>
    <p v-else-if="!pushSupported" class="text-sm text-muted-foreground">{{ $t('learning.path.push.unsupported_help') }}</p>
    <p v-else-if="pushPermission === 'denied'" class="text-sm text-muted-foreground">{{ $t('learning.path.push.unblock') }}</p>
    <p v-else-if="!serviceWorkerReady && !hasPushSubscription" class="text-sm text-muted-foreground">{{ $t('learning.path.push.preparing') }}</p>
    <Button
      v-if="canSubscribeToPush && !needsInstallation && !hasPushSubscription && serviceWorkerReady"
      class="h-auto min-h-11 max-w-full whitespace-normal text-left"
      variant="outline"
      voice="sentence"
      :disabled="isSubscribingToPush"
      data-testid="enable-browser-notifications"
      @click="enable"
    >
      <BellPlus class="size-4" aria-hidden="true" />{{ $t('learning.path.push.enable') }}
    </Button>
    <Button class="h-auto min-h-11 max-w-full whitespace-normal text-left" as-child variant="link" voice="sentence">
      <Link :href="route('profile.notifications')">{{ $t('learning.path.push.manage') }}</Link>
    </Button>
    <Button
      v-if="hasPushSubscription && needsConfirmation"
      class="h-auto min-h-11 max-w-full whitespace-normal text-left"
      variant="outline"
      voice="sentence"
      :disabled="saving"
      data-testid="confirm-browser-notifications"
      @click="emit('confirm')"
    >
      {{ $t('learning.path.push.confirm') }}
    </Button>
    <p v-if="activationFailed" role="alert" class="text-sm text-destructive">{{ $t('learning.path.push.failed') }}</p>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { BellPlus } from '@lucide/vue';
import { trans as $t } from 'laravel-vue-i18n';

import { Button } from '@/Components/ui/button';
import { usePWA } from '@/Composables/usePWA';
import { installationPlatform } from '@/Utils/installationPlatform';

defineProps<{ needsConfirmation?: boolean; saving?: boolean }>();
const emit = defineEmits<{ confirm: [] }>();
const { isPWA, pushSupported, pushPermission, hasPushSubscription, canSubscribeToPush, serviceWorkerReady, isSubscribingToPush, subscribeToPush } = usePWA();
const isAppleMobile = ref(false);
const activationFailed = ref(false);
const needsInstallation = computed(() => isAppleMobile.value && !isPWA.value);
const statusKey = computed(() => {
  if (hasPushSubscription.value) return 'learning.path.push.enabled_here';
  if (needsInstallation.value) return 'learning.path.push.install_required';
  if (!pushSupported.value) return 'learning.path.push.unsupported';
  return pushPermission.value === 'denied' ? 'learning.path.push.blocked' : 'learning.path.push.not_enabled_here';
});

onMounted(() => {
  isAppleMobile.value = installationPlatform(navigator.userAgent, navigator.platform, navigator.maxTouchPoints) === 'ios';
});

async function enable() {
  activationFailed.value = false;
  try {
    activationFailed.value = !(await subscribeToPush());
    if (!activationFailed.value) router.reload({ only: ['path', 'step'], preserveScroll: true });
  }
  catch {
    activationFailed.value = true;
  }
}
</script>
