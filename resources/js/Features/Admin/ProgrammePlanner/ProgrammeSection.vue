<template>
  <div class="my-2 rounded-md border bg-zinc-50/50 p-4 shadow-sm">
    <div class="mb-2 flex flex-row items-center gap-4">
      <button
        class="inline-flex items-center gap-2 rounded-md px-2 py-1 text-2xl font-bold tracking-tight transition hover:bg-zinc-200"
        :class="[handleClass]">
        <IFluentWindowBulletList20Filled /> {{ section?.title[$page.props.app.locale] ?? section?.title }}
      </button>
      <span v-if="showTimes" class="text-zinc-500">
        {{ formatStaticTime(new Date(sectionStartTime), { hour: 'numeric', minute: '2-digit' }) }}-{{
          formatStaticTime(new Date(sectionEndTime),
            {
              hour: 'numeric', minute: '2-digit'
            }) }}
      </span>
      <span v-else class="text-zinc-500">
        <template v-if="section?.duration > 60">
          {{ Math.floor(section?.duration / 60) }} val. {{ section?.duration % 60 }} min.
        </template>
        <template v-else>
          {{ section?.duration }} min.
        </template>
      </span>
      <slot name="buttons" />
    </div>
    <div class="mb-2 grid grid-cols-2 gap-x-4">
      <ProgrammeBlock v-for="(block, index) in section.blocks" :key="block.id" v-model:block="section.blocks[index]"
        :section-start-time>
        <template v-if="editable" #buttons>
          <NButton size="tiny" secondary circle @click="deleteProgrammeBlock(index)">
            <template #icon>
              <IFluentDelete24Filled />
            </template>
          </NButton>
        </template>
      </ProgrammeBlock>
    </div>
    <NTooltip v-if="editable">
      <template #trigger>
        <NButton size="small" circle @click="createProgrammeBlock">
          <template #icon>
            <IFluentLayerDiagonalAdd24Regular />
          </template>
        </NButton>
      </template>
      Pridėti programos bloką
    </NTooltip>
  </div>
</template>

<script setup lang="ts">
import { computed, inject, type Ref } from 'vue';
import { router } from "@inertiajs/vue3";

import { formatStaticTime } from '@/Utils/IntlTime';
import ProgrammeBlock from './ProgrammeBlock.vue';

const section = defineModel<App.Entities.ProgrammeSection | App.Entities.ProgrammePart>('element')

const { parent } = defineProps<{
  parent: App.Entities.ProgrammeDay;
  handleClass?: string;
}>();

const showTimes = inject<Ref<boolean>>('show-times');
const editable = inject<Ref<boolean>>('editable');

function createProgrammeBlock() {
  section.value?.blocks?.push({
    id: 'programme-block-' + section.value.blocks.length,
    title: {
      lt: 'Programos blokas ' + (section.value.blocks.length + 1),
      en: 'Programme Block ' + (section.value.blocks.length + 1),
    },
    type: 'block',
    parts: []
  });
}

const sectionStartTime = computed(() => {
  // get duration of all previous blocks from order of the array
  const elapsedTime = parent.elements
    .slice(0, parent.elements.indexOf(section.value))
    .reduce((acc, section) => acc + section.duration, 0);

  return (new Date(parent.start_time)).getTime() + 1000 * 60 * elapsedTime;
});

const sectionEndTime = computed(() => {
  return (new Date(sectionStartTime.value)).getTime() + 1000 * 60 * section.value?.duration;
});

function deleteProgrammeBlock(index: number) {

  if (typeof section.value?.blocks[index].id !== 'string') {

    router.delete(route('programmeBlocks.destroy', { programmeBlock: section.value?.blocks[index].id }), {
      preserveScroll: true,
    });
  }

  section.value?.blocks?.splice(index, 1);
}
</script>
