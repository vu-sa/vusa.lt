<template>
  <AdminContentPage title="Atstovavimas">
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2 xl:grid-cols-3">
      <NCard :segmented="{
        footer: 'soft',
      }">
        <template #header>
          <div class="inline-flex items-center gap-2">
            <component :is="Icons.MEETING" />
            Artėjantys susitikimai
          </div>
        </template>
        <span class="mb-4 inline-block text-4xl font-bold">
          <NNumberAnimation :from="0" :to="upcomingMeetings.length" />
        </span>
        <p v-if="upcomingMeetings.length > 0" class="mt-4">
          Kitas susitikimas:
          <Link class="font-bold" :href="route('meetings.show', upcomingMeetings[0].id)">
          {{ formatStaticTime(new
            Date(upcomingMeetings[0].start_time), {
            month: 'long', day:
              'numeric'
          }) }}
          </Link> ({{ upcomingMeetings[0].institutions?.[0].name }})
        </p>
        <p v-else>
          Artėjančių susitikimų nėra! Nepamiršk, kad gali sukurti susitikimą į priekį! 🎉
        </p>
        <template #footer>
          <div class="flex items-center gap-2">
            <NewMeetingButton @click="showMeetingModal = true" />
            <NewMeetingModal :show-modal="showMeetingModal" @close="showMeetingModal = false" />
            <NButton secondary size="small" @click="showAllMeetingModal = true">
              Rodyti visus
            </NButton>
            <CardModal v-model:show="showAllMeetingModal" title="Visi susitikimai" @close="showAllMeetingModal = false">
              <NDataTable :data="meetings" :columns="allMeetingColumns" :pagination="{ pageSize: 7 }" />
            </CardModal>
          </div>
        </template>
      </NCard>
      <NCard :segmented="{
        footer: 'soft',
      }">
        <template #header>
          <div class="inline-flex items-center gap-2">
            <component :is="Icons.INSTITUTION" />
            Institucijos
          </div>
        </template>
        <div class="grid grid-cols-2 gap-2">
          <div>
            <p class="mb-1">
              Visos institucijos
            </p>
          </div>
          <p class="leading-tight">
            Institucijos su <strong>artėjančiais</strong> posėdžiais
          </p>
          <span class="mb-4 inline-block text-4xl font-bold">
            <NNumberAnimation :from="0" :to="institutions.length" />
          </span>
          <span class="mb-4 inline-block text-4xl font-bold">
            <NNumberAnimation :from="0" :to="hasUpcomingMeetingCount" />
          </span>
        </div>
        <template #footer>
          <NButton size="small" secondary @click="showAllInstitutionModal = true">
            Rodyti visas
          </NButton>
          <CardModal v-model:show="showAllInstitutionModal" title="Visos institucijos"
            @close="showAllInstitutionModal = false">
            <NDataTable :data="institutions" :columns="allInstitutionColumns" :pagination="{ pageSize: 7 }" />
          </CardModal>
        </template>
      </NCard>
      <div class="my-calendar">
        <Calendar :is-dark :initial-page :locale="{
          id: $page.props.app.locale,
          firstDayOfWeek: 2,
          masks: { weekdays: 'WW' },
        }" color="red" borderless class="rounded-md shadow-sm" :attributes="calendarAttributes">
          <template #day-popover="{ attributes, dayTitle }">
            <div class="max-w-md">
              <div class="mb-1 text-center text-xs font-semibold text-gray-300 dark:text-zinc-700">
                {{ dayTitle }}
              </div>
              <PopoverRow v-for="attr in attributes" :key="attr.key" :attribute="attr">
                <div class="inline-flex items-center gap-2">
                  <Link :href="route('meetings.show', attr.key)
                    ">
                  {{ attr.popover.label }}
                  </Link>
                </div>
              </PopoverRow>
            </div>
          </template>
        </Calendar>
      </div>
    </div>
    <NDivider v-if="tenants.length > 0" />
    <section v-if="tenants.length > 0" class="mt-8">
      <h3 class="mb-2">Atstovavimas padalinyje</h3>
      <div class="w-48 mb-4">
        <NSelect :value="providedTenant?.id"
          :options="tenants.map(tenant => ({ label: tenant.shortname, value: tenant.id }))"
          @update:value="handleTenantUpdateValue" />
      </div>
      <div class="grid grid-cols-1 gap-4 lg:grid-cols-2 xl:grid-cols-3">
        <NCard title="Visų susitikimų statistika">
          <div ref="wrapper" />
        </NCard>
        <div class="my-calendar">
          <Calendar :is-dark :initial-page :locale="{
            id: $page.props.app.locale,
            firstDayOfWeek: 2,
            masks: { weekdays: 'WW' },
          }" color="red" borderless class="rounded-md shadow-sm" :attributes="tenantCalendarAttributes">
            <template #day-popover="{ attributes, dayTitle }">
              <div class="max-w-md">
                <div class="mb-1 text-center text-xs font-semibold text-gray-300 dark:text-zinc-700">
                  {{ dayTitle }}
                </div>
                <PopoverRow v-for="attr in attributes" :key="attr.key" :attribute="attr">
                  <div class="inline-flex items-center gap-2">
                    <Link :href="route('meetings.show', attr.key)
                      ">
                    {{ attr.popover.label }}
                    </Link>
                  </div>
                </PopoverRow>
              </div>
            </template>
          </Calendar>
        </div>
      </div>
    </section>
  </AdminContentPage>
