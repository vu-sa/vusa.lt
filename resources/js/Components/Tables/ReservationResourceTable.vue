<template>
  <NDataTable
    :data="reservation?.resources"
    :columns="dataTableColumns"
    size="small"
  ></NDataTable>

  <CardModal
    v-model:show="showStateChangeModal"
    title="Naujinti būseną"
    @close="showStateChangeModal = false"
  >
    <div class="not-prose relative w-full">
      <InfoText>Palik trumpą komentarą</InfoText>

      <CommentTipTap
        v-model:text="commentText"
        class="mt-4"
        rounded-top
        :loading="loading"
        :enable-approve="selectedReservationResource?.approvable"
        :disabled="false"
        @submit:comment="submitComment"
      >
        <template #submit-text>Pateikti</template>
      </CommentTipTap>
    </div>
  </CardModal>
</template>

<script setup lang="tsx">
import { NButton, NDataTable, NIcon } from "naive-ui";
import { ref } from "vue";
import { router, usePage } from "@inertiajs/vue3";

import { Delete16Regular } from "@vicons/fluent";
import CardModal from "../Modals/CardModal.vue";
import CommentTipTap from "@/Features/Admin/CommentViewer/CommentTipTap.vue";
import InfoText from "../SmallElements/InfoText.vue";
import ReservationResourceStateTag from "../Tag/ReservationResourceStateTag.vue";

defineProps<{
  reservation: App.Entities.Reservation & { approvable: boolean };
}>();

const selectedReservationResource =
  defineModel<App.Entities.ReservationResource | null>(
    "selectedReservationResource"
  );

const dataTableColumns = [
  {
    title: "Pavadinimas",
    key: "name",
  },
  {
    title: "Rezervacijos pradžia",
    key: "pivot.start_time",
  },
  {
    title: "Rezervacijos pabaiga",
    key: "pivot.end_time",
  },
  {
    title: "Kiekis",
    key: "pivot.quantity",
  },
  {
    title: "Būsena",
    key: "pivot.state",
    render(row) {
      return (
        <ReservationResourceStateTag
          reservationResource={row.pivot}
        ></ReservationResourceStateTag>
      );
    },
  },
  {
    title: "Veiksmai",
    key: "pivot.actions",
    render(row) {
      return (
        <div class="flex items-center space-x-2">
          <NButton
            size="small"
            type="warning"
            onClick={() => handleStateChange(row)}
          >
            Keisti būseną
          </NButton>
          {["cancelled", "rejected"].includes(row.pivot.state) ? (
            <NButton
              size="small"
              type="error"
              onClick={() => handlePivotDelete(row)}
            >
              {{ icon: () => <NIcon component={Delete16Regular} /> }}
            </NButton>
          ) : null}
        </div>
      );
    },
  },
];

const showStateChangeModal = ref(false);
const commentText = ref("");
const loading = ref(false);

const handleStateChange = (row: any) => {
  selectedReservationResource.value = row.pivot;
  showStateChangeModal.value = true;
};

const submitComment = (decision?: "approve" | "reject") => {
  // add decision attribute
  loading.value = true;
  router.post(
    route("users.comments.store", usePage().props.auth?.user.id),
    {
      commentable_type: "reservation_resource",
      commentable_id: selectedReservationResource.value?.id,
      comment: commentText.value,
      decision: decision ?? "progress",
    },
    {
      preserveScroll: true,
      onSuccess: () => {
        loading.value = false;
      },
      onError: () => {
        loading.value = false;
      },
    }
  );
};

const handlePivotDelete = (row: App.Entities.Resource) => {
  router.delete(
    route("reservationResources.destroy", {
      reservationResource: row.pivot.id,
    }),
    {
      preserveScroll: true,
      onSuccess: () => {
        selectedReservationResource.value = null;
      },
    }
  );
};
</script>
