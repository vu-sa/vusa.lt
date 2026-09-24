<template>
  <FormPanel :title="$t('notifications.push_devices.title')" :icon="Smartphone" title-class="text-brand">
    <p class="text-xs text-muted-foreground leading-relaxed">
      {{ $t('notifications.push_devices.description') }}
    </p>

    <!-- Current device status -->
    <div class="flex flex-col gap-2.5 border border-border p-3 bg-muted/10">
      <div class="flex items-center justify-between gap-2">
        <div class="flex items-center gap-2 min-w-0">
          <component :is="currentDeviceIcon" class="size-4 shrink-0 text-muted-foreground" aria-hidden="true" />
          <p class="font-medium text-xs truncate">
            {{ $t('notifications.push_devices.this_device') }}
          </p>
        </div>
        <span
          v-if="hasPushSubscription"
          class="inline-flex items-center gap-1 text-xs font-medium text-[var(--status-success)]"
        >
          <Check class="size-3" aria-hidden="true" />
          {{ $t('notifications.push_devices.status_enabled') }}
        </span>
        <span
          v-else-if="pushPermission === 'denied'"
          class="text-xs font-medium text-destructive"
        >
          {{ $t('notifications.push_devices.status_blocked') }}
        </span>
        <span
          v-else-if="!pushSupported"
          class="text-xs text-muted-foreground"
        >
          {{ $t('notifications.push_devices.status_not_supported') }}
        </span>
        <span
          v-else
          class="text-xs text-muted-foreground"
        >
          {{ $t('notifications.push_devices.status_disabled') }}
        </span>
      </div>

      <div v-if="canSubscribeToPush || hasPushSubscription">
        <Button
          v-if="!hasPushSubscription && canSubscribeToPush"
          :disabled="isSubscribingToPush"
          variant="outline"
          size="sm"
          class="w-full text-xs"
          @click="handleSubscribe"
        >
          <BellPlus v-if="!isSubscribingToPush" class="size-3.5 mr-1.5" />
          <Loader2 v-else class="size-3.5 mr-1.5 animate-spin" />
          {{ $t('notifications.channels.push_enable') }}
        </Button>
        <Button
          v-else-if="hasPushSubscription"
          :disabled="isUnsubscribingFromPush"
          variant="outline"
          size="sm"
          class="w-full text-xs"
          @click="handleUnsubscribe"
        >
          <BellOff v-if="!isUnsubscribingFromPush" class="size-3.5 mr-1.5" />
          <Loader2 v-else class="size-3.5 mr-1.5 animate-spin" />
          {{ $t('notifications.channels.push_disable') }}
        </Button>
      </div>
    </div>

    <!-- Device list header with refresh button -->
    <div class="flex items-center justify-between pt-1">
      <h3 class="text-xs font-bold uppercase tracking-wider text-muted-foreground">
        {{ $t('notifications.push_devices.all_devices') }} ({{ devices.length }})
      </h3>
      <Button
        variant="ghost"
        size="sm"
        class="h-7 px-2 text-xs"
        :disabled="isLoading || isRefreshingSubscriptionStatus"
        @click="refreshDevices"
      >
        <RefreshCw :class="['size-3.5 mr-1', { 'animate-spin': isLoading || isRefreshingSubscriptionStatus }]" />
        {{ $t('Atnaujinti') }}
      </Button>
    </div>

    <!-- Devices list -->
    <div v-if="isLoading" class="flex items-center justify-center py-6">
      <Loader2 class="size-5 animate-spin text-muted-foreground" />
    </div>

    <div v-else-if="devices.length === 0" class="text-center py-6 text-muted-foreground border border-dashed border-border p-4">
      <PhoneOff class="size-6 mx-auto mb-1.5 opacity-40" />
      <p class="text-xs">
        {{ $t('notifications.push_devices.no_devices') }}
      </p>
    </div>

    <div v-else class="space-y-2">
      <TransitionGroup name="device-list">
        <div
          v-for="device in devices"
          :key="device.id"
          :class="[
            'flex items-center justify-between border p-2.5 transition-colors',
            device.isCurrentDevice ? 'border-foreground/30 bg-muted/30' : 'border-border bg-background',
          ]"
        >
          <div class="flex items-center gap-2.5 min-w-0">
            <component :is="getDeviceIcon(device.device_name)" class="size-4 shrink-0 text-muted-foreground" />
            <div class="min-w-0">
              <div class="flex items-center gap-1.5 flex-wrap">
                <p class="font-medium text-xs truncate">
                  {{ device.device_name || $t('notifications.push_devices.unknown_device') }}
                </p>
                <span
                  v-if="device.isCurrentDevice"
                  class="text-[10px] font-bold uppercase tracking-wider px-1.5 py-0.5 border border-border bg-card text-muted-foreground"
                >
                  {{ $t('notifications.push_devices.current_badge') }}
                </span>
              </div>
              <p v-if="device.created_at" class="text-[11px] text-muted-foreground">
                {{ formatDate(device.created_at) }}
              </p>
            </div>
          </div>
          <Button
            variant="ghost"
            size="sm"
            class="size-8 p-0 shrink-0 text-muted-foreground hover:text-destructive hover:bg-destructive/10 pointer-coarse:size-9"
            :disabled="removingDeviceId === device.id"
            :aria-label="$t('Ištrinti įrenginį')"
            @click="handleRemoveDevice(device)"
          >
            <Loader2 v-if="removingDeviceId === device.id" class="size-3.5 animate-spin" />
            <Trash2 v-else class="size-3.5" />
          </Button>
        </div>
      </TransitionGroup>
    </div>

    <!-- Test notification button -->
    <div v-if="hasAnyPushSubscription" class="pt-3 border-t border-border space-y-2">
      <Button
        :disabled="testHttp.processing"
        variant="secondary"
        size="sm"
        class="w-full text-xs"
        @click="handleSendTestNotification"
      >
        <BellRing v-if="!testHttp.processing" class="size-3.5 mr-1.5" />
        <Loader2 v-else class="size-3.5 mr-1.5 animate-spin" />
        {{ $t('notifications.push_devices.send_test') }}
      </Button>
      <p v-if="testHttp.recentlySuccessful" class="text-xs text-[var(--status-success)] flex items-center gap-1.5">
        <Check class="size-3.5 shrink-0" />
        {{ $t('notifications.push_devices.test_sent') }}
      </p>
    </div>
  </FormPanel>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useHttp } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import {
  BellPlus,
  BellOff,
  BellRing,
  Check,
  Globe,
  Laptop,
  Loader2,
  Monitor,
  PhoneOff,
  RefreshCw,
  Smartphone,
  Tablet,
  Trash2,
} from 'lucide-vue-next';

