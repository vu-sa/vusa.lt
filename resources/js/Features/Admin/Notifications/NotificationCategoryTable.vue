<template>
  <div class="space-y-2">
    <div class="overflow-x-auto border border-border bg-background">
      <table class="w-full border-collapse text-sm">
        <thead>
          <tr class="border-b border-border bg-muted/40">
            <th class="py-3 px-4 text-left text-xs font-bold uppercase tracking-wider text-muted-foreground">
              {{ $t('notifications.category') }}
            </th>
            <th
              v-for="channel in channels"
              :key="channel.value"
              class="py-3 px-4 text-center text-xs font-bold uppercase tracking-wider text-muted-foreground"
            >
              <div class="inline-flex items-center gap-1.5">
                <component :is="getChannelIcon(channel.value)" class="size-3.5 text-muted-foreground" aria-hidden="true" />
                <span>{{ $t(`notifications.channels.${channel.value}`) }}</span>
              </div>
            </th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="category in categories"
            :key="category.value"
            class="border-t border-border hover:bg-muted/20 transition-colors"
          >
            <td class="py-3 px-4">
              <div class="flex items-center gap-3">
                <div :class="['size-7 shrink-0 flex items-center justify-center border border-border', getCategoryColorClass(category.color)]">
                  <component :is="getCategoryIcon(category.modelEnumKey)" class="size-3.5" aria-hidden="true" />
                </div>
                <span class="font-medium text-foreground">
                  {{ $t(`notifications.categories.${category.value}`) }}
                </span>
              </div>
            </td>
            <td
              v-for="channel in channels"
              :key="channel.value"
              class="py-3 px-4 text-center"
            >
              <div class="flex justify-center">
                <Checkbox
                  :model-value="getChannelEnabled(category.value, channel.value)"
                  :aria-label="`${$t(`notifications.categories.${category.value}`)} — ${$t(`notifications.channels.${channel.value}`)}`"
                  @update:model-value="(val: boolean) => setChannelEnabled(category.value, channel.value, val)"
                />
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="flex items-start gap-2 pt-1 text-xs text-muted-foreground">
      <Info class="size-3.5 shrink-0 mt-0.5" aria-hidden="true" />
      <span>{{ $t('Naršyklės pranešimams (Push) taip pat reikalingas naršyklės leidimas, kurį galite valdyti šoninėje skiltyje.') }}</span>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { Component } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import {
  Bookmark,
  Building2,
  CalendarClock,
  ClipboardList,
  FileText,
  Globe,
  Info,
  Mail,
  MessageSquare,
  Puzzle,
  Smartphone,
  User,
} from 'lucide-vue-next';

import { Checkbox } from '@/Components/ui/checkbox';
import { notificationColors, type NotificationColorKey } from '@/Composables/useNotificationFormatting';

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
  categories: Record<string, CategoryOption>;
  channels: Record<string, ChannelOption>;
  formChannels: Record<string, Record<string, boolean>>;
}>();

const emit = defineEmits<{
  'update:channel': [category: string, channel: string, enabled: boolean];
}>();

const getChannelEnabled = (category: string, channel: string): boolean => {
  const channelDefault = props.channels[channel]?.enabledByDefault ?? true;
  return props.formChannels[category]?.[channel] ?? channelDefault;
};

const setChannelEnabled = (category: string, channel: string, enabled: boolean) => {
  emit('update:channel', category, channel, enabled);
};

const getCategoryColorClass = (color: string): string => {
  const colorKey = (color in notificationColors ? color : 'neutral') as NotificationColorKey;
  return notificationColors[colorKey].combined;
};

const getCategoryIcon = (modelEnumKey: string): Component => {
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

const getChannelIcon = (channel: string): Component => {
  switch (channel) {
    case 'in_app':
      return Globe;
    case 'email_digest':
      return Mail;
    case 'push':
      return Smartphone;
    default:
      return Globe;
  }
};
</script>
