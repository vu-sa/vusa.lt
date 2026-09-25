<template>
  <FormPage
    :title="$t('notifications.preferences.title')"
    :bar-title="$t('Pranešimų nustatymai')"
    :lead="$t('notifications.preferences.lead')"
    :back-href="route('profile')"
    :back-label="$t('Profilis')"
    :save-label="$t('Išsaugoti nustatymus')"
    :processing="form.processing"
    :dirty="form.isDirty"
    :errors="form.errors"
    :available-locales="[]"
    @submit="handleSubmit"
  >
    <Collapsible v-model:open="howOpen" class="border-y border-border" data-testid="how-it-works">
      <CollapsibleTrigger
        class="flex w-full items-center justify-between gap-3 py-3 text-left text-sm font-bold text-foreground pointer-coarse:min-h-11"
      >
        <span class="flex items-center gap-2">
          <Info class="size-4 shrink-0 text-brand" aria-hidden="true" />
          {{ $t('notifications.preferences.how_title') }}
        </span>
        <ChevronDown :class="['size-4 shrink-0 text-muted-foreground transition-transform', howOpen && 'rotate-180']" aria-hidden="true" />
      </CollapsibleTrigger>
      <CollapsibleContent>
        <ul class="flex max-w-prose list-disc flex-col gap-1.5 pb-4 pl-5 text-xs leading-snug text-pretty text-muted-foreground">
          <li v-for="key in howKeys" :key>
            {{ $t(`notifications.preferences.${key}`) }}
          </li>
        </ul>
      </CollapsibleContent>
    </Collapsible>

    <p
      v-if="!pushAvailable"
      data-testid="push-unavailable"
      class="flex items-start gap-2 border border-[var(--status-attention-border)] bg-[var(--status-attention-surface)] p-3 text-xs leading-snug text-[var(--status-attention)]"
    >
      <Smartphone class="size-4 shrink-0" aria-hidden="true" />
      {{ $t('notifications.preferences.push_no_devices') }}
    </p>

    <FormPanel
      v-for="section in sections"
      :key="section.value"
      :title="$t(`notifications.categories.${section.value}`)"
      :icon="section.icon"
      title-class="text-brand"
      flush
      data-slot="notification-section"
    >
      <template #action>
        <DropdownMenu>
          <DropdownMenuTrigger as-child>
            <button
              type="button"
              data-testid="section-all"
              :class="[controlVariants({ size: 'sm', voice: 'sentence' }), 'h-7 gap-1 px-2 font-medium pointer-coarse:min-h-11']"
              :aria-label="$t('notifications.preferences.section_all_label')"
            >
              {{ $t('notifications.preferences.section_all') }}
              <ChevronDown class="size-3.5" aria-hidden="true" />
            </button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end" class="w-56">
            <DropdownMenuLabel class="text-xs text-muted-foreground">
              {{ $t('notifications.preferences.section_all_label') }}
            </DropdownMenuLabel>
            <template v-if="section.editableTypes.length > 0">
              <DropdownMenuItem
                v-for="option in emailOptions"
                :key="option.value"
                :data-section-email="option.value"
                @select="setSectionEmail(section.editableTypes, option.value)"
              >
                <component :is="option.icon" class="size-4" aria-hidden="true" />
                {{ option.label }}
              </DropdownMenuItem>
              <DropdownMenuSeparator />
            </template>
            <DropdownMenuItem :disabled="!pushAvailable" @select="setSectionPush(section.types, true)">
              <BellRing class="size-4" aria-hidden="true" />
              {{ $t('notifications.preferences.push_on_all') }}
            </DropdownMenuItem>
            <DropdownMenuItem :disabled="!pushAvailable" @select="setSectionPush(section.types, false)">
              <BellOff class="size-4" aria-hidden="true" />
              {{ $t('notifications.preferences.push_off_all') }}
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      </template>

      <NotificationTypeRow
        v-for="type in section.types"
        :key="type.value"
        :type
        :email="form.types[type.value].email"
        :push="form.types[type.value].push"
        :push-available
        @update:email="value => form.types[type.value].email = value"
        @update:push="value => form.types[type.value].push = value"
      >
        <ReminderChips
          v-if="type.value === 'task_reminder'"
          :label="$t('notifications.preferences.task_reminder_days')"
          :options="[7, 3, 1]"
          :unit="$t('d.')"
          :model-value="form.reminder_settings.task_reminder_days"
          @update:model-value="value => form.reminder_settings.task_reminder_days = value"
        />
        <ReminderChips
          v-else-if="type.value === 'meeting_reminder'"
          :label="$t('notifications.preferences.meeting_reminder_hours')"
          :options="[24, 12, 1]"
          :unit="$t('val.')"
          :model-value="form.reminder_settings.meeting_reminder_hours"
          @update:model-value="value => form.reminder_settings.meeting_reminder_hours = value"
        />
      </NotificationTypeRow>
    </FormPanel>

    <div class="flex justify-end">
      <Button type="button" variant="ghost" size="sm" voice="sentence" data-testid="reset-defaults" @click="resetOpen = true">
        <RotateCcw class="size-3.5" aria-hidden="true" />
        {{ $t('notifications.preferences.reset') }}
      </Button>
    </div>

    <ConfirmDialog
      v-model:open="resetOpen"
      :title="$t('notifications.preferences.reset_title')"
      :description="$t('notifications.preferences.reset_description')"
      :confirm-label="$t('notifications.preferences.reset')"
      @confirm="resetToDefaults"
    />

    <template #aside>
      <FormPanel :title="$t('notifications.preferences.mute_all')" :icon="BellOff" title-class="text-brand">
        <template v-if="mutedUntil">
          <p data-testid="muted-banner" class="flex items-center gap-1.5 text-sm font-medium text-[var(--status-attention)]">
            <BellOff class="size-4 shrink-0" aria-hidden="true" />
            {{ $t('notifications.preferences.muted_until', { date: formatDateTime(mutedUntil) }) }}
          </p>
          <Button type="button" variant="outline" size="sm" voice="sentence" class="w-full" data-testid="unmute" @click="mute(null)">
            <Bell class="size-3.5" aria-hidden="true" />
            {{ $t('notifications.preferences.unmute') }}
          </Button>
        </template>
        <FormSegmentedControl
          v-else
          :model-value="0"
          :options="muteDurationOptions"
          :aria-label="$t('notifications.preferences.mute_all')"
          test-id-prefix="mute"
          @update:model-value="hours => mute(hours)"
        />
        <p class="text-xs leading-snug text-pretty text-muted-foreground">
          {{ $t('notifications.preferences.mute_description') }}
        </p>
      </FormPanel>

      <FormPanel :title="$t('notifications.preferences.emails_title')" :icon="MailIcon" title-class="text-brand">
        <DigestEmailSelector
          v-model="form.emails"
          :available-emails
          :default-email
        />

        <FormFieldWrapper id="digest-frequency" :label="$t('notifications.preferences.digest_label')" :hint="$t('notifications.preferences.digest_hint')">
          <FormSegmentedControl
            v-model="form.digest_frequency_hours"
            :options="digestFrequencyOptions"
            :aria-label="$t('notifications.preferences.digest_label')"
            test-id-prefix="digest-frequency"
          />
        </FormFieldWrapper>

        <div class="flex flex-col gap-1.5 border-t border-border pt-4">
          <Button type="button" :disabled="testEmailLoading" variant="outline" size="sm" voice="sentence" class="w-full" @click="handleSendTestEmail">
            <Loader2 v-if="testEmailLoading" class="size-3.5 animate-spin" />
            <MailIcon v-else class="size-3.5" />
            {{ $t('notifications.test_email_button') }}
          </Button>
          <p class="text-xs leading-snug text-pretty text-muted-foreground">
            {{ $t('notifications.test_email_hint') }}
          </p>
        </div>
      </FormPanel>

      <PushDeviceManagement />
    </template>
  </FormPage>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Bell, BellOff, BellRing, ChevronDown, Info, Loader2, Mail as MailIcon, RotateCcw, Smartphone } from 'lucide-vue-next';

