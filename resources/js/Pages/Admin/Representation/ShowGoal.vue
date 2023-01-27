<template>
  <ShowPageLayout
    :model="goal"
    :title="goal.title"
    :breadcrumb-options="breadcrumbItems"
    :related-models="relatedModels"
    :current-tab="currentTab"
    @change:tab="currentTab = $event"
  >
    <template #more-options>
      <MoreOptionsButton
        edit
        delete
        @edit-click="showModal = true"
        @delete-click="handleDelete"
      />
    </template>
    <div class="mb-2 flex min-w-min flex-wrap items-center gap-2">
      <FilterPopselect :options="buttonNames" @click="handleFilterClick" />
    </div>
    <div class="grid grid-cols-3 gap-x-4">
      <MatterCard
        v-for="matter in shownMatters"
        :key="matter.id"
        :matter="matter"
        ><div v-for="institution in matter.institutions" :key="institution.id">
          <ModelChip
            ><template #icon><NIcon :component="Icons.GOAL" /> </template
            >{{ institution.name }}</ModelChip
          >
        </div>
      </MatterCard>
    </div>
    <CardModal
      v-model:show="showModal"
      title="Redaguoti klausimo grupę"
      @close="showModal = false"
    >
      <GoalForm :goal="goal" @form-success="showModal = false" />
    </CardModal>
    <template #below>
      <CommentViewer
        v-if="currentTab === 'Komentarai'"
        class="mt-auto h-min"
        :commentable_type="'goal'"
        :model="goal"
      />
      <TaskManager
        v-else-if="currentTab === 'Užduotys'"
        :taskable="{ id: goal.id, type: 'App\\Models\\Goal' }"
        :tasks="goal.tasks"
      />
    </template>
  </ShowPageLayout>
</template>

<script setup lang="tsx">
import { NIcon } from "naive-ui";
import { computed, ref } from "vue";
import { router } from "@inertiajs/vue3";

import { useStorage } from "@vueuse/core";
import CardModal from "@/Components/Modals/CardModal.vue";
import CommentViewer from "@/Features/Admin/CommentViewer/CommentViewer.vue";
import FilterPopselect from "@/Components/Buttons/FilterPopselect.vue";
import GoalForm from "@/Components/AdminForms/GoalForm.vue";
import Icons from "@/Types/Icons/filled";
import MatterCard from "@/Components/Cards/MatterCard.vue";
import ModelChip from "@/Components/Chips/ModelChip.vue";
import MoreOptionsButton from "@/Components/Buttons/MoreOptionsButton.vue";
import ShowPageLayout from "@/Components/Layouts/ShowModel/ShowPageLayout.vue";
import TaskManager from "@/Features/Admin/TaskManager/TaskManager.vue";
import type { BreadcrumbOption } from "@/Components/Layouts/ShowModel/Breadcrumbs/AdminBreadcrumbDisplayer.vue";

const props = defineProps<{
  goal: App.Entities.Goal;
  institutions: App.Entities.Institution[];
}>();

const showModal = ref(false);
const selectedInstitution = ref<string | null>(null);
const buttonNames = props.institutions.map((institution) => institution.name);
// add null to the beginning of the array
buttonNames.unshift("Visi");

const breadcrumbItems: BreadcrumbOption[] = [
  {
    label: "Klausimų grupės",
    icon: Icons.GOAL_GROUP,
  },
  {
    label: props.goal.title,
    icon: Icons.GOAL,
  },
];

const currentTab = useStorage("show-goal-tab", "Komentarai");

const relatedModels = [
  // {
  //   name: "Failai",
  //   icon: Icons.SHAREPOINT_FILE,
  // },
  {
    name: "Komentarai",
    icon: Icons.COMMENT,
    count: props.goal.comments?.length,
  },
  {
    name: "Užduotys",
    icon: Icons.TASK,
    count: props.goal.tasks?.length,
  },
];

const shownMatters = computed(() => {
  let matters = props.goal.matters;

  if (selectedInstitution.value === "Visi") {
    return matters;
  }

  return matters?.filter((matter) => {
    return matter.institutions?.some((institution) => {
      return institution.name === selectedInstitution.value;
    });
  });
});

const handleFilterClick = (name: string | null) => {
  selectedInstitution.value = name ?? "Be pavadinimo";
};

const handleDelete = () => {
  router.delete(route("goals.destroy", props.goal.id));
};
</script>
