<template>
  <Dialog :open="isOpen" @update:open="emit('update:isOpen', $event)">
    <DialogContent class="sm:max-w-[95vw] w-full h-[95vh] !flex flex-col overflow-hidden">
      <DialogHeader class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <DialogTitle class="flex items-center gap-2">
            <component :is="MeetingIconFilled" class="h-5 w-5" />
            {{
              ganttType === 'user'
                ? $t('Tavo institucijos — laiko juosta')
                : `${currentTenant?.shortname || $t('Padalinys')} — ${$t('laiko juosta')}`
            }}
          </DialogTitle>
          <DialogDescription>
            {{
              ganttType === 'user'
                ? $t('Peržiūrėkite visas savo institucijas ir jų veiklą laiko juostoje')
                : $t('Peržiūrėkite padalinio institucijas ir jų veiklą laiko juostoje')
            }}
          </DialogDescription>
        </div>
        <div class="flex flex-wrap items-center justify-end gap-2">
          <TenantScopeSelector
            v-if="ganttType === 'tenant' && availableTenants.length > 0"
            compact
            :tenants="availableTenants"
            :selected-tenants="filters.selectedTenantForGantt.value"
            @update:selected-tenants="filters.setSelectedTenants"
          />
          <TenantScopeSelector
            v-else-if="ganttType === 'user' && filters.availableTenantsUser.value.length > 0"
            compact
            :tenants="filters.availableTenantsUser.value"
            :selected-tenants="filters.userTenantFilter.value"
            @update:selected-tenants="filters.setUserTenantFilter"
          />
          <GanttFilterDropdown
            v-if="ganttType === 'tenant'"
            :show-only-with-activity="filters.showOnlyWithActivityTenant.value"
            :show-only-with-public-meetings="filters.showOnlyWithPublicMeetingsTenant.value"
            :show-duty-members="filters.showDutyMembersTenant.value"
            :show-activity-status="filters.showActivityStatusTenant.value"
            :show-activity-status-option="filters.showDutyMembersTenant.value"
            :show-tenant-headers="ganttSettings.showTenantHeaders.value"
            :show-reset="false"
            :trigger-label-override="$t('Rodymo nustatymai')"
            @update:show-only-with-activity="(val: boolean) => filters.showOnlyWithActivityTenant.value = val"
            @update:show-only-with-public-meetings="(val: boolean) => filters.showOnlyWithPublicMeetingsTenant.value = val"
            @update:show-duty-members="(val: boolean) => filters.showDutyMembersTenant.value = val"
            @update:show-activity-status="(val: boolean) => filters.showActivityStatusTenant.value = val"
            @update:show-tenant-headers="(val: boolean) => ganttSettings.showTenantHeaders.value = val"
          />
          <GanttFilterDropdown
            v-else-if="ganttType === 'user'"
            :show-only-with-activity="filters.showOnlyWithActivityUser.value"
            :show-only-with-public-meetings="filters.showOnlyWithPublicMeetingsUser.value"
            :show-duty-members="filters.showDutyMembersUser.value"
            :show-tenant-headers="ganttSettings.showTenantHeaders.value"
            :show-related-institutions="filters.showRelatedInstitutionsUser.value"
            :has-related-institutions
            :show-reset="false"
            :trigger-label-override="$t('Rodymo nustatymai')"
            @update:show-only-with-activity="(val: boolean) => filters.showOnlyWithActivityUser.value = val"
            @update:show-only-with-public-meetings="(val: boolean) => filters.showOnlyWithPublicMeetingsUser.value = val"
            @update:show-duty-members="(val: boolean) => filters.showDutyMembersUser.value = val"
            @update:show-tenant-headers="(val: boolean) => ganttSettings.showTenantHeaders.value = val"
            @update:show-related-institutions="(val: boolean) => filters.showRelatedInstitutionsUser.value = val"
          />
        </div>
      </DialogHeader>

      <div class="flex-1 min-h-0 mt-4">
        <div v-if="ganttType === 'user' && userInstitutions.length > 0" class="h-full">
          <TimelineGanttChart
            :institutions="mergedUserInstitutions"
            :meetings="mergedUserMeetings"
            :gaps="userGaps"
            :tenant-filter="[]"
            :show-only-with-activity="filters.showOnlyWithActivityUser.value"
            :show-only-with-public-meetings="filters.showOnlyWithPublicMeetingsUser.value"
            :institution-names="mergedUserInstitutionNames"
            :tenant-names
            :institution-tenant="mergedUserInstitutionTenant"
            :institution-has-public-meetings="mergedUserInstitutionHasPublicMeetings"
            :institution-periodicity="mergedUserInstitutionPeriodicity"
            :duty-members="mergedUserDutyMembers"
            :inactive-periods="mergedUserInactivePeriods"
            :show-duty-members="filters.showDutyMembersUser.value"
            :empty-message="$t('Neturi tiesiogiai priskirtų institucijų')"
            height="100%"
            hide-fullscreen-button
            @create-meeting="$emit('create-meeting', $event)"
            @create-check-in="$emit('create-check-in', $event)"
          />
        </div>

        <div v-else-if="ganttType === 'tenant'" class="h-full">
          <TimelineGanttChart
            :institutions="tenantInstitutions"
            :meetings="tenantMeetings"
            :gaps="tenantGaps"
            :tenant-filter="[]"
            :show-only-with-activity="filters.showOnlyWithActivityTenant.value"
            :show-only-with-public-meetings="filters.showOnlyWithPublicMeetingsTenant.value"
            :institution-names="tenantInstitutionNames"
            :tenant-names
            :institution-tenant="tenantInstitutionTenant"
            :institution-has-public-meetings="tenantInstitutionHasPublicMeetings"
            :institution-has-activity="tenantInstitutionHasActivity"
            :institution-periodicity="tenantInstitutionPeriodicity"
            :duty-members="tenantDutyMembers"
            :inactive-periods="tenantInactivePeriods"
            :show-duty-members="filters.showDutyMembersTenant.value"
            :show-activity-status="filters.showActivityStatusTenant.value"
            :loading-range="tenantLoadingRange" :meetings-loading="tenantMeetingsLoading"
            :empty-message="$t('Šiame padalinyje nėra institucijų')"
            height="100%"
            hide-fullscreen-button
            @create-meeting="$emit('create-meeting', $event)"
            @create-check-in="$emit('create-check-in', $event)"
            @range-changed="(min: Date, max: Date) => $emit('range-changed', min, max)"
          />
        </div>

        <div v-else-if="ganttType === 'user'" class="text-center py-8">
          <p class="text-muted-foreground">
            {{ $t('Neturi tiesiogiai priskirtų institucijų') }}
          </p>
        </div>
      </div>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { computed, toRef } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import { useGanttSettings } from '../Composables/useGanttSettings';
