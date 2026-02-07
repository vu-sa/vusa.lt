<template>
  <div class="my-2 rounded-md border bg-zinc-50/50 p-4 shadow-xs dark:border-zinc-900 dark:bg-zinc-800">
    <div class="mb-2 flex flex-row items-center gap-4">
      <button
        class="inline-flex items-center gap-2 rounded-md px-2 py-1 text-2xl font-bold tracking-tight transition hover:bg-zinc-200 dark:hover:bg-zinc-700"
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
          <Button size="icon-xs" variant="secondary" class="rounded-full" @click="showBlockEditModal = true; selectedBlock = block">
            <IFluentEdit24Filled />
          </Button>
          <Button size="icon-xs" variant="secondary" class="rounded-full" @click="deleteProgrammeBlock(index)">
            <IFluentDelete24Filled />
          </Button>
        </template>
      </ProgrammeBlock>
    </div>
    <TooltipProvider v-if="editable">
      <Tooltip>
        <TooltipTrigger as-child>
          <Button size="icon-sm" class="rounded-full" @click="createProgrammeBlock">
            <IFluentLayerDiagonalAdd24Regular />
          </Button>
        </TooltipTrigger>
        <TooltipContent>
          Pridėti programos bloką
        </TooltipContent>
      </Tooltip>
    </TooltipProvider>
    <CardModal v-model:show="showBlockEditModal" @close="showBlockEditModal = false">
      <FormFieldWrapper id="block-title" label="Dienos pavadinimas">
        <MultiLocaleInput v-model:input="selectedBlock.title" />
      </FormFieldWrapper>
      <Button variant="outline" @click="showBlockEditModal = false">
        Uždaryti
      </Button>
    </CardModal>
  </div>
</template>

<script setup lang="ts">
import { computed, inject, ref, type Ref } from 'vue';
import { router } from "@inertiajs/vue3";

import { formatStaticTime } from '@/Utils/IntlTime';
import ProgrammeBlock from './ProgrammeBlock.vue';
import CardModal from '@/Components/Modals/CardModal.vue';
import { Button } from '@/Components/ui/button';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/Components/ui/tooltip';
import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import MultiLocaleInput from '@/Components/FormItems/MultiLocaleInput.vue';

const section = defineModel<App.Entities.ProgrammeSection | App.Entities.ProgrammePart>('element')

const { parent } = defineProps<{
  parent: App.Entities.ProgrammeDay;
  handleClass?: string;
}>();

const showTimes = inject<Ref<boolean>>('show-times');
const editable = inject<Ref<boolean>>('editable');

const showBlockEditModal = ref(false);
const selectedBlock = ref<App.Entities.ProgrammeBlock | null>(null);

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
