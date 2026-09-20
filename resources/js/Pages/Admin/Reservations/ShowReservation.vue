<template>
  <RecordPage
    v-model:section="currentSection"
    :title="reservation.name"
    :entity-type="ModelEnum.RESERVATION"
    :status="undefined"
    :facts="recordFacts"
    :sections="tabs"
    :primary-action
    :overflow-actions
    @action="handleRecordAction"
  >
    <!-- One item can be approved while another waits, so the badge reports every state, not one. -->
    <template #subtitle>
      <div class="flex flex-wrap items-center gap-2">
        <ReservationStateSummary :states="reservationStates" :unresolved="isUnresolved" />
        <Button variant="ghost" size="icon-sm" class="size-6 pointer-coarse:size-11" @click="showReservationHelpModal = true">
          <Info class="size-4 text-muted-foreground" aria-hidden="true" />
          <span class="sr-only">{{ $t('Būsenų informacija') }}</span>
        </Button>
      </div>
    </template>

    <template #fact-managers>
      <div v-if="reservation.users?.length" class="flex items-center gap-2">
        <UsersAvatarGroup :users="reservation.users" :max="3" :size="20" />
        <span class="text-sm text-muted-foreground tabular-nums">{{ reservation.users.length }}</span>
      </div>
      <span v-else>—</span>
    </template>

    <template #resources>
      <div class="space-y-3">
        <!-- Only worth showing once the reservation spans more than one unit. -->
        <div v-if="resourceTenants.length > 1" class="flex items-center justify-end">
          <Select v-model="tenantFilter">
            <SelectTrigger class="w-[170px]" :aria-label="$t('reservations.dashboard.filters.tenant')">
              <SelectValue :placeholder="$t('reservations.dashboard.filters.tenant')" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">
                {{ $t('reservations.dashboard.filters.tenant_all') }}
              </SelectItem>
              <SelectItem v-for="tenant in resourceTenants" :key="tenant.id" :value="tenant.id">
                {{ $t(tenant.shortname) }}
              </SelectItem>
            </SelectContent>
          </Select>
        </div>

        <!-- The filter hid everything — distinct from a reservation that simply has no resources. -->
        <div v-if="hasNoResourcesForTenant" class="flex flex-col items-center gap-3 py-8" data-testid="filtered-out">
          <p class="text-sm text-muted-foreground">
            {{ $t('reservations.show.no_resources_for_tenant') }}
          </p>
          <Button variant="outline" size="sm" @click="tenantFilter = 'all'">
            {{ $t('reservations.dashboard.filters.clear') }}
          </Button>
        </div>
        <ReservationResourceList
          v-else
          :resources="visibleResources"
          :target="decisionTarget"
          :can-edit="can.update"
          @add-resource="handleAddResource"
          @edit="editReservationResource"
        />
      </div>
    </template>

    <template #description>
      <p v-if="reservation.description" class="max-w-prose whitespace-pre-wrap text-sm text-foreground">
        {{ reservation.description }}
      </p>
      <p v-else class="text-sm italic text-muted-foreground">
        {{ $t('Aprašymas nepateiktas.') }}
      </p>
    </template>

    <template #activity>
      <RecordActivity
        subject-type="reservation"
        :subject-id="reservation.id"
        commentable-type="reservation"
        :commentable-id="reservation.id"
      />
    </template>
  </RecordPage>

  <Dialog :open="showReservationHelpModal" @update:open="showReservationHelpModal = $event">
    <DialogContent class="max-w-3xl">
      <DialogHeader>
        <DialogTitle>
          {{ $t('entities.meta.help', { model: $tChoice('entities.reservation.model', 2) }) }}
        </DialogTitle>
      </DialogHeader>
      <MdSuspenseWrapper directory="reservations" :locale="$page.props.app.locale" file="help" />
    </DialogContent>
  </Dialog>

  <Dialog :open="showReservationResourceCreateModal" @update:open="showReservationResourceCreateModal = $event">
    <DialogContent class="max-h-[90vh] max-w-3xl overflow-y-auto">
      <DialogHeader>
        <DialogTitle>
          {{ RESERVATION_CARD_MODAL_TITLES.create_reservation_resource[$page.props.app.locale][reservationResourceFormRouteName] }}
        </DialogTitle>
      </DialogHeader>
      <ReservationResourceForm
        :reservation-resource-form
        :all-resources
        :reservation-resource-form-route-name
        :currently-used-capacity
        @success="showReservationResourceCreateModal = false"
      />
    </DialogContent>
  </Dialog>

  <Dialog :open="showReservationAddUserModal" @update:open="showReservationAddUserModal = $event">
    <DialogContent class="max-w-lg">
      <DialogHeader>
        <DialogTitle>
          {{ RESERVATION_CARD_MODAL_TITLES.attach_user_to_reservation[$page.props.app.locale] }}
        </DialogTitle>
      </DialogHeader>
      <div class="space-y-4">
        <div class="space-y-2">
          <Label>{{ $t('Naudotojai') }}</Label>
          <MultiSelect
            ref="userMultiSelectRef"
            v-model="selectedUsersList"
            :options="allUsers ?? []"
            label-field="name"
            value-field="id"
            :placeholder="`${$t('Pasirinkite')}...`"
            :empty-text="$t('No users found.')"
          >
            <template #selected-item="{ item: user }">
              <div class="flex items-center gap-1">
                <UserAvatar :user="(user as unknown as App.Entities.User)" :size="16" />
                <span class="max-w-[120px] truncate">{{ (user as unknown as App.Entities.User).name }}</span>
              </div>
            </template>
            <template #option="{ item: user }">
              <UserAvatar :user="(user as unknown as App.Entities.User)" :size="24" class="shrink-0" />
              <span class="min-w-0 truncate">{{ (user as unknown as App.Entities.User).name }}</span>
            </template>
          </MultiSelect>
        </div>
        <Button variant="brand" :disabled="selectedUsersList.length === 0 || reservationUserForm.processing" @click="handleSubmitUserForm">
          <Check v-if="!reservationUserForm.processing" class="size-4" aria-hidden="true" />
          {{ $t("forms.submit") }}
        </Button>
      </div>
    </DialogContent>
  </Dialog>

  <ReservationDecisionDialog
    v-model:open="decideAllOpen"
    decision="approved"
    :targets="[decisionTarget]"
  />

  <ConfirmDialog
    v-model:open="deleteOpen"
    :title="$t('Ištrinti rezervaciją?')"
    :description="$t('Rezervacija bus perkelta į šiukšlinę.')"
    :confirm-label="$t('Ištrinti')"
    destructive
    @confirm="deleteReservation"
  />
