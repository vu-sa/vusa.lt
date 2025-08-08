<template>
  <NDataTable v-bind="$attrs" :data="reservation?.resources" :columns="dataTableColumns" :row-key="rowKey"
    :scroll-x="650" size="small" />

  <CardModal v-model:show="showStateChangeModal" :title="$page.props.app.locale === 'lt'
    ? 'Palikti komentarą arba naujinti būseną'
    : 'Leave a comment or update state'
    " @close="showStateChangeModal = false">
    <div class="relative w-full">
      <InfoText>
        <template v-if="$page.props.app.locale === 'lt'">
          Palik trumpą komentarą
        </template>
        <template v-else>
          Leave a short comment
        </template>
      </InfoText>

      <CommentTipTap v-model:text="commentText" class="mt-4" rounded-top :loading="loading"
        :enable-approve="selectedReservationResource?.approvable" :submit-text="$t('Komentuoti')"
        :approve-text="approveText" :reject-text="capitalize($t('state.decision.reject'))" :disabled="false"
        @submit:comment="submitComment" />
    </div>
  </CardModal>
</template>

<script setup lang="tsx">
import { trans as $t, transChoice as $tChoice } from "laravel-vue-i18n";
import { Link, router, usePage } from "@inertiajs/vue3";
import { type MenuOption, NButton, NIcon, NImage, NImageGroup, NPopover, NSpace } from "naive-ui";
import { computed, ref } from "vue";

import Delete16Regular from "~icons/fluent/delete16-regular";
import DismissCircle24Regular from "~icons/fluent/dismiss-circle24-regular";

import { RESERVATION_DATE_TIME_FORMAT } from "@/Constants/DateTimeFormats";
import { capitalize } from "@/Utils/String";
import { formatStaticTime } from "@/Utils/IntlTime";
import CardModal from "../Modals/CardModal.vue";
import CommentTipTap from "@/Features/Admin/CommentViewer/CommentTipTap.vue";
import InfoText from "../SmallElements/InfoText.vue";
import ReservationResourceStateTag from "../Tag/ReservationResourceStateTag.vue";
import UsersAvatarGroup from "../Avatars/UsersAvatarGroup.vue";
import MoreOptionsButton from "../Buttons/MoreOptionsButton.vue";

defineProps<{
  reservation: App.Entities.Reservation & { approvable: boolean };
}>();

const emit = defineEmits<{
  'edit:reservationResource': [reservationResource: App.Entities.ReservationResource]
}>()

const selectedReservationResource =
  defineModel<App.Entities.ReservationResource | null>(
    "selectedReservationResource"
  );

const showStateChangeModal = ref(false);
const rowKey = (row: App.Entities.Resource) => row.pivot?.id;

