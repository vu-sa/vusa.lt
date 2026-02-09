<template>
  <FormElement>
    <template #title>
      {{ $t('Push pranešimų įrenginiai') }}
    </template>
    <template #description>
      {{ $t('Valdykite įrenginius, kuriuose įjungti push pranešimai.') }}
    </template>

    <div class="space-y-4">
      <!-- Current device status -->
      <div class="flex items-center justify-between p-4 border rounded-lg dark:border-zinc-700">
        <div class="flex items-center gap-3">
          <component :is="currentDeviceIcon" class="size-5 text-zinc-500" />
          <div>
            <p class="font-medium">
              {{ $t('Šis įrenginys') }}
            </p>
            <p class="text-sm text-muted-foreground">
              {{ currentDeviceStatus }}
            </p>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <Button
            v-if="!hasPushSubscription && canSubscribeToPush"
            :disabled="isSubscribingToPush"
            variant="outline"
            size="sm"
            @click="handleSubscribe"
          >
            <IMdiBellPlus v-if="!isSubscribingToPush" class="size-4" />
            <IMdiLoading v-else class="size-4 animate-spin" />
            {{ $t('Įjungti') }}
          </Button>
          <Button
            v-else-if="hasPushSubscription"
            :disabled="isUnsubscribingFromPush"
            variant="outline"
            size="sm"
            @click="handleUnsubscribe"
          >
            <IMdiBellOff v-if="!isUnsubscribingFromPush" class="size-4" />
            <IMdiLoading v-else class="size-4 animate-spin" />
            {{ $t('Išjungti') }}
          </Button>
          <span v-else-if="pushPermission === 'denied'" class="text-sm text-destructive">
            {{ $t('Užblokuota naršyklėje') }}
          </span>
          <span v-else-if="!pushSupported" class="text-sm text-muted-foreground">
            {{ $t('Nepalaikoma') }}
          </span>
        </div>
      </div>

      <!-- Device list header with refresh button -->
      <div class="flex items-center justify-between">
        <h4 class="font-medium text-sm">
          {{ $t('Visi įrenginiai') }} ({{ devices.length }})
        </h4>
        <Button
          variant="ghost"
          size="sm"
          :disabled="isLoading || isRefreshingSubscriptionStatus"
          @click="refreshDevices"
        >
          <IMdiRefresh :class="['size-4', { 'animate-spin': isLoading || isRefreshingSubscriptionStatus }]" />
          {{ $t('Atnaujinti') }}
        </Button>
      </div>

      <!-- Devices list -->
      <div v-if="isLoading" class="flex items-center justify-center py-8">
        <IMdiLoading class="size-6 animate-spin text-muted-foreground" />
      </div>

      <div v-else-if="devices.length === 0" class="text-center py-8 text-muted-foreground">
        <IMdiCellphoneOff class="size-8 mx-auto mb-2 opacity-50" />
        <p class="text-sm">
          {{ $t('Nėra įrenginių su įjungtais push pranešimais') }}
        </p>
      </div>

      <div v-else class="space-y-2">
        <TransitionGroup name="device-list">
          <div
            v-for="device in devices"
            :key="device.id"
            class="flex items-center justify-between p-3 border rounded-lg dark:border-zinc-700 group"
            :class="{ 'bg-blue-50/50 dark:bg-blue-950/20 border-blue-200 dark:border-blue-800': device.isCurrentDevice }"
          >
            <div class="flex items-center gap-3">
              <component :is="getDeviceIcon(device.device_name)" class="size-5 text-zinc-500" />
              <div>
                <div class="flex items-center gap-2">
                  <p class="font-medium text-sm">
                    {{ device.device_name || $t('Nežinomas įrenginys') }}
                  </p>
                  <span
                    v-if="device.isCurrentDevice"
                    class="text-[10px] font-medium px-1.5 py-0.5 rounded bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300"
                  >
                    {{ $t('Šis') }}
                  </span>
                </div>
                <p class="text-xs text-muted-foreground">
                  {{ device.created_at ? $t('Pridėta') + ': ' + formatDate(device.created_at) : '' }}
                </p>
              </div>
            </div>
            <Button
              variant="ghost"
              size="sm"
              class="text-destructive hover:text-destructive hover:bg-destructive/10 opacity-0 group-hover:opacity-100 transition-opacity"
              :class="{ 'opacity-100': device.isCurrentDevice }"
              :disabled="removingDeviceId === device.id"
              @click="handleRemoveDevice(device)"
            >
              <IMdiLoading v-if="removingDeviceId === device.id" class="size-4 animate-spin" />
              <IMdiTrashCan v-else class="size-4" />
            </Button>
          </div>
        </TransitionGroup>
      </div>

      <!-- Test notification button -->
      <div v-if="hasAnyPushSubscription" class="pt-4 border-t dark:border-zinc-700">
        <Button
          :disabled="testNotificationLoading"
          variant="secondary"
          size="sm"
          @click="handleSendTestNotification"
        >
          <IMdiBellRing v-if="!testNotificationLoading" class="size-4" />
          <IMdiLoading v-else class="size-4 animate-spin" />
          {{ $t('Siųsti bandomąjį pranešimą') }}
        </Button>
        <p v-if="testNotificationSuccess" class="text-sm text-green-600 dark:text-green-400 mt-2">
          {{ $t('Bandomasis pranešimas išsiųstas!') }}
        </p>
      </div>
    </div>
  </FormElement>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, h } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import { usePWA, type PushSubscriptionDevice } from '@/Composables/usePWA';
