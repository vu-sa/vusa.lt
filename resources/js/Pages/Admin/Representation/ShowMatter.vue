<template>
  <PageContent :title="matter.title" breadcrumb>
    <template #above-header>
      <AdminBreadcrumbDisplayer
        :options="breadcrumbOptions"
        class="mb-4 w-full"
      />
    </template>
    <!-- <template #after-heading>
        <StatusTag :status="matter.status" />
      </template> -->
    <template #aside-header>
      <div class="inline-flex gap-2">
        <ShowActivityLog :activities="matter.activities" />
        <MoreOptionsButton
          disabled
          edit
          @edit-click="showMatterModal = true"
        ></MoreOptionsButton>
      </div>
    </template>
    <GoalCard :matter="matter" :goals="matter.goals" />
    <NTabs
      animated
      type="line"
      :default-value="currentMattersTabPane"
      @update:value="updateMattersTabPane"
    >
      <NTabPane name="Apie">
        <p>{{ matter.description }}</p>
      </NTabPane>
      <NTabPane name="Veiklos">
        <template #tab>
          <div class="flex gap-2">
            Veiklos
            <!-- <NTag size="small" round>
              {{ matter.doings.length }}
            </NTag> -->
          </div>
        </template>

        <DoingsTabPane
          :matter="matter"
          :doings="matter.doings"
          :doing-template="doingTemplate"
          :doing-types="doingTypes"
        ></DoingsTabPane>
      </NTabPane>
    </NTabs>
  </PageContent>
  <CardModal
    v-model:show="showMatterModal"
    title="Redaguoti klausimą"
    @close="showMatterModal = false"
    ><MatterForm
      :form="matter"
      :institution="firstInstitution"
      @matter-stored="showMatterModal = false"
    ></MatterForm
  ></CardModal>
</template>

<script setup lang="tsx">
import { BookQuestionMark24Filled, PeopleTeam24Filled } from "@vicons/fluent";
import { NTabPane, NTabs } from "naive-ui";
import { computed, ref } from "vue";
import { useStorage } from "@vueuse/core";

import AdminBreadcrumbDisplayer from "@/Components/Breadcrumbs/AdminBreadcrumbDisplayer.vue";
import AdminLayout from "@/Components/Layouts/AdminLayout.vue";
import CardModal from "@/Components/Modals/CardModal.vue";
import DoingsTabPane from "@/Components/TabPaneContent/DoingsTabPane.vue";
import GoalCard from "@/Components/Cards/QuickContentCards/GoalCard.vue";
import MatterForm from "@/Components/AdminForms/MatterForm.vue";
import MoreOptionsButton from "@/Components/Buttons/MoreOptionsButton.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import ShowActivityLog from "@/Components/Buttons/ActivityLogButton.vue";

defineOptions({ layout: AdminLayout });

const props = defineProps<{
  matter: App.Models.Matter;
  doingTypes: Record<string, any>;
}>();

const showMatterModal = ref(false);
const currentMattersTabPane = useStorage("admin-CurrentMattersTabPane", "Apie");

const updateMattersTabPane = (value: string | number) => {
  currentMattersTabPane.value = value;
};

const doingTemplate = {
  title: "",
  type_id: "",
  status: "Sukurtas",
  // datetime now YYYY-MM-DD HH:MM:SS and delimit T
  date: new Date().toISOString().split("T").join(" ").slice(0, 16) + ":00",
};

const firstInstitution = computed(() => {
  if (props.matter.institutions?.length === 0) {
    return {
      id: 0,
      name: "Nenurodyta",
    };
  }

  return props.matter?.institutions?.[0];
});

const breadcrumbOptions: App.Props.BreadcrumbOption[] = [
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
