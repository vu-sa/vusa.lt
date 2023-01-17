<template>
  <ShowPageLayout
    :title="duty.name"
    :model="duty"
    :breadcrumb-options="breadcrumbOptions"
  >
    <template #more-options>
      <MoreOptionsButton
        edit
        delete
        @edit-click="handleEdit"
        @delete-click="handleDelete"
      ></MoreOptionsButton>
    </template>
    <h2>Dabar einantys pareigas</h2>
    <div class="flex flex-wrap gap-2">
      <template v-for="user in duty.users" :key="user.id">
        <UserPopover show-name :user="user" />
      </template>
    </div>
    <NDivider />
    <h2>Anksčiau ėję pareigas</h2>
  </ShowPageLayout>
</template>

<script setup lang="tsx">
import { router } from "@inertiajs/core";

import { NButton, NDivider, NPopover } from "naive-ui";
import Icons from "@/Types/Icons/filled";
import MoreOptionsButton from "@/Components/Buttons/MoreOptionsButton.vue";
import ShowPageLayout from "@/Components/Layouts/ShowModel/ShowPageLayout.vue";
import UserPopover from "@/Components/Avatars/UserPopover.vue";
import type { BreadcrumbOption } from "@/Components/Layouts/ShowModel/Breadcrumbs/AdminBreadcrumbDisplayer.vue";

const props = defineProps<{
  duty: App.Entities.Duty;
}>();

const handleEdit = () => {
  router.get(route("duties.edit", props.duty.id));
};

const handleDelete = () => {
  router.delete(route("duties.destroy", props.duty.id));
};

const breadcrumbOptions: BreadcrumbOption[] = [
  {
    label: props.duty.institution?.name,
    icon: Icons.INSTITUTION,
    routeOptions: {
      name: "institutions.show",
      params: {
        institution: props.duty?.institution?.id,
      },
    },
  },
  {
    label: props.duty.name,
    icon: Icons.DUTY,
  },
];
</script>
