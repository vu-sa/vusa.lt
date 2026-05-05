<template>
  <div
    class="group relative flex items-center gap-4 p-4 rounded-xl border transition-all duration-200 cursor-pointer"
    :class="[
      notification.read_at
        ? 'bg-white dark:bg-zinc-900 border-zinc-200 dark:border-zinc-800 hover:border-zinc-300 dark:hover:border-zinc-700'
        : 'bg-blue-50/50 dark:bg-blue-950/20 border-blue-200/50 dark:border-blue-800/30 hover:border-blue-300 dark:hover:border-blue-700/50',
      'hover:shadow-sm'
    ]"
    @click="handleNavigate"
  >
    <!-- Unread indicator dot -->
    <div
      v-if="!notification.read_at"
      class="absolute left-2 top-1/2 -translate-y-1/2 size-1.5 rounded-full bg-blue-500"
    />

    <!-- Icon -->
    <div
      :class="[
        'flex items-center justify-center size-11 rounded-xl shrink-0',
        colors.combined
      ]"
    >
      <component :is="icon" class="size-5" />
    </div>

    <!-- Content -->
    <div class="flex-1 min-w-0 space-y-1">
      <!-- Header with avatar and title -->
      <div class="flex items-center gap-2">
        <img
          v-if="notification.data.subject?.image"
          :src="notification.data.subject.image"
          :alt="notification.data.subject.name"
          class="size-5 rounded-full object-cover ring-2 ring-white dark:ring-zinc-900"
        >
        <h4
          class="text-sm truncate"
          :class="notification.read_at
            ? 'font-medium text-zinc-700 dark:text-zinc-300'
            : 'font-semibold text-zinc-900 dark:text-zinc-100'"
        >
          {{ title }}
        </h4>
      </div>

      <!-- Body -->
      <p
        class="text-sm text-zinc-600 dark:text-zinc-400 line-clamp-2"
        v-html="message"
      />

      <!-- Footer with timestamp and actions on mobile -->
      <div class="flex items-center justify-between pt-1">
        <span class="text-xs text-zinc-500 dark:text-zinc-500">
          {{ formattedTime }}
        </span>

        <!-- Mobile actions -->
        <div class="flex items-center gap-1 sm:hidden">
          <button
            v-if="!notification.read_at"
            class="p-1.5 rounded-md text-emerald-600 hover:bg-emerald-100 dark:text-emerald-400 dark:hover:bg-emerald-900/30"
            :title="$t('Pažymėti kaip skaitytą')"
            @click.stop="emit('markAsRead', notification.id)"
          >
            <IFluentCheckmark24Filled class="size-4" />
          </button>
          <button
            class="p-1.5 rounded-md text-red-600 hover:bg-red-100 dark:text-red-400 dark:hover:bg-red-900/30"
            :title="$t('Ištrinti')"
            @click.stop="emit('delete', notification.id)"
          >
            <IFluentDelete24Regular class="size-4" />
          </button>
        </div>
      </div>
    </div>

    <!-- Desktop actions (show on hover) -->
    <div
      class="hidden sm:flex items-center gap-1 shrink-0 opacity-0 group-hover:opacity-100 transition-opacity duration-200"
    >
      <Tooltip v-if="!notification.read_at">
        <TooltipTrigger as-child>
          <button
            class="p-2 rounded-lg text-emerald-600 hover:bg-emerald-100 dark:text-emerald-400 dark:hover:bg-emerald-900/30 transition-colors"
            @click.stop="emit('markAsRead', notification.id)"
          >
            <IFluentCheckmark24Filled class="size-4" />
          </button>
        </TooltipTrigger>
        <TooltipContent>{{ $t('Pažymėti kaip skaitytą') }}</TooltipContent>
      </Tooltip>

      <Tooltip>
        <TooltipTrigger as-child>
          <button
            class="p-2 rounded-lg text-zinc-500 hover:text-red-600 hover:bg-red-100 dark:text-zinc-400 dark:hover:text-red-400 dark:hover:bg-red-900/30 transition-colors"
            @click.stop="emit('delete', notification.id)"
          >
            <IFluentDelete24Regular class="size-4" />
          </button>
        </TooltipTrigger>
        <TooltipContent>{{ $t('Ištrinti') }}</TooltipContent>
      </Tooltip>
    </div>

    <!-- Action URL indicator -->
    <div
      v-if="url"
      class="hidden sm:flex items-center shrink-0 text-zinc-400 dark:text-zinc-600 group-hover:text-zinc-600 dark:group-hover:text-zinc-400 transition-colors"
    >
      <IFluentArrowRight16Filled class="size-4" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import {
  getNotificationIcon,
  getNotificationColorClasses,
  getNotificationTitle,
  getNotificationMessage,
  getNotificationUrl,
  formatNotificationTime,
  type Notification,
} from '@/Composables/useNotificationFormatting';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/Components/ui/tooltip';
import IFluentCheckmark24Filled from '~icons/fluent/checkmark24-filled';
import IFluentDelete24Regular from '~icons/fluent/delete24-regular';
import IFluentArrowRight16Filled from '~icons/fluent/arrow-right16-filled';

const props = defineProps<{
  notification: Notification;
}>();

const emit = defineEmits<{
  (event: 'markAsRead', id: string): void;
  (event: 'delete', id: string): void;
}>();

const icon = computed(() => getNotificationIcon(props.notification));
const colors = computed(() => getNotificationColorClasses(props.notification));
const title = computed(() => getNotificationTitle(props.notification));
const message = computed(() => getNotificationMessage(props.notification));
const url = computed(() => getNotificationUrl(props.notification));
const formattedTime = computed(() => formatNotificationTime(props.notification));

const handleNavigate = () => {
  if (url.value) {
    if (!props.notification.read_at) {
      emit('markAsRead', props.notification.id);
    }
    router.visit(url.value);
  }
};
</script>
