<template>
  <FormPage
    :title="$t('notifications.preferences.title')"
    :bar-title="$t('Pranešimų nustatymai')"
    :lead="$t('notifications.preferences.description')"
    :back-href="route('profile')"
    :back-label="$t('Profilis')"
    :save-label="$t('Išsaugoti nustatymus')"
    :processing="form.processing"
    :dirty="form.isDirty"
    :errors="form.errors"
    :available-locales="[]"
    @submit="handleSubmit"
  >
    <!-- 1. Kanalai, kategorijos ir priminimai -->
    <FormSection :title="$t('Kokie pranešimai tave pasiekia?')">
      <NotificationCategoryTable
        :categories="notificationCategories"
        :channels="notificationChannels"
        :form-channels="form.channels"
        @update:channel="handleChannelUpdate"
      />

      <div class="space-y-4 border-t border-border pt-4">
        <!-- Task reminder days -->
        <FormFieldWrapper
          id="task-reminder-days"
          :label="$t('notifications.preferences.task_reminder_days')"
          :hint="$t('notifications.preferences.task_reminder_days_description')"
        >
          <div class="flex flex-wrap items-center gap-2" role="group" :aria-label="$t('notifications.preferences.task_reminder_days')">
            <button
              v-for="days in [7, 3, 1]"
              :key="days"
              type="button"
              :aria-pressed="isTaskDaySelected(days)"
              :class="controlVariants({ size: 'sm', active: isTaskDaySelected(days), voice: 'sentence' })"
              @click="toggleTaskReminderDay(days)"
            >
              <span>{{ days }} {{ days === 1 ? $t('d.') : $t('d.') }}</span>
            </button>
          </div>
        </FormFieldWrapper>

        <!-- Meeting reminder hours -->
        <FormFieldWrapper
          id="meeting-reminder-hours"
          :label="$t('notifications.preferences.meeting_reminder_hours')"
          :hint="$t('notifications.preferences.meeting_reminder_hours_description')"
        >
          <div class="flex flex-wrap items-center gap-2" role="group" :aria-label="$t('notifications.preferences.meeting_reminder_hours')">
            <button
              v-for="hours in [24, 12, 1]"
              :key="hours"
              type="button"
              :aria-pressed="isMeetingHourSelected(hours)"
              :class="controlVariants({ size: 'sm', active: isMeetingHourSelected(hours), voice: 'sentence' })"
              @click="toggleMeetingReminderHour(hours)"
            >
              <span>{{ hours }} {{ hours === 1 ? $t('val.') : $t('val.') }}</span>
            </button>
          </div>
        </FormFieldWrapper>

        <FormFieldWrapper
          id="followed-institutions-push"
          :label="$t('Sekamos institucijos')"
          :hint="$t('Kai sekamoje institucijoje sukuriamas posėdis ar užpildoma jo darbotvarkė, gausi naršyklės pranešimą. Institucijas seki institucijų sąraše.')"
        >
          <div class="flex items-center gap-3">
            <Switch
              id="followed-institutions-push"
              :model-value="form.followed_institutions.push"
              @update:model-value="value => form.followed_institutions.push = value"
            />
            <span class="text-sm text-muted-foreground">
              {{ form.followed_institutions.push ? $t('Naršyklės pranešimai įjungti') : $t('Tik pranešimų centre ir suvestinėje') }}
            </span>
          </div>
        </FormFieldWrapper>
      </div>
    </FormSection>

    <!-- 2. El. pašto suvestinė -->
    <FormSection :title="$t('El. pašto suvestinė')">
      <!-- Digest frequency -->
      <FormFieldWrapper id="digest-frequency" :label="$t('notifications.preferences.digest_frequency')">
        <div class="flex flex-wrap items-center gap-2" role="group" :aria-label="$t('notifications.preferences.digest_frequency')">
          <button
            v-for="freq in digestFrequencyOptions"
            :key="freq.value"
            type="button"
            :aria-pressed="form.digest_frequency_hours === freq.value"
            :class="controlVariants({ size: 'sm', active: form.digest_frequency_hours === freq.value, voice: 'sentence' })"
            @click="form.digest_frequency_hours = freq.value"
          >
            <span>{{ freq.label }}</span>
          </button>
        </div>
      </FormFieldWrapper>

      <!-- Digest email recipients -->
      <DigestEmailSelector
        v-model="form.digest_emails"
        :available-emails="availableDigestEmails"
      />

      <!-- Test email action -->
      <div class="space-y-2 border border-border bg-muted/10 p-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div>
            <p class="text-sm font-medium text-foreground">
              {{ $t('notifications.test_email_button') }}
            </p>
            <p class="text-xs text-muted-foreground">
              {{ $t('notifications.test_email_hint') }}
            </p>
          </div>
          <Button
            :disabled="testEmailLoading"
            variant="outline"
            size="sm"
            class="shrink-0"
            @click="handleSendTestEmail"
          >
            <MailIcon v-if="!testEmailLoading" class="size-4 mr-1.5" />
            <Loader2 v-else class="size-4 mr-1.5 animate-spin" />
            {{ $t('notifications.test_email_button') }}
          </Button>
        </div>
      </div>
    </FormSection>

    <template #aside>
      <!-- Nutildymas -->
      <FormPanel :title="$t('notifications.preferences.mute_all')" :icon="BellOff" title-class="text-brand">
        <div v-if="form.muted_until" class="space-y-3">
          <div class="border border-[var(--status-attention-border)] bg-[var(--status-attention-surface)] p-3 text-[var(--status-attention)] space-y-1">
            <div class="flex items-center gap-1.5 font-medium text-xs">
              <BellOff class="size-3.5 shrink-0" aria-hidden="true" />
              <span>{{ $t('notifications.preferences.muted_until', { date: formatDateTime(form.muted_until) }) }}</span>
            </div>
            <p class="text-[11px] text-muted-foreground">
              {{ $t('Pranešimai laikinai sustabdyti.') }}
            </p>
          </div>
          <Button variant="outline" size="sm" class="w-full text-xs" @click="unmute">
            {{ $t('notifications.preferences.unmute') }}
          </Button>
        </div>

        <div v-else class="space-y-3">
          <p class="text-xs text-muted-foreground leading-relaxed">
            {{ $t('notifications.preferences.mute_all_description') }}
          </p>
          <div class="space-y-1.5">
            <p class="text-[11px] font-bold uppercase tracking-wider text-muted-foreground">
              {{ $t('Nutildyti:') }}
            </p>
            <div class="flex flex-wrap items-center gap-1.5" role="group" :aria-label="$t('notifications.preferences.mute_all')">
              <button
                v-for="item in muteDurationOptions"
                :key="item.hours"
                type="button"
                :class="controlVariants({ size: 'sm', active: false, voice: 'sentence' })"
                @click="handleMuteChange(String(item.hours))"
              >
                <span>{{ item.label }}</span>
              </button>
            </div>
          </div>
        </div>
      </FormPanel>

      <!-- Naršyklės pranešimai (Push) -->
      <PushDeviceManagement />

      <!-- Apie pranešimus -->
      <FormPanel :title="$t('Apie pranešimus')" :icon="Info" title-class="text-brand">
        <div class="space-y-3 text-xs text-muted-foreground leading-relaxed">
          <div class="space-y-1">
            <p class="font-medium text-foreground flex items-center gap-1.5">
              <Globe class="size-3.5 text-muted-foreground" aria-hidden="true" />
              {{ $t('notifications.channels.in_app') }}
            </p>
            <p>{{ $t('Realiuoju laiku rodomi pranešimų varpelyje ir pranešimų centre.') }}</p>
          </div>
          <div class="space-y-1">
            <p class="font-medium text-foreground flex items-center gap-1.5">
              <Smartphone class="size-3.5 text-muted-foreground" aria-hidden="true" />
              {{ $t('notifications.channels.push') }}
            </p>
            <p>{{ $t('Pasiekia jūsų įrenginį tiesiogiai, net kai naršyklė nenaudojama.') }}</p>
          </div>
          <div class="space-y-1">
            <p class="font-medium text-foreground flex items-center gap-1.5">
              <MailIcon class="size-3.5 text-muted-foreground" aria-hidden="true" />
              {{ $t('notifications.channels.email_digest') }}
            </p>
            <p>{{ $t('Apibendrinta neskaitytų pranešimų suvestinė pasirinktu dažnumu.') }}</p>
          </div>
        </div>
        <div class="pt-2 border-t border-border">
          <Button as-child variant="ghost" size="sm" class="w-full justify-start text-xs font-semibold text-brand hover:text-brand hover:bg-muted/30">
            <Link :href="route('notifications.index')" class="flex items-center gap-2">
              <Bell class="size-3.5" aria-hidden="true" />
              <span>{{ $t('Visi pranešimai') }}</span>
            </Link>
          </Button>
        </div>
      </FormPanel>
    </template>
  </FormPage>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Bell, BellOff, Globe, Info, Loader2, Mail as MailIcon, Smartphone } from 'lucide-vue-next';

