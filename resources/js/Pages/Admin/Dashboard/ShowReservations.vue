<template>
  <AdminContentPage title="Rezervacijos">
    <ThemeProvider>
      <div class="grid grid-cols-1 gap-4 lg:grid-cols-2 2xl:grid-cols-3">
      <NCard :segmented="{
        footer: 'soft',
      }">
        <template #header>
          <div class="inline-flex items-center gap-2">
            <component :is="Icons.RESERVATION" />
            Tavo rezervacijos
          </div>
        </template>
        <div class="grid grid-cols-2 gap-2">
          <p>Visos</p>
          <p>✅ Užbaigtos</p>
          <span class="inline-block text-4xl font-bold">
            <NNumberAnimation :from="0" :to="reservations.length" />
          </span>
          <span class="inline-block text-4xl font-bold">
            <NNumberAnimation :from="0" :to="reservations.filter(reservation => reservation.isCompleted).length" />
          </span>
        </div>
        <p class="mt-4">
          Užbaigtoje rezervacijoje visi daiktai yra pažymėti kaip grąžinti.
        </p>
        <template #footer>
          <div class="flex items-center gap-2">
            <Link :href="route('reservations.create')">
            <Button size="sm">
              <IFluentBookmarkAdd24Filled />
              Kurti naują
            </Button>
            </Link>

            <Button size="sm" variant="secondary" @click="showReservationsModal = true">
              <Icons.RESERVATION />
              {{ $t('Peržiūrėti visas') }}
            </Button>
            <CardModal v-model:show="showReservationsModal" title="Visos rezervacijos"
              @close="showReservationsModal = false">
              <NDataTable :data="reservations" :columns="reservationColumns" :pagination="{ pageSize: 7 }" />
            </CardModal>
          </div>
        </template>
      </NCard>
      <NCard :segmented="{
        footer: 'soft',
      }">
        <template #header>
          <div class="inline-flex items-center gap-2">
            <component :is="Icons.RESERVATION" />
            Skolinami daiktai
          </div>
        </template>
        <div class="grid grid-cols-2 gap-2">
          <p>Visi daiktai</p>
          <p>Iš viso skirtingų išteklių</p>
          <span class="inline-block text-4xl font-bold">
            <NNumberAnimation :from="0" :to="resources.sumOfCapacity" />
          </span>
          <span class="inline-block text-4xl font-bold">
            <NNumberAnimation :from="0" :to="resources.active" />
          </span>
        </div>
        <template #footer>
          <div class="flex items-center gap-2">
            <Link :href="route('resources.index')">
            <Button size="sm" variant="secondary">
              <IFluentCube24Filled />
              Peržiūrėti visus
            </Button>
            </Link>
          </div>
        </template>
      </NCard>
      </div>
    </ThemeProvider>
    <Separator v-if="tenants.length > 0" class="my-8" />
    <section v-if="tenants.length > 0" class="mt-8">
      <div class="mb-8 flex flex-wrap items-center justify-between gap-4">
        <div class="inline-flex items-center gap-6">
          <h3 class="mb-0">
            Rezervacijos padalinyje
          </h3>
          <div>
            <ThemeProvider>
              <NSelect :value="providedTenant?.id" filterable
                :options="tenants.map(tenant => ({ label: tenant.shortname, value: tenant.id }))"
                @update:value="handleTenantUpdateValue" />
            </ThemeProvider>
          </div>
        </div>
        <Link :href="route('tasks.summary', { taskable_type: 'App\\Models\\Reservation' })">
          <Button variant="outline" size="sm" class="gap-2">
            <IFluentClipboardTask24Filled class="h-4 w-4" />
            {{ $t('tasks.summary.view_reservation_tasks') }}
          </Button>
        </Link>
      </div>
      <ThemeProvider>
        <NCard :segmented="{
          footer: 'soft',
        }">
        <template #header>
          <div class="inline-flex items-center gap-2">
            <component :is="Icons.RESERVATION" />
            Aktyvios rezervacijos
          </div>
        </template>
        <div class="grid grid-cols-2 gap-2">
          <p>Padalinio daiktų</p>
          <p>Tos, kurias sukūrė padalinio žmonės</p>
          <span class="inline-block text-4xl font-bold">
            <NNumberAnimation :from="0"
              :to="providedTenant?.activeReservations?.filter(reservation => !reservation.isCompleted).length" />
          </span>
          <span class="inline-block text-4xl font-bold">
            <NNumberAnimation :from="0" :to="providedTenant?.reservations?.filter(reservation => !reservation.isCompleted).length" />
          </span>
        </div>
        <template #footer>
          <div class="flex items-center gap-2">
            <Button size="sm" @click="showTenantReservationsModal = true">
              <Icons.RESERVATION />
              Peržiūrėti aktyvias
            </Button>
            <Button size="sm" variant="secondary" @click="showTenantUsersReservationsModal = true">
              <Icons.TENANT />
              Ką skolinasi padalinys?
            </Button>
          </div>
          <CardModal v-model:show="showTenantReservationsModal" class="max-w-6xl" title="Visos rezervacijos"
            @close="showTenantReservationsModal = false">
            <h4> Rezervacijos su padalinio ištekliais </h4>
            <ReservationsWithUnitResources show-if-completed :pagination="{ pageSize: 8 }"
              :active-reservations="providedTenant?.activeReservations" />
          </CardModal>
          <CardModal v-model:show="showTenantUsersReservationsModal" class="max-w-6xl" title="Visos rezervacijos"
            @close="showTenantUsersReservationsModal = false">
            <h4> Padalinio žmonių sukurtos rezervacijos </h4>
            <ReservationsWithUnitResources show-if-completed :pagination="{ pageSize: 8 }"
              :active-reservations="providedTenant?.reservations" />
          </CardModal>
        </template>
      </NCard>
      </ThemeProvider>
    </section>
  </AdminContentPage>
