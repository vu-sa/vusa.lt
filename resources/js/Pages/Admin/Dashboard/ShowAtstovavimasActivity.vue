<template>
  <AdminContentPage :title="`Pokyčių istorija (${date} | ${providedTenant.shortname})`">
    <div class="flex gap-2">
      <NFormItem label="Veiksmo data">
        <NDatePicker v-model:formatted-value="form.date" value-format="yyyy-MM-dd" type="date" class="mb-6"
          @update:formatted-value="handleUpdate" />
      </NFormItem>
      <NFormItem class="grow" label="Padalinys">
        <NSelect v-model:value="form.tenant_id"
          :options="tenants.map(tenant => ({ label: tenant.shortname, value: tenant.id }))"
          @update:value="handleTenantUpdateValue" />
      </NFormItem>
    </div>
    <!-- Show activities of each meeting and changedAgendaItems -->
    <section v-for="meeting in filteredMeetings" :key="meeting.id"
      class="bg-white mb-4 dark:bg-zinc-950 p-6 shadow-sm border dark:border-zinc-800 rounded-sm">
      <h2 class="mb-6 border-b pb-3 dark:border-zinc-800">
        <SmartLink :href="route('institutions.show', meeting.institutions?.at(0)?.id)">
          {{ meeting.institutions?.at(0)?.name }}
        </SmartLink>
      </h2>
      <div class="border p-4 rounded-md">
        <div class="flex items-center gap-2 mb-4">
          <Icons.MEETING />
          <h3 class="text-lg mb-0">
            <SmartLink :href="route('meetings.show', meeting.id)">
              {{ meeting.title }}
            </SmartLink>
          </h3>
        </div>

        <div v-if="meeting.activities?.length > 0" class="flex flex-col gap-4 mb-4">
          <div v-for="activity in meeting.activities" :key="activity.id"
            class="dark:bg-zinc-900 bg-zinc-100 p-3 rounded-sm">
            <ActivityLogItem :activity="activity" />
          </div>
        </div>
        <div v-if="meeting.changedAgendaItems?.length > 0" class="p-4 border dark:border-zinc-700 my-2 rounded-lg">
          <NCollapse accordion default-expanded-names="changedAgendaItems">
            <NCollapseItem title="Pakeisti darbotvarkės punktai" name="changedAgendaItems">
              <div v-for="agendaItem in meeting.changedAgendaItems" :key="agendaItem.id"
                class="mb-4 border-b pb-4 last:border-0 dark:border-zinc-700 last:pb-0">
                <div class="flex items-center gap-1 mb-3">
                  <Icons.AGENDA_ITEM width="14" height="14" />
                  <h5>
                    {{ agendaItem.title }}
                  </h5>
                </div>
                <div v-if="agendaItem.activities?.length > 0" class="flex flex-col gap-4">
                  <div v-for="activity in agendaItem.activities" :key="activity.id"
                    class="bg-zinc-100 dark:bg-zinc-900 p-3 rounded-sm">
                    <ActivityLogItem :activity="activity" />
                  </div>
                </div>
              </div>
            </NCollapseItem>
          </NCollapse>
        </div>
      </div>
    </section>
    <div v-if="filteredMeetings.length === 0" class="text-zinc-500">
      Nėra pokyčių šią dieną.
      <ul class="list-disc list-inside">
        <li>
          {{ providedTenant ? `Pasirinktas padalinys: ${providedTenant.shortname}` : '' }}
        </li>
        <li>
          {{ date ? `Pasirinkta data: ${date}` : '' }}
        </li>
      </ul>
    </div>
  </AdminContentPage>
</template>

<script setup lang="ts">
import AdminContentPage from '@/Components/Layouts/AdminContentPage.vue';
import SmartLink from '@/Components/Public/SmartLink.vue';
import ActivityLogItem from '@/Features/Admin/ActivityLogViewer/ActivityLogItem.vue';
import Icons from '@/Types/Icons/filled';
import { router, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
  meetings: Array<App.Entities.Meeting & { changedAgendaItems: App.Entities.AgendaItem[] }>;
  date: string;
  tenants: App.Entities.Tenant[];
  providedTenant: App.Entities.Tenant;
}>();

const form = useForm({
  date: props.date,
  tenant_id: props.providedTenant.id,
});

// Filter meetings by if they have an activity on this date
const filteredMeetings = computed(() => {
  return props.meetings.filter(meeting => {
    return meeting.activities?.some(activity => activity.created_at.includes(props.date));
  });
});

const handleUpdate = (value: string) => {
  router.visit(route('dashboard.atstovavimas.summary', { date: value, tenant_id: props.providedTenant.id }));
};

const handleTenantUpdateValue = (value) => {
  router.reload({ data: { tenant_id: value } });
}

</script>
