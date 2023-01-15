<template>
  <ShowPageLayout :breadcrumb-options="breadcrumbOptions" :model="doing">
    <template #title>
      {{ doing.title }}
    </template>
    <template #more-options>
      <MoreOptionsButton
        edit
        @edit-click="showModal = true"
      ></MoreOptionsButton>
    </template>
  </ShowPageLayout>
  <CardModal
    v-model:show="showModal"
    title="Redaguoti veiklą"
    @close="showModal = false"
  >
    <DoingForm :doing="doing" @submit="handleSubmit"></DoingForm>
  </CardModal>
</template>

<script setup lang="tsx">
import { Person24Filled, Sparkle20Filled } from "@vicons/fluent";
import { ref } from "vue";

import { usePage } from "@inertiajs/vue3";
import CardModal from "@/Components/Modals/CardModal.vue";
import DoingForm from "@/Components/AdminForms/DoingForm.vue";
import MoreOptionsButton from "@/Components/Buttons/MoreOptionsButton.vue";
import ShowPageLayout from "@/Components/Layouts/ShowPageLayout.vue";
import type { BreadcrumbOption } from "@/Components/Breadcrumbs/AdminBreadcrumbDisplayer.vue";

const props = defineProps<{
  doing: App.Entities.Doing;
}>();

const showModal = ref(false);

const handleSubmit = (form: any) => {
  form
    .transform((data: any) => ({
      ...data,
      user_id: usePage().props.auth?.user.id,
    }))
    .patch(route("doings.update", props.doing.id));
  showModal.value = false;
};

const breadcrumbOptions: BreadcrumbOption[] = [
  {
    label: props.doing.users?.[0].name,
    icon: Person24Filled,
  },
  {
    label: props.doing.title,
    icon: Sparkle20Filled,
  },
];
</script>