</template>

<script setup lang="ts">
//import UserAvatar from '@/Components/Avatars/UserAvatar.vue';
import { Link, router } from '@inertiajs/vue3';
import { h, ref, computed } from 'vue';
import { trans as $t } from "laravel-vue-i18n";

import AdminContentPage from '@/Components/Layouts/AdminContentPage.vue';
import { Button } from '@/Components/ui/button';
import CardModal from '@/Components/Modals/CardModal.vue';
import ReservationsWithUnitResources from '@/Components/Tables/ReservationsWithUnitResources.vue';
import { Separator } from '@/Components/ui/separator';
import Icons from "@/Types/Icons/filled";
import { formatStaticTime } from '@/Utils/IntlTime';
import { usePageBreadcrumbs, BreadcrumbHelpers } from '@/Composables/useBreadcrumbsUnified';
import IFluentBookmarkAdd24Filled from '~icons/fluent/bookmark-add-24-filled';
import IFluentCube24Filled from '~icons/fluent/cube-24-filled';
import IFluentClipboardTask24Filled from '~icons/fluent/clipboard-task-24-filled';
import ThemeProvider from "@/Components/Providers/ThemeProvider.vue";

const { reservations, tenants, providedTenant } = defineProps<{
  reservations: App.Entities.Reservation[];
  resources: {
    active: number;
    sumOfCapacity: number;
  };
  tenants: App.Entities.Tenant[];
  providedTenant: App.Entities.Tenant | null;
}>();

const showReservationsModal = ref(false);
const showTenantReservationsModal = ref(false);
const showTenantUsersReservationsModal = ref(false);

const handleTenantUpdateValue = (value) => {
  router.reload({ data: { tenant_id: value } });
}

const reservationColumns = [
  {
    title: 'Pavadinimas',
    key: 'name',
    render(row: App.Entities.Reservation) {
      return h(Link, { href: route('reservations.show', { reservation: row.id }) }, row.name);
    },
  },
  {
    title: 'Laikas',
    key: 'start_time',
    render: (row: App.Entities.Reservation) => formatStaticTime(new Date(row.start_time)),
  },
  {
    title: 'Pabaigos laikas',
    key: 'end_time',
    render: (row: App.Entities.Reservation) => formatStaticTime(new Date(row.end_time)),
  },
  {
    title: 'Ar užbaigta',
    key: 'isCompleted',
    render: (row: App.Entities.Reservation) => row.isCompleted ? 'Taip' : 'Ne',
  },
];

// Setup breadcrumbs for the Reservations page
usePageBreadcrumbs([
  { label: $t('Rezervacijos'), icon: Icons.RESERVATION }
]);
</script>
