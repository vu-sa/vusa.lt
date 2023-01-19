<template>
  <ShowPageLayout
    :breadcrumb-options="breadcrumbOptions"
    :model="matter"
    :title="matter.title"
  >
    <template #more-options>
      <MoreOptionsButton
        disabled
        edit
        @edit-click="showMatterModal = true"
      ></MoreOptionsButton>
    </template>
    <GoalCard :matter="matter" :goals="goals" />
    <template #below>
      <h3>Veiklos</h3>
      <DoingsTabPane
        :matter="matter"
        :doings="matter.doings"
        :doing-template="doingTemplate"
        :doing-types="doingTypes"
      ></DoingsTabPane>
    </template>
  </ShowPageLayout>
  <CardModal
    v-model:show="showMatterModal"
    title="Redaguoti klausimą"
    @close="showMatterModal = false"
    ><MatterForm
      :form="matter"
      :institution="firstInstitution"
      @submit="handleMatterSubmit"
    ></MatterForm
  ></CardModal>
</template>

<script setup lang="tsx">
import { BookQuestionMark24Filled, PeopleTeam24Filled } from "@vicons/fluent";
import { computed, ref } from "vue";

import { doingTemplate } from "@/Types/formTemplates";
import CardModal from "@/Components/Modals/CardModal.vue";
import DoingsTabPane from "@/Components/TabPaneContent/DoingsTabPane.vue";
import GoalCard from "@/Components/Cards/QuickContentCards/GoalCard.vue";
import MatterForm from "@/Components/AdminForms/MatterForm.vue";
import MoreOptionsButton from "@/Components/Buttons/MoreOptionsButton.vue";
import ShowPageLayout from "@/Components/Layouts/ShowModel/ShowPageLayout.vue";
import type { BreadcrumbOption } from "@/Components/Layouts/ShowModel/Breadcrumbs/AdminBreadcrumbDisplayer.vue";

const props = defineProps<{
  matter: App.Entities.Matter;
  goals?: App.Entities.Goal[];
  doingTypes: Record<string, any>;
}>();

const showMatterModal = ref(false);

const firstInstitution = computed(() => {
  if (props.matter.institutions?.length === 0) {
    return {
      id: 0,
      name: "Nenurodyta",
    };
  }

  return props.matter?.institutions?.[0];
});

const handleMatterSubmit = (form: Record<string, any>) => {
  console.log(form);
};

const breadcrumbOptions: BreadcrumbOption[] = [
  {
    label: firstInstitution.value?.name ?? "Nenurodyta",
    icon: PeopleTeam24Filled,
    routeOptions: {
      name: "institutions.show",
      params: {
        institution: firstInstitution.value?.id,
      },
    },
  },
  {
    label: props.matter.title,
    icon: BookQuestionMark24Filled,
    routeOptions: {
      name: "institutions.matters.show",
      params: {
        institution: firstInstitution.value?.id,
        matter: props.matter.id,
      },
    },
  },
];
</script>
