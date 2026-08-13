<template>
  <ShowPageLayout
    :title="reservation.name"
    :icon="ReservationIconFilled"
    :model="reservation"
    audit-subject-type="reservation"
    :tabs
    tab-storage-key="show-reservation-tab"
  >
    <template #badge>
      <ReservationStateSummary :states="reservationStates" :unresolved="isUnresolved" />
      <Button variant="ghost" size="icon-sm" class="size-6" @click="showReservationHelpModal = true">
        <Info class="size-4 text-muted-foreground" />
        <span class="sr-only">{{ $t('Būsenų informacija') }}</span>
      </Button>
    </template>

    <template #info>
      <p class="inline-flex items-center gap-1.5 text-xs text-muted-foreground sm:text-sm">
        <CalendarDays class="size-3.5 shrink-0 sm:size-4" />
        <span>{{ formattedDateRange }}</span>
      </p>

      <div v-if="reservation.users?.length" class="flex items-center gap-1">
        <UsersAvatarGroup :users="reservation.users" :max="3" :size="20" />
        <span class="text-xs leading-5 text-muted-foreground">
          {{ reservation.users.length }} {{ $t('valdytojai') }}
        </span>
      </div>

      <div class="flex items-stretch gap-2 sm:gap-3">
        <div class="flex min-w-16 flex-col items-center justify-center rounded-lg border bg-background px-3 py-1.5">
          <span class="text-xl font-semibold leading-none">{{ resourcesCount }}</span>
          <span class="mt-0.5 text-[10px] leading-tight text-muted-foreground">
            {{ $tChoice('entities.resource.model', resourcesCount) }}
          </span>
        </div>
        <div
          v-if="pendingCount > 0"
          class="flex min-w-16 flex-col items-center justify-center rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 dark:border-amber-900 dark:bg-amber-950"
        >
          <span class="text-xl font-semibold leading-none text-amber-600 dark:text-amber-400">
            {{ pendingCount }}
          </span>
          <span class="mt-0.5 text-[10px] leading-tight text-amber-600 dark:text-amber-400">
            {{ $t('laukia') }}
          </span>
        </div>
      </div>
    </template>

    <template #actions>
      <Button variant="outline" size="sm" class="h-9 gap-1.5" @click="handleAddUser">
        <UserPlus class="size-4 shrink-0" />
        <span class="hidden sm:inline">{{ $t('Pridėti valdytoją') }}</span>
      </Button>
      <Button size="sm" class="h-9 gap-1.5" @click="handleAddResource">
        <Plus class="size-4 shrink-0" />
        <span class="hidden xs:inline">{{ $t('Pridėti išteklių') }}</span>
      </Button>
    </template>

    <template #resources>
      <div class="space-y-4">
        <SectionCard
          :title="$t('Rezervuoti ištekliai')"
          :icon="ResourceIconFilled"
          :count="filteredReservation.resources?.length"
          :empty="hasNoResourcesForTenant"
        >
          <template #action>
            <!-- Only worth showing once the reservation spans more than one unit. -->
            <Select v-if="resourceTenants.length > 1" v-model="tenantFilter">
              <SelectTrigger class="w-[170px]">
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
            <Button size="sm" @click="handleAddResource">
              <Plus class="size-4" />
              {{ $t('Pridėti') }}
            </Button>
          </template>

          <!-- The table renders its own empty state, so it stays mounted even with no rows. -->
          <ReservationResourceTable
            v-model:selected-reservation-resource="selectedReservationResource"
            :reservation="filteredReservation"
            @edit:reservation-resource="editReservationResource"
            @add-resource="handleAddResource"
          />

          <!-- Compact in-card empty: the filter hid everything, the reservation isn't empty. -->
          <template #empty>
            <div class="flex flex-col items-center gap-3 py-8">
              <p class="text-sm text-muted-foreground">
                {{ $t('reservations.show.no_resources_for_tenant') }}
              </p>
              <Button variant="outline" size="sm" @click="tenantFilter = 'all'">
                {{ $t('reservations.dashboard.filters.clear') }}
              </Button>
            </div>
          </template>
        </SectionCard>

        <!-- Reservation discussion lives below the resources. -->
        <section class="border-t pt-6 dark:border-zinc-800">
          <DiscussionPanel commentable-type="reservation" :commentable-id="reservation.id" />
        </section>
      </div>
    </template>

    <template #description>
      <SectionCard :title="$t('Aprašymas')" :icon="FileText" :empty="!reservation.description">
        <p class="text-sm text-muted-foreground whitespace-pre-wrap">
          {{ reservation.description }}
        </p>
        <template #empty>
          <p class="text-sm text-muted-foreground italic">
            {{ $t('Aprašymas nepateiktas.') }}
          </p>
        </template>
      </SectionCard>
    </template>

    <!-- Dialogs -->
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
      <DialogContent class="max-w-3xl max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle>
            {{
              RESERVATION_CARD_MODAL_TITLES.create_reservation_resource[$page.props.app.locale][reservationResourceFormRouteName]
            }}
          </DialogTitle>
        </DialogHeader>
        <ReservationResourceForm :reservation-resource-form :all-resources :reservation-resource-form-route-name
          :currently-used-capacity @success="showReservationResourceCreateModal = false" />
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
          <Button :disabled="selectedUsersList.length === 0 || reservationUserForm.processing"
            @click="handleSubmitUserForm">
            <Check v-if="!reservationUserForm.processing" class="size-4" />
            {{ $t("forms.submit") }}
          </Button>
        </div>
      </DialogContent>
    </Dialog>
  </ShowPageLayout>
