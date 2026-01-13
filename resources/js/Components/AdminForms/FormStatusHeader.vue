<template>
  <div
    class="sticky top-0 z-40 -mx-4 -mt-4 mb-6 border-b bg-white/95 px-4 py-3 backdrop-blur-sm dark:bg-zinc-900/95 dark:border-zinc-800 sm:-mx-6 sm:px-6">
    <div class="flex flex-wrap items-center gap-3">
      <!-- Left side: Status toggle and publish time -->
      <div class="flex items-center gap-3">
        <!-- Visibility toggle -->
        <div class="flex items-center gap-2">
          <Switch id="status-toggle" :model-value="isPublished"
            @update:model-value="$emit('update:isPublished', $event)" />
          <Label for="status-toggle" class="cursor-pointer text-sm font-medium"
            :class="isPublished ? 'text-green-700 dark:text-green-400' : 'text-muted-foreground'">
            {{ isPublished ? $t('Paskelbta') : $t('Juodraštis') }}
          </Label>
        </div>

        <!-- Publish time (for News/Pages with scheduled publishing) -->
        <Popover v-if="showPublishTime">
          <PopoverTrigger as-child>
            <Button variant="ghost" size="sm" class="h-8 gap-2 text-sm font-normal"
              :class="publishTimeStatus === 'scheduled' ? 'text-amber-600 dark:text-amber-400' : 'text-muted-foreground'">
              <IFluentClock24Regular class="h-4 w-4" />
              <span v-if="publishTime" class="hidden sm:inline">
                {{ formattedPublishTime }}
              </span>
              <span v-else class="hidden sm:inline">
                {{ $t('Nustatyti laiką') }}
              </span>
            </Button>
          </PopoverTrigger>
          <PopoverContent class="w-auto p-0" align="start">
            <div class="p-3 border-b">
              <p class="text-sm font-medium">
                {{ $t('Paskelbimo laikas') }}
              </p>
              <p class="text-xs text-muted-foreground mt-1">
                {{ $t('Naujiena bus automatiškai paskelbta nurodytu laiku') }}
              </p>
            </div>
            <div class="p-3">
              <DateTimePicker :model-value="publishTime" @update:model-value="$emit('update:publishTime', $event)" />
            </div>
            <div v-if="publishTime" class="border-t p-3">
              <Button variant="ghost" size="sm" class="w-full text-muted-foreground"
                @click="$emit('update:publishTime', null)">
                {{ $t('Išvalyti laiką') }}
              </Button>
            </div>
          </PopoverContent>
        </Popover>
      </div>

      <!-- Spacer -->
      <div class="flex-1" />

      <!-- Right side: Links and Status badge -->
      <div class="flex items-center gap-2">
        <!-- Links -->
        <template v-if="!isCreate && links.length > 0">
          <div class="hidden items-center gap-1 sm:flex">
            <FormLinkButton v-for="(link, idx) in links" :key="idx" :url="link.url" :label="link.label"
              :icon="link.icon" :show-copy="link.showCopy !== false" compact />
          </div>

          <!-- Mobile: dropdown for links -->
          <DropdownMenu v-if="links.length > 0">
            <DropdownMenuTrigger as-child class="sm:hidden">
              <Button variant="outline" size="sm" class="h-8 gap-1.5">
                <IFluentLink24Regular class="h-4 w-4" />
                <span class="sr-only">{{ $t('Nuorodos') }}</span>
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" class="w-64">
              <DropdownMenuLabel class="text-xs font-medium text-muted-foreground">
                {{ $t('Nuorodos') }}
              </DropdownMenuLabel>
              <DropdownMenuSeparator />
              <DropdownMenuItem v-for="(link, idx) in links" :key="idx" as-child>
                <a :href="link.url" target="_blank" rel="noopener noreferrer" class="flex w-full items-center gap-2">
                  <component :is="link.icon" v-if="link.icon" class="h-4 w-4 shrink-0 text-muted-foreground" />
                  <IFluentGlobe24Regular v-else class="h-4 w-4 shrink-0 text-muted-foreground" />
                  <div class="flex flex-col gap-0.5 overflow-hidden">
                    <span v-if="link.label" class="text-xs font-medium">{{ link.label }}</span>
                    <span class="truncate font-mono text-xs text-muted-foreground">
                      {{ stripProtocol(link.url) }}
                    </span>
                  </div>
                  <IFluentArrowUpRight24Regular class="ml-auto h-3.5 w-3.5 shrink-0 text-muted-foreground" />
                </a>
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>

          <Separator orientation="vertical" class="h-6" />
        </template>

        <!-- Status badge with dot indicator -->
        <div class="flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium" :class="statusBadgeClass">
          <span class="relative flex h-2 w-2">
            <span v-if="isPublished && publishTimeStatus !== 'scheduled'"
              class="absolute inline-flex h-full w-full animate-ping rounded-full opacity-75" :class="statusDotClass" />
            <span class="relative inline-flex h-2 w-2 rounded-full" :class="statusDotClass" />
          </span>
          <span>{{ statusText }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, type Component } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import FormLinkButton from './FormLinkButton.vue';

