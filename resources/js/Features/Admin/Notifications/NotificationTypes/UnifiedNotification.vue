<template>
  <div class="flex items-center gap-3 w-full">
    <!-- Icon based on modelClass or category -->
    <div
      :class="[
        'flex items-center justify-center size-10 rounded-full shrink-0',
        colorClasses
      ]"
    >
      <component :is="notificationIcon" class="size-5" />
    </div>

    <!-- Content -->
    <div class="flex-1 min-w-0">
      <!-- Title with subject avatar if present -->
      <div class="flex items-center gap-2">
        <img
          v-if="notification.data.subject?.image"
          :src="notification.data.subject.image"
          :alt="notification.data.subject.name"
          class="size-5 rounded-full object-cover"
        />
        <p class="font-medium text-sm truncate">
          {{ notification.data.title || legacyTitle }}
        </p>
      </div>

      <!-- Body -->
      <p
        class="text-sm text-zinc-600 dark:text-zinc-400 line-clamp-2"
        v-html="notification.data.body || notification.data.text"
      />

      <!-- Timestamp -->
      <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
        {{ formatRelativeTime(new Date(notification.created_at)) }}
      </p>
    </div>

    <!-- Actions dropdown for muting (if applicable) -->
    <DropdownMenu v-if="canMute">
      <DropdownMenuTrigger as-child>
        <Button
          variant="ghost"
          size="icon-xs"
          class="shrink-0"
          @click.stop
        >
          <IFluentMoreVertical20Regular class="size-4" />
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end">
        <DropdownMenuItem @click.stop="handleMuteThread">
          <IFluentSpeakerMute24Regular class="mr-2 size-4" />
          {{ $t('notifications.mute_thread') }}
        </DropdownMenuItem>
      </DropdownMenuContent>
    </DropdownMenu>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { formatRelativeTime } from '@/Utils/IntlTime';
import { getModelIcon } from '@/Components/icons';
import { Button } from '@/Components/ui/button';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import type { ModelEnum } from '@/Types/enums';

// Default icons for notification categories
import IFluentComment24Regular from '~icons/fluent/comment24-regular';
import IFluentTaskListSquareLtr24Regular from '~icons/fluent/task-list-square-ltr24-regular';
import IFluentBookmark24Regular from '~icons/fluent/bookmark24-regular';
import IFluentDeviceMeetingRoomRemote24Regular from '~icons/fluent/device-meeting-room-remote24-regular';
import IFluentDocumentBulletList24Regular from '~icons/fluent/document-bullet-list24-regular';
import IFluentPerson24Regular from '~icons/fluent/person24-regular';
import IFluentPuzzlePiece24Regular from '~icons/fluent/puzzle-piece24-regular';
import IFluentAlert24Regular from '~icons/fluent/alert24-regular';
import IFluentMoreVertical20Regular from '~icons/fluent/more-vertical20-regular';
import IFluentSpeakerMute24Regular from '~icons/fluent/speaker-mute24-regular';

interface NotificationData {
  category?: string;
  modelClass?: string;
  title?: string;
  body?: string;
  url?: string;
  icon?: string;
  color?: string;
  actions?: Array<{ label: string; url: string }>;
  subject?: { modelClass: string; name: string; image?: string };
  object?: { modelClass: string; name: string; url: string; id?: string };
  // Legacy fields
  text?: string;
}

const props = defineProps<{
  notification: {
    id: string;
    type: string;
    data: NotificationData;
    created_at: string;
    read_at: string | null;
  };
}>();

const emit = defineEmits<{
  (event: 'muteThread', modelClass: string, modelId: string): void;
}>();

// Get the notification type from the full class name
const notificationType = computed(() => props.notification.type.split('\\').pop() || '');

// Map modelClass to ModelEnum key for icon lookup
const modelEnumKey = computed((): keyof typeof ModelEnum | null => {
  const modelClass = props.notification.data.modelClass || props.notification.data.object?.modelClass;

  if (!modelClass) return null;

  // Handle direct ModelEnum keys (e.g., 'TASK', 'MEETING')
  if (modelClass === modelClass.toUpperCase()) {
    return modelClass as keyof typeof ModelEnum;
  }

  // Map model class names to ModelEnum keys
  const mapping: Record<string, keyof typeof ModelEnum> = {
    Reservation: 'RESERVATION',
    ReservationResource: 'RESERVATION_RESOURCE',
    Task: 'TASK',
    Meeting: 'MEETING',
    Comment: 'COMMENT',
    Duty: 'DUTY',
    Form: 'FORM',
    User: 'USER',
    Institution: 'INSTITUTION',
  };

  return mapping[modelClass] || null;
});

// Get the icon component
const notificationIcon = computed(() => {
  // Try to get model-specific icon first
  if (modelEnumKey.value) {
    return getModelIcon(modelEnumKey.value);
  }

  // Fall back to category-based icons
  const category = props.notification.data.category;
  switch (category) {
    case 'comment':
      return IFluentComment24Regular;
    case 'task':
      return IFluentTaskListSquareLtr24Regular;
    case 'reservation':
      return IFluentBookmark24Regular;
    case 'meeting':
      return IFluentDeviceMeetingRoomRemote24Regular;
    case 'registration':
      return IFluentDocumentBulletList24Regular;
    case 'user':
      return IFluentPerson24Regular;
    case 'duty':
      return IFluentPuzzlePiece24Regular;
    default:
      return IFluentAlert24Regular;
  }
});

// Get color classes based on category
const colorClasses = computed(() => {
  const color = props.notification.data.color;
  const colorMap: Record<string, string> = {
    blue: 'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400',
    orange: 'bg-orange-100 text-orange-600 dark:bg-orange-900/30 dark:text-orange-400',
    purple: 'bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400',
    green: 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400',
    cyan: 'bg-cyan-100 text-cyan-600 dark:bg-cyan-900/30 dark:text-cyan-400',
    gray: 'bg-zinc-100 text-zinc-600 dark:bg-zinc-900/30 dark:text-zinc-400',
    amber: 'bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400',
    red: 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400',
  };

  return colorMap[color || 'gray'] || colorMap.gray;
});

// Legacy title fallback for old notifications
const legacyTitle = computed(() => {
  const type = notificationType.value;
  switch (type) {
    case 'ModelCommented':
    case 'CommentPostedNotification':
      return 'New Comment';
    case 'MemberRegistered':
    case 'MemberRegistrationNotification':
      return 'New Registration';
    default:
      return 'Notification';
  }
});

// Can mute if there's an object with modelClass and id
const canMute = computed(() => {
  const obj = props.notification.data.object;
  return obj?.modelClass && obj?.id;
});

const handleMuteThread = () => {
  const obj = props.notification.data.object;
  if (obj?.modelClass && obj?.id) {
    emit('muteThread', obj.modelClass, obj.id);
  }
};
</script>
