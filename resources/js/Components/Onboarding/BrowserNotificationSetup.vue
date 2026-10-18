<template>
  <div class="space-y-3" data-slot="onboarding-push">
    <p role="status" class="text-sm font-medium">{{ $t(statusKey) }}</p>
    <p v-if="needsInstallation" class="text-sm text-muted-foreground">{{ $t('onboarding.push.install_first') }}</p>
    <p v-else-if="!pushSupported" class="text-sm text-muted-foreground">{{ $t('onboarding.push.unsupported_help') }}</p>
    <p v-else-if="pushPermission === 'denied'" class="text-sm text-muted-foreground">{{ $t('onboarding.push.unblock') }}</p>
    <p v-else-if="!serviceWorkerReady && !hasPushSubscription" class="text-sm text-muted-foreground">{{ $t('onboarding.push.preparing') }}</p>
    <Button
      v-if="canSubscribeToPush && !needsInstallation && !hasPushSubscription && serviceWorkerReady"
      class="h-auto min-h-11 max-w-full whitespace-normal text-left"
      variant="outline"
      voice="sentence"
      :disabled="isSubscribingToPush"
      data-testid="enable-browser-notifications"
      @click="enable"
    >
      <BellPlus class="size-4" aria-hidden="true" />{{ $t('onboarding.push.enable') }}
    </Button>
    <Button class="h-auto min-h-11 max-w-full whitespace-normal text-left" as-child variant="link" voice="sentence">
      <Link :href="route('profile.notifications')">{{ $t('onboarding.push.manage') }}</Link>
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
      {{ $t('onboarding.push.confirm') }}
    </Button>
    <p v-if="activationFailed" role="alert" class="text-sm text-destructive">{{ $t('onboarding.push.failed') }}</p>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
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
  if (hasPushSubscription.value) return 'onboarding.push.enabled_here';
  if (needsInstallation.value) return 'onboarding.push.install_required';
  if (!pushSupported.value) return 'onboarding.push.unsupported';
  return pushPermission.value === 'denied' ? 'onboarding.push.blocked' : 'onboarding.push.not_enabled_here';
});

onMounted(() => {
  isAppleMobile.value = installationPlatform(navigator.userAgent, navigator.platform, navigator.maxTouchPoints) === 'ios';
});

async function enable() {
  activationFailed.value = false;
  try {
    activationFailed.value = !(await subscribeToPush());
  }
  catch {
    activationFailed.value = true;
  }
}
</script>
