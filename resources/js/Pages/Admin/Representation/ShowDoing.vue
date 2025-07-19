<template>
  <ShowPageLayout :title="doing.title" :model="doing"
    :related-models="relatedModels" :current-tab="currentTab" @change:tab="currentTab = $event">
    <template #more-options>
      <MoreOptionsButton edit delete @edit-click="showEditModal = true" @delete-click="handleDelete" />
      <CardModal v-model:show="showEditModal" title="Redaguoti veiklą" @close="showEditModal = false">
        <DoingForm :doing="doing" @submit="handleSubmit" />
      </CardModal>
    </template>
    <DoingStateCard :doing="doing" />
    <template #below>
      <div v-if="currentTab === 'Failai'">
        <Suspense v-if="doing.types.length > 0">
          <SimpleFileViewer :fileable="{ id: doing.id, type: 'Doing' }" />
          <template #fallback>
            <div class="flex h-24 items-center justify-center">
              Kraunami susiję failai...
            </div>
          </template>
        </Suspense>
        <FileManager :starting-path="doing.sharepointPath" :fileable="{ id: doing.id, type: 'Doing' }" />
      </div>
      <CommentViewer v-else-if="currentTab === 'Komentarai'" class="mt-auto h-min" :commentable_type="'doing'"
        :model="doing" />
      <TaskManager v-else-if="currentTab === 'Užduotys'" :taskable="{ id: doing.id, type: 'App\\Models\\Doing' }"
        :tasks="doing.tasks" />
    </template>
  </ShowPageLayout>
</template>

<script setup lang="tsx">
import { computed, ref } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import { useStorage } from "@vueuse/core";

import Person24Filled from "~icons/fluent/person24-filled";
import Sparkle20Filled from "~icons/fluent/sparkle20-filled";

import CardModal from "@/Components/Modals/CardModal.vue";
import CommentViewer from "@/Features/Admin/CommentViewer/CommentViewer.vue";
import DoingForm from "@/Components/AdminForms/DoingForm.vue";
import DoingStateCard from "@/Components/Cards/QuickContentCards/DoingStateCard.vue";
import FileManager from "@/Features/Admin/SharepointFileManager/Viewer/FileManager.vue";
import Icons from "@/Types/Icons/filled";
import MoreOptionsButton from "@/Components/Buttons/MoreOptionsButton.vue";
import ShowPageLayout from "@/Components/Layouts/ShowModel/ShowPageLayout.vue";
import SimpleFileViewer from "@/Features/Admin/SharepointFileManager/Viewer/SimpleFileViewer.vue";
import TaskManager from "@/Features/Admin/TaskManager/TaskManager.vue";
import { usePageBreadcrumbs, BreadcrumbHelpers } from '@/Composables/useBreadcrumbsUnified';

const props = defineProps<{
  doing: App.Entities.Doing;
}>();

const currentTab = useStorage("show-doing-tab", "Failai");

const showEditModal = ref(false);

const handleSubmit = (form: any) => {
  form
    .transform((data: any) => ({
      ...data,
      user_id: usePage().props.auth?.user.id,
    }))
    .patch(route("doings.update", props.doing.id));
  showEditModal.value = false;
};

// Setup breadcrumbs for the Doing page
usePageBreadcrumbs([
  { label: props.doing.users?.[0]?.name || 'Unknown User', icon: Person24Filled },
  { label: props.doing.title, icon: Sparkle20Filled }
]);

const completedTasks = computed(() => {
  return props.doing.tasks?.filter((task) => task.completed_at);
});

const relatedModels = [
  {
    name: "Failai",
    icon: Icons.SHAREPOINT_FILE,
    count: props.doing.files?.length,
  },
  {
    name: "Komentarai",
    icon: Icons.COMMENT,
    count: props.doing.comments?.length,
  },
  {
    name: "Užduotys",
    icon: Icons.TASK,
    // count is string of completed tasks / total tasks
    count: `${completedTasks.value?.length} / ${props.doing.tasks?.length}`,
  },
];

const handleDelete = () => {
  router.delete(route("doings.destroy", props.doing.id));
};
</script>