</template>

<script setup lang="ts">
import "v-calendar/style.css";
import NewMeetingButton from '@/Components/Buttons/NewMeetingButton.vue';
import AdminContentPage from '@/Components/Layouts/AdminContentPage.vue';
import NewMeetingModal from '@/Components/Modals/NewMeetingModal.vue';
import { formatStaticTime } from '@/Utils/IntlTime';
import { Link, router } from '@inertiajs/vue3';
import { computed, h, onMounted, ref, watch } from 'vue';
import { Calendar, PopoverRow } from "v-calendar";
import { useDark } from "@vueuse/core";
import CardModal from "@/Components/Modals/CardModal.vue";
import Icons from "@/Types/Icons/filled";
import { barY, binX, groupX, plot, rectY } from "@observablehq/plot";

const props = defineProps<{
  user: App.Entities.User;
  providedTenant?: App.Entities.Tenant;
  tenants: App.Entities.Tenant[];
}>();

const showMeetingModal = ref(false);
const showAllMeetingModal = ref(false);
const showAllInstitutionModal = ref(false);

const isDark = useDark();

const initialPage = {
  year: new Date().getFullYear(),
  month: new Date().getMonth() + 1,
  day: new Date().getDate(),
};

const institutions = props.user.current_duties.map(duty => {

  if (!duty.institution) {
    // skip if institution is not set
    return null
  }

  return {
    ...duty.institution, hasUpcomingMeetings: duty.institution?.meetings.some(meeting => new Date(meeting.start_time) > new Date())
  }
}).filter(Boolean).filter((institution, index, self) =>
  index === self.findIndex((t) => (
    t.id === institution.id
  ))
)

const hasUpcomingMeetingCount = computed(() => institutions.filter(institution => institution.hasUpcomingMeetings).length);

const meetings = institutions.map(institution => institution.meetings).flat();

const upcomingMeetings = meetings.filter(meeting => new Date(meeting.start_time) > new Date()).sort((a, b) => new Date(a.start_time).getTime() - new Date(b.start_time).getTime());

const calendarAttributes = meetings.map((meeting) => {
  let calendarAttrObject = {
    dates: new Date(meeting.start_time),
    dot: 'red',
    popover: {
      label: meeting.institutions[0].name,
      isInteractive: true,
    },
    key: meeting.id,
  };
  return calendarAttrObject;
});

calendarAttributes.push({
  dates: new Date(),
  highlight: { color: "red", fillMode: "outline" },
  order: 1,
});

const allMeetingColumns = [
  {
    title: 'Institucija',
    key: 'institution.title',
    render(row) {
      return h(Link, { href: route('institutions.show', row.institutions[0].id), }, row.institutions[0].name);
    },
  },
  {
    title: 'Susitikimo pradžia',
    key: 'start_time',
    render: (row) => h(Link, { href: route('meetings.show', row.id) }, formatStaticTime(new Date(row.start_time), { year: 'numeric', month: 'long', day: 'numeric', hour: 'numeric', minute: 'numeric' })),
    sorter: (a, b) => new Date(a.start_time).getTime() - new Date(b.start_time).getTime(),
    defaultSortOrder: 'descend',
  },
];

const allInstitutionColumns = [
  {
    title: 'Pavadinimas',
    key: 'name',
    render(row) {
      return h(Link, { href: route('institutions.show', row.id) }, row.name);
    },
  },
  {
    title: 'Artėjantys posėdžiai',
    key: 'meetings',
    render(row) {
      return row.hasUpcomingMeetings ? 'Taip' : 'Ne';
    },
  },
];

const handleTenantUpdateValue = (value) => {
  router.reload({ data: { tenant_id: value } });
}

const allTenantMeetings = computed(() => props.providedTenant?.institutions.map(institution => {
  return institution.meetings?.map(meeting => {
    return {
      institution: institution.name,
      start_time: new Date(meeting.start_time),
      id: meeting.id,
    }
  })
}).flat())

const tenantCalendarAttributes = computed(() => allTenantMeetings.value?.map((meeting) => {
  let calendarAttrObject = {
    dates: meeting?.start_time,
    dot: 'red',
    popover: {
      label: meeting?.institution,
      isInteractive: true,
    },
    key: meeting?.id,
  };
  return calendarAttrObject;
}))

const wrapper = ref(null);

const generatePlot = () => plot({
  x: { type: "time", label: "Laikas" },
  // don't show decimal
  y: { grid: true, label: "Susitikimų skaičius", round: true, nice: true, ticks: 3 },
  marks: [
    rectY(allTenantMeetings.value, binX({ y: "count" }, {
      x: "start_time", fill: '#aa2430ee', interval: 'month'
    })),
  ],
  marginTop: 30,
  marginBottom: 45,
  width: 320,
  height: 190,
});

watch(() => allTenantMeetings.value, () => {
  if (wrapper.value) {
    wrapper.value.innerHTML = ''
    wrapper.value.appendChild(generatePlot())
  }
});

onMounted(() => {
  if (wrapper.value) {
    wrapper.value?.appendChild(generatePlot());
  }
});

</script>

<style scoped>
.my-calendar :deep(.vc-container.vc-dark) {
  @apply bg-zinc-800;
}

.my-calendar :deep(button.vc-arrow) {
  background-color: transparent;
}

.vc-container {
  font-family: "Inter", sans-serif !important;
  border: 0 !important;
}
</style>
