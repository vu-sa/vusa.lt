<template>
  <div
    class="my-2 flex flex-col gap-3 rounded-lg border bg-zinc-100/70 p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
    <div class="inline-flex items-center gap-3 p-1 text-xl font-bold">
      <IFluentLayerDiagonal24Regular /> {{ block.title[$page.props.app.locale] ?? block.title }}
      <slot name="buttons" />
    </div>
    <div ref="blockPartEl">
      <ProgrammePart v-for="(part, index) in block?.parts" :key="part.id" v-model:element="block.parts[index]"
        :data-id="part.id" :data-type="part.type" handle-class="block-part-handle" :section-start-time :parent="block">
        <template v-if="editable" #buttons>
          <NButton size="tiny" secondary circle @click="handleEditPart(part)">
            <template #icon>
              <IFluentEdit24Filled />
            </template>
          </NButton>
          <NButton size="tiny" secondary circle @click="deleteProgrammePart(index)">
            <template #icon>
              <IFluentDelete24Filled />
            </template>
          </NButton>
        </template>
      </ProgrammePart>
    </div>
    <CardModal v-if="selectedPart" v-model:show="showPartEditModal" :part="selectedPart"
      @close="showPartEditModal = false">
      <NFormItem label="Dalies pavadinimas">
        <MultiLocaleInput v-model:input="selectedPart.title" />
      </NFormItem>
      <NFormItem label="Dalies trukmė">
        <NInputNumber v-model:value="selectedPart.duration" />
      </NFormItem>
      <NFormItem label="Instruktorius">
        <NInput v-model:value="selectedPart.instructor" />
      </NFormItem>
      <NFormItem label="Aprašymas">
        <MultiLocaleInput v-model:input="selectedPart.description" />
      </NFormItem>
      <NButton @click="showPartEditModal = false">
        Uždaryti
      </NButton>
    </CardModal>
    <NTooltip v-if="editable">
      <template #trigger>
        <NButton size="small" secondary circle @click="createProgrammePart">
          <template #icon>
            <IFluentAdd24Filled />
          </template>
        </NButton>
      </template>
      Pridėti programos dalį
    </NTooltip>
  </div>
</template>

<script setup lang="ts">
import { useSortable } from '@vueuse/integrations/useSortable.mjs';
import { inject, nextTick, ref, useTemplateRef } from 'vue';
import { router } from "@inertiajs/vue3";

import ProgrammePart from './ProgrammePart.vue';
import MultiLocaleInput from '@/Components/FormItems/MultiLocaleInput.vue';
import CardModal from '@/Components/Modals/CardModal.vue';

defineProps<{
  sectionStartTime: number;
}>();

const editable = inject<boolean>('editable');

const showPartEditModal = ref(false);
const selectedPart = ref<App.Entities.ProgrammePart | null>(null);

const block = defineModel<App.Entities.ProgrammeBlock>('block')
const blockPartEl = useTemplateRef<HTMLDivElement | null>('blockPartEl');

const { movedElement, updateMovedElement } = inject('movedElement');

if (editable) useSortable<HTMLDivElement | null>(blockPartEl, block.value?.parts, {
  handle: '.block-part-handle',
  group: {
    name: 'elements',
    put(from, to, item) {
      return item.dataset.type === 'part';
    }
  },
  animation: 200,
  async onAdd({ newIndex }: { newIndex: number }) {
    await nextTick();

    block.value?.parts?.splice(newIndex, 0, movedElement.value as App.Entities.ProgrammePart);

    if (typeof movedElement.value.id !== 'string' && typeof block.value?.id !== 'string') {
      router.post(route('programmeParts.attach', movedElement.value.id), {
        programmeBlock: block.value?.id,
        order: newIndex
      }, {
        preserveScroll: true,
      });
    }
  },
  onRemove({ oldIndex }: { oldIndex: number }) {
    updateMovedElement(block.value?.parts?.[oldIndex] as App.Entities.ProgrammePart);

    if (typeof movedElement.value.id !== 'string' && typeof block.value?.id !== 'string') {
      router.post(route('programmeParts.detach', { programmePart: movedElement.value.id }), { programmeBlock: block.value.id }, {
        preserveScroll: true,
      });
    }

    block.value?.parts?.splice(oldIndex, 1);
  }
});

function createProgrammePart() {
  block.value?.parts?.push({
    id: 'programme-part-' + 100 + block.value.parts.length,
    type: 'part',
    title: {
      lt: 'Programos dalis ' + (block.value.parts.length + 1),
      en: 'Programme Part ' + (block.value.parts.length + 1),
    },
    description: {
      lt: '',
      en: '',
    },
    instructor: null,
    duration: 45,
  });
}

function handleEditPart(part: App.Entities.ProgrammePart) {
  selectedPart.value = part as App.Entities.ProgrammePart;
  showPartEditModal.value = true;
}

function deleteProgrammePart(index: number) {
  if (!block.value?.parts) return;

  if (typeof block.value.parts[index].id !== 'string') {
    router.delete(route('programmeParts.destroy', { programmePart: block.value.parts[index].id }), {
      preserveScroll: true,
    });
  }

  block.value?.parts?.splice(index, 1);
}
</script>