import { Button } from '@/Components/ui/button';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { Label } from '@/Components/ui/label';
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';
import { Separator } from '@/Components/ui/separator';
import { Switch } from '@/Components/ui/switch';
import DateTimePicker from '@/Components/ui/date-picker/DateTimePicker.vue';

export interface FormLink {
  /** The URL to link to */
  url: string;
  /** Optional label (e.g., "Public", "Admin") */
  label?: string;
  /** Optional icon component */
  icon?: Component;
  /** Show copy button (default: true) */
  showCopy?: boolean;
}

const props = withDefaults(defineProps<{
  /** Whether the item is published (ON) or draft (OFF) - controls the switch */
  isPublished: boolean;
  /** Server-side publish state for badge display (if different from form state) */
  serverIsPublished?: boolean;
  /** Scheduled publish time (for News) */
  publishTime?: Date | string | null;
  /** Show publish time picker */
  showPublishTime?: boolean;
  /** Array of links to display (public, admin, etc.) */
  links?: FormLink[];
  /** Whether this is a create form (hides links) */
  isCreate?: boolean;
}>(), {
  showPublishTime: false,
  links: () => [],
  isCreate: false,
});

defineEmits<{
  (e: 'update:isPublished', value: boolean): void;
  (e: 'update:publishTime', value: Date | null): void;
}>();

// Utility to strip protocol
const stripProtocol = (url: string) => url.replace(/^https?:\/\//, '');

// Determine publish time status
const publishTimeStatus = computed(() => {
  if (!props.publishTime) return 'none';
  const publishDate = props.publishTime instanceof Date
    ? props.publishTime
    : new Date(props.publishTime);

  if (isNaN(publishDate.getTime())) return 'none';

  return publishDate > new Date() ? 'scheduled' : 'published';
});

// Format publish time for display
const formattedPublishTime = computed(() => {
  if (!props.publishTime) return '';

  const date = props.publishTime instanceof Date
    ? props.publishTime
    : new Date(props.publishTime);

  if (isNaN(date.getTime())) return '';

  const formatter = new Intl.DateTimeFormat(document.documentElement.lang || 'lt', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });

  return formatter.format(date);
});

// Badge shows SERVER state (original), switch shows FORM state (being edited)
const badgeIsPublished = computed(() => props.serverIsPublished ?? props.isPublished);

// Status badge styling and text
const statusBadgeClass = computed(() => {
  if (props.isCreate) {
    return 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400';
  }
  if (!badgeIsPublished.value) {
    return 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-400';
  }
  if (publishTimeStatus.value === 'scheduled') {
    return 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-400';
  }
  return 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-400';
});

// Status dot color
const statusDotClass = computed(() => {
  if (props.isCreate) {
    return 'bg-zinc-400 dark:bg-zinc-500';
  }
  if (!badgeIsPublished.value) {
    return 'bg-amber-500';
  }
  if (publishTimeStatus.value === 'scheduled') {
    return 'bg-blue-500';
  }
  return 'bg-green-500';
});

const statusText = computed(() => {
  if (props.isCreate) {
    return $t('Nesukurta');
  }
  if (!badgeIsPublished.value) {
    return $t('Juodraštis');
  }
  if (publishTimeStatus.value === 'scheduled') {
    return $t('Suplanuota');
  }
  return $t('Aktyvus');
});
</script>
