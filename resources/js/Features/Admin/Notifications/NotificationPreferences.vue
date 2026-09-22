<template>
  <SectionCard :title="$t('notifications.preferences.title')">
    <p class="mb-4 text-sm text-muted-foreground">
      {{ $t('notifications.preferences.description') }}
    </p>

    <!-- Global Mute -->
    <div class="space-y-4">
      <div class="flex items-center justify-between border border-border p-4">
        <div class="flex items-center gap-3">
          <BellOff class="size-5 text-muted-foreground" />
          <div>
            <p class="font-medium">
              {{ $t('notifications.preferences.mute_all') }}
            </p>
            <p class="text-sm text-muted-foreground">
              {{ $t('notifications.preferences.mute_all_description') }}
            </p>
          </div>
        </div>
        <div v-if="form.muted_until" class="flex items-center gap-2">
          <span class="text-sm text-destructive">
            {{ $t('notifications.preferences.muted_until', { date: formatDate(form.muted_until) }) }}
          </span>
          <Button variant="ghost" size="sm" @click="unmute">
            {{ $t('notifications.preferences.unmute') }}
          </Button>
        </div>
        <Select v-else v-model="muteSelection" @update:model-value="handleMuteChange">
          <SelectTrigger class="w-40">
            <SelectValue :placeholder="$t('notifications.preferences.mute')" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="1">
              1 {{ $t('hour') }}
            </SelectItem>
            <SelectItem value="4">
              4 {{ $t('hours') }}
            </SelectItem>
            <SelectItem value="24">
              24 {{ $t('hours') }}
            </SelectItem>
            <SelectItem value="168">
              {{ $t('1 week') }}
            </SelectItem>
          </SelectContent>
        </Select>
      </div>

      <!-- Digest Frequency -->
      <FormFieldWrapper id="digest-frequency" :label="$t('notifications.preferences.digest_frequency')">
        <div class="flex items-center gap-4">
          <Select v-model="digestFrequencyString" @update:model-value="updateDigestFrequency">
            <SelectTrigger class="w-40">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="1">
                {{ $t('Every hour') }}
              </SelectItem>
              <SelectItem value="4">
                {{ $t('Every 4 hours') }}
              </SelectItem>
              <SelectItem value="12">
                {{ $t('Every 12 hours') }}
              </SelectItem>
              <SelectItem value="24">
                {{ $t('Once daily') }}
              </SelectItem>
            </SelectContent>
          </Select>
          <p class="text-sm text-muted-foreground">
            {{ $t('notifications.preferences.digest_frequency_description') }}
          </p>
        </div>
      </FormFieldWrapper>

      <!-- Digest Email Selection -->
      <DigestEmailSelector
        v-model="form.digest_emails"
        :available-emails="availableDigestEmails"
      />

      <!-- Test email -->
      <div class="border-t border-border pt-4">
        <Button
          :disabled="testEmailLoading"
          variant="secondary"
          size="sm"
          @click="handleSendTestEmail"
        >
          <MailIcon v-if="!testEmailLoading" class="size-4" />
          <Loader2 v-else class="size-4 animate-spin" />
          {{ $t('notifications.test_email_button') }}
        </Button>
        <p class="mt-2 text-sm text-muted-foreground">
          {{ $t('notifications.test_email_hint') }}
        </p>
      </div>

      <!-- Category Channel Settings -->
      <div class="space-y-2">
        <h4 class="font-medium">
          {{ $t('notifications.preferences.category_settings') }}
        </h4>
        <p class="text-sm text-muted-foreground mb-4">
          {{ $t('notifications.preferences.category_settings_description') }}
        </p>

        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-border">
                <th class="text-left py-2 pr-4 font-medium">
                  {{ $t('notifications.category') }}
                </th>
                <th v-for="channel in notificationChannels" :key="channel.value" class="text-center py-2 px-2 font-medium">
                  {{ $t(`notifications.channels.${channel.value}`) }}
                </th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="category in notificationCategories"
                :key="category.value"
                class="border-b border-border last:border-0"
              >
                <td class="py-3 pr-4">
                  <div class="flex items-center gap-2">
                    <div :class="['size-6 flex items-center justify-center border border-border', getCategoryColorClass(category.color)]">
                      <component :is="getCategoryIcon(category.modelEnumKey)" class="size-3.5" />
                    </div>
                    {{ $t(`notifications.categories.${category.value}`) }}
                  </div>
                </td>
                <td v-for="channel in notificationChannels" :key="channel.value" class="text-center py-3 px-2">
                  <Checkbox
                    :model-value="getChannelEnabled(category.value, channel.value)"
                    @update:model-value="(val: boolean) => setChannelEnabled(category.value, channel.value, val)"
                  />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Reminder Settings -->
      <div class="space-y-4 border-t border-border pt-4">
        <h4 class="font-medium">
          {{ $t('notifications.preferences.reminder_settings') }}
        </h4>

        <!-- Task Reminder Days -->
        <FormFieldWrapper id="task-reminder-days" :label="$t('notifications.preferences.task_reminder_days')">
          <ToggleGroup
            type="multiple"
            :model-value="taskReminderDays.map(String)"
            @update:model-value="updateTaskReminderDays"
          >
            <ToggleGroupItem v-for="days in [7, 3, 1]" :key="days" :value="String(days)">
              {{ days }} {{ days === 1 ? $t('day') : $t('days') }}
            </ToggleGroupItem>
          </ToggleGroup>
          <p class="text-sm text-muted-foreground mt-2">
            {{ $t('notifications.preferences.task_reminder_days_description') }}
          </p>
        </FormFieldWrapper>

        <!-- Meeting Reminder Hours -->
        <FormFieldWrapper id="meeting-reminder-hours" :label="$t('notifications.preferences.meeting_reminder_hours')">
          <ToggleGroup
            type="multiple"
            :model-value="meetingReminderHours.map(String)"
            @update:model-value="updateMeetingReminderHours"
          >
            <ToggleGroupItem v-for="hours in [24, 12, 1]" :key="hours" :value="String(hours)">
              {{ hours }} {{ hours === 1 ? $t('hour') : $t('hours') }}
            </ToggleGroupItem>
          </ToggleGroup>
          <p class="text-sm text-muted-foreground mt-2">
            {{ $t('notifications.preferences.meeting_reminder_hours_description') }}
          </p>
        </FormFieldWrapper>
      </div>

      <Button :disabled="loading" @click="handleSubmit">
        <Save v-if="!loading" class="mr-2" />
        <Loader2 v-else class="mr-2 animate-spin" />
        {{ $t('Išsaugoti') }}
      </Button>
    </div>
  </SectionCard>
