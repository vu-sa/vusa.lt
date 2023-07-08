<template>
  <NDataTable
    :data="reservation?.resources"
    :columns="dataTableColumns"
    size="small"
  ></NDataTable>

  <CardModal
    v-model:show="showStateChangeModal"
    title="Palikti komentarą arba naujinti būseną"
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
        submit-text="Palikti komentarą"
        :approve-text="approveText"
        reject-text="... ir atmesti"
        :disabled="false"
        @submit:comment="submitComment"
      >
      </CommentTipTap>
    </div>
  </CardModal>
</template>

<script setup lang="tsx">
import { NButton, NDataTable, NIcon, NPopover } from "naive-ui";
import { computed, ref } from "vue";
import { router, usePage } from "@inertiajs/vue3";

import { Delete16Regular, DismissCircle24Regular } from "@vicons/fluent";
import { formatStaticTime } from "@/Utils/IntlTime";
import CardModal from "../Modals/CardModal.vue";
import CommentTipTap from "@/Features/Admin/CommentViewer/CommentTipTap.vue";
import InfoText from "../SmallElements/InfoText.vue";
import ReservationResourceStateTag from "../Tag/ReservationResourceStateTag.vue";
import UsersAvatarGroup from "../Avatars/UsersAvatarGroup.vue";

defineProps<{
  reservation: App.Entities.Reservation & { approvable: boolean };
}>();

const selectedReservationResource =
  defineModel<App.Entities.ReservationResource | null>(
    "selectedReservationResource"
  );

const showStateChangeModal = ref(false);

const dataTableColumns = [
  {
    title: "Pavadinimas",
    key: "name",
  },
  {
    title: "Kiekis",
    key: "pivot.quantity",
  },
  {
    title: "Padalinys",
    key: "padalinys.shortname",
    render(row: App.Entities.Resource) {
      return (
        <div class="inline-flex items-center gap-2">
          <span
            class={
              row.pivot?.state === "created" ? "font-bold text-vusa-red" : ""
            }
          >
            {row.padalinys?.shortname}
          </span>
          <UsersAvatarGroup
            users={row.managers}
            class="ml-2"
            size={24}
            max={2}
          />
        </div>
      );
    },
  },
  {
    title: "Rezervacijos pradžia",
    key: "pivot.start_time",
    render(row: App.Entities.Resource) {
      return (
        <span
          class={
            row.pivot.state === "reserved" ? "font-bold text-vusa-red" : ""
          }
        >
          {formatStaticTime(new Date(row.pivot.start_time), {
            weekday: "short",
            day: "2-digit",
            month: "narrow",
            hour: "numeric",
            minute: "numeric",
          })}
        </span>
      );
    },
  },
  {
    title: "Rezervacijos pabaiga",
    key: "pivot.end_time",
    render(row: App.Entities.Resource) {
      return (
        <span
          class={row.pivot.state === "lent" ? "font-bold text-vusa-red" : ""}
        >
          {formatStaticTime(new Date(row.pivot.end_time), {
            weekday: "short",
            day: "2-digit",
            month: "narrow",
            hour: "numeric",
            minute: "numeric",
          })}
        </span>
      );
    },
  },
  {
    title: "Būsena",
    key: "pivot.state",
    render(row: App.Entities.Resource) {
      return (
        <ReservationResourceStateTag
          state={row.pivot?.state} state_properties={row.pivot?.state_properties}
        ></ReservationResourceStateTag>
      );
    },
  },
  {
    title: "Veiksmai",
    key: "pivot.actions",
    render(row: App.Entities.Resource) {
      return (
        <div class="flex items-center space-x-2">
          {["created", "reserved", "lent"].includes(row.pivot.state) ? (
            <NButton
              size="tiny"
              type={row.pivot.approvable ? "primary" : "info"}
              onClick={() => handleStateChange(row)}
            >
              {{
                default: () => (
                  <span>
                    {row.pivot.approvable ? "Keisti būseną" : "Komentuoti"}
                  </span>
                ),
              }}
            </NButton>
          ) : null}
          {["cancelled", "rejected"].includes(row.pivot.state) ? (
            <NButton
              size="small"
              type="error"
              onClick={() => handlePivotDelete(row)}
            >
              {{ icon: () => <NIcon component={Delete16Regular} /> }}
            </NButton>
          ) : null}
          {["created", "reserved"].includes(row.pivot.state) ? (
            <NPopover>
              {{
                trigger: () => (
                    <NButton type="primary" quaternary circle size="small" onClick={() => handleReservationResourceCancel(row)}>
                      {{
                        icon: () => <NIcon component={DismissCircle24Regular} />,
                      }}
                    </NButton>
                ),
                default: () => "Atšaukti rezervaciją",
              }}
            </NPopover>
          ) : null}
        </div>
      );
    },
  },
];

const commentText = ref("");
const loading = ref(false);

const handleStateChange = (row: any) => {
  selectedReservationResource.value = row.pivot;
  showStateChangeModal.value = true;
};

const approveText = computed(() => {
  if (selectedReservationResource.value?.state === "reserved") {
    return "... ir pažymėti, kaip paskolintą";
  }

  if (selectedReservationResource.value?.state === "lent") {
    return "... ir pažymėti, kaip grąžintą";
  }

  return "... ir patvirtinti";
});

const setSelectedReservationResource = async (value: App.Entities.ReservationResource) => {
  selectedReservationResource.value = value;
}

const handleReservationResourceCancel = async (row: App.Entities.Resource) => {
  await setSelectedReservationResource(row.pivot);
  submitComment("cancel", "<p>Atšaukiu išteklio rezervaciją</p>");
};

const submitComment = (decision?: "approve" | "reject" | "cancel", comment = commentText.value) => {
  // add decision attribute
  loading.value = true;
  router.post(
    route("users.comments.store", usePage().props.auth?.user.id),
    {
      commentable_type: "reservation_resource",
      commentable_id: selectedReservationResource.value?.id,
      comment: comment,
      decision: decision ?? undefined,
    },
    {
      preserveScroll: true,
      onSuccess: () => {
        loading.value = false;

        if (usePage().props.flash.statusCode !== 403) {
          showStateChangeModal.value = false;
          selectedReservationResource.value = null;
          commentText.value = "";
        }
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