import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import FormPage from '@/Components/Layouts/FormPage.vue';
import { FormPanel, FormSection } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { controlVariants } from '@/Components/ui/control';
import { Switch } from '@/Components/ui/switch';
import { useApiMutation } from '@/Composables/useApi';
import DigestEmailSelector from '@/Features/Admin/Notifications/DigestEmailSelector.vue';
import NotificationCategoryTable from '@/Features/Admin/Notifications/NotificationCategoryTable.vue';
import PushDeviceManagement from '@/Features/Admin/Notifications/PushDeviceManagement.vue';
import { formatDateTime } from '@/Utils/dateTime';

interface NotificationPreferencesData {
  channels: Record<string, Record<string, boolean>>;
  digest_frequency_hours: number;
  digest_emails: string[];
  muted_until: string | null;
  muted_threads: Record<string, string[]>;
  reminder_settings: {
    task_reminder_days: number[];
    meeting_reminder_hours: number[];
  };
  followed_institutions?: { push: boolean };
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

interface EmailOption {
  email: string;
  label: string;
  type: 'user' | 'duty';
}

const props = defineProps<{
  notificationPreferences: NotificationPreferencesData;
  notificationCategories: Record<string, CategoryOption>;
  notificationChannels: Record<string, ChannelOption>;
  availableDigestEmails: EmailOption[];
}>();

const testEmailLoading = ref(false);

const form = useForm({
  channels: { ...props.notificationPreferences.channels },
  digest_frequency_hours: props.notificationPreferences.digest_frequency_hours,
  digest_emails: props.notificationPreferences.digest_emails || [],
  muted_until: props.notificationPreferences.muted_until,
  reminder_settings: {
    task_reminder_days: props.notificationPreferences.reminder_settings?.task_reminder_days || [7, 3, 1],
    meeting_reminder_hours: props.notificationPreferences.reminder_settings?.meeting_reminder_hours || [24, 1],
  },
  followed_institutions: {
    push: props.notificationPreferences.followed_institutions?.push ?? true,
  },
});

const taskReminderDays = computed(() => form.reminder_settings?.task_reminder_days || [7, 3, 1]);
const meetingReminderHours = computed(() => form.reminder_settings?.meeting_reminder_hours || [24, 1]);

const isTaskDaySelected = (days: number): boolean => {
  return taskReminderDays.value.includes(days);
};

const toggleTaskReminderDay = (days: number) => {
  const current = [...taskReminderDays.value];
  const idx = current.indexOf(days);
  if (idx >= 0) {
    current.splice(idx, 1);
  }
  else {
    current.push(days);
  }
  current.sort((a, b) => b - a);
  form.reminder_settings.task_reminder_days = current;
};

const isMeetingHourSelected = (hours: number): boolean => {
  return meetingReminderHours.value.includes(hours);
};

const toggleMeetingReminderHour = (hours: number) => {
  const current = [...meetingReminderHours.value];
  const idx = current.indexOf(hours);
  if (idx >= 0) {
    current.splice(idx, 1);
  }
  else {
    current.push(hours);
  }
  current.sort((a, b) => b - a);
  form.reminder_settings.meeting_reminder_hours = current;
};

const digestFrequencyOptions = computed(() => [
  { value: 1, label: $t('notifications.digest_frequency_1') },
  { value: 4, label: $t('notifications.digest_frequency_4') },
  { value: 12, label: $t('notifications.digest_frequency_12') },
  { value: 24, label: $t('notifications.digest_frequency_24') },
]);

const muteDurationOptions = computed(() => [
  { hours: 1, label: `1 ${$t('val.')}` },
  { hours: 4, label: `4 ${$t('val.')}` },
  { hours: 24, label: `24 ${$t('val.')}` },
  { hours: 168, label: $t('1 sav.') },
]);

const handleChannelUpdate = (category: string, channel: string, enabled: boolean) => {
  if (!form.channels[category]) {
    form.channels[category] = {};
  }
  form.channels[category][channel] = enabled;
};

const handleMuteChange = (hours: string) => {
  const hoursNum = parseInt(hours, 10);
  const mutedUntil = new Date();
  mutedUntil.setHours(mutedUntil.getHours() + hoursNum);
  form.muted_until = mutedUntil.toISOString();
};

const unmute = () => {
  form.muted_until = null;
};

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

const handleSubmit = () => {
  form.patch(route('profile.updateNotificationPreferences'), {
    preserveScroll: true,
    onSuccess: () => {
      form.defaults();
    },
  });
};

</script>
