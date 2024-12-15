<template>
  <div class="flex flex-row gap-1 rounded-lg bg-zinc-50/50 px-2 py-1">
    <div class="my-1 h-auto w-1 bg-black" />
    <div class="px-1">
      <div class="flex gap-2">
        <button :class="[handleClass]"
          class="rounded-lg px-2 py-0.5 text-left font-bold transition hover:bg-zinc-200/70">
          {{ part.title[$page.props.app.locale] }}
        </button>
        <span class="text-gray-500">
          {{ part?.id ? 'ID: ' + part.id : '' }}
        </span>
        <slot name="buttons" />
      </div>
      <div>
        <div class="px-2 text-zinc-500">
          <template v-if="showTimes">
            {{ formatStaticTime(new Date(partStartTime), { hour: 'numeric', minute: '2-digit' }) }}-{{
              formatStaticTime(new Date(partEndTime),
                {
                  hour: 'numeric', minute: '2-digit'
                }) }}
          </template>
          <template v-else>
            {{ part.duration }} min.
          </template>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { formatStaticTime } from '@/Utils/IntlTime';
import { computed, inject, type Ref, watch } from 'vue';

const part = defineModel<App.Entities.ProgrammePart>('element')

const { parent, sectionStartTime } = defineProps<{
  parent: any
  handleClass?: string;
  sectionStartTime?: number;
}>();

const showTimes = inject<Ref<boolean>>('show-times');

const partStartTime = computed(() => {
  // get duration of all previous parts from order of the array
  if (parent.type === 'day') {
    const elapsedTime = parent.elements
      .slice(0, parent.elements.indexOf(part.value))
      .reduce((acc, element) => acc + element.duration, 0);

    return (new Date(parent.start_time)).getTime() + 1000 * 60 * elapsedTime;
  } else if (parent.type === 'block') {
    const elapsedTime = parent.parts
      .slice(0, parent.parts.indexOf(part.value))
      .reduce((acc, element) => acc + element.duration, 0);

    return (new Date(sectionStartTime)).getTime() + 1000 * 60 * elapsedTime;
  } else {
    return '';
  }
});

const partEndTime = computed(() => {
  return (new Date(partStartTime.value)).getTime() + 1000 * 60 * part.value?.duration;
});
</script>
