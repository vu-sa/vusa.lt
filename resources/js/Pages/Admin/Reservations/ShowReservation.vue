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
        @edit-click="showEditModal = true"
      ></MoreOptionsButton>
    </template>
    <ReservationResourceTable
      v-model:selectedReservationResource="selectedReservationResource"
      :reservation="reservation"
    />
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
import { computed, ref } from "vue";
import { useStorage } from "@vueuse/core";

import CommentViewer from "@/Features/Admin/CommentViewer/CommentViewer.vue";
import Icons from "@/Types/Icons/filled";
import InfoText from "@/Components/SmallElements/InfoText.vue";
import MoreOptionsButton from "@/Components/Buttons/MoreOptionsButton.vue";
import ReservationResourceTable from "@/Components/Tables/ReservationResourceTable.vue";
import ShowPageLayout from "@/Components/Layouts/ShowModel/ShowPageLayout.vue";
import type { BreadcrumbOption } from "@/Components/Layouts/ShowModel/Breadcrumbs/AdminBreadcrumbDisplayer.vue";

const props = defineProps<{
  reservation: App.Entities.Reservation;
}>();

const currentTab = useStorage("show-reservation-tab", "Komentarai");
const selectedReservationResource =
  ref<App.Entities.ReservationResource | null>(null);

const showEditModal = ref(false);

const breadcrumbOptions: BreadcrumbOption[] = [
  {
    label: props.reservation.name,
    icon: Icons.RESERVATION,
  },
];

const getAllComments = () => {
  let comments = props.reservation.comments ?? [];

  if (props.reservation.resources) {
    props.reservation.resources.forEach((resource) => {
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
