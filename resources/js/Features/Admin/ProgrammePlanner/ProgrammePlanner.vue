<template>
  <NFormItem v-if="showTimeSwitch" label="Rodyti laikus">
    <NSwitch v-model:value="showTimes" />
  </NFormItem>
  <div class="flex flex-col gap-4">
    <div ref="programmeEl" class="mb-3 flex flex-col gap-2">
      <ProgrammeDay v-for="(day, index) in programmeDays" :key="day.id" v-model:day="programmeDays[index]"
        :data-id="day.id">
        <template v-if="showEditDeleteButtons" #buttons>
          <NButton size="tiny" secondary circle @click="showDayEditModal = true; selectedDay = day">
            <template #icon>
              <IFluentEdit24Filled />
            </template>
          </NButton>
          <NButton size="tiny" secondary circle @click="deleteProgrammeDay(index)">
            <template #icon>
              <IFluentDelete24Filled />
            </template>
          </NButton>
        </template>
      </ProgrammeDay>
    </div>
  </div>
  <CardModal v-model:show="showDayEditModal" @close="showDayEditModal = false">
    <NFormItem label="Dienos pavadinimas">
      <MultiLocaleInput v-model:input="selectedDay.title" />
    </NFormItem>
    <NFormItem label="Dienos pradžios laikas">
      <NDatePicker v-model:value="selectedDay.start_time" :first-day-of-week="0" :format="'yyyy-MM-dd HH:mm'"
        :time-picker-props="{
          format: 'HH:mm',
          minutes: 5,
          hours: Array.from({ length: 22 - 8 + 1 }, (v, i) => i + 8),
        }" type="datetime" clearable :actions="['confirm']" />
    </NFormItem>
    <NButton @click="showDayEditModal = false">
      Uždaryti
    </NButton>
  </CardModal>
  <NButton v-if="editable" rounded @click="createDay">
    <template #icon>
      <IFluentCalendarAdd24Regular />
    </template>
    Pridėti programos dieną
  </NButton>
  <NButton v-if="editable" type="primary" @click="submitForm">
    Išsaugoti
  </NButton>
</template>

<script setup lang="ts">
import { provide, ref, useTemplateRef } from 'vue';
import { useSortable } from '@vueuse/integrations/useSortable'
import { router } from "@inertiajs/vue3";

import ProgrammeDay from './ProgrammeDay.vue';
import MultiLocaleInput from '@/Components/FormItems/MultiLocaleInput.vue';
import CardModal from '@/Components/Modals/CardModal.vue';

defineEmits<{
  (e: "close"): void;
}>();

const props = defineProps<{
  programme: App.Entities.Programme
  editable?: boolean;
  showTimes: boolean;
  showTimeSwitch?: boolean;
  startTime: Date;
}>();

const programmeEl = useTemplateRef<HTMLDivElement | null>('programmeEl')
const programmeDays = ref(props.programme.days);

const selectedDay = ref<App.Entities.ProgrammeDay | null>(null);
const showDayEditModal = ref(false);

const showTimes = ref(props.showTimes);
provide('show-times', showTimes);

provide('editable', props.editable);

const movedElement = ref<App.Entities.ProgrammeSection | App.Entities.ProgrammePart | null>(null);
const updateMovedElement = (element: App.Entities.ProgrammeSection | App.Entities.ProgrammePart) => {
  movedElement.value = element;
}
provide('movedElement', { movedElement, updateMovedElement });

if (props.editable) useSortable<HTMLDivElement | null>(programmeEl, programmeDays, {
  handle: '.day-handle',
});

function createDay() {
  programmeDays.value?.push({
    id: 'programme-day-' + Math.random().toString(36).substring(7),
    title: {
      lt: String(programmeDays.value.length + 1) + ' diena',
      en: String(programmeDays.value.length + 1) + ' day',
    },
    type: 'day',
    elements: [],
    start_time: (new Date(props.startTime.getTime() + 1000 * 60 * 60 * 24 * programmeDays.value.length)).toISOString(),
  });
}

function deleteProgrammeDay(index: number) {
  router.delete(route('programmeDays.destroy', { programmeDay: programmeDays.value[index].id }), {
    onSuccess: () => {
      programmeDays.value?.splice(index, 1);
    },
    preserveScroll: true,
  });
}

function submitForm() {
  router.patch(route('programmes.update', { programme: props.programme.id }), {
    days: programmeDays.value,
  }, {
    preserveScroll: true,
    onSuccess: () => {
      programmeDays.value = props.programme.days
    },
  });
}
</script>
