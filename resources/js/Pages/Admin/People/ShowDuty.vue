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
    <template #default>
      <div class="flex flex-wrap gap-4">
        <template
          v-for="(user, index) in filteredUsers.currentUsers"
          :key="user.id"
        >
          <div
            class="flex items-center p-3"
            :class="
              index >= duty.places_to_occupy ? 'border border-vusa-yellow' : ''
            "
          >
            <UserPopover show-name :user="user" />
          </div>
        </template>
        <div
          v-for="(unOccupied, index) in Math.max(
            duty.places_to_occupy - filteredUsers.currentUsers.length,
            0
          )"
          :key="index"
          class="flex w-36 items-center justify-center border border-zinc-300 p-3 dark:border-zinc-600"
        >
          <span class="rounded-xs text-xs text-zinc-500">Neužimta vieta</span>
        </div>
      </div>
      <Separator />
      <NTable v-if="filteredUsers.oldUsers.length > 0" size="small">
        <thead>
          <tr>
            <th></th>
            <th>Pareigų ėjimo pradžia</th>
            <th>Pareigų ėjimo pabaiga</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="user in filteredUsers.oldUsers" :key="user.id">
            <th><UserPopover :size="32" show-name :user="user" /></th>
            <th>{{ user.pivot.start_date }}</th>
            <th>{{ user.pivot.end_date }}</th>
          </tr>
        </tbody>
      </NTable>
      <p v-else>Nėra duomenų apie anksčiau ėjusius (-as) šias pareigas.</p>
      <div v-if="duty.types && duty.types.length > 0">
        <Separator />
        <Suspense>
          <SimpleFileViewer
            :fileable="{ id: duty.id, type: 'Duty' }"
          ></SimpleFileViewer>
          <template #fallback>
            <div class="flex h-24 items-center justify-center">
              Kraunami susiję failai...
            </div>
          </template>
        </Suspense>
      </div>
    </template>
    <template #below> </template>
  </ShowPageLayout>
</template>

<script setup lang="tsx">
import { computed } from "vue";
import { router } from "@inertiajs/vue3";

import Icons from "@/Types/Icons/filled";
import MoreOptionsButton from "@/Components/Buttons/MoreOptionsButton.vue";
import ShowPageLayout from "@/Components/Layouts/ShowModel/ShowPageLayout.vue";
import SimpleFileViewer from "@/Features/Admin/SharepointFileManager/Viewer/SimpleFileViewer.vue";
import UserPopover from "@/Components/Avatars/UserPopover.vue";
import { useBreadcrumbs, type BreadcrumbItem } from "@/Composables/useBreadcrumbs";
import { Separator } from "@/Components/ui/separator";

const props = defineProps<{
  duty: App.Entities.Duty;
}>();

const handleEdit = () => {
  router.get(route("duties.edit", props.duty.id));
};

const handleDelete = () => {
  router.delete(route("duties.destroy", props.duty.id));
};

const filteredUsers = computed(() => {
  if (!props.duty.users) return [];

  // if user is between start_date and end_date or end_date is null, use reduce function

  return props.duty.users.reduce(
    (acc, user: App.Entities.User) => {
      if (!user.pivot) return acc;

      if (user.pivot.end_date === null) {
        acc.currentUsers.push(user);
        return acc;
      }

      if (
        new Date(user.pivot.start_date) <= new Date() &&
        new Date(user.pivot.end_date) >= new Date()
      ) {
        acc.currentUsers.push(user);
      } else {
        acc.oldUsers.push(user);
      }
      return acc;
    },
    { currentUsers: [], oldUsers: [] }
  );
});

const { createRouteBreadcrumb, createBreadcrumbItem } = useBreadcrumbs();

const breadcrumbOptions = computed((): BreadcrumbItem[] => [
  createRouteBreadcrumb(props.duty.institution?.name, "institutions.show", { institution: props.duty?.institution?.id }, Icons.INSTITUTION),
  createBreadcrumbItem(props.duty.name, undefined, Icons.DUTY),
]);
</script>
