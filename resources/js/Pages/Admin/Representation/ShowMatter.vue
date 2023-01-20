<template>
  <ShowPageLayout
    :breadcrumb-options="breadcrumbOptions"
    :model="matter"
    :title="matter.title"
    :current-tab="currentTab"
    :related-models="relatedModels"
    @change:tab="currentTab = $event"
  >
    <template #more-options>
      <MoreOptionsButton
        disabled
        edit
        @edit-click="showMatterModal = true"
      ></MoreOptionsButton>
    </template>
    <GoalCard :matter="matter" :goals="goals" />
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
    <template #below>
      <DoingsTabPane
        :matter="matter"
        :doings="matter.doings"
        :doing-template="doingTemplate"
        :doing-types="doingTypes"
      ></DoingsTabPane>
    </template>
  </ShowPageLayout>
</template>

<script setup lang="tsx">
import { BookQuestionMark24Filled, PeopleTeam24Filled } from "@vicons/fluent";
import { computed, ref } from "vue";

import { doingTemplate } from "@/Types/formTemplates";
import CardModal from "@/Components/Modals/CardModal.vue";
import DoingsTabPane from "@/Components/TabPaneContent/DoingsTabPane.vue";
import GoalCard from "@/Components/Cards/QuickContentCards/GoalCard.vue";
import Icons from "@/Types/Icons/filled";
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