import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import FormPage from '@/Components/Layouts/FormPage.vue';
import { ConfirmDialog, FormPanel, FormSegmentedControl, type FormSegmentOption } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/Components/ui/collapsible';
import { controlVariants } from '@/Components/ui/control';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { useApiMutation } from '@/Composables/useApi';
import { usePWA } from '@/Composables/usePWA';
import { getEntityTypeDefinition } from '@/Constants/entityTypes';
import DigestEmailSelector from '@/Features/Admin/Notifications/DigestEmailSelector.vue';
import { emailDeliveryOptions, type EmailDeliveryValue } from '@/Features/Admin/Notifications/emailDelivery';
import NotificationTypeRow, { type NotificationTypeOption } from '@/Features/Admin/Notifications/NotificationTypeRow.vue';
import PushDeviceManagement from '@/Features/Admin/Notifications/PushDeviceManagement.vue';
import ReminderChips from '@/Features/Admin/Notifications/ReminderChips.vue';
import { formatDateTime } from '@/Utils/dateTime';

interface EmailOption {
  email: string;
  label: string;
  type: 'user' | 'duty';
}

const props = defineProps<{
  notificationTypes: NotificationTypeOption[];
  notificationPreferences: {
    digest_frequency_hours: number;
    emails: string[];
    muted_until: string | null;
    reminder_settings: {
      task_reminder_days: number[];
      meeting_reminder_hours: number[];
    };
  };
  availableEmails: EmailOption[];
  defaultEmail: string;
}>();

