<template>
  <div class="space-y-4">
    <!-- Loading state -->
    <div v-if="loading" class="flex h-[200px] w-full items-center justify-center">
      <LoaderIcon class="h-6 w-6 animate-spin text-muted-foreground" />
    </div>

    <!-- Error state -->
    <div v-else-if="error" class="flex flex-col items-center justify-center py-8">
      <AlertTriangleIcon class="h-8 w-8 text-destructive" />
      <p class="mt-2 text-center text-muted-foreground">
        {{ $t('Nepavyko užkrauti veiklos') }}
      </p>
      <Button variant="outline" class="mt-4" @click="fetchActivities">
        {{ $t('Bandyti dar kartą') }}
      </Button>
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
                v-if="activity.properties"
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

    <!-- Load more button -->
    <div v-if="hasMoreActivities" class="flex justify-center pt-2">
      <Button variant="ghost" size="sm" :disabled="loadingMore" @click="loadMore">
        <LoaderIcon v-if="loadingMore" class="mr-2 h-4 w-4 animate-spin" />
        {{ $t('Krovimas daugiau') }}
      </Button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { formatDistance, parseISO } from 'date-fns';
import { lt, enUS } from 'date-fns/locale';
import axios from 'axios';

// UI components
import {
  Activity as ActivityIcon,
  AlertTriangle as AlertTriangleIcon,
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
  userId?: number;
  subjectType?: string;
  subjectId?: number;
  tenantId?: number;
  limit?: number;
}

const props = withDefaults(defineProps<Props>(), {
  userId: undefined,
  subjectType: undefined,
  subjectId: undefined,
  tenantId: undefined,
  limit: 10,
});

// Activity interface
interface Activity {
  id: number;
  description: string;
  causer_id: number | null;
  subject_type: string;
  subject_id: number;
  created_at: string;
  properties: Record<string, any>;
  user?: {
    id: number;
    name: string;
    avatar: string | null;
  };
  actionText?: string;
  link?: string;
}

// State
const activities = ref<Activity[]>([]);
const loading = ref(true);
const error = ref(false);
const page = ref(1);
const loadingMore = ref(false);
const hasMoreActivities = ref(true);
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

// Fetch activities from the API
const fetchActivities = async () => {
  loading.value = true;
  error.value = false;

  try {
    // Prepare query parameters
    const params = new URLSearchParams();
    params.append('page', page.value.toString());
    params.append('per_page', props.limit.toString());

    if (props.userId) {
      params.append('causer_id', props.userId.toString());
    }

    if (props.subjectType) {
      params.append('subject_type', props.subjectType);
    }

    if (props.subjectId) {
      params.append('subject_id', props.subjectId.toString());
    }

    if (props.tenantId) {
      params.append('tenant_id', props.tenantId.toString());
    }

    const response = await axios.get(`/api/activities?${params.toString()}`);

    if (!response.data) {
      throw new Error('Failed to fetch activities');
    }

    activities.value = response.data.data;
    hasMoreActivities.value = response.data.current_page < response.data.last_page;
  }
  catch (e) {
    console.error('Error fetching activities:', e);
    error.value = true;
  }
  finally {
    loading.value = false;
  }
};

// Load more activities
const loadMore = async () => {
  if (loadingMore.value) return;

  loadingMore.value = true;
  page.value++;

  try {
    // Prepare query parameters
    const params = new URLSearchParams();
    params.append('page', page.value.toString());
    params.append('per_page', props.limit.toString());

    if (props.userId) {
      params.append('causer_id', props.userId.toString());
    }

    if (props.subjectType) {
      params.append('subject_type', props.subjectType);
    }

    if (props.subjectId) {
      params.append('subject_id', props.subjectId.toString());
    }

    if (props.tenantId) {
      params.append('tenant_id', props.tenantId.toString());
    }

    const response = await axios.get(`/api/activities?${params.toString()}`);

    if (!response.data) {
      throw new Error('Failed to load more activities');
    }

    activities.value = [...activities.value, ...response.data.data];
    hasMoreActivities.value = response.data.current_page < response.data.last_page;
  }
  catch (e) {
    console.error('Error loading more activities:', e);
  }
  finally {
    loadingMore.value = false;
  }
};

// Get readable subject name
const getSubjectName = (activity: Activity): string => {
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

// Load activities on component mount
onMounted(() => {
  fetchActivities();
});
</script>