import { usePWA, type PushSubscriptionDevice } from '@/Composables/usePWA';
import { FormPanel } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { formatDateTime } from '@/Utils/dateTime';
import ISimpleIconsApple from '~icons/simple-icons/apple';
import ISimpleIconsLinux from '~icons/simple-icons/linux';
import ISimpleIconsWindows from '~icons/simple-icons/windows';

const {
  pushSupported,
  pushPermission,
  canSubscribeToPush,
  hasPushSubscription,
  hasAnyPushSubscription,
  isSubscribingToPush,
  isUnsubscribingFromPush,
  isRefreshingSubscriptionStatus,
  subscribeToPush,
  unsubscribeFromPush,
  removeSubscriptionById,
  fetchPushSubscriptions,
  refreshSubscriptionStatus,
} = usePWA();

const devices = ref<PushSubscriptionDevice[]>([]);
const isLoading = ref(false);
const removingDeviceId = ref<number | null>(null);
const testHttp = useHttp({});

// Get current device icon based on status
const currentDeviceIcon = computed(() => {
  if (!pushSupported.value) return Globe;
  return getDeviceIcon(null);
});

// Get appropriate icon for device
const getDeviceIcon = (deviceName: string | null) => {
  if (!deviceName) return Globe;

  const name = deviceName.toLowerCase();
  if (name.includes('iphone') || name.includes('android')) return Smartphone;
  if (name.includes('ipad') || name.includes('tablet')) return Tablet;
  if (name.includes('mac')) return ISimpleIconsApple;
  if (name.includes('windows')) return ISimpleIconsWindows;
  if (name.includes('linux')) return ISimpleIconsLinux;
  if (name.includes('laptop')) return Laptop;
  return Monitor;
};

// Format date with canonical helper
const formatDate = (dateString: string): string => {
  return formatDateTime(dateString);
};

// Load devices on mount
const loadDevices = async () => {
  isLoading.value = true;
  try {
    devices.value = await fetchPushSubscriptions();
  }
  finally {
    isLoading.value = false;
  }
};

// Refresh devices and subscription status
const refreshDevices = async () => {
  await refreshSubscriptionStatus();
  await loadDevices();
};

// Subscribe to push
const handleSubscribe = async () => {
  await subscribeToPush();
  await loadDevices();
};

// Unsubscribe from push
const handleUnsubscribe = async () => {
  await unsubscribeFromPush();
  await loadDevices();
};

// Remove a device
const handleRemoveDevice = async (device: PushSubscriptionDevice) => {
  removingDeviceId.value = device.id;
  try {
    const success = await removeSubscriptionById(device.id);
    if (success) {
      devices.value = devices.value.filter(d => d.id !== device.id);
    }
  }
  finally {
    removingDeviceId.value = null;
  }
};

// Send test notification
const handleSendTestNotification = async () => {
  await testHttp.post(route('push-subscription.test'), {
    onError: () => {
      console.error('Failed to send test notification');
    },
    onHttpException: (response) => {
      console.error('Failed to send test notification:', response);
    },
    onNetworkError: (error) => {
      console.error('Failed to send test notification:', error);
    },
  });
};

onMounted(() => {
  loadDevices();
});
</script>

<style scoped>
.device-list-enter-from {
  opacity: 0;
  transform: translateX(-20px);
}

.device-list-leave-to {
  opacity: 0;
  transform: translateX(20px);
}

.device-list-enter-active {
  transition: all 0.3s ease-out;
}

.device-list-leave-active {
  transition: all 0.2s ease-in;
  position: absolute;
}

.device-list-move {
  transition: transform 0.3s ease;
}
</style>