const howKeys = ['how_list', 'how_bell', 'how_email', 'how_tasks', 'how_meetings', 'how_followed', 'how_reservations', 'how_self', 'how_quiet', 'how_mute'];
const howOpen = ref(false);

const emailOptions = emailDeliveryOptions();

const { hasAnyPushSubscription } = usePWA();
const pushAvailable = computed(() => hasAnyPushSubscription.value);

const form = useForm({
  types: Object.fromEntries(
    props.notificationTypes.map(type => [type.value, { email: type.email, push: type.push }]),
  ) as Record<string, { email: EmailDeliveryValue; push: boolean }>,
  digest_frequency_hours: props.notificationPreferences.digest_frequency_hours,
  emails: [...props.notificationPreferences.emails],
  reminder_settings: {
    task_reminder_days: [...props.notificationPreferences.reminder_settings.task_reminder_days],
    meeting_reminder_hours: [...props.notificationPreferences.reminder_settings.meeting_reminder_hours],
  },
});

const sections = computed(() => {
  const grouped = new Map<string, NotificationTypeOption[]>();

  for (const type of props.notificationTypes) {
    grouped.set(type.section, [...(grouped.get(type.section) ?? []), type]);
  }

  return [...grouped.entries()].map(([value, types]) => ({
    value,
    types,
    icon: getEntityTypeDefinition(types[0].sectionModelEnumKey)?.icon ?? Bell,
    editableTypes: types.filter(type => type.lockedEmail === null),
  }));
});

const setSectionEmail = (types: NotificationTypeOption[], option: EmailDeliveryValue) => {
  for (const type of types) {
    form.types[type.value].email = option;
  }
};

const setSectionPush = (types: NotificationTypeOption[], push: boolean) => {
  for (const type of types) {
    form.types[type.value].push = push;
  }
};

const digestFrequencyOptions: FormSegmentOption<number>[] = [1, 4, 12, 24].map(hours => ({
  value: hours,
  label: `${hours} ${$t('val.')}`,
}));

const muteDurationOptions: FormSegmentOption<number>[] = [
  { value: 1, label: `1 ${$t('val.')}` },
  { value: 4, label: `4 ${$t('val.')}` },
  { value: 24, label: `24 ${$t('val.')}` },
  { value: 168, label: $t('1 sav.') },
];

const mutedUntil = computed(() => props.notificationPreferences.muted_until);

// Muting applies at once, apart from the form: it answers "stop now", not "change my settings".
const mute = (hours: number | null) => {
  router.patch(route('profile.muteNotifications'), { hours }, { preserveScroll: true, preserveState: true });
};

const resetOpen = ref(false);

// A fresh page state, so the form picks up the restored defaults instead of the edited values.
const resetToDefaults = () => {
  resetOpen.value = false;
  router.delete(route('profile.resetNotificationPreferences'), { preserveScroll: true, preserveState: false });
};

const testEmailLoading = ref(false);

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