</template>

<script setup lang="ts">
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import { computed, ref, watch, capitalize } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { Check, CheckCheck, Info, Plus, Trash2, UserPlus } from 'lucide-vue-next';

import UserAvatar from '@/Components/Avatars/UserAvatar.vue';
import UsersAvatarGroup from '@/Components/Avatars/UsersAvatarGroup.vue';
import ReservationResourceForm from '@/Components/AdminForms/ReservationResourceForm.vue';
import RecordPage, { type RecordFact, type RecordPageSection } from '@/Components/Layouts/RecordPage.vue';
import type { ActionDescriptor } from '@/Components/Layouts/RecordPageAction.vue';
import { ConfirmDialog } from '@/Components/Patterns';
import ReservationDecisionDialog from '@/Components/Reservations/ReservationDecisionDialog.vue';
import ReservationResourceList from '@/Components/Reservations/ReservationResourceList.vue';
import ReservationStateSummary from '@/Components/Tag/ReservationStateSummary.vue';
import { Button } from '@/Components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { Label } from '@/Components/ui/label';
import { MultiSelect } from '@/Components/ui/multi-select';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { ReservationIconFilled } from '@/Components/icons';
import { usePageBreadcrumbs, BreadcrumbHelpers } from '@/Composables/useBreadcrumbsUnified';
import { RESERVATION_CARD_MODAL_TITLES } from '@/Constants/I18n/CardModalTitles';
import RecordActivity from '@/Features/Admin/ActivityLogViewer/RecordActivity.vue';
import MdSuspenseWrapper from '@/Features/MarkdownGetterFromDocs/MdSuspenseWrapper.vue';
import { ModelEnum } from '@/Types/enums';
import type { DashboardReservation, ReservationResourceState } from '@/Utils/ReservationStatus';
import { getPrimaryAction, isPivotUnresolved, summarizeStates } from '@/Utils/ReservationStatus';
import { formatDateTime } from '@/Utils/dateTime';

