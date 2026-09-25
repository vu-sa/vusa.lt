<template>
  <Popover v-model:open="isOpen">
    <PopoverTrigger as-child>
      <Button
        variant="ghost"
        size="icon"
        class="relative size-8 shrink-0 border border-border text-muted-foreground hover:border-brand hover:text-foreground pointer-coarse:size-11"
        data-tour="notifications-indicator"
        :aria-label="$t('Pranešimai')"
      >
        <Bell class="size-4" :class="{ 'animate-bell-swing': hasNewNotification }" />
        <Transition name="count" mode="out-in">
          <span :key="`count-${unreadNotificationsCount}`" class="sr-only" aria-live="polite">
            {{ unreadNotificationsCount }}
          </span>
        </Transition>
        <span
          v-if="unreadNotificationsCount > 0"
          class="absolute -right-1 -top-1 flex min-h-4 min-w-4 items-center justify-center bg-brand-fill px-0.5 text-[10px] font-bold text-brand-foreground"
          aria-live="polite"
        >
          {{ unreadNotificationsCount > 9 ? '9+' : unreadNotificationsCount }}
        </span>
        <span class="sr-only">{{ $t('Pranešimai') }}</span>
      </Button>
    </PopoverTrigger>
    <PopoverContent
      :class="[
        'flex max-h-[min(36rem,var(--reka-popover-content-available-height))] w-80 max-w-[calc(100vw-2rem)] flex-col overflow-hidden',
        'rounded-none border border-border bg-popover p-0 shadow-xl sm:w-96',
      ]"
      align="end"
      :collision-padding="16"
      :side-offset="8"
    >
      <!-- Header -->
      <div class="flex shrink-0 items-center justify-between gap-3 border-b border-border px-4 py-3">
        <h2 class="text-sm font-bold text-foreground">
          {{ $t('Pranešimai') }}
        </h2>
        <div class="flex items-center gap-1.5">
          <Button
            v-if="unreadNotificationsCount > 0"
            variant="ghost"
            size="xs"
            voice="sentence"
            class="text-xs font-bold uppercase tracking-wide text-brand hover:text-foreground pointer-coarse:min-h-11"
            @click="markAllAsRead"
          >
            {{ $t('Žymėti skaitytais') }}
          </Button>
          <SpotlightPopover
            :title="$t('notifications.preferences.spotlight_title')"
            :description="$t('notifications.preferences.spotlight_description')"
            :is-dismissed="!settingsSpotlight.isVisible.value"
            position="bottom"
            float
            @dismiss="settingsSpotlight.dismiss"
          >
            <Link
              :href="route('profile.notifications')"
              :title="$t('shell.account.notifications')"
              :class="[
                'flex size-7 items-center justify-center border border-border text-muted-foreground',
                'transition-colors hover:border-brand hover:text-foreground pointer-coarse:size-11',
              ]"
              @click="openSettings"
            >
              <Settings class="size-3.5" />
              <span class="sr-only">{{ $t('shell.account.notifications') }}</span>
            </Link>
          </SpotlightPopover>
        </div>
      </div>

      <!-- Notifications List -->
      <div class="max-h-96 min-h-0 flex-1 overflow-y-auto overscroll-contain">
        <div v-if="notifications.length > 0" class="divide-y divide-border/60">
          <div
            v-for="notification in notifications"
            :key="notification.id"
            class="group relative flex items-start gap-3 p-3.5 transition-colors hover:bg-secondary/60 sm:gap-3.5"
            :class="notification.read_at ? '' : 'bg-secondary/30'"
          >
            <!-- Square category icon box -->
            <div
              :class="[
                'mt-0.5 flex size-8 shrink-0 items-center justify-center border border-border',
                getNotificationColorClasses(notification).combined,
              ]"
            >
              <component :is="getNotificationIcon(notification)" class="size-4" />
            </div>

            <!-- Content Area -->
            <div
              class="min-w-0 flex-1 cursor-pointer focus-visible:outline-hidden focus-visible:ring-1 focus-visible:ring-brand"
              role="button"
              tabindex="0"
              @click="navigateToNotification(notification)"
              @keydown.enter="navigateToNotification(notification)"
              @keydown.space.prevent="navigateToNotification(notification)"
            >
              <!-- Eyebrow: Tag & Unread marker dot -->
              <div class="flex items-center gap-1.5">
                <span class="text-[10px] font-bold uppercase tracking-wide text-brand">
                  {{ getNotificationCategoryTag(notification) }}
                </span>
                <span
                  v-if="!notification.read_at"
                  class="size-1.5 shrink-0 bg-brand-fill"
                  aria-hidden="true"
                />
              </div>

              <!-- Title with avatar if present -->
              <div class="mt-0.5 flex items-center gap-1.5">
                <img
                  v-if="notification.data.subject?.image"
                  :src="notification.data.subject.image"
                  :alt="notification.data.subject.name"
                  class="size-4 shrink-0 object-cover"
                >
                <p
                  class="line-clamp-2 text-sm leading-snug text-pretty break-words"
                  :class="notification.read_at
                    ? 'font-medium text-muted-foreground'
                    : 'font-semibold text-foreground'"
                >
                  {{ getNotificationTitle(notification) }}
                </p>
              </div>

              <!-- Message body -->
              <p
                v-if="getNotificationMessage(notification)"
                class="mt-0.5 line-clamp-2 text-xs text-muted-foreground break-words"
                v-html="getNotificationMessage(notification)"
              />

              <!-- The ask action button -->
              <div
                v-if="getPrimaryAction(notification)"
                class="mt-2"
              >
                <Button
                  variant="outline"
                  size="xs"
                  voice="sentence"
                  class="pointer-coarse:min-h-11"
                  @click.stop="openAction(notification, getPrimaryAction(notification)!.url)"
                >
                  {{ getPrimaryAction(notification)!.label }}
                </Button>
              </div>

              <!-- Timestamp -->
              <p class="mt-1 text-xs text-muted-foreground">
                {{ formatNotificationTime(notification) }}
              </p>
            </div>

            <!-- Mark as read button (sibling) -->
            <div v-if="!notification.read_at" class="flex shrink-0 items-center self-start pt-0.5">
              <button
                type="button"
                :class="[
                  'flex size-7 items-center justify-center border border-border text-muted-foreground',
                  'transition-colors hover:border-brand hover:text-foreground pointer-coarse:size-11',
                ]"
                :title="$t('Pažymėti kaip skaitytą')"
                :aria-label="$t('Pažymėti kaip skaitytą')"
                @click.stop="markAsRead(notification.id)"
              >
                <Check class="size-3.5" />
              </button>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div
          v-else
          class="flex flex-col items-center justify-center p-8 text-center"
        >
          <div class="mb-3 flex size-10 items-center justify-center border border-border bg-secondary">
            <Bell class="size-5 text-muted-foreground" />
          </div>
          <p class="text-sm font-semibold text-foreground">
            {{ $t('Nėra naujų pranešimų') }}
          </p>
          <p class="mt-1 max-w-[200px] text-xs text-muted-foreground">
            {{ $t('Visi pranešimai perskaityti!') }}
          </p>
        </div>
      </div>

      <!-- Footer: v0 prototype style prominent link -->
      <Link
        :href="route('notifications.index')"
        :class="[
          'block shrink-0 border-t border-border px-4 py-3 text-center text-xs font-bold uppercase tracking-wide',
          'text-brand transition-colors hover:bg-secondary/60 pointer-coarse:min-h-11',
        ]"
        @click="isOpen = false"
      >
        {{ $t('notifications.view_all') }}
      </Link>
    </PopoverContent>
  </Popover>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Bell, Check, Settings } from 'lucide-vue-next';