const dataTableColumns = [
  //{
  //  type: 'selection',
  //  disabled(row) {
  //    return !row?.pivot?.approvable
  //  }
  //},
  {
    type: "expand",
    renderExpand(row) {
      return (
        <section class="flex flex-col gap-2 p-2">
          <NImageGroup>
            <NSpace>
              {row.media?.map((image) => (
                <NImage width="150" src={image.original_url} alt={image.name} />
              ))}
            </NSpace>
          </NImageGroup>
          <div>
            <strong>{$t("forms.fields.description")}</strong>
            <p>{row.description}</p>
          </div>
        </section>
      );
    },
  },
  {
    title() {
      return $t("forms.fields.title");
    },
    key: "name",
    fixed: "left",
    minWidth: 75,
    render(row) {
      return <Link href={route("resources.edit", row.id)}>{row.name}</Link>;
    },
  },
  {
    title() {
      return $t("forms.fields.quantity");
    },
    key: "pivot.quantity",
    minWidth: 75,
  },
  {
    title() {
      return capitalize($tChoice("entities.tenant.model", 1));
    },
    key: "tenant.shortname",
    maxWidth: 150,
    width: 150,
    resizable: true,
    render(row: App.Entities.Resource) {
      return (
        <div class="inline-flex items-center gap-2">
          <span
            class={
              row.pivot?.state === "created" ? "font-bold text-vusa-red" : ""
            }
          >
            {$t(row.tenant?.shortname)}
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
    title() {
      return capitalize($t("entities.reservation.start_time"));
    },
    key: "pivot.start_time",
    minWidth: 75,
    render(row: App.Entities.Resource) {
      return (
        <span
          class={
            row.pivot.state === "reserved" ? "font-bold text-vusa-red" : ""
          }
        >
          {formatStaticTime(
            new Date(row.pivot.start_time),
            RESERVATION_DATE_TIME_FORMAT,
            usePage().props.app.locale
          )}
        </span>
      );
    },
  },
  {
    title() {
      return capitalize($t("entities.reservation.end_time"));
    },
    key: "pivot.end_time",
    minWidth: 75,
    render(row: App.Entities.Resource) {
      return (
        <span
          class={row.pivot?.state === "lent" ? "font-bold text-vusa-red" : ""}
        >
          {formatStaticTime(
            new Date(row.pivot.end_time),
            RESERVATION_DATE_TIME_FORMAT,
            usePage().props.app.locale
          )}
        </span>
      );
    },
  },
  {
    title() {
      return $t("forms.fields.state");
    },
    key: "pivot.state",
    render(row: App.Entities.Resource) {
      return (
        <ReservationResourceStateTag
          state={row.pivot.state}
          state_properties={row.pivot?.state_properties}
        ></ReservationResourceStateTag>
      );
    },
  },
  {
    title() {
      return $t("Veiksmai");
    },
    key: "pivot.actions",
    fixed: "right",
    render(row: App.Entities.Resource) {
      return (
        <div class="flex items-center space-x-2">
          {["created", "reserved", "lent"].includes(row.pivot?.state) ? (
            <NButton
              size="tiny"
              type={row.pivot?.approvable ? "primary" : "info"}
              onClick={() => handleStateChange(row)}
            >
              {{
                default: () => (
                  <span>
                    {row.pivot?.approvable
                      ? $t("Keisti būseną")
                      : $t("Komentuoti")}
                  </span>
                ),
              }}
            </NButton>
          ) : null}
          {["cancelled", "rejected"].includes(row.pivot?.state) ? (
            <NButton
              size="small"
              type="error"
              onClick={() => handlePivotDelete(row)}
            >
              {{
                icon: () => <NIcon component={Delete16Regular} />,
              }}
            </NButton>
          ) : null}
          {["created", "reserved"].includes(row.pivot?.state) ? (
            <>
              <MoreOptionsButton edit more-options={moreOptions} onEditClick={() => handleEditClick(row)} onMoreOptionClick={(key) => handleMoreOptionClick(key, row)} />
            </>
          ) : null}
        </div>
      );
    },
  },
];

const moreOptions: MenuOption[] = [
  {
    label() {
      return $t("Atšaukti rezervaciją");
    },
    icon() {
      return <NIcon component={DismissCircle24Regular}></NIcon>;
    },
    key: "dismiss-reservation",
  },
];

const handleMoreOptionClick = (key: 'dismiss-reservation', row) => {
  switch (key) {
    case "dismiss-reservation":
      handleReservationResourceCancel(row)
      break;
    default:
      break;
  }
};

const handleEditClick = (row: App.Entities.Resource) => {
  emit('edit:reservationResource', row.pivot)
};

const commentText = ref("");
const loading = ref(false);

const handleStateChange = (row: any) => {
  selectedReservationResource.value = row.pivot;
  showStateChangeModal.value = true;
};

const approveText = computed(() => {
  if (selectedReservationResource.value?.state === "reserved") {
    return capitalize($t("state.comment.lent"))
  }

  if (selectedReservationResource.value?.state === "lent") {
    return capitalize($t("state.comment.return"))
  }

  return capitalize($t("state.decision.approve"));
});

const setSelectedReservationResource = async (
  value: App.Entities.ReservationResource
) => {
  selectedReservationResource.value = value;
};

const handleReservationResourceCancel = async (row: App.Entities.Resource) => {
  await setSelectedReservationResource(row.pivot);
  submitComment("cancel", "<p>Atšaukiu išteklio rezervaciją</p>");
};

const submitComment = (
  decision?: "approve" | "reject" | "cancel",
  comment = commentText.value
) => {
  // add decision attribute
  loading.value = true;
  router.post(
    route("users.comments.store", usePage().props.auth?.user.id as number),
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

        if (!usePage().props.flash.error) {
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
