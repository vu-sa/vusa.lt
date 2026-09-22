<template>
  <div
    class="group relative flex items-start gap-4 p-4 cursor-pointer transition-colors hover:bg-accent"
    :class="notification.read_at ? '' : 'bg-brand/5'"
    role="button"
    tabindex="0"
    @click="handleNavigate"
    @keydown.enter="handleNavigate"
    @keydown.space.prevent="handleNavigate"
  >
    <!-- Unread indicator -->
    <div
      v-if="!notification.read_at"
      class="absolute left-0 top-0 h-full w-0.5 bg-brand"
      aria-hidden="true"
    />

    <!-- Icon -->
    <div
      :class="[
        'flex items-center justify-center size-11 shrink-0 border border-border',
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
          class="size-5 object-cover"
        >
        <h4
          class="text-sm truncate"
          :class="notification.read_at ? 'font-medium text-muted-foreground' : 'font-semibold text-foreground'"
        >
          {{ title }}
        </h4>
      </div>

      <!-- Body -->
      <p
        class="text-sm text-muted-foreground line-clamp-2"
        v-html="message"
      />

      <!-- Context rows: what this is about -->
      <dl
        v-if="context.length"
        data-slot="notification-context"
        class="grid grid-cols-[auto_1fr] gap-x-3 gap-y-0.5 border-t border-border pt-2 text-xs"
      >
        <template v-for="row in context" :key="row.label">
          <dt class="text-muted-foreground">
            {{ row.label }}
          </dt>
          <dd class="min-w-0 truncate text-foreground">
            {{ row.value }}
          </dd>
        </template>
      </dl>

      <!-- The ask -->
      <div
        v-if="primaryAction"
        data-slot="notification-actions"
        class="flex flex-wrap items-center gap-2 pt-1"
      >
        <Button
          variant="brand-outline"
          size="sm"
          class="max-sm:h-11"
          @click.stop="visit(primaryAction.url)"
        >
          {{ primaryAction.label }}
        </Button>
        <Button
          v-if="secondaryAction"
          variant="ghost"
          size="sm"
          class="max-sm:h-11"
          @click.stop="visit(secondaryAction.url)"
        >
          {{ secondaryAction.label }}
        </Button>
      </div>

      <!-- Footer with timestamp and actions on mobile -->
      <div class="flex items-center justify-between pt-1">
        <span class="text-xs text-muted-foreground">
          {{ formattedTime }}
        </span>

        <!-- Mobile actions -->
        <div class="flex items-center gap-1 sm:hidden">
          <button
            v-if="!notification.read_at"
            type="button"
            class="p-1.5 text-status-success hover:bg-status-success-surface"
            :title="$t('Pažymėti kaip skaitytą')"
            @click.stop="emit('markAsRead', notification.id)"
          >
            <Check class="size-4" />
          </button>
          <button
            type="button"
            class="p-1.5 text-destructive hover:bg-destructive/10"
            :title="$t('Ištrinti')"
            @click.stop="emit('delete', notification.id)"
          >
            <Trash2 class="size-4" />
          </button>
        </div>
      </div>
    </div>

    <!-- Desktop actions (show on hover) -->
    <div
      class="hidden sm:flex items-center gap-1 shrink-0 opacity-0 group-hover:opacity-100 transition-opacity"
    >
      <Tooltip v-if="!notification.read_at">
        <TooltipTrigger as-child>
          <button
            type="button"
            class="p-2 text-status-success hover:bg-status-success-surface transition-colors"
            @click.stop="emit('markAsRead', notification.id)"
          >
            <Check class="size-4" />
          </button>
        </TooltipTrigger>
        <TooltipContent>{{ $t('Pažymėti kaip skaitytą') }}</TooltipContent>
      </Tooltip>

      <Tooltip>
        <TooltipTrigger as-child>
          <button
            type="button"
            class="p-2 text-muted-foreground hover:text-destructive hover:bg-destructive/10 transition-colors"
            @click.stop="emit('delete', notification.id)"
          >
            <Trash2 class="size-4" />
          </button>
        </TooltipTrigger>
        <TooltipContent>{{ $t('Ištrinti') }}</TooltipContent>
      </Tooltip>
    </div>

    <!-- Action URL indicator -->
    <div
      v-if="url"
      class="hidden sm:flex items-center shrink-0 text-muted-foreground group-hover:text-foreground transition-colors"
    >
      <ArrowRight class="size-4" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { ArrowRight, Check, Trash2 } from 'lucide-vue-next';

import {
  getNotificationIcon,
  getNotificationColorClasses,
  getNotificationTitle,
  getNotificationMessage,
  getNotificationUrl,
  getNotificationPrimaryAction,
  getNotificationSecondaryAction,
  getNotificationContext,
  formatNotificationTime,
  type Notification,
} from '@/Composables/useNotificationFormatting';
import { Button } from '@/Components/ui/button';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/Components/ui/tooltip';

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
const primaryAction = computed(() => getNotificationPrimaryAction(props.notification));
const secondaryAction = computed(() => getNotificationSecondaryAction(props.notification));
const context = computed(() => getNotificationContext(props.notification));
const formattedTime = computed(() => formatNotificationTime(props.notification));

const visit = (target: string) => {
  if (!props.notification.read_at) {
    emit('markAsRead', props.notification.id);
  }
  router.visit(target);
};

const handleNavigate = () => {
  if (url.value) {
    visit(url.value);
  }
};
</script>
