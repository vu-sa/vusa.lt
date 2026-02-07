<template>
  <FormFieldWrapper v-if="showTimeSwitch" id="show-times" label="Rodyti laikus">
    <Switch :checked="showTimes" @update:checked="val => showTimes = val" />
  </FormFieldWrapper>
  <div class="flex flex-col gap-4 bg-white dark:bg-zinc-800 rounded-lg">
    <div ref="programmeEl" class="mb-3 flex flex-col gap-2">
      <ProgrammeDay v-for="(day, index) in programmeDays" :key="day.id" v-model:day="programmeDays[index]"
        :data-id="day.id">
        <template v-if="editable" #buttons>
          <Button size="icon-xs" variant="ghost" class="rounded-full" @click="showDayEditModal = true; selectedDay = day">
            <IFluentEdit24Filled />
          </Button>
          <Button size="icon-xs" variant="ghost" class="rounded-full" @click="deleteProgrammeDay(index)">
            <IFluentDelete24Filled />
          </Button>
        </template>
      </ProgrammeDay>
    </div>
  </div>
  <CardModal v-model:show="showDayEditModal" @close="showDayEditModal = false">
    <FormFieldWrapper id="day-title" label="Dienos pavadinimas">
      <MultiLocaleInput v-model:input="selectedDay.title" />
    </FormFieldWrapper>
    <FormFieldWrapper id="day-start-time" label="Dienos pradžios laikas">
      <Input
        type="datetime-local"
        :value="formatDatetimeLocal(selectedDay.start_time)"
        @input="(e: Event) => selectedDay.start_time = parseDatetimeLocal((e.target as HTMLInputElement).value)"
      />
    </FormFieldWrapper>
    <Button variant="outline" @click="showDayEditModal = false">
      Uždaryti
    </Button>
  </CardModal>
  <div class="flex items-center justify-between gap-2">
    <Button v-if="editable" variant="outline" @click="createDay">
      <IFluentCalendarAdd24Regular />
      Pridėti programos dieną
    </Button>
    <Button v-if="editable" @click="submitForm">
      Išsaugoti
    </Button>
  </div>
</template>

<script setup lang="ts">
import { provide, ref, useTemplateRef } from 'vue';
import { useSortable } from '@vueuse/integrations/useSortable'
import { router } from "@inertiajs/vue3";

import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Switch } from '@/Components/ui/switch';
import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import ProgrammeDay from './ProgrammeDay.vue';
import MultiLocaleInput from '@/Components/FormItems/MultiLocaleInput.vue';
import CardModal from '@/Components/Modals/CardModal.vue';

defineEmits<{
  (e: "close"): void;
}>();

const props = defineProps<{
  programme: App.Entities.Programme
  editable?: boolean;
  showTimes?: boolean;
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

function formatDatetimeLocal(value: string | number | null | undefined): string {
  if (!value) return '';
  const date = new Date(value);
  if (isNaN(date.getTime())) return '';
  const pad = (n: number) => String(n).padStart(2, '0');
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

function parseDatetimeLocal(value: string): string {
  if (!value) return '';
  return new Date(value).toISOString();
}

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