</template>

<script setup lang="ts">
import type { Component } from 'vue';
import { computed, ref, reactive, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import {
  BellOff,
  Bookmark,
  Building2,
  CalendarClock,
  ClipboardList,
  FileText,
  Loader2,
  Mail as MailIcon,
  MessageSquare,
  Puzzle,
  Save,
  User,
} from 'lucide-vue-next';

import { useApiMutation } from '@/Composables/useApi';
import { notificationColors, type NotificationColorKey } from '@/Composables/useNotificationFormatting';
import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import { SectionCard } from '@/Components/Patterns';
import DigestEmailSelector from '@/Features/Admin/Notifications/DigestEmailSelector.vue';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { ToggleGroup, ToggleGroupItem } from '@/Components/ui/toggle-group';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/Components/ui/select';

interface NotificationPreferences {
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

interface EmailOption {
  email: string;
  label: string;
  type: 'user' | 'duty';
}

interface CategoryOption {
  value: string;
  modelEnumKey: string;
  color: string;
}

interface ChannelOption {
  value: string;
  enabledByDefault: boolean;
}

const props = defineProps<{
  notificationPreferences: NotificationPreferences;
  notificationCategories: Record<string, CategoryOption>;
  notificationChannels: Record<string, ChannelOption>;
  availableDigestEmails: EmailOption[];
}>();

const loading = ref(false);
const testEmailLoading = ref(false);
const muteSelection = ref<string>('');

/**
 * Sends a sample digest to the saved digest addresses. The toasts carry the
 * server's message, so a transport failure shows the actual SMTP error.
 */
const handleSendTestEmail = async () => {
  testEmailLoading.value = true;

  try {
    const { execute } = useApiMutation(route('profile.sendTestNotificationEmail'));

    await execute();
  }
  finally {
    testEmailLoading.value = false;
  }
};

const form = useForm({
  channels: { ...props.notificationPreferences.channels },
  digest_frequency_hours: props.notificationPreferences.digest_frequency_hours,
  digest_emails: props.notificationPreferences.digest_emails || [],
  muted_until: props.notificationPreferences.muted_until,
  reminder_settings: {
    task_reminder_days: props.notificationPreferences.reminder_settings?.task_reminder_days || [7, 3, 1],
    meeting_reminder_hours: props.notificationPreferences.reminder_settings?.meeting_reminder_hours || [24, 1],
  },
});

const digestFrequencyString = computed({
  get: () => String(form.digest_frequency_hours),
  set: (val: string) => {
    form.digest_frequency_hours = parseInt(val, 10);
  },
});

const taskReminderDays = computed(() => form.reminder_settings?.task_reminder_days || [7, 3, 1]);
const meetingReminderHours = computed(() => form.reminder_settings?.meeting_reminder_hours || [24, 1]);

const updateDigestFrequency = (val: string) => {
  form.digest_frequency_hours = parseInt(val, 10);
};

const handleMuteChange = (hours: string) => {
  const hoursNum = parseInt(hours, 10);
  const mutedUntil = new Date();
  mutedUntil.setHours(mutedUntil.getHours() + hoursNum);
  form.muted_until = mutedUntil.toISOString();
  muteSelection.value = '';
};

const unmute = () => {
  form.muted_until = null;
};

const getChannelEnabled = (category: string, channel: string): boolean => {
  const channelDefault = props.notificationChannels[channel]?.enabledByDefault ?? true;
  return form.channels[category]?.[channel] ?? channelDefault;
};

const setChannelEnabled = (category: string, channel: string, enabled: boolean) => {
  if (!form.channels[category]) {
    form.channels[category] = {};
  }
  form.channels[category][channel] = enabled;
};

const updateTaskReminderDays = (values: string[]) => {
  form.reminder_settings.task_reminder_days = values.map(Number).sort((a, b) => b - a);
};

const updateMeetingReminderHours = (values: string[]) => {
  form.reminder_settings.meeting_reminder_hours = values.map(Number).sort((a, b) => b - a);
};

const getCategoryColorClass = (color: string): string => {
  const colorKey = (color in notificationColors ? color : 'neutral') as NotificationColorKey;
  return notificationColors[colorKey].combined;
};

const getCategoryIcon = (modelEnumKey: string) => {
  const iconMap: Record<string, Component> = {
    COMMENT: MessageSquare,
    TASK: ClipboardList,
    RESERVATION: Bookmark,
    MEETING: CalendarClock,
    FORM: FileText,
    USER: User,
    DUTY: Puzzle,
    TENANT: Building2,
  };
  return iconMap[modelEnumKey] || MessageSquare;
};

const formatDate = (dateStr: string): string => {
  const date = new Date(dateStr);
  return date.toLocaleString();
};

const handleSubmit = () => {
  loading.value = true;
  form.patch(route('profile.updateNotificationPreferences'), {
    preserveScroll: true,
    onSuccess: () => {
      loading.value = false;
    },
    onError: () => {
      loading.value = false;
    },
  });
};
</script>