</template>

<script setup lang="ts">
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import { computed, ref, watch, capitalize } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import { CalendarDays, Check, FileText, Info, Plus, UserPlus } from 'lucide-vue-next';

import { Button } from '@/Components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/Components/ui/select';
import { RESERVATION_CARD_MODAL_TITLES } from '@/Constants/I18n/CardModalTitles';
import { usePageBreadcrumbs, BreadcrumbHelpers } from '@/Composables/useBreadcrumbsUnified';
import ShowPageLayout from '@/Components/Layouts/ShowPageLayout.vue';
import { SectionCard } from '@/Components/Patterns';
import DiscussionPanel from '@/Components/Discussions/DiscussionPanel.vue';
import MdSuspenseWrapper from '@/Features/MarkdownGetterFromDocs/MdSuspenseWrapper.vue';
import ReservationResourceForm from '@/Components/AdminForms/ReservationResourceForm.vue';
import ReservationResourceTable from '@/Components/Tables/ReservationResourceTable.vue';
import UserAvatar from '@/Components/Avatars/UserAvatar.vue';
import UsersAvatarGroup from '@/Components/Avatars/UsersAvatarGroup.vue';
import ReservationStateSummary from '@/Components/Tag/ReservationStateSummary.vue';
import { MultiSelect } from '@/Components/ui/multi-select';
import { Label } from '@/Components/ui/label';
import { ReservationIconFilled, ResourceIconFilled } from '@/Components/icons';
import type { ReservationResourceState } from '@/Utils/ReservationStatus';
import { isPivotUnresolved, summarizeStates } from '@/Utils/ReservationStatus';

const props = defineProps<{
  reservation: App.Entities.Reservation;
  allResources?: App.Entities.Resource[];
  allUsers?: App.Entities.User[];
}>();

// Breadcrumbs setup
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

const tabs = computed(() => [
  {
    value: 'resources',
    label: capitalize($tChoice('entities.resource.model', 2)),
    count: props.reservation.resources?.length ?? 0,
    icon: ResourceIconFilled,
  },
  { value: 'description', label: $t('Aprašymas'), icon: FileText },
]);

const locale = computed(() => usePage().props.app.locale);

const dateFormatter = computed(() => new Intl.DateTimeFormat(locale.value, {
  day: 'numeric',
  month: 'short',
  year: 'numeric',
  hour: '2-digit',
  minute: '2-digit',
}));