type RecordResource = NonNullable<App.Entities.Reservation['resources']>[number];

const props = defineProps<{
  reservation: App.Entities.Reservation;
  /** The same reservation with the server's approve / backtrack / cancel flags per item. */
  decisionTarget: DashboardReservation;
  can: { update: boolean; delete: boolean };
  allResources?: App.Entities.Resource[];
  allUsers?: App.Entities.User[];
}>();

usePageBreadcrumbs(() => [
  BreadcrumbHelpers.homeItem(),
  BreadcrumbHelpers.createRouteBreadcrumb(
    capitalize($tChoice('entities.reservation.model', 2)),
    'reservations.index',
    {},
    ReservationIconFilled,
  ),
  BreadcrumbHelpers.createBreadcrumbItem(props.reservation.name),
]);

// --- Sections and key facts -------------------------------------------------------------------

const currentSection = ref('resources');

const tabs = computed<RecordPageSection[]>(() => [
  {
    value: 'resources',
    label: capitalize($tChoice('entities.resource.model', 2)),
    count: props.reservation.resources?.length ?? 0,
  },
  { value: 'description', label: $t('Aprašymas') },
]);

const period = computed(() => `${formatDateTime(props.reservation.start_time)} – ${formatDateTime(props.reservation.end_time)}`);
const resourcesCount = computed(() => props.reservation.resources?.length ?? 0);
const pendingCount = computed(() => (props.reservation.resources ?? []).filter(
  resource => resource.pivot?.state === 'created' || resource.pivot?.state === 'reserved',
).length);

const recordFacts = computed<RecordFact[]>(() => [
  { key: 'period', label: $t('Laikotarpis'), value: period.value },
  { key: 'resources', label: capitalize($tChoice('entities.resource.model', 2)), value: String(resourcesCount.value) },
  ...(pendingCount.value > 0 ? [{ key: 'pending', label: $t('laukia'), value: String(pendingCount.value) }] : []),
  { key: 'managers', label: $t('valdytojai'), value: '' },
]);

/**
 * The reservation's overall state, reported the way the reservation hub reports it: every state
 * its items are in, rather than one label that has to guess which of them matters.
 */
const reservationStates = computed(() => summarizeStates(
  (props.reservation.resources ?? [])
    .map(resource => resource.pivot?.state)
    .filter((state): state is ReservationResourceState => state != null),
));

const isUnresolved = computed(() => (props.reservation.resources ?? []).some(
  resource => resource.pivot != null && isPivotUnresolved(resource.pivot),
));

// --- Unit filter -------------------------------------------------------------------------------

// A reservation can pull resources from several units, and a manager usually only cares about their own.
const tenantFilter = ref<string>('all');

const resourceTenants = computed(() => {
  const tenants = new Map<string, { id: string; shortname: string }>();

  props.reservation.resources?.forEach((resource) => {
    if (resource.tenant) {
      tenants.set(String(resource.tenant.id), { id: String(resource.tenant.id), shortname: resource.tenant.shortname ?? '' });
    }
  });

  return [...tenants.values()];
});

const visibleResources = computed(() => (props.reservation.resources ?? []).filter(
  resource => tenantFilter.value === 'all' || String(resource.tenant?.id) === tenantFilter.value,
) as unknown as InstanceType<typeof ReservationResourceList>['$props']['resources']);

const hasNoResourcesForTenant = computed(() =>
  Boolean(props.reservation.resources?.length) && visibleResources.value.length === 0);