import { useRealtimeNotifications } from '@/Composables/useRealtimeNotifications';
import { useUnreadNotificationCount } from '@/Composables/useUnreadNotificationCount';
import {
  getNotificationIcon,
  getNotificationColorClasses,
  getNotificationTitle,
  getNotificationMessage,
  getNotificationUrl,
  getNotificationPrimaryAction as getPrimaryAction,
  getNotificationCategoryTag,
  formatNotificationTime,
  type Notification,
} from '@/Composables/useNotificationFormatting';
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from '@/Components/ui/popover';
import { Button } from '@/Components/ui/button';
import SpotlightPopover from '@/Components/Onboarding/SpotlightPopover.vue';
import { useFeatureSpotlight } from '@/Composables/useFeatureSpotlight';

const isOpen = ref(false);

// Notification settings moved from categories to one row per notification.
const settingsSpotlight = useFeatureSpotlight('notification-settings-v2');

const openSettings = () => {
  isOpen.value = false;
  settingsSpotlight.dismiss();
};

const page = usePage();
const authUser = computed(() => page.props.auth?.user);

const { hasNewNotification } = useRealtimeNotifications();

const notifications = computed(() => {
  return (authUser.value?.unreadNotifications || []) as Notification[];
});

const unreadNotificationsCount = useUnreadNotificationCount();

const navigateToNotification = (notification: Notification) => {
  const url = getNotificationUrl(notification);
  if (url) {
    markAsRead(notification.id);
    isOpen.value = false;
    router.visit(url);
  }
};

const openAction = (notification: Notification, url: string) => {
  markAsRead(notification.id);
  isOpen.value = false;
  router.visit(url);
};

const markAsRead = async (id: string) => {
  await router.post(route('notifications.markAsRead', id), {}, {
    preserveState: true,
    preserveScroll: true,
  });
};

const markAllAsRead = () => {
  router.post(route('notifications.mark-as-read.all'), {}, {
    preserveState: true,
    preserveScroll: true,
  });
};
</script>

<style scoped>
@keyframes bell-swing {
  0% { transform: rotate(0deg); }
  15% { transform: rotate(12deg); }
  30% { transform: rotate(-10deg); }
  45% { transform: rotate(8deg); }
  60% { transform: rotate(-6deg); }
  75% { transform: rotate(4deg); }
  100% { transform: rotate(0deg); }
}

.animate-bell-swing {
  animation: bell-swing 0.8s ease;
  transform-origin: top center;
}

/* Count badge transition */
.count-enter-from,
.count-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}

.count-enter-active,
.count-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}

.count-enter-to,
.count-leave-from {
  opacity: 1;
  transform: translateY(0);
}
</style>
