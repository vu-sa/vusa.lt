<template>
  <PageContent :title="goal.title" breadcrumb>
    <template #above-header>
      <NBreadcrumb class="mb-4 w-full">
        <NBreadcrumbItem @click="Inertia.get(route('dashboard'))">
          <div>
            <NIcon class="mr-2" size="16" :component="Home24Filled"> </NIcon>

            Pradinis
          </div>
        </NBreadcrumbItem>
        <NBreadcrumbItem
          ><div>
            <NIcon
              class="mr-2"
              size="16"
              :component="NotebookQuestionMark24Filled"
            >
            </NIcon>
            <NEllipsis style="max-width: 200px"> {{ goal.title }}</NEllipsis>
          </div>
        </NBreadcrumbItem>
      </NBreadcrumb>
    </template>
    <template #aside-header>
      <MoreOptionsButton
        edit
        delete
        @edit-click="showModal = true"
        @delete-click="handleDelete"
      ></MoreOptionsButton>
    </template>
    <div class="mb-2 flex min-w-min flex-wrap items-center gap-2">
      <NButton
        v-for="institution in institutions"
        :key="institution.id"
        size="small"
        :type="institution.id === selectedInstitution ? 'primary' : 'default'"
        strong
        @click="handleClick(institution.id)"
        >{{ institution.name }}</NButton
      >
    </div>
    <div class="grid grid-cols-3 gap-x-4">
      <MatterCard
        v-for="matter in shownMatters"
        :key="matter.id"
        :matter="matter"
        >{{ matter.institution?.name }}</MatterCard
      >
    </div>
    <CardModal
      v-model:show="showModal"
      title="Redaguoti klausimo grupę"
      @close="showModal = false"
    >
      <GoalForm :matter-group="goal" @form-success="showModal = false" />
    </CardModal>
  </PageContent>
</template>

<script setup lang="tsx">
import { Home24Filled, NotebookQuestionMark24Filled } from "@vicons/fluent";
import { Inertia } from "@inertiajs/inertia";
import {
  NBreadcrumb,
  NBreadcrumbItem,
  NButton,
  NEllipsis,
  NIcon,
} from "naive-ui";
import { computed, ref } from "vue";
import route from "ziggy-js";

import AdminLayout from "@/Components/Layouts/AdminLayout.vue";
import CardModal from "@/Components/Modals/CardModal.vue";
import GoalForm from "@/Components/AdminForms/GoalForm.vue";
import MatterCard from "@/Components/Cards/MatterCard.vue";
import MoreOptionsButton from "@/Components/Buttons/MoreOptionsButton.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";

defineOptions({
  layout: AdminLayout,
});

const props = defineProps<{
  goal: App.Models.Goal;
  institutions: App.Models.Institution[];
}>();

const showModal = ref(false);
const selectedInstitution = ref<number | null>(null);

const shownMatters = computed(() => {
  let matters = props.goal.matters;

  if (selectedInstitution.value === null) {
    return matters;
  }

  return matters?.filter((matter) => {
    return matter.institution?.id === selectedInstitution.value ?? matters;
  });
});

const handleClick = (id: number) => {
  if (selectedInstitution.value === id) {
    selectedInstitution.value = null;
    return;
  }
  selectedInstitution.value = id;
  return;
};

const handleDelete = () => {
  Inertia.delete(route("goals.destroy", props.goal.id));
};
</script>
