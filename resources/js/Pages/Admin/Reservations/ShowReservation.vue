<template>
  <ShowPageLayout :title="reservation.name" :model="reservation"
    :related-models="relatedModels" :current-tab="currentTab" @change:tab="currentTab = $event">
    <template #after-heading>
      <UsersAvatarGroup v-if="reservation.users && reservation.users.length > 0" class="mr-2"
        :users="reservation.users" />
    </template>
    <template #more-options>
      <MoreOptionsButton :more-options="moreOptions" @more-option-click="handleMoreOptionClick" />
    </template>

    <ReservationResourceTable v-model:selected-reservation-resource="selectedReservationResource" class="mb-4"
      :reservation @edit:reservation-resource="editReservationResource" />
    <NButton secondary size="small" @click="handleMoreOptionClick('add-resource')">
      <template #icon>
        <IFluentAdd24Filled />
      </template>
      {{ $t("Pridėti rezervacijos išteklių") }}
    </NButton>

    <CardModal :title="$t('entities.meta.help', {
      model: $tChoice('entities.reservation.model', 2),
    })
      " :show="showReservationHelpModal" @close="showReservationHelpModal = false">
      <MdSuspenseWrapper directory="reservations" :locale="$page.props.app.locale" file="help" />
    </CardModal>
    <CardModal :show="showReservationResourceCreateModal" :title="RESERVATION_CARD_MODAL_TITLES.create_reservation_resource[
      $page.props.app.locale
    ][reservationResourceFormRouteName]
      " @close="showReservationResourceCreateModal = false">
      <Suspense>
        <ReservationResourceForm :reservation-resource-form="reservationResourceForm" :all-resources
          :reservation-resource-form-route-name :currently-used-capacity
          @success="showReservationResourceCreateModal = false" />
      </Suspense>
    </CardModal>
    <CardModal :show="showReservationAddUserModal" :title="RESERVATION_CARD_MODAL_TITLES.attach_user_to_reservation[
      $page.props.app.locale
    ]
      " @close="showReservationAddUserModal = false">
      <NForm :model="reservationUserForm">
        <NFormItem :label="$t('Naudotojai')">
          <NSelect v-model:value="reservationUserForm.users" :placeholder="`${$t('Pasirinkite')}...`" filterable
            clearable label-field="name" value-field="id" multiple :render-label="renderUserFormLabel"
            :render-tag="renderUserFormTag" :options="allUsers" />
        </NFormItem>
        <NFormItem>
          <NButton type="primary" @click="handleSubmitUserForm">
            {{ $t("forms.submit") }}
          </NButton>
        </NFormItem>
      </NForm>
    </CardModal>
    <template #below>
      <div v-if="currentTab === 'Komentarai'">
        <InfoText class="mb-4">
          {{
            RESERVATION_HELP_TEXTS.comments[$page.props.app.locale]
          }}
        </InfoText>
        <CommentViewer class="mt-auto h-min" :commentable_type="'reservation'" :model="reservation"
          :comments="getAllComments()" />
      </div>
      <div v-else-if="currentTab === 'Aprašymas'">
        <p>{{ reservation.description }}</p>
      </div>
    </template>
  </ShowPageLayout>
</template>

<script setup lang="tsx">
import { trans as $t, transChoice as $tChoice } from "laravel-vue-i18n";
import {
  NIcon,
  NTag,
  NButton,
  NForm,
  NFormItem,
  NSelect,
  type MenuOption,
  type SelectRenderLabel,
  type SelectRenderTag,
} from "naive-ui";
import { ref, toRaw, computed } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import { useStorage } from "@vueuse/core";
import { capitalize } from "vue";

import { RESERVATION_CARD_MODAL_TITLES } from "@/Constants/I18n/CardModalTitles";
import { RESERVATION_HELP_TEXTS } from "@/Constants/I18n/HelpTexts";
import { usePageBreadcrumbs, BreadcrumbHelpers } from '@/Composables/useBreadcrumbsUnified';

