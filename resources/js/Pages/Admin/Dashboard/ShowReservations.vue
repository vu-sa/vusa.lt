<template>
  <AdminContentPage title="Rezervacijos">
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2 2xl:grid-cols-3">
      <Card>
        <CardHeader>
          <CardTitle>
            <div class="inline-flex items-center gap-2">
              <component :is="Icons.RESERVATION" />
              Tavo rezervacijos
            </div>
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div class="grid grid-cols-2 gap-2">
            <p>Visos</p>
            <p>Užbaigtos</p>
            <span class="inline-block text-4xl font-bold">
              {{ reservations.length }}
            </span>
            <span class="inline-block text-4xl font-bold">
              {{ reservations.filter(reservation => reservation.isCompleted).length }}
            </span>
          </div>
          <p class="mt-4">
            Užbaigtoje rezervacijoje visi daiktai yra pažymėti kaip grąžinti.
          </p>
        </CardContent>
        <CardFooter>
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
              <SimpleDataTable :data="reservations" :columns="reservationColumns" enable-pagination :page-size="7" />
            </CardModal>
          </div>
        </CardFooter>
      </Card>
      <Card>
        <CardHeader>
          <CardTitle>
            <div class="inline-flex items-center gap-2">
              <component :is="Icons.RESERVATION" />
              Skolinami daiktai
            </div>
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div class="grid grid-cols-2 gap-2">
            <p>Visi daiktai</p>
            <p>Iš viso skirtingų išteklių</p>
            <span class="inline-block text-4xl font-bold">
              {{ resources.sumOfCapacity }}
            </span>
            <span class="inline-block text-4xl font-bold">
              {{ resources.active }}
            </span>
          </div>
        </CardContent>
        <CardFooter>
          <div class="flex items-center gap-2">
            <Link :href="route('resources.index')">
              <Button size="sm" variant="secondary">
                <IFluentCube24Filled />
                Peržiūrėti visus
              </Button>
            </Link>
          </div>
        </CardFooter>
      </Card>
    </div>
    <Separator v-if="tenants.length > 0" class="my-8" />
    <section v-if="tenants.length > 0" class="mt-8">
      <div class="mb-8 flex flex-wrap items-center justify-between gap-4">
        <div class="inline-flex items-center gap-6">
          <h3 class="mb-0">
            Rezervacijos padalinyje
          </h3>
          <div>
            <Select :model-value="selectedTenantId" @update:model-value="handleTenantUpdateValue">
              <SelectTrigger class="w-[200px]">
                <SelectValue placeholder="Pasirinkite padalinį" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="tenant in tenants" :key="tenant.id" :value="String(tenant.id)">
                  {{ tenant.shortname }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>
        </div>
        <Link :href="route('tasks.summary', { taskable_type: 'App\\Models\\Reservation' })">
          <Button variant="outline" size="sm" class="gap-2">
            <IFluentClipboardTask24Filled class="h-4 w-4" />
            {{ $t('tasks.summary.view_reservation_tasks') }}
          </Button>
        </Link>
      </div>
      <Card>
        <CardHeader>
          <CardTitle>
            <div class="inline-flex items-center gap-2">
              <component :is="Icons.RESERVATION" />
              Aktyvios rezervacijos
            </div>
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div class="grid grid-cols-2 gap-2">
            <p>Padalinio daiktų</p>
            <p>Tos, kurias sukūrė padalinio žmonės</p>
            <span class="inline-block text-4xl font-bold">
              {{ providedTenant?.activeReservations?.filter(reservation => !reservation.isCompleted).length }}
            </span>
            <span class="inline-block text-4xl font-bold">
              {{ providedTenant?.reservations?.filter(reservation => !reservation.isCompleted).length }}
            </span>
          </div>
        </CardContent>
        <CardFooter>
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
        </CardFooter>
      </Card>
    </section>
  </AdminContentPage>
</template>

<script setup lang="tsx">
import { Link, router } from '@inertiajs/vue3';
import { h, ref, computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import type { ColumnDef } from '@tanstack/vue-table';

import AdminContentPage from '@/Components/Layouts/AdminContentPage.vue';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@/Components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import CardModal from '@/Components/Dialogs/CardModal.vue';
import ReservationsWithUnitResources from '@/Components/Tables/ReservationsWithUnitResources.vue';
import SimpleDataTable from '@/Components/Tables/SimpleDataTable.vue';
import { Separator } from '@/Components/ui/separator';
import Icons from '@/Types/Icons/filled';
import { formatStaticTime } from '@/Utils/IntlTime';
import { usePageBreadcrumbs, BreadcrumbHelpers } from '@/Composables/useBreadcrumbsUnified';
import IFluentBookmarkAdd24Filled from '~icons/fluent/bookmark-add-24-filled';
import IFluentCube24Filled from '~icons/fluent/cube-24-filled';
import IFluentClipboardTask24Filled from '~icons/fluent/clipboard-task-24-filled';

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

const selectedTenantId = computed(() => providedTenant?.id ? String(providedTenant.id) : undefined);

const handleTenantUpdateValue = (value: string) => {
  router.reload({ data: { tenant_id: Number(value) } });
};

const reservationColumns: ColumnDef<App.Entities.Reservation, any>[] = [
  {
    header: 'Pavadinimas',
    accessorKey: 'name',
    cell: ({ row }) => (
      <Link href={route('reservations.show', { reservation: row.original.id })}>
        {row.getValue('name')}
      </Link>
    ),
  },
  {
    header: 'Laikas',
    accessorKey: 'start_time',
    cell: ({ row }) => formatStaticTime(new Date(row.original.start_time)),
  },
  {
    header: 'Pabaigos laikas',
    accessorKey: 'end_time',
    cell: ({ row }) => formatStaticTime(new Date(row.original.end_time)),
  },
  {
    header: 'Ar uzbaigta',
    accessorKey: 'isCompleted',
    cell: ({ row }) => row.original.isCompleted ? 'Taip' : 'Ne',
  },
];

// Setup breadcrumbs for the Reservations page
usePageBreadcrumbs([
  { label: $t('Rezervacijos'), icon: Icons.RESERVATION },
]);
</script>
