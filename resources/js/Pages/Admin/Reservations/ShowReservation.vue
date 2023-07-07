<template>
  <ShowPageLayout
    :title="reservation.name"
    :breadcrumb-options="breadcrumbOptions"
    :model="reservation"
    :related-models="relatedModels"
    :current-tab="currentTab"
    @change:tab="currentTab = $event"
  >
    <template #after-heading>
      <UsersAvatarGroup
        v-if="reservation.users && reservation.users.length > 0"
        :users="reservation.users"
      />
    </template>
    <template #more-options>
      <NButton size="small" text @click="showReservationHelpModal = true">
        <template #icon><NIcon :component="QuestionCircle16Regular" /></template>
        <CardModal
          title="Kaip veikia rezervacija?"
          :show="showReservationHelpModal"
          @close="showReservationHelpModal = false"
        >
        <p>Kiekviena rezervacija gali turėti 6 skirtingus statusus: sukurta, rezervuota, paskolinta, grąžinta, atmesta, atšaukta.</p>
        <p class="my-4"><strong>Įprastas rezervacijos veiksmų tvirtinimo procesas</strong></p>
        <NTimeline horizontal>
          <NTimelineItem type="info" :title="$t('state.created')" />
          <NTimelineItem type="success" :title="$t('state.reserved')" />
          <NTimelineItem type="warning" :title="$t('state.lent')" />
          <NTimelineItem type="success" :title="$t('state.returned')" />
        </NTimeline>

        Sukūrus išteklio rezervacijos užklausą, ją galima atmesti. Rezervacijos kūrėjai taip pat gali
        atšaukti išteklio rezervaciją, iki daikto pasiskolinimo.
        </CardModal>

      </NButton>
      <MoreOptionsButton
        :more-options="moreOptions"
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
    <CardModal
      :show="showReservationAddUserModal"
      title="Pridėti valdytoją prie rezervacijos"
      @close="showReservationAddUserModal = false"
    >
      <NForm :model="reservationUserForm">
        <NFormItem label="Naudotojai">
          <NSelect placeholder="Pasirink rezervacijos valdytojus..."
            v-model:value="reservationUserForm.users"
            filterable
            clearable
            label-field="name"
            value-field="id"
            multiple
            :render-label="renderUserFormLabel"
            :render-tag="renderUserFormTag"
            :options="allUsers"
          ></NSelect>
        </NFormItem>
        <NFormItem>
          <NButton type="primary" @click="handleSubmitUserForm"
            >Pridėti</NButton
          >
        </NFormItem>
      </NForm>
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
          :comments="getAllComments()"
        />
      </div>
    </template>
  </ShowPageLayout>
</template>

<script setup lang="tsx">
import { ref, toRaw } from "vue";
import { useStorage } from "@vueuse/core";

import {
  type MenuOption,
  NButton,
  NForm,
  NFormItem,
  NIcon,
  NSelect,
type SelectRenderLabel,
type SelectRenderTag,
NTag,
type SelectOption,
NTimelineItem,
NTimeline,
} from "naive-ui";
import { router, useForm } from "@inertiajs/vue3";
import CardModal from "@/Components/Modals/CardModal.vue";
import CommentViewer from "@/Features/Admin/CommentViewer/CommentViewer.vue";
import Icons from "@/Types/Icons/filled";
import InfoText from "@/Components/SmallElements/InfoText.vue";
import MoreOptionsButton from "@/Components/Buttons/MoreOptionsButton.vue";
import ReservationResourceForm from "@/Components/AdminForms/ReservationResourceForm.vue";
import ReservationResourceTable from "@/Components/Tables/ReservationResourceTable.vue";
import ReservationResourceStateTag from "@/Components/Tag/ReservationResourceStateTag.vue";
import ShowPageLayout from "@/Components/Layouts/ShowModel/ShowPageLayout.vue";
import UsersAvatarGroup from "@/Components/Avatars/UsersAvatarGroup.vue";
import type { BreadcrumbOption } from "@/Components/Layouts/ShowModel/Breadcrumbs/AdminBreadcrumbDisplayer.vue";
import UserAvatar from "@/Components/Avatars/UserAvatar.vue";
import { QuestionCircle16Regular } from "@vicons/fluent";

const props = defineProps<{
  reservation: App.Entities.Reservation;
  allResources?: App.Entities.Resource[];
  allUsers?: App.Entities.User[];
}>();

const currentTab = useStorage("show-reservation-tab", "Komentarai");
const selectedReservationResource =
  ref<App.Entities.ReservationResource | null>(null);

const showReservationResourceCreateModal = ref(false);
const showReservationAddUserModal = ref(false);
const showReservationHelpModal = ref(false);

const reservationResourceForm = useForm({
  resource_id: null,
  reservation_id: props.reservation.id,
  quantity: 1,
  start_time: new Date(props.reservation.start_time).getTime(),
  end_time: new Date(props.reservation.end_time).getTime(),
});

const reservationUserForm = useForm({
  users: null,
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
  {
    label: "Pridėti rezervacijos valdytoją",
    icon() {
      return <NIcon component={Icons.USER}></NIcon>;
    },
    key: "add-user",
  },
];

const handleMoreOptionClick = (key: string) => {
  switch (key) {
    case "add-resource":
      showReservationResourceCreateModal.value = true;
      break;
    case "add-user":
      router.reload({
        only: ["allUsers"],
      });
      showReservationAddUserModal.value = true;
      break;
    default:
      break;
  }
};

const handleSubmitUserForm = () => {
  reservationUserForm.put(
    route("reservations.add-users", {
      reservation: props.reservation.id,
    }),
    {
      onSuccess: () => {
        showReservationAddUserModal.value = false;
      },
    }
  );
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

  // filter by unique id
  // ! They should be unique already, but they're not..? Maybe optimize
  comments = comments.filter(
    (comment, index, self) =>
      index === self.findIndex((c) => c.id === comment.id)
  );

  return comments;
};

const renderUserFormLabel: SelectRenderLabel = (option, selected) => {
  return (
    <div class="inline-flex items-center gap-2">
      <UserAvatar user={option} size={20} />
      <span>{option.name}</span>
    </div>
  )
}

const renderUserFormTag: SelectRenderTag = ({ option, handleClose } : { option: App.Entities.User, handleClose: () => void }) => {
  return (
    <NTag round closable onClose={
      (e) => {
        e.stopPropagation();
        handleClose();
      }
    }>
      <div class="inline-flex items-center gap-2 align-middle">
        <UserAvatar user={option} size={18} />
        <span>{option.name}</span>
      </div>
    </NTag>
  )
};

const relatedModels = [
  {
    name: "Komentarai",
    icon: Icons.COMMENT,
    count: props.reservation.comments?.length,
  },
];
</script>
