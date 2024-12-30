<template>
  <div class="light:bg-zinc-50/50 rounded-lg border p-4 dark:border-zinc-600">
    <div class="mb-4 flex flex-row items-center gap-2">
      <button
        class="day-handle inline-flex items-center gap-3 rounded-lg px-2 py-0.5 text-2xl font-black transition hover:bg-zinc-200 dark:hover:bg-zinc-700">
        <IFluentCalendar24Regular />
        {{ day.title[$page.props.app.locale] ?? day?.title }}
      </button>
      <!-- span>
        {{ day?.id ? 'ID: ' + day.id : '' }}
      </span -->
      <span class="px-2 text-zinc-500">
        {{ formatStaticTime(day.start_time, { month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' }) }}
      </span>
      <slot name="buttons" />
    </div>
    <div ref="elementsEl">
      <component :is="element?.type === 'section' ? ProgrammeSection : ProgrammePart"
        v-for="(element, index) in day.elements" :key="element?.id ?? index" v-model:element="day.elements[index]"
        :parent="day" handle-class="element-handle" :data-id="element.id" :data-type="element.type">
        <template v-if="editable" #buttons>
          <NButton size="tiny" secondary circle @click="handleEditElement(element)">
            <template #icon>
              <IFluentEdit24Filled />
            </template>
          </NButton>
          <NButton size="tiny" secondary circle @click="deleteProgrammeElement(index)">
            <template #icon>
              <IFluentDelete24Filled />
            </template>
          </NButton>
        </template>
      </component>
      <!-- calculate current time from previous parts and blocks -->
    </div>
    <CardModal v-model:show="showPartEditModal" :part="selectedPart" @close="showPartEditModal = false">
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
    <CardModal v-model:show="showSectionEditModal" :section="selectedSection" @close="showSectionEditModal = false">
      <NFormItem label="Sekcijos pavadinimas">
        <MultiLocaleInput v-model:input="selectedSection.title" />
      </NFormItem>
      <NFormItem label="Sekcijos trukmė">
        <NInputNumber v-model:value="selectedSection.duration" />
      </NFormItem>
      <NButton type="primary" @click="showSectionEditModal = false">
        Išsaugoti
      </NButton>
    </CardModal>
    <div v-if="editable" class="mt-2 flex gap-1">
      <NTooltip>
        <template #trigger>
          <NButton size="small" circle @click="createProgrammePart">
            <template #icon>
              <IFluentAdd24Filled />
            </template>
          </NButton>
        </template>
        Pridėti programos dalį
      </NTooltip>
      <NTooltip>
        <template #trigger>
          <NButton size="small" circle @click="createProgrammeSection">
            <template #icon>
              <IFluentWindowBulletListAdd20Filled />
            </template>
          </NButton>
        </template>
        Pridėti programos sekciją
      </NTooltip>
    </div>
  </div>
</template>

<script setup lang="ts">
import { inject, nextTick, onMounted, provide, ref } from 'vue';
import { useSortable } from '@vueuse/integrations/useSortable';
import { router } from "@inertiajs/vue3";

import ProgrammePart from './ProgrammePart.vue';
import ProgrammeSection from './ProgrammeSection.vue';
import { formatStaticTime } from '@/Utils/IntlTime';
import CardModal from '@/Components/Modals/CardModal.vue';
import MultiLocaleInput from '@/Components/FormItems/MultiLocaleInput.vue';

const day = defineModel<App.Entities.ProgrammeDay>('day')

const elementsEl = ref<HTMLDivElement | null>('elementsEl');

const { movedElement, updateMovedElement } = inject('movedElement');
const editable = inject<boolean>('editable');

function createProgrammeSection() {
  if (!day.value) return;

  day.value.elements?.push({
    id: 'programme-section-' + day.value.elements.length,
    type: 'section',
    title: {
      lt: 'Programos sekcija ' + (day.value.elements.length + 1),
      en: 'Programme Section ' + (day.value.elements.length + 1),
    },
    duration: 60,
    blocks: []
  });
}

function createProgrammePart() {
  if (!day.value) return;

  day.value.elements?.push({
    // generate unique substring
    id: 'programme-part-' + Math.random().toString(36).substring(7),
    type: 'part',
    title: {
      lt: 'Programos dali ' + (day.value.elements.length + 1),
      en: 'Programme Part ' + (day.value.elements.length + 1),
    },
    description: {
      lt: 'Programos dalies aprašymas',
      en: 'Programme Part Description',
    },
    instructor: 'Programme Part Instructor',
    duration: 45,
  });
}

function deleteProgrammeElement(index: number) {
  if (!day.value) return;

  // check if part
  if (day.value.elements[index].type === 'part' && typeof day.value.elements[index].id !== 'string') {

    router.delete(route('programmeParts.destroy', { programmePart: day.value.elements[index].id }), {
      preserveScroll: true,
    });
  } else if (day.value.elements[index].type === 'section' && typeof day.value.elements[index].id !== 'string') {
    router.delete(route('programmeSections.destroy', { programmeSection: day.value.elements[index].id }), {
      preserveScroll: true,
    });
  }
  day.value.elements?.splice(index, 1);
}

// For handling edits
const showPartEditModal = ref(false);
const showSectionEditModal = ref(false);

const selectedPart = ref<App.Entities.ProgrammePart | null>(null);
const selectedSection = ref<App.Entities.ProgrammeSection | null>(null);

function handleEditElement(element: App.Entities.ProgrammePart | App.Entities.ProgrammeSection) {
  if (element.type === 'part') {
    selectedPart.value = element as App.Entities.ProgrammePart;
    showPartEditModal.value = true;
  } else if (element.type === 'section') {
    selectedSection.value = element as App.Entities.ProgrammeSection;
    showSectionEditModal.value = true;
  }
}

function createSortableElement() {
  const sortable = useSortable<HTMLDivElement | null>(elementsEl, day.value?.elements, {
    handle: '.element-handle',
    group: {
      name: 'elements',
    },
    animation: 100,
    async onAdd({ newIndex }: { newIndex: number }) {
      await nextTick();

      day.value?.elements?.splice(newIndex, 0, movedElement.value as App.Entities.ProgrammeSection | App.Entities.ProgrammePart);

      // Only attach on the server if the elements are already there
      if (typeof movedElement.value.id !== 'string' && typeof day.value?.id !== 'string') {
        if (movedElement.value.type === 'part') {
          router.post(route('programmeParts.attach', movedElement.value.id), {
            programmeDay: day.value.id,
            order: newIndex
          }, {
            preserveScroll: true,
          });
        } else if (movedElement.value.type === 'section') {
          router.post(route('programmeSections.attach', { programmeDay: day.value.id, programmeSection: movedElement.value.id, order: newIndex }), {
            preserveScroll: true,
          });
        }
      }
    },
    onRemove({ oldIndex }: { oldIndex: number }) {
      updateMovedElement(day.value?.elements?.[oldIndex] as App.Entities.ProgrammeSection | App.Entities.ProgrammePart);

      if (typeof movedElement.value.id !== 'string' && typeof day.value?.id !== 'string') {
        if (movedElement.value.type === 'part') {
          router.post(route('programmeParts.detach', { programmePart: movedElement.value.id }), { programmeDay: day.value.id }, {
            preserveScroll: true,
          });
        } else if (movedElement.value.type === 'section') {
          router.delete(route('programmeSections.detach', { programmeDay: day.value.id, programmeSection: movedElement.value.id }), {
            preserveScroll: true,
          });
        }
      }

      day.value?.elements?.splice(oldIndex, 1);
    }
  });

  return sortable
}

if (editable) createSortableElement();
</script>