import { useTimelineFilters } from '../Composables/useTimelineFilters';
import { useUserTimelineData } from '../Composables/useUserTimelineData';
import type {
  GanttMeeting,
  GanttInstitution,
  AtstovavimasGap,
  AtstovavimasTenant,
  GanttDutyMember,
  InactivePeriod,
  AtstovavimasInstitution,
} from '../types';

import TimelineGanttChart from './TimelineGanttChart.vue';
import GanttFilterDropdown from './GanttFilterDropdown.vue';
import TenantScopeSelector from './TenantScopeSelector.vue';

import { MeetingIconFilled } from '@/Components/icons';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/Components/ui/dialog';

interface Props {
  isOpen: boolean;
  ganttType: 'user' | 'tenant';
  availableTenants: AtstovavimasTenant[];

  // User data (base data from user's direct institutions)
  userInstitutions: AtstovavimasInstitution[];
  userMeetings: GanttMeeting[];
  userGaps: AtstovavimasGap[];
  userInstitutionNames: Record<string, string>;
  userInstitutionTenant: Record<string, string>;
  userInstitutionHasPublicMeetings?: Record<string, boolean>;
  userInstitutionPeriodicity?: Record<string | number, number>;
  userDutyMembers?: GanttDutyMember[];
  userInactivePeriods?: InactivePeriod[];
  // Related institutions (lazy-loaded)
  userRelatedInstitutions?: AtstovavimasInstitution[];
  // Flag to show related institutions filter even when data is lazy-loaded
  mayHaveRelatedInstitutions?: boolean;

