<template>
  <ShowPageLayout :breadcrumb-options="breadcrumbOptions" :model="matter" :title="matter.title"
    :current-tab="currentTab" :related-models="relatedModels" @change:tab="currentTab = $event">
    <template #more-options>
      <MoreOptionsButton disabled edit @edit-click="showMatterModal = true" />
    </template>
    <GoalCard :matter="matter" :goals="goals" />
    <CardModal v-model:show="showMatterModal" title="Redaguoti klausimą" @close="showMatterModal = false">
      <MatterForm :form="matter" :institution="firstInstitution" @submit="handleMatterSubmit" />
    </CardModal>
    <template #below>
      <DoingsTabPane :matter="matter" :doings="matter.doings" :doing-template="doingTemplate" :doing-types="doingTypes" />
    </template>
  </ShowPageLayout>
</template>

<script setup lang="tsx">
import { computed, ref } from "vue";

import BookQuestionMark24Filled from "~icons/fluent/book-question-mark24-filled";
import PeopleTeam24Filled from "~icons/fluent/people-team24-filled";

import { doingTemplate } from "@/Types/formTemplates";
import CardModal from "@/Components/Modals/CardModal.vue";
import DoingsTabPane from "@/Components/TabPaneContent/DoingsTabPane.vue";
import GoalCard from "@/Components/Cards/QuickContentCards/GoalCard.vue";
import Icons from "@/Types/Icons/filled";
import MatterForm from "@/Components/AdminForms/MatterForm.vue";
import MoreOptionsButton from "@/Components/Buttons/MoreOptionsButton.vue";
import ShowPageLayout from "@/Components/Layouts/ShowModel/ShowPageLayout.vue";
import { useBreadcrumbs, type BreadcrumbItem } from "@/Composables/useBreadcrumbs";

const props = defineProps<{
  matter: App.Entities.Matter;
  goals?: App.Entities.Goal[];
  doingTypes: Record<string, any>;
}>();

const showMatterModal = ref(false);

const currentTab = ref("Veiksmai");

const relatedModels = [
  {
    name: "Veiksmai",
    icon: Icons.DOING,
    count: props.matter.doings?.length ?? 0,
  },
];

const firstInstitution = computed(() => {
  if (props.matter.institutions?.length === 0) {
    return {
      id: 0,
      name: "Nenurodyta",
    };
  }

  return props.matter?.institutions?.[0];
});

const handleMatterSubmit = (form: Record<string, any>) => { };

const { createRouteBreadcrumb, createBreadcrumbItem } = useBreadcrumbs();

const breadcrumbOptions = computed((): BreadcrumbItem[] => [
  createRouteBreadcrumb(firstInstitution.value?.name ?? "Nenurodyta", "institutions.show", { institution: firstInstitution.value?.id }, PeopleTeam24Filled),
  createBreadcrumbItem(props.matter.title, undefined, BookQuestionMark24Filled),
]);
</script>