import CardModal from "@/Components/Modals/CardModal.vue";
import CommentViewer from "@/Features/Admin/CommentViewer/CommentViewer.vue";
import Icons from "@/Types/Icons/filled";
import InfoText from "@/Components/SmallElements/InfoText.vue";
import ReservationResourceForm from "@/Components/AdminForms/ReservationResourceForm.vue";
import ReservationResourceTable from "@/Components/Tables/ReservationResourceTable.vue";
import ShowPageLayout from "@/Components/Layouts/ShowModel/ShowPageLayout.vue";
import UserAvatar from "@/Components/Avatars/UserAvatar.vue";
import UsersAvatarGroup from "@/Components/Avatars/UsersAvatarGroup.vue";
import MoreOptionsButton from "@/Components/Buttons/MoreOptionsButton.vue";
import MdSuspenseWrapper from "@/Features/MarkdownGetterFromDocs/MdSuspenseWrapper.vue";

const props = defineProps<{
  reservation: App.Entities.Reservation;
  // NOTE: allResources is used only in reservationResourceForm
  allResources?: App.Entities.Resource[];
  allUsers?: App.Entities.User[];
}>();

// Breadcrumbs setup
usePageBreadcrumbs(() => [
  BreadcrumbHelpers.homeItem(),
  BreadcrumbHelpers.createBreadcrumbItem($t("administration.title"), route("administration")),
  BreadcrumbHelpers.createRouteBreadcrumb(
    capitalize($tChoice("entities.reservation.model", 2)), 
    "reservations.index", 
    {}, 
    Icons.RESERVATION
  ),
  BreadcrumbHelpers.createBreadcrumbItem(props.reservation.name)
]);

const currentTab = useStorage("show-reservation-tab", "Komentarai");
const selectedReservationResource =
  ref<App.Entities.ReservationResource | null>(null);

const showReservationResourceCreateModal = ref(false);
const showReservationAddUserModal = ref(false);
const showReservationHelpModal = ref(false);

const reservationResourceForm = useForm({
  id: undefined,
  resource_id: null,
  reservation_id: props.reservation.id,
  quantity: 1,
  start_time: new Date(props.reservation.start_time).getTime(),
  end_time: new Date(props.reservation.end_time).getTime(),
});

// NOTE: I try to manage both cases of forms in one file, so this is needed
const reservationResourceFormRouteName = ref('reservationResources.store');
const currentlyUsedCapacity = ref(0);

const reservationUserForm = useForm({
  users: null,
});

const moreOptions: MenuOption[] = [
  {
    label() {
      return $t("Pridėti rezervacijos išteklių");
    },
    icon() {
      return <NIcon component={Icons.RESOURCE}></NIcon>;
    },
    key: "add-resource",
  },
  {
    label() {
      return $t("Pridėti rezervacijos valdytoją");
    },
    icon() {
      return <NIcon component={Icons.USER}></NIcon>;
    },
    key: "add-user",
  },
];

const handleMoreOptionClick = (key: 'add-resource' | 'add-user') => {
  switch (key) {
    case "add-resource":
      reservationResourceFormRouteName.value = 'reservationResources.store';
      currentlyUsedCapacity.value = 0;
      reservationResourceForm.reset();
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
        reservationUserForm.reset();
        showReservationAddUserModal.value = false;
      },
    }
  );
};

const editReservationResource = (rResource: App.Entities.ReservationResource) => {
  reservationResourceForm.reset();
  reservationResourceForm.id = rResource.id;
  reservationResourceForm.resource_id = rResource.resource_id;
  reservationResourceForm.quantity = rResource.quantity;
  reservationResourceForm.start_time = new Date(rResource.start_time).getTime();
  reservationResourceForm.end_time = new Date(rResource.end_time).getTime();

  reservationResourceFormRouteName.value = 'reservationResources.update';
  currentlyUsedCapacity.value = rResource.quantity;

  showReservationResourceCreateModal.value = true;
};

const getAllComments = () => {
  // WARNING: Not using toRaw() here causes a bug: Uncaught DOMException: Failed to execute 'replaceState' on 'History': #<Object> could not be cloned
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
  );
};

const renderUserFormTag: SelectRenderTag = ({
  option,
  handleClose,
}: {
  option: App.Entities.User;
  handleClose: () => void;
}) => {
  return (
    <NTag
      round
      closable
      onClose={(e) => {
        e.stopPropagation();
        handleClose();
      }}
    >
      <div class="inline-flex items-center gap-2 align-middle">
        <UserAvatar user={option} size={18} />
        <span>{option.name}</span>
      </div>
    </NTag>
  );
};

const relatedModels = [
  {
    name: "Aprašymas",
  },
  {
    name: "Komentarai",
    icon: Icons.COMMENT,
    count: props.reservation.comments?.length,
  },
];
</script>