  // Tenant data (already computed in parent)
  tenantInstitutions: GanttInstitution[];
  tenantMeetings: GanttMeeting[];
  tenantGaps: AtstovavimasGap[];
  tenantInstitutionNames: Record<string, string>;
  tenantInstitutionTenant: Record<string, string>;
  tenantInstitutionHasPublicMeetings?: Record<string, boolean>;
  tenantInstitutionHasActivity?: Record<string, boolean>;
  tenantInstitutionPeriodicity?: Record<string | number, number>;
  tenantDutyMembers?: GanttDutyMember[];
  tenantInactivePeriods?: InactivePeriod[];
  tenantLoadingRange?: { from: Date; until: Date } | null;
  tenantMeetingsLoading?: boolean;

  // Shared
  tenantNames: Record<string, string>;
}

const props = defineProps<Props>();

const emit = defineEmits<{
  'update:isOpen': [value: boolean];
  'create-meeting': [payload: { institution_id: string | number; suggestedAt: Date }];
  'create-check-in': [payload: { institution_id: string | number; startDate: Date; endDate: Date }];
  'range-changed': [min: Date, max: Date];
}>();

// Get shared settings and filter state from providers
const ganttSettings = useGanttSettings();
const filters = useTimelineFilters();

// Computed: check if we have related institutions for user view (or might have when lazy-loaded)
const hasRelatedInstitutions = computed(() => {
  return (props.userRelatedInstitutions?.length ?? 0) > 0 || props.mayHaveRelatedInstitutions === true;
});

// Computed: current tenant for display
const currentTenant = computed(() => filters.currentTenant.value);

// Use composable for merged user timeline data (same as UserTimelineSection)
const relatedInstitutionsRef = computed(() => props.userRelatedInstitutions ?? []);
const institutionsRef = toRef(props, 'userInstitutions');
const meetingsRef = toRef(props, 'userMeetings');
const baseDutyMembersRef = computed(() => props.userDutyMembers ?? []);
const baseInactivePeriodsRef = computed(() => props.userInactivePeriods ?? []);
const baseInstitutionNamesRef = toRef(props, 'userInstitutionNames');
const baseInstitutionTenantRef = toRef(props, 'userInstitutionTenant');
const baseInstitutionHasPublicMeetingsRef = computed(() => props.userInstitutionHasPublicMeetings ?? {});
const baseInstitutionPeriodicityRef = computed(() => props.userInstitutionPeriodicity ?? {});

const {
  mergedInstitutions: mergedUserInstitutions,
  mergedMeetings: mergedUserMeetings,
  mergedDutyMembers: mergedUserDutyMembers,
  mergedInactivePeriods: mergedUserInactivePeriods,
  mergedInstitutionNames: mergedUserInstitutionNames,
  mergedInstitutionTenant: mergedUserInstitutionTenant,
  mergedInstitutionHasPublicMeetings: mergedUserInstitutionHasPublicMeetings,
  mergedInstitutionPeriodicity: mergedUserInstitutionPeriodicity,
} = useUserTimelineData({
  institutions: institutionsRef,
  meetings: meetingsRef,
  relatedInstitutions: relatedInstitutionsRef,
  showRelatedInstitutions: filters.showRelatedInstitutionsUser,
  baseDutyMembers: baseDutyMembersRef,
  baseInactivePeriods: baseInactivePeriodsRef,
  baseInstitutionNames: baseInstitutionNamesRef,
  baseInstitutionTenant: baseInstitutionTenantRef,
  baseInstitutionHasPublicMeetings: baseInstitutionHasPublicMeetingsRef,
  baseInstitutionPeriodicity: baseInstitutionPeriodicityRef,
});
</script>
