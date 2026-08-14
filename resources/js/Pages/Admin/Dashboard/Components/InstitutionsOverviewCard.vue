<template>
  <Card data-tour="institution-card" :class="cardClasses" role="region" :aria-label="$t('Tavo institucijos')">
    <!-- Status indicator corner -->
    <div data-tour="institution-status" :class="statusIndicatorClasses" aria-hidden="true" />

    <CardHeader class="pb-3 relative z-10">
      <CardTitle class="flex items-center gap-2.5">
        <component :is="InstitutionIconFilled" :class="iconClasses" aria-hidden="true" />
        <span class="font-semibold">{{ $t('Tavo institucijos') }}</span>
        <span v-if="limitedInstitutions.length < institutions.length"
          class="text-xs px-2 py-1 rounded-full bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400 ml-auto font-medium">
          {{ limitedInstitutions.length }}/{{ institutions.length }}
        </span>
      </CardTitle>
    </CardHeader>

    <CardContent class="flex-1 relative z-10 space-y-4 pt-2">
      <!-- Institution List - improved spacing -->
      <div class="space-y-2">
        <InstitutionCompactCard v-for="inst in limitedInstitutions" :key="inst.id" :institution="inst"
          :show-actions="true" :can-schedule-meeting="true" :can-add-check-in="true"
          :follow-loading="followLoadingStates[String(inst.id)]"
          :mute-loading="muteLoadingStates[String(inst.id)]"
          @schedule-meeting="$emit('schedule-meeting', $event)" @add-check-in="handleAddCheckIn" @remove-active-check-in="handleRemoveActiveCheckIn"
          @toggle-follow="handleToggleFollow" @toggle-mute="handleToggleMute" />
      </div>
    </CardContent>

    <CardFooter :class="footerClasses" class="p-4 relative z-10">
      <div class="flex gap-3 w-full">
        <Button data-tour="all-institutions" size="sm" variant="outline" class="flex-1 font-medium" @click="$emit('show-all-modal')">
          <component :is="InstitutionIconFilled" class="h-3.5 w-3.5 mr-2" />
          {{ $t('Visos institucijos') }}
        </Button>
        <TooltipProvider>
          <Tooltip>
            <TooltipTrigger as-child>
              <Button data-tour="create-meeting" size="sm" variant="default" class="w-11 h-9" @click="$emit('create-meeting')">
                <component :is="MeetingIconFilled" class="h-3.5 w-3.5" />
              </Button>
            </TooltipTrigger>
            <TooltipContent>{{ $t('Naujas susitikimas') }}</TooltipContent>
          </Tooltip>
        </TooltipProvider>
      </div>
    </CardFooter>

    <!-- Create Check-in Dialog -->
    <AddCheckInDialog v-if="showCreateCheckIn" :open="!!showCreateCheckIn" :institution-id="showCreateCheckIn"
      :institution-name="checkInInstitutionName" :reload-props="['user', 'userInstitutions', 'tenantInstitutions']" @close="showCreateCheckIn = null" />
  </Card>
</template>

<script setup lang="ts">
import { computed, ref, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import type { AtstovavimasInstitution } from '../types';
import { useInstitutionSubscription } from '../Composables/useInstitutionSubscription';

import AddCheckInDialog from '@/Components/Institutions/AddCheckInDialog.vue';
import InstitutionCompactCard from '@/Components/Institutions/InstitutionCompactCard.vue';
import { Card, CardHeader, CardTitle, CardContent, CardFooter } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/Components/ui/tooltip';
import { InstitutionIconFilled, MeetingIconFilled } from '@/Components/icons';
import {
  useDashboardCardStyles,
  type UrgencyLevel,
} from '@/Composables/useDashboardCardStyles';

const props = defineProps<{
  institutions: AtstovavimasInstitution[];
  maxDisplayCount?: number;
  currentUserId?: string;
}>();

const emit = defineEmits<{
  'show-all-modal': [];
  'schedule-meeting': [institutionId: string];
  'show-institution-details': [institutionId: string];
  'create-meeting': [];
}>();

const showCreateCheckIn = ref<string | null>(null);
const actionLoading = reactive<Record<string, boolean>>({});

const institutionsNeedingAttention = computed(() => {
  return props.institutions.filter(inst => inst.activity_status.requires_action);
});

// Sort institutions by priority - attention needed institutions first
const sortedInstitutions = computed(() => {
  return [...props.institutions].sort((a, b) => {
    if (a.activity_status.priority !== b.activity_status.priority) {
      return b.activity_status.priority - a.activity_status.priority;
    }

    return a.name.localeCompare(b.name);
  });
});

const limitedInstitutions = computed(() => {
  return sortedInstitutions.value.slice(0, props.maxDisplayCount);
});

const checkInInstitutionName = computed(() => {
  const institutionId = showCreateCheckIn.value;
  if (!institutionId) return undefined;

  return props.institutions.find(inst => String(inst.id) === String(institutionId))?.name;
});

// Determine overall urgency level for theming
const urgencyLevel = computed((): UrgencyLevel => {
  const needingAttentionCount = institutionsNeedingAttention.value.length;
  const totalCount = props.institutions.length;

  if (needingAttentionCount === 0) return 'success';
  if (needingAttentionCount / totalCount > 0.5) return 'danger';
  return 'warning';
});

// Use the composable for consistent styling
const {
  cardClasses,
  footerClasses,
  statusIndicatorClasses,
  iconClasses,
} = useDashboardCardStyles(urgencyLevel);

// Action handlers with loading states
const setLoading = (institutionId: string, loading: boolean) => {
  actionLoading[institutionId] = loading;
};

const handleAddCheckIn = (institutionId: string) => {
  showCreateCheckIn.value = institutionId;
};

const handleRemoveActiveCheckIn = (institutionId: string) => {
  setLoading(institutionId, true);
  router.delete(route('institutions.check-ins.destroyActive', institutionId), {
    preserveScroll: true,
    onFinish: () => setLoading(institutionId, false),
    onSuccess: () => {
      // Refresh data to update UI after check-in deletion
      router.reload({ only: ['user', 'userInstitutions', 'tenantInstitutions'] });
    },
  });
};

// Institution subscription handlers
const { toggleFollow, toggleMute, followLoading, muteLoading } = useInstitutionSubscription();

const followLoadingStates = computed(() => followLoading.value);
const muteLoadingStates = computed(() => muteLoading.value);

const handleToggleFollow = async (institutionId: string) => {
  const institution = props.institutions.find(i => String(i.id) === String(institutionId));
  if (!institution) return;

  const currentState = institution.subscription ?? {
    is_followed: false,
    is_muted: false,
    is_duty_based: false,
  };

  await toggleFollow(institutionId, currentState, ['user', 'userInstitutions', 'tenantInstitutions']);
};

const handleToggleMute = async (institutionId: string) => {
  const institution = props.institutions.find(i => String(i.id) === String(institutionId));
  if (!institution) return;

  const currentState = institution.subscription ?? {
    is_followed: false,
    is_muted: false,
    is_duty_based: false,
  };

  await toggleMute(institutionId, currentState, ['user', 'userInstitutions', 'tenantInstitutions']);
};
</script>
