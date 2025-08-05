<template>
  <ShowPageLayout
    :model="goal"
    :title="goal.title"
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
import GoalForm from "@/Components/AdminForms/GoalForm.vue";
import Icons from "@/Types/Icons/filled";
import MoreOptionsButton from "@/Components/Buttons/MoreOptionsButton.vue";
import ShowPageLayout from "@/Components/Layouts/ShowModel/ShowPageLayout.vue";
import TaskManager from "@/Features/Admin/TaskManager/TaskManager.vue";
import { BreadcrumbHelpers, usePageBreadcrumbs } from "@/Composables/useBreadcrumbsUnified";

const props = defineProps<{
  goal: App.Entities.Goal;
}>();

const showModal = ref(false);

// Generate breadcrumbs automatically with new simplified API
usePageBreadcrumbs([
  { label: "Klausimų grupės", icon: Icons.GOAL_GROUP },
  { label: props.goal.title, icon: Icons.GOAL }
]);

const currentTab = useStorage("show-goal-tab", "Komentarai");

const relatedModels = [
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

const handleDelete = () => {
  router.delete(route("goals.destroy", props.goal.id));
};
</script>
