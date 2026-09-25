<template>
  <FormPanel :title="$t('notifications.push_devices.title')" :icon="Smartphone" title-class="text-brand" flush>
    <div class="flex flex-col gap-3 border-b border-border px-4 py-3">
      <div class="flex items-center justify-between gap-3">
        <div class="flex min-w-0 items-center gap-2">
          <component :is="currentDeviceIcon" class="size-4 shrink-0 text-muted-foreground" aria-hidden="true" />
          <p class="truncate text-sm font-bold text-foreground">
            {{ $t('notifications.push_devices.this_device') }}
          </p>
        </div>
        <span :class="['inline-flex shrink-0 items-center gap-1 text-xs', statusClass]">
          <Check v-if="hasPushSubscription" class="size-3.5" aria-hidden="true" />
          {{ statusLabel }}
        </span>
      </div>

      <p v-if="pushSupported && !serviceWorkerReady && !hasPushSubscription" class="text-xs leading-snug text-muted-foreground text-pretty">
        {{ $t('notifications.push_devices.worker_missing') }}
      </p>

      <Button
        v-if="hasPushSubscription"
        type="button"
        :disabled="isUnsubscribingFromPush"
        variant="outline"
        size="sm"
        voice="sentence"
        class="w-full"
        @click="handleUnsubscribe"
      >
        <Loader2 v-if="isUnsubscribingFromPush" class="size-3.5 animate-spin" />
        <BellOff v-else class="size-3.5" />
        {{ $t('notifications.channels.push_disable') }}
      </Button>
      <Button
        v-else-if="canSubscribeToPush"
        type="button"
        :disabled="isSubscribingToPush"
        variant="outline"
        size="sm"
        voice="sentence"
        class="w-full"
        @click="handleSubscribe"
      >
        <Loader2 v-if="isSubscribingToPush" class="size-3.5 animate-spin" />
        <BellPlus v-else class="size-3.5" />
        {{ $t('notifications.channels.push_enable') }}
      </Button>
    </div>

    <div class="flex items-center justify-between gap-2 px-4 pt-3 pb-1">
      <h3 class="text-[11px] font-bold uppercase tracking-[0.18em] text-muted-foreground">
        {{ $t('notifications.push_devices.all_devices') }} · {{ devices.length }}
      </h3>
      <Button
        type="button"
        variant="ghost"
        size="sm"
        voice="sentence"
        class="h-8 px-2 text-xs pointer-coarse:min-h-11"
        :disabled="isLoading || isRefreshingSubscriptionStatus"
        @click="refreshDevices"
      >
        <RefreshCw :class="['size-3.5', { 'animate-spin': isLoading || isRefreshingSubscriptionStatus }]" />
        {{ $t('Atnaujinti') }}
      </Button>
    </div>

    <div v-if="isLoading" class="flex items-center justify-center py-6">
      <Loader2 class="size-5 animate-spin text-muted-foreground" />
    </div>

    <p v-else-if="devices.length === 0" class="flex items-center gap-2 px-4 pb-3 text-xs text-muted-foreground">
      <PhoneOff class="size-4 shrink-0" aria-hidden="true" />
      {{ $t('notifications.push_devices.no_devices') }}
    </p>

    <ul v-else class="pb-1">
      <li
        v-for="device in devices"
        :key="device.id"
        class="flex items-center justify-between gap-2 border-b border-border px-4 py-2 last:border-b-0"
      >
        <div class="flex min-w-0 items-center gap-2.5">
          <component :is="getDeviceIcon(device.device_name)" class="size-4 shrink-0 text-muted-foreground" aria-hidden="true" />
          <div class="min-w-0">
            <p class="truncate text-sm text-foreground">
              {{ deviceLabel(device.device_name, device.endpoint) || $t('notifications.push_devices.unknown_device') }}
              <span v-if="device.isCurrentDevice" class="text-xs text-muted-foreground">· {{ $t('notifications.push_devices.current_badge') }}</span>
            </p>
            <p v-if="device.created_at" class="text-xs text-muted-foreground">
              {{ formatDate(device.created_at) }}
            </p>
          </div>
        </div>
        <Button
          type="button"
          variant="ghost"
          size="sm"
          class="size-8 shrink-0 p-0 text-muted-foreground hover:bg-destructive/10 hover:text-destructive pointer-coarse:size-11"
          :disabled="removingDeviceId === device.id"
          :aria-label="$t('Ištrinti įrenginį')"
          @click="handleRemoveDevice(device)"
        >
          <Loader2 v-if="removingDeviceId === device.id" class="size-3.5 animate-spin" />
          <Trash2 v-else class="size-3.5" />
        </Button>
      </li>
    </ul>

    <div v-if="devices.length > 0" class="border-t border-border px-4 py-3">
      <Button
        type="button"
        :disabled="isSendingTest"
        variant="outline"
        size="sm"
        voice="sentence"
        class="w-full"
        @click="handleSendTestNotification"
      >
        <Loader2 v-if="isSendingTest" class="size-3.5 animate-spin" />
        <BellRing v-else class="size-3.5" />
        {{ $t('notifications.push_devices.send_test') }}
      </Button>
    </div>
  </FormPanel>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
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

import { useApiMutation } from '@/Composables/useApi';
import { usePWA, type PushSubscriptionDevice } from '@/Composables/usePWA';
import { FormPanel } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { formatDateTime } from '@/Utils/dateTime';
import { deviceLabel } from '@/Utils/pushDevice';
import ISimpleIconsApple from '~icons/simple-icons/apple';
import ISimpleIconsLinux from '~icons/simple-icons/linux';
import ISimpleIconsWindows from '~icons/simple-icons/windows';

const {
  pushSupported,
  pushPermission,
  canSubscribeToPush,
  hasPushSubscription,
  isSubscribingToPush,
  isUnsubscribingFromPush,
  isRefreshingSubscriptionStatus,
  subscribeToPush,
  unsubscribeFromPush,
  removeSubscriptionById,
  fetchPushSubscriptions,
  refreshSubscriptionStatus,
  serviceWorkerReady,
} = usePWA();

const statusLabel = computed(() => {
  if (hasPushSubscription.value) return $t('notifications.push_devices.status_enabled');
  if (!pushSupported.value) return $t('notifications.push_devices.status_not_supported');
  if (pushPermission.value === 'denied') return $t('notifications.push_devices.status_blocked');

  return $t('notifications.push_devices.status_disabled');
});

const statusClass = computed(() => {
  if (hasPushSubscription.value) return 'font-medium text-[var(--status-success)]';
  if (pushPermission.value === 'denied') return 'font-medium text-[var(--status-danger)]';

  return 'text-muted-foreground';
});

const devices = ref<PushSubscriptionDevice[]>([]);
const isLoading = ref(false);
const removingDeviceId = ref<number | null>(null);
const isSendingTest = ref(false);

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
    // Removing only the server row would leave this browser subscribed and reporting "įjungti".
    const success = device.isCurrentDevice
      ? await unsubscribeFromPush()
      : await removeSubscriptionById(device.id);
    if (success) {
      devices.value = devices.value.filter(d => d.id !== device.id);
    }
  }
  finally {
    removingDeviceId.value = null;
  }
};

// Send test notification
// The server answers with how many devices the push service accepted; useApiMutation toasts it.
const handleSendTestNotification = async () => {
  isSendingTest.value = true;
  try {
    const { execute } = useApiMutation(route('push-subscription.test'));
    await execute();
    await loadDevices();
  }
  finally {
    isSendingTest.value = false;
  }
};

onMounted(() => {
  loadDevices();
});
</script>