import FormElement from '@/Components/AdminForms/FormElement.vue';
import { Button } from '@/Components/ui/button';
import IMdiBellPlus from '~icons/mdi/bell-plus';
import IMdiBellOff from '~icons/mdi/bell-off';
import IMdiBellRing from '~icons/mdi/bell-ring';
import IMdiLoading from '~icons/mdi/loading';
import IMdiRefresh from '~icons/mdi/refresh';
import IMdiTrashCan from '~icons/mdi/trash-can';
import IMdiCellphoneOff from '~icons/mdi/cellphone-off';
import IMdiCellphone from '~icons/mdi/cellphone';
import IMdiTablet from '~icons/mdi/tablet';
import IMdiLaptop from '~icons/mdi/laptop';
import IMdiDesktopClassic from '~icons/mdi/desktop-classic';
import IMdiApple from '~icons/mdi/apple';
import IMdiMicrosoftWindows from '~icons/mdi/microsoft-windows';
import IMdiLinux from '~icons/mdi/linux';
import IMdiWeb from '~icons/mdi/web';

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
const testNotificationLoading = ref(false);
const testNotificationSuccess = ref(false);

// Get current device icon based on status
const currentDeviceIcon = computed(() => {
  if (!pushSupported.value) return IMdiWeb;
  return getDeviceIcon(null);
});

// Get current device status text
const currentDeviceStatus = computed(() => {
  if (!pushSupported.value) {
    return $t('Push pranešimai nepalaikomi šioje naršyklėje');
  }
  if (pushPermission.value === 'denied') {
    return $t('Push pranešimai užblokuoti naršyklės nustatymuose');
  }
  if (hasPushSubscription.value) {
    return $t('Push pranešimai įjungti');
  }
  return $t('Push pranešimai išjungti');
});

// Get appropriate icon for device
const getDeviceIcon = (deviceName: string | null) => {
  if (!deviceName) return IMdiWeb;

  const name = deviceName.toLowerCase();
  if (name.includes('iphone') || name.includes('android')) return IMdiCellphone;
  if (name.includes('ipad') || name.includes('tablet')) return IMdiTablet;
  if (name.includes('mac')) return IMdiApple;
  if (name.includes('windows')) return IMdiMicrosoftWindows;
  if (name.includes('linux')) return IMdiLinux;
  if (name.includes('laptop')) return IMdiLaptop;
  return IMdiDesktopClassic;
};

// Format date
const formatDate = (dateString: string): string => {
  const date = new Date(dateString);
  return date.toLocaleDateString('lt-LT', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
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
  testNotificationLoading.value = true;
  testNotificationSuccess.value = false;

  try {
    const page = usePage();
    const csrfToken = (page.props.csrf_token as string) || '';

    const response = await fetch(route('push-subscription.test'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
      },
    });

    if (response.ok) {
      testNotificationSuccess.value = true;
      setTimeout(() => {
        testNotificationSuccess.value = false;
      }, 5000);
    }
  }
  catch (error) {
    console.error('Failed to send test notification:', error);
  }
  finally {
    testNotificationLoading.value = false;
  }
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