const formattedDateRange = computed(() => {
  const format = dateFormatter.value;
  return `${format.format(new Date(props.reservation.start_time))} – ${format.format(new Date(props.reservation.end_time))}`;
});

const resourcesCount = computed(() => props.reservation.resources?.length ?? 0);

const pendingCount = computed(() => props.reservation.resources?.filter(
  r => r.pivot?.state === 'created' || r.pivot?.state === 'reserved',
).length ?? 0);

/**
 * The reservation's overall state, reported the same way the reservation hub reports it: every
 * state its items are in, rather than one label that has to guess which of them matters.
 */
const reservationStates = computed(() => summarizeStates(
  (props.reservation.resources ?? [])
    .map(resource => resource.pivot?.state)
    .filter((state): state is ReservationResourceState => state != null),
));

const isUnresolved = computed(() => (props.reservation.resources ?? []).some(
  resource => resource.pivot != null && isPivotUnresolved(resource.pivot),
));

// Tenant filter — a reservation can pull resources from several units, and a manager usually
// only cares about the ones their unit owns.
const tenantFilter = ref<string>('all');

/** The filter hid everything — distinct from a reservation that simply has no resources. */
const hasNoResourcesForTenant = computed(() =>
  Boolean(props.reservation.resources?.length) && !filteredReservation.value.resources?.length,
);

const resourceTenants = computed(() => {
  const tenants = new Map<string, { id: string; shortname: string }>();

  props.reservation.resources?.forEach((resource) => {
    if (resource.tenant) {
      tenants.set(String(resource.tenant.id), {
        id: String(resource.tenant.id),
        shortname: resource.tenant.shortname ?? '',
      });
    }
  });

  return [...tenants.values()];
});

const filteredReservation = computed(() => {
  if (tenantFilter.value === 'all') {
    return props.reservation;
  }

  return {
    ...props.reservation,
    resources: (props.reservation.resources ?? []).filter(
      resource => String(resource.tenant?.id) === tenantFilter.value,
    ),
  };
});

// Resource form state
const selectedReservationResource = ref<App.Entities.ReservationResource | null>(null);
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

// User form state
const reservationUserForm = useForm({
  users: null as string[] | null,
});

const selectedUsersList = ref<App.Entities.User[]>([]);
const userMultiSelectRef = ref<{ reset: () => void } | null>(null);

// Watch selection changes to update form
watch(selectedUsersList, (users) => {
  reservationUserForm.users = users.map(u => u.id);
}, { deep: true });

// Action handlers
const handleAddResource = () => {
  reservationResourceFormRouteName.value = 'reservationResources.store';
  currentlyUsedCapacity.value = 0;
  reservationResourceForm.reset();
  showReservationResourceCreateModal.value = true;
};

const handleAddUser = () => {
  router.reload({
    only: ['allUsers'],
  });
  showReservationAddUserModal.value = true;
};

const handleSubmitUserForm = () => {
  reservationUserForm.put(
    route('reservations.add-users', {
      reservation: props.reservation.id,
    }),
    {
      onSuccess: () => {
        reservationUserForm.reset();
        selectedUsersList.value = [];
        userMultiSelectRef.value?.reset();
        showReservationAddUserModal.value = false;
      },
    },
  );
};

const editReservationResource = (rResource: App.Entities.ReservationResource) => {
  reservationResourceForm.reset();
  reservationResourceForm.id = rResource.id;
  reservationResourceForm.resource_id = rResource.resource_id;
  reservationResourceForm.quantity = rResource.quantity;
  reservationResourceForm.start_time = new Date(rResource.start_time).getTime();
  reservationResourceForm.end_time = new Date(rResource.end_time).getTime();

  reservationResourceFormRouteName.value = 'reservationResources.update';
  currentlyUsedCapacity.value = rResource.quantity;

  showReservationResourceCreateModal.value = true;
};
</script>
