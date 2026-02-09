<template>
  <FormElement>
    <template #title>
      {{ $t('notifications.preferences.title') }}
    </template>
    <template #description>
      {{ $t('notifications.preferences.description') }}
    </template>

    <!-- Global Mute -->
    <div class="space-y-4">
      <div class="flex items-center justify-between p-4 border rounded-lg dark:border-zinc-700">
        <div class="flex items-center gap-3">
          <IFluentAlertOff24Regular class="size-5 text-zinc-500" />
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
              <tr class="border-b dark:border-zinc-700">
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
                class="border-b dark:border-zinc-700 last:border-0"
              >
                <td class="py-3 pr-4">
                  <div class="flex items-center gap-2">
                    <div :class="['size-6 rounded-full flex items-center justify-center', getCategoryColorClass(category.color)]">
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
      <div class="space-y-4 pt-4 border-t dark:border-zinc-700">
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

        <!-- Calendar Reminder Hours -->
        <FormFieldWrapper id="calendar-reminder-hours" :label="$t('notifications.preferences.calendar_reminder_hours')">
          <ToggleGroup
            type="multiple"
            :model-value="calendarReminderHours.map(String)"
            @update:model-value="updateCalendarReminderHours"
          >
            <ToggleGroupItem v-for="hours in [24, 12, 1]" :key="hours" :value="String(hours)">
              {{ hours }} {{ hours === 1 ? $t('hour') : $t('hours') }}
            </ToggleGroupItem>
          </ToggleGroup>
          <p class="text-sm text-muted-foreground mt-2">
            {{ $t('notifications.preferences.calendar_reminder_hours_description') }}
          </p>
        </FormFieldWrapper>
      </div>

      <Button :disabled="loading" @click="handleSubmit">
        <IFluentSave24Regular v-if="!loading" class="mr-2" />
        <IMdiLoading v-else class="mr-2 animate-spin" />
        {{ $t('Išsaugoti') }}
      </Button>
    </div>
  </FormElement>
</template>

<script setup lang="ts">
import { computed, ref, reactive, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import FormElement from '@/Components/AdminForms/FormElement.vue';
import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
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
import IFluentAlertOff24Regular from '~icons/fluent/alert-off-24-regular';
import IFluentSave24Regular from '~icons/fluent/save-24-regular';
import IMdiLoading from '~icons/mdi/loading';

// Category icons
import IFluentComment24Regular from '~icons/fluent/comment-24-regular';
import IFluentTaskListSquareLtr24Regular from '~icons/fluent/task-list-square-ltr24-regular';
import IFluentBookmark24Regular from '~icons/fluent/bookmark24-regular';
import IFluentDeviceMeetingRoomRemote24Regular from '~icons/fluent/device-meeting-room-remote24-regular';
import IFluentDocumentBulletList24Regular from '~icons/fluent/document-bullet-list24-regular';
import IFluentPerson24Regular from '~icons/fluent/person24-regular';
import IFluentPuzzlePiece24Regular from '~icons/fluent/puzzle-piece24-regular';
import IFluentBuilding24Regular from '~icons/fluent/building24-regular';
import IFluentNews24Regular from '~icons/fluent/news-24-regular';
import IFluentCalendar24Regular from '~icons/fluent/calendar-24-regular';

interface NotificationPreferences {
  channels: Record<string, Record<string, boolean>>;
  digest_frequency_hours: number;
  digest_emails: string[];
  muted_until: string | null;
  muted_threads: Record<string, string[]>;
  reminder_settings: {
    task_reminder_days: number[];
    meeting_reminder_hours: number[];
    calendar_reminder_hours: number[];
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
const muteSelection = ref<string>('');

const form = useForm({
  channels: { ...props.notificationPreferences.channels },
  digest_frequency_hours: props.notificationPreferences.digest_frequency_hours,
  digest_emails: props.notificationPreferences.digest_emails || [],
  muted_until: props.notificationPreferences.muted_until,
  reminder_settings: {
    task_reminder_days: props.notificationPreferences.reminder_settings?.task_reminder_days || [7, 3, 1],
    meeting_reminder_hours: props.notificationPreferences.reminder_settings?.meeting_reminder_hours || [24, 1],
    calendar_reminder_hours: props.notificationPreferences.reminder_settings?.calendar_reminder_hours || [24],
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
const calendarReminderHours = computed(() => form.reminder_settings?.calendar_reminder_hours || [24]);

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

const updateCalendarReminderHours = (values: string[]) => {
  form.reminder_settings.calendar_reminder_hours = values.map(Number).sort((a, b) => b - a);
};

const getCategoryColorClass = (color: string): string => {
  const colorMap: Record<string, string> = {
    blue: 'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400',
    orange: 'bg-orange-100 text-orange-600 dark:bg-orange-900/30 dark:text-orange-400',
    purple: 'bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400',
    green: 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400',
    cyan: 'bg-cyan-100 text-cyan-600 dark:bg-cyan-900/30 dark:text-cyan-400',
    gray: 'bg-zinc-100 text-zinc-600 dark:bg-zinc-900/30 dark:text-zinc-400',
    amber: 'bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400',
    red: 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400',
    indigo: 'bg-indigo-100 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400',
    teal: 'bg-teal-100 text-teal-600 dark:bg-teal-900/30 dark:text-teal-400',
  };
  return colorMap[color] || colorMap.gray;
};

const getCategoryIcon = (modelEnumKey: string) => {
  const iconMap: Record<string, any> = {
    COMMENT: IFluentComment24Regular,
    TASK: IFluentTaskListSquareLtr24Regular,
    RESERVATION: IFluentBookmark24Regular,
    MEETING: IFluentDeviceMeetingRoomRemote24Regular,
    FORM: IFluentDocumentBulletList24Regular,
    USER: IFluentPerson24Regular,
    DUTY: IFluentPuzzlePiece24Regular,
    TENANT: IFluentBuilding24Regular,
    NEWS: IFluentNews24Regular,
    CALENDAR: IFluentCalendar24Regular,
  };
  return iconMap[modelEnumKey] || IFluentComment24Regular;
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