// --- Actions -----------------------------------------------------------------------------------

/** Deciding the whole reservation in one go is the only decision worth a button of its own. */
const decideAllOpen = ref(false);
const canDecideAll = computed(() => getPrimaryAction(props.decisionTarget)?.pivotIds.length ?? 0);

const primaryAction = computed<ActionDescriptor | undefined>(() => {
  if (canDecideAll.value > 1) {
    return { key: 'decide-all', label: $t('reservations.actions.approve'), icon: CheckCheck };
  }

  return props.can.update ? { key: 'add-resource', label: $t('Pridėti išteklių'), icon: Plus } : undefined;
});

const overflowActions = computed<ActionDescriptor[]>(() => {
  const actions: ActionDescriptor[] = [];

  if (props.can.update) {
    if (canDecideAll.value > 1) {
      actions.push({ key: 'add-resource', label: $t('Pridėti išteklių'), icon: Plus });
    }

    actions.push({ key: 'add-user', label: $t('Pridėti valdytoją'), icon: UserPlus });
  }

  if (props.can.delete) {
    actions.push({ key: 'delete', label: $t('Ištrinti rezervaciją'), icon: Trash2, destructive: true });
  }

  return actions;
});

const handleRecordAction = (key: string) => {
  switch (key) {
    case 'decide-all':
      decideAllOpen.value = true;
      break;
    case 'add-resource':
      handleAddResource();
      break;
    case 'add-user':
      handleAddUser();
      break;
    case 'delete':
      deleteOpen.value = true;
      break;
  }
};

const deleteOpen = ref(false);
const deleteReservation = () => router.delete(route('reservations.destroy', props.reservation.id));

// --- Resource and manager forms ----------------------------------------------------------------

const showReservationResourceCreateModal = ref(false);
const showReservationAddUserModal = ref(false);
const showReservationHelpModal = ref(false);

const reservationResourceForm = useForm({
  id: undefined as string | undefined,
  resource_id: null as string | null,
  reservation_id: props.reservation.id,
  quantity: 1,
  start_time: new Date(props.reservation.start_time).getTime(),
  end_time: new Date(props.reservation.end_time).getTime(),
});

const reservationResourceFormRouteName = ref('reservationResources.store');
const currentlyUsedCapacity = ref(0);

const reservationUserForm = useForm({
  users: null as string[] | null,
});

const selectedUsersList = ref<App.Entities.User[]>([]);
const userMultiSelectRef = ref<{ reset: () => void } | null>(null);

watch(selectedUsersList, (users) => {
  reservationUserForm.users = users.map(user => user.id);
}, { deep: true });

const handleAddResource = () => {
  reservationResourceFormRouteName.value = 'reservationResources.store';
  currentlyUsedCapacity.value = 0;
  reservationResourceForm.reset();
  showReservationResourceCreateModal.value = true;
};

const handleAddUser = () => {
  router.reload({ only: ['allUsers'] });
  showReservationAddUserModal.value = true;
};

const handleSubmitUserForm = () => {
  reservationUserForm.put(route('reservations.add-users', { reservation: props.reservation.id }), {
    onSuccess: () => {
      reservationUserForm.reset();
      selectedUsersList.value = [];
      userMultiSelectRef.value?.reset();
      showReservationAddUserModal.value = false;
    },
  });
};

const editReservationResource = (resource: RecordResource | { pivot: Record<string, unknown>; id: string }) => {
  const pivot = resource.pivot as Record<string, unknown>;

  reservationResourceForm.reset();
  reservationResourceForm.id = pivot.id;
  reservationResourceForm.resource_id = pivot.resource_id;
  reservationResourceForm.quantity = pivot.quantity;
  reservationResourceForm.start_time = new Date(pivot.start_time).getTime();
  reservationResourceForm.end_time = new Date(pivot.end_time).getTime();

  reservationResourceFormRouteName.value = 'reservationResources.update';
  currentlyUsedCapacity.value = pivot.quantity;

  showReservationResourceCreateModal.value = true;
};

</script>
