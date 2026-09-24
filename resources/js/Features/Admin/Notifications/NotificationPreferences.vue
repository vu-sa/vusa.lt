<template>
  <NotificationCategoryTable
    :categories="notificationCategories"
    :channels="notificationChannels"
    :form-channels="notificationPreferences.channels"
    @update:channel="handleChannelUpdate"
  />
</template>

<script setup lang="ts">
import NotificationCategoryTable from './NotificationCategoryTable.vue';

export interface NotificationPreferences {
  channels: Record<string, Record<string, boolean>>;
  digest_frequency_hours: number;
  digest_emails: string[];
  muted_until: string | null;
  muted_threads: Record<string, string[]>;
  reminder_settings: {
    task_reminder_days: number[];
    meeting_reminder_hours: number[];
  };
}

export interface CategoryOption {
  value: string;
  modelEnumKey: string;
  color: string;
}

export interface ChannelOption {
  value: string;
  enabledByDefault: boolean;
}

export interface EmailOption {
  email: string;
  label: string;
  type: 'user' | 'duty';
}

defineProps<{
  notificationPreferences: NotificationPreferences;
  notificationCategories: Record<string, CategoryOption>;
  notificationChannels: Record<string, ChannelOption>;
  availableDigestEmails?: EmailOption[];
}>();

const emit = defineEmits<{
  'update:channel': [category: string, channel: string, enabled: boolean];
}>();

const handleChannelUpdate = (category: string, channel: string, enabled: boolean) => {
  emit('update:channel', category, channel, enabled);
};
</script>
