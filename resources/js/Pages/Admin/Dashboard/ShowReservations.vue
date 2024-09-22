<template>
  <AdminContentPage title="Rezervacijos">
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2 xl:grid-cols-3">
      <NCard :segmented="{
        footer: 'soft',
      }">
        <template #header>
          <div class="inline-flex items-center gap-2">
            <component :is="Icons.RESERVATION" />
            Rezervacijos
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
            <NButton size="small">
              <template #icon>
                <IFluentBookmarkAdd24Filled />
              </template>
              Kurti naują
            </NButton>
            </Link>

            <NButton size="small" secondary @click="showReservationsModal = true">
              <template #icon>
                <Icons.RESERVATION />
              </template>
              {{ $t('Peržiūrėti visas') }}
            </NButton>
            <CardModal v-model:show="showReservationsModal" title="Visos rezervacijos"
              @close="showReservationsModal = false">
              <NDataTable :data="reservations" :columns="reservationColumns" :pagination="{ pageSize: 7 }" />
            </CardModal>
          </div>
        </template>
      </NCard>
    </div>
    <NDivider v-if="tenants.length > 0" />
    <section v-if="tenants.length > 0" class="mt-8">
      <div class="mb-8 inline-flex items-center gap-6">
        <h3 class="mb-0">
          Rezervacijos padalinyje
        </h3>
        <div>
          <NSelect :value="providedTenant?.id" filterable
            :options="tenants.map(tenant => ({ label: tenant.shortname, value: tenant.id }))"
            @update:value="handleTenantUpdateValue" />
        </div>
      </div>
      <h4> Rezervacijos su padalinio ištekliais </h4>
      <ReservationsWithUnitResources :pagination="{ pageSize: 8 }"
        :active-reservations="providedTenant?.activeReservations" />
      <h4> Padalinio žmonių sukurtos rezervacijos </h4>
      <ReservationsWithUnitResources :pagination="{ pageSize: 8 }"
        :active-reservations="providedTenant?.reservations" />
    </section>
  </AdminContentPage>
</template>

<script setup lang="ts">
import AdminContentPage from '@/Components/Layouts/AdminContentPage.vue';
import CardModal from '@/Components/Modals/CardModal.vue';
import ReservationsWithUnitResources from '@/Components/Tables/ReservationsWithUnitResources.vue';
import Icons from "@/Types/Icons/filled";
import { formatStaticTime } from '@/Utils/IntlTime';
import { Link, router } from '@inertiajs/vue3';
import { h, ref } from 'vue';

const { reservations, tenants, providedTenant } = defineProps<{
  reservations: App.Entities.Reservation[];
  tenants: App.Entities.Tenant[];
  providedTenant: App.Entities.Tenant | null;
}>();

console.log(reservations, providedTenant);

const showReservationsModal = ref(false);

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

console.log(reservations);
</script>
