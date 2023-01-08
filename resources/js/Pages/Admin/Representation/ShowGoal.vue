<template>
  <PageContent :title="goal.title" breadcrumb>
    <template #above-header>
      <AdminBreadcrumbDisplayer class="mb-4 w-full" :options="breadcrumbItems">
      </AdminBreadcrumbDisplayer>
    </template>
    <template #aside-header>
      <div class="inline-flex gap-2">
        <ActivityLogButton :activities="goal.activities" />
        <MoreOptionsButton
          edit
          delete
          @edit-click="showModal = true"
          @delete-click="handleDelete"
        ></MoreOptionsButton>
      </div>
    </template>
    <div class="mb-2 flex min-w-min flex-wrap items-center gap-2">
      <FilterButtonGroup
        :button-names="buttonNames"
        @click="handleFilterClick"
      />
    </div>
    <div class="grid grid-cols-3 gap-x-4">
      <MatterCard
        v-for="matter in shownMatters"
        :key="matter.id"
        :matter="matter"
        ><div v-for="institution in matter.institutions" :key="institution.id">
          <ModelChip
            ><template #icon
              ><NIcon :component="StarLineHorizontal324Filled" /> </template
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
  </PageContent>
</template>

<script setup lang="tsx">
import { Home24Filled, StarLineHorizontal324Filled } from "@vicons/fluent";
import { Inertia } from "@inertiajs/inertia";
import { NIcon } from "naive-ui";
import { computed, ref } from "vue";

import ActivityLogButton from "@/Components/Buttons/ActivityLogButton.vue";
import AdminBreadcrumbDisplayer from "@/Components/Breadcrumbs/AdminBreadcrumbDisplayer.vue";
import AdminLayout from "@/Components/Layouts/AdminLayout.vue";
import CardModal from "@/Components/Modals/CardModal.vue";
import FilterButtonGroup from "@/Components/Buttons/FilterButtonGroup.vue";
import GoalForm from "@/Components/AdminForms/GoalForm.vue";
import MatterCard from "@/Components/Cards/MatterCard.vue";
import ModelChip from "@/Components/Chips/ModelChip.vue";
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
const selectedInstitution = ref<string | null>(null);
const buttonNames = props.institutions.map((institution) => institution.name);
const breadcrumbItems: App.Props.BreadcrumbOption[] = [
  {
    label: "Klausimų grupės",
  },
  {
    label: props.goal.title,
    icon: StarLineHorizontal324Filled,
  },
];

const shownMatters = computed(() => {
  let matters = props.goal.matters;

  if (selectedInstitution.value === null) {
    return matters;
  }

  return matters?.filter((matter) => {
    return matter.institutions?.some((institution) => {
      console.log(institution.name, selectedInstitution.value);
      return institution.name === selectedInstitution.value;
    });
  });
});

const handleFilterClick = (name: string | null) => {
  selectedInstitution.value = name ?? "Be pavadinimo";
};

const handleDelete = () => {
  Inertia.delete(route("goals.destroy", props.goal.id));
};
</script>
