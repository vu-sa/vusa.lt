<template>
  <NFormItem label="Rodyti laikus">
    <NSwitch v-model:value="showTimes" />
  </NFormItem>
  <div class="flex flex-col gap-4">
    <div ref="programmeEl" class="mb-3 flex flex-col gap-2">
      <ProgrammeDay v-for="(day, index) in programmeDays" :key="day.id" v-model:day="programmeDays[index]"
        :data-id="day.id">
        <template #buttons>
          <NButton size="tiny" secondary circle @click="deleteProgrammeDay(index)">
            <template #icon>
              <IFluentDelete24Filled />
            </template>
          </NButton>
        </template>
      </ProgrammeDay>
    </div>
  </div>
  <NButton rounded @click="createDay">
    <template #icon>
      <IFluentCalendarAdd24Regular />
    </template>
    Pridėti programos dieną
  </NButton>
</template>

<script setup lang="ts">
import { provide, ref, useTemplateRef } from 'vue';
import { useSortable } from '@vueuse/integrations/useSortable'

import ProgrammeDay from './ProgrammeDay.vue';

defineEmits<{
  (e: "close"): void;
}>();

const props = defineProps<{
  programme: App.Entities.Programme
  show: boolean;
  startTime: Date;
}>();

const programmeEl = useTemplateRef<HTMLDivElement | null>('programmeEl')
const programmeDays = ref(props.programme.days);

const showTimes = ref(false);
provide('show-times', showTimes);

const movedElement = ref<App.Entities.ProgrammeSection | App.Entities.ProgrammePart | null>(null);
const updateMovedElement = (element: App.Entities.ProgrammeSection | App.Entities.ProgrammePart) => {
  movedElement.value = element;
}
provide('movedElement', { movedElement, updateMovedElement });

useSortable<HTMLDivElement | null>(programmeEl, programmeDays, {
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
  programmeDays.value?.splice(index, 1);
}
</script>
