<template>
  <PageContent :title="matter.title" breadcrumb>
    <template #above-header>
      <NBreadcrumb class="mb-4 w-full">
        <NBreadcrumbItem @click="Inertia.get(route('dashboard'))">
          <div>
            <NIcon class="mr-2" size="16" :component="Home24Filled"> </NIcon>

            Pradinis
          </div>
        </NBreadcrumbItem>
        <NBreadcrumbItem
          @click="
            Inertia.get(route('institutions.show', matter.institution.id))
          "
          ><div>
            <NIcon class="mr-2" size="16" :component="PeopleTeam24Filled">
            </NIcon>
            <NEllipsis style="max-width: 200px">
              {{ matter.institution.name }}</NEllipsis
            >
          </div>
        </NBreadcrumbItem>
        <NBreadcrumbItem>
          <div>
            <NIcon
              class="mr-2"
              size="16"
              :component="BookQuestionMark20Filled"
            />
            {{ matter.title }}
          </div>
        </NBreadcrumbItem>
      </NBreadcrumb>
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
    <GoalCard :matter="matter" :matter-group="matter.matter_group" />
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
            <NTag size="small" round>
              {{ matter.doings.length }}
            </NTag>
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
      :institution="matter.institution"
      @matter-stored="showMatterModal = false"
    ></MatterForm
  ></CardModal>
</template>

<script setup lang="tsx">
import {
  BookQuestionMark20Filled,
  Home24Filled,
  PeopleTeam24Filled,
} from "@vicons/fluent";
import { Inertia } from "@inertiajs/inertia";
import {
  NBreadcrumb,
  NBreadcrumbItem,
  NEllipsis,
  NIcon,
  NTabPane,
  NTabs,
  NTag,
} from "naive-ui";
import { ref } from "vue";
import { useStorage } from "@vueuse/core";
import route from "ziggy-js";

import AdminLayout from "@/Components/Layouts/AdminLayout.vue";
import CardModal from "@/Components/Modals/CardModal.vue";
import DoingsTabPane from "@/Components/TabPaneContent/DoingsTabPane.vue";
import GoalCard from "@/Components/Cards/QuickContentCards/GoalCard.vue";
import MatterForm from "@/Components/AdminForms/MatterForm.vue";
import MoreOptionsButton from "@/Components/Buttons/MoreOptionsButton.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import ShowActivityLog from "@/Components/Buttons/ShowActivityLog.vue";

defineOptions({ layout: AdminLayout });

defineProps<{
  matter: Record<string, any>;
  doingTypes: Record<string, any>;
}>();

const showMatterModal = ref(false);

const currentMattersTabPane = useStorage("admin-CurrentMattersTabPane", "Apie");

const updateMattersTabPane = (value) => {
  currentMattersTabPane.value = value;
};

const doingTemplate = {
  title: "",
  type_id: "",
  status: "Sukurtas",
  // datetime now YYYY-MM-DD HH:MM:SS and delimit T
  date: new Date().toISOString().split("T").join(" ").slice(0, 16) + ":00",
};
</script>
