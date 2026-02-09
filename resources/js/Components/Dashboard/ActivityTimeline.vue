<template>
  <div class="space-y-4">
    <!-- Loading state -->
    <div v-if="loading" class="flex h-[200px] w-full items-center justify-center">
      <LoaderIcon class="h-6 w-6 animate-spin text-muted-foreground" />
    </div>

    <!-- Empty state -->
    <div v-else-if="activities.length === 0" class="flex flex-col items-center justify-center py-8">
      <ActivityIcon class="h-8 w-8 text-muted-foreground" />
      <p class="mt-2 text-center text-muted-foreground">
        {{ $t('Nerasta veiksmų') }}
      </p>
    </div>

    <!-- Activity timeline -->
    <ul v-else class="space-y-4">
      <li v-for="(activity, index) in activities" :key="activity.id" class="relative pl-6">
        <!-- Timeline connector -->
        <div class="absolute left-0 top-2 h-2 w-2 rounded-full border border-primary bg-background" />
        <div v-if="index < activities.length - 1" class="absolute bottom-0 left-[3.5px] top-4 w-[1px] bg-border" />

        <!-- Activity content -->
        <div class="space-y-1">
          <div class="flex items-center gap-2">
            <Avatar v-if="activity.user?.avatar" class="h-6 w-6">
              <AvatarImage :src="activity.user.avatar" :alt="activity.user?.name || 'User'" />
              <AvatarFallback>{{ getInitials(activity.user?.name) }}</AvatarFallback>
            </Avatar>
            <Avatar v-else class="h-6 w-6">
              <AvatarFallback>{{ getInitials(activity.user?.name) }}</AvatarFallback>
            </Avatar>
            <span class="font-medium">{{ activity.user?.name || $t('Nežinomas vartotojas') }}</span>
            <span class="text-muted-foreground">{{ activity.actionText }}</span>
            <span class="text-foreground">
              {{ getSubjectName(activity) }}
            </span>
          </div>
          <div v-if="activity.properties && showDetails" class="text-sm text-muted-foreground ml-8">
            <pre class="whitespace-pre-wrap text-xs">{{ formatProperties(activity.properties) }}</pre>
          </div>
          <div class="flex items-center justify-between">
            <div class="text-xs text-muted-foreground">
              {{ formatDate(activity.created_at) }}
            </div>
            <div class="flex gap-2">
              <Link v-if="activity.link" :href="activity.link" class="text-xs text-primary hover:underline">
                {{ $t('Peržiūrėti') }}
              </Link>
              <Button
                v-if="activity.properties && Object.keys(activity.properties).length > 0"
                variant="ghost"
                size="sm"
                class="h-6 text-xs px-2 py-0"
                @click="toggleDetails"
              >
                {{ showDetails ? $t('Slėpti detales') : $t('Rodyti detales') }}
              </Button>
            </div>
          </div>
        </div>
      </li>
    </ul>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { formatDistance, parseISO } from 'date-fns';
import { lt, enUS } from 'date-fns/locale';

// UI components
import {
  Activity as ActivityIcon,
  Loader as LoaderIcon,
} from 'lucide-vue-next';

import {
  Avatar,
  AvatarImage,
  AvatarFallback,
} from '@/Components/ui/avatar';
import { Button } from '@/Components/ui/button';

// Icons

// Props
interface Props {
  activities: any[];
  loading?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  activities: () => [],
  loading: false,
});

// State
const showDetails = ref(false);

// Get user locale for date formatting
const locale = ref(usePage().props.app.locale === 'lt' ? lt : enUS);

const toggleDetails = () => {
  showDetails.value = !showDetails.value;
};

// Format properties for better display
const formatProperties = (properties: Record<string, any>): string => {
  if (!properties) return '';

  // Check if there are changes to display
  if (properties.attributes || properties.old) {
    const result = [];

    // Get all attribute keys
    const keys = new Set([
      ...Object.keys(properties.attributes || {}),
      ...Object.keys(properties.old || {}),
    ]);

    // Compare old and new values
    keys.forEach((key) => {
      const oldValue = properties.old?.[key];
      const newValue = properties.attributes?.[key];

      if (oldValue !== newValue) {
        if (oldValue && newValue) {
          result.push(`${key}: ${JSON.stringify(oldValue)} → ${JSON.stringify(newValue)}`);
        }
        else if (oldValue) {
          result.push(`${key}: ${JSON.stringify(oldValue)} → removed`);
        }
        else {
          result.push(`${key}: added ${JSON.stringify(newValue)}`);
        }
      }
    });

    return result.join('\n');
  }

  // For other property types, just stringify
  return JSON.stringify(properties, null, 2);
};

// Get readable subject name
const getSubjectName = (activity: any): string => {
  if (!activity.subject_type) return '';

  // Extract the model name from the subject type
  const subjectName = activity.subject_type.split('\\').pop() || '';

  // Add subject ID if available
  return subjectName;
};

// Format the date in a user-friendly way
const formatDate = (dateString: string) => {
  try {
    const date = parseISO(dateString);
    return formatDistance(date, new Date(), {
      addSuffix: true,
      locale: locale.value,
    });
  }
  catch (e) {
    return dateString;
  }
};

// Get initials for avatar fallback
const getInitials = (name?: string): string => {
  if (!name) return 'U';

  return name
    .split(' ')
    .map(part => part[0])
    .join('')
    .toUpperCase()
    .substring(0, 2);
};
</script>
