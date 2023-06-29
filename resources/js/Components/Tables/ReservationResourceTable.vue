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
import { NButton, NDataTable } from "naive-ui";
import { ref } from "vue";
import { router, usePage } from "@inertiajs/vue3";

import CardModal from "../Modals/CardModal.vue";
import CommentTipTap from "@/Features/Admin/CommentViewer/CommentTipTap.vue";
import InfoPopover from "../Buttons/InfoPopover.vue";
import InfoText from "../SmallElements/InfoText.vue";

const props = defineProps<{
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
        <div class="inline-flex gap-1">
          <span>{doingStateDescriptions[row.pivot.state].title}</span>
          <InfoPopover>
            {doingStateDescriptions[row.pivot.state].description}
          </InfoPopover>
        </div>
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

const doingStateDescriptions: Record<
  App.Entities.Reservation["state"],
  Record<"title" | "description", any>
> = {
  created: {
    title: "Sukurtas",
    description: (
      <span>
        Daikto rezervacijos užklausa yra sukurta! Laukiama, kol administratorius
        patvirtins rezervaciją.
      </span>
    ),
  },
  updated: {
    title: "Atnaujintas",
    description: (
      <span>
        <strong>Rezervacija atnaujinta!</strong> Laukiama, kol administratorius
        patvirtins rezervaciją.
      </span>
    ),
  },
  reserved: {
    title: "Rezervuotas",
    description: (
      <span>
        <strong>Daiktas rezervuotas!</strong> Rezervuotą daiktą galima atsiimti
        nurodytu laiku.
      </span>
    ),
  },
  lent: {
    title: "Atsiimtas",
    description: (
      <span>
        <strong>Daiktas paimtas!</strong> Daiktas sėkmingai paimtas rezervacijos
        organizatorių ir įpareigotas grąžinti nurodytu laiku.
      </span>
    ),
  },
  returned: {
    title: "Grąžintas",
    description: (
      <span>
        <strong>Daiktas grąžintas!</strong> Daiktas sėkmingai grąžintas
        rezervacijos organizatorių.
      </span>
    ),
  },
  rejected: {
    title: "Atmestas",
    description: <span>Daikto rezervacija atmesta.</span>,
  },
  cancelled: {
    title: "Atšauktas",
    description: <span>Daikto rezervacija atšaukta.</span>,
  },
};
</script>
