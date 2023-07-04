<template>
  <ShowPageLayout
    :title="reservation.name"
    :breadcrumb-options="breadcrumbOptions"
    :model="reservation"
    :related-models="relatedModels"
    :current-tab="currentTab"
    @change:tab="currentTab = $event"
  >
    <template #more-options>
      <MoreOptionsButton
        edit
        :more-options="moreOptions"
        @edit-click="showEditModal = true"
        @more-option-click="handleMoreOptionClick"
      ></MoreOptionsButton>
    </template>
    <ReservationResourceTable
      v-model:selectedReservationResource="selectedReservationResource"
      :reservation="reservation"
    />
    <CardModal
      :show="showReservationResourceCreateModal"
      title="Pridėti išteklių prie rezervacijos"
      @close="showReservationResourceCreateModal = false"
    >
      <Suspense>
        <ReservationResourceForm
          :reservation-resource-form="reservationResourceForm"
          :all-resources="allResources"
          @success="showReservationResourceCreateModal = false"
        />
      </Suspense>
    </CardModal>
    <template #below>
      <div v-if="currentTab === 'Komentarai'">
        <InfoText class="mb-4"
          >Rodomi ir komentarai ties konkrečiais išteklių užsakymais.</InfoText
        >
        <CommentViewer
          class="mt-auto h-min"
          :commentable_type="'reservation'"
          :model="reservation"
          :comments="allComments"
        />
      </div>
    </template>
  </ShowPageLayout>
</template>

<script setup lang="tsx">
import { ref, toRaw } from "vue";
import { useStorage } from "@vueuse/core";

import { type MenuOption, NIcon } from "naive-ui";
import { useForm } from "@inertiajs/vue3";
import CardModal from "@/Components/Modals/CardModal.vue";
import CommentViewer from "@/Features/Admin/CommentViewer/CommentViewer.vue";
import Icons from "@/Types/Icons/filled";
import InfoText from "@/Components/SmallElements/InfoText.vue";
import MoreOptionsButton from "@/Components/Buttons/MoreOptionsButton.vue";
import ReservationResourceForm from "@/Components/AdminForms/ReservationResourceForm.vue";
import ReservationResourceTable from "@/Components/Tables/ReservationResourceTable.vue";
import ShowPageLayout from "@/Components/Layouts/ShowModel/ShowPageLayout.vue";
import type { BreadcrumbOption } from "@/Components/Layouts/ShowModel/Breadcrumbs/AdminBreadcrumbDisplayer.vue";

const props = defineProps<{
  reservation: App.Entities.Reservation;
  allResources?: App.Entities.Resource[];
}>();

const currentTab = useStorage("show-reservation-tab", "Komentarai");
const selectedReservationResource =
  ref<App.Entities.ReservationResource | null>(null);

const showEditModal = ref(false);
const showReservationResourceCreateModal = ref(false);
const reservationResourceForm = useForm({
  resource_id: null,
  reservation_id: props.reservation.id,
  quantity: 1,
  start_time: new Date(props.reservation.start_time).getTime(),
  end_time: new Date(props.reservation.end_time).getTime(),
});

const breadcrumbOptions: BreadcrumbOption[] = [
  {
    label: props.reservation.name,
    icon: Icons.RESERVATION,
  },
];

const moreOptions: MenuOption[] = [
  {
    label: "Pridėti rezervacijos išteklių",
    icon() {
      return <NIcon component={Icons.RESOURCE}></NIcon>;
    },
    key: "add-resource",
  },
];

const handleMoreOptionClick = (key: string) => {
  switch (key) {
    case "add-resource":
      showReservationResourceCreateModal.value = true;
      break;
  }
};

const getAllComments = () => {
  // ! Not using toRaw() here causes a bug: Uncaught DOMException: Failed to execute 'replaceState' on 'History': #<Object> could not be cloned
  let comments = toRaw(props.reservation.comments) ?? [];
  let resources = toRaw(props.reservation.resources) ?? [];

  if (resources.length > 0) {
    resources.forEach((resource) => {
      // for each comment in resource.pivot.comments, prepend the resource name
      resource.pivot?.comments?.forEach((comment) => {
        comments.push({
          ...comment,
          comment: `<strong>${resource.name}</strong> ${comment.comment}`,
        });
      });
    });
  }

  // order by created_at
  comments.sort((a, b) => {
    return new Date(a.created_at).getTime() - new Date(b.created_at).getTime();
  });

  return comments;
};

const allComments = getAllComments();

const relatedModels = [
  {
    name: "Komentarai",
    icon: Icons.COMMENT,
    count: props.reservation.comments?.length,
  },
];
</script>
