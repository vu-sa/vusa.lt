<template>
  <AdminContentPage>
    <div class="min-w-0 w-full space-y-6">
      <!-- Hero Section -->
      <ReservationHero :reservation @add-resource="handleAddResource" @add-user="handleAddUser"
        @show-help="showReservationHelpModal = true" />

      <!-- Main Content with Tabs -->
      <Tabs v-model="currentTab" class="space-y-4">
        <TabsList class="h-auto flex-wrap gap-2">
          <TabsTrigger value="resources" class="gap-2">
            <component :is="Icons.RESOURCE" class="size-4" />
            {{ capitalize($tChoice('entities.resource.model', 2)) }}
            <Badge variant="secondary" class="ml-1">
              {{ reservation.resources?.length ?? 0 }}
            </Badge>
          </TabsTrigger>
          <TabsTrigger value="description" class="gap-2">
            <IFluentTextDescription24Regular class="size-4" />
            {{ $t('Aprašymas') }}
          </TabsTrigger>
          <TabsTrigger value="comments" class="gap-2">
            <component :is="Icons.COMMENT" class="size-4" />
            {{ $t('Komentarai') }}
            <Badge v-if="allCommentsCount > 0" variant="secondary" class="ml-1">
              {{ allCommentsCount }}
            </Badge>
          </TabsTrigger>
        </TabsList>

        <!-- Resources Tab -->
        <TabsContent value="resources" class="space-y-4">
          <Card>
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-4">
              <div>
                <CardTitle class="text-base">
                  {{ $t('Rezervuoti ištekliai') }}
                </CardTitle>
                <CardDescription>{{ $t('Valdyk rezervacijos išteklius ir jų būsenas') }}</CardDescription>
              </div>
              <Button size="sm" @click="handleAddResource">
                <IFluentAdd24Filled class="size-4" />
                {{ $t('Pridėti') }}
              </Button>
            </CardHeader>
            <CardContent class="pt-0">
              <div class="overflow-x-auto">
                <ReservationResourceTable v-model:selected-reservation-resource="selectedReservationResource"
                  :reservation @edit:reservation-resource="editReservationResource" />
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        <!-- Description Tab -->
        <TabsContent value="description">
          <Card>
            <CardHeader>
              <CardTitle class="flex items-center gap-2 text-base">
                <IFluentTextDescription24Regular class="size-5" />
                {{ $t('Aprašymas') }}
              </CardTitle>
            </CardHeader>
            <CardContent>
              <p v-if="reservation.description" class="text-sm text-muted-foreground whitespace-pre-wrap">
                {{ reservation.description }}
              </p>
              <p v-else class="text-sm text-muted-foreground italic">
                {{ $t('Aprašymas nepateiktas.') }}
              </p>
            </CardContent>
          </Card>
        </TabsContent>

        <!-- Comments Tab -->
        <TabsContent value="comments" class="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle class="flex items-center gap-2 text-base">
                <component :is="Icons.COMMENT" class="size-5" />
                {{ $t('Komentarai') }}
              </CardTitle>
              <CardDescription>
                {{ RESERVATION_HELP_TEXTS.comments[$page.props.app.locale] }}
              </CardDescription>
            </CardHeader>
            <CardContent>
              <CommentViewer commentable_type="reservation" :model="reservation" :comments="getAllComments()" />
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>

    <!-- Dialogs -->
    <Dialog :open="showReservationHelpModal" @update:open="showReservationHelpModal = $event">
      <DialogContent class="max-w-3xl">
        <DialogHeader>
          <DialogTitle>{{ $t('entities.meta.help', { model: $tChoice('entities.reservation.model', 2) }) }}
          </DialogTitle>
        </DialogHeader>
        <MdSuspenseWrapper directory="reservations" :locale="$page.props.app.locale" file="help" />
      </DialogContent>
    </Dialog>

    <Dialog :open="showReservationResourceCreateModal" @update:open="showReservationResourceCreateModal = $event">
      <DialogContent class="max-w-3xl max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle>
            {{
              RESERVATION_CARD_MODAL_TITLES.create_reservation_resource[$page.props.app.locale][reservationResourceFormRouteName]
            }}
          </DialogTitle>
        </DialogHeader>
        <ReservationResourceForm :reservation-resource-form :all-resources :reservation-resource-form-route-name
          :currently-used-capacity @success="showReservationResourceCreateModal = false" />
      </DialogContent>
    </Dialog>

    <Dialog :open="showReservationAddUserModal" @update:open="showReservationAddUserModal = $event">
      <DialogContent class="max-w-lg">
        <DialogHeader>
          <DialogTitle>
            {{ RESERVATION_CARD_MODAL_TITLES.attach_user_to_reservation[$page.props.app.locale] }}
          </DialogTitle>
        </DialogHeader>
        <div class="space-y-4">
          <UserMultiSelect ref="userMultiSelectRef" v-model="selectedUsersList" :users="allUsers ?? []"
            :label="$t('Naudotojai')" :placeholder="`${$t('Pasirinkite')}...`" :empty-text="$t('No users found.')" />
          <Button :disabled="selectedUsersList.length === 0 || reservationUserForm.processing"
            @click="handleSubmitUserForm">
            <IFluentCheckmark24Filled v-if="!reservationUserForm.processing" class="size-4" />
            {{ $t("forms.submit") }}
          </Button>
        </div>
      </DialogContent>
    </Dialog>
  </AdminContentPage>
</template>

<script setup lang="ts">
import { trans as $t, transChoice as $tChoice } from "laravel-vue-i18n";
import { ref, toRaw, computed, watch, capitalize } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import { useStorage } from "@vueuse/core";

import ReservationHero from "./Partials/ReservationHero.vue";

import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/Components/ui/card";
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/Components/ui/dialog";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/Components/ui/tabs";
import { RESERVATION_CARD_MODAL_TITLES } from "@/Constants/I18n/CardModalTitles";
import { RESERVATION_HELP_TEXTS } from "@/Constants/I18n/HelpTexts";
import { usePageBreadcrumbs, BreadcrumbHelpers } from '@/Composables/useBreadcrumbsUnified';
import AdminContentPage from "@/Components/Layouts/AdminContentPage.vue";
import CommentViewer from "@/Features/Admin/CommentViewer/CommentViewer.vue";
import Icons from "@/Types/Icons/filled";
import MdSuspenseWrapper from "@/Features/MarkdownGetterFromDocs/MdSuspenseWrapper.vue";
import ReservationResourceForm from "@/Components/AdminForms/ReservationResourceForm.vue";
import ReservationResourceTable from "@/Components/Tables/ReservationResourceTable.vue";
import UserMultiSelect from "@/Components/Forms/UserMultiSelect.vue";

const props = defineProps<{
  reservation: App.Entities.Reservation;
  allResources?: App.Entities.Resource[];
  allUsers?: App.Entities.User[];
}>();

// Breadcrumbs setup
usePageBreadcrumbs(() => [
  BreadcrumbHelpers.homeItem(),
  BreadcrumbHelpers.createRouteBreadcrumb(
    capitalize($tChoice("entities.reservation.model", 2)),
    "reservations.index",
    {},
    Icons.RESERVATION
  ),
  BreadcrumbHelpers.createBreadcrumbItem(props.reservation.name)
]);

// Tab management
const currentTab = useStorage("show-reservation-tab", "resources");

// Resource form state
const selectedReservationResource = ref<App.Entities.ReservationResource | null>(null);
const showReservationResourceCreateModal = ref(false);
const showReservationAddUserModal = ref(false);
const showReservationHelpModal = ref(false);

const reservationResourceForm = useForm({
  id: undefined as string | undefined,
  resource_id: null as string | null,
  reservation_id: props.reservation.id,
  quantity: 1,
  start_time: new Date(props.reservation.start_time).getTime(),
  end_time: new Date(props.reservation.end_time).getTime(),
});

const reservationResourceFormRouteName = ref('reservationResources.store');
const currentlyUsedCapacity = ref(0);

// User form state
const reservationUserForm = useForm({
  users: null as number[] | null,
});

const selectedUsersList = ref<App.Entities.User[]>([]);
const userMultiSelectRef = ref<InstanceType<typeof UserMultiSelect> | null>(null);

// Watch selection changes to update form
watch(selectedUsersList, (users) => {
  reservationUserForm.users = users.map(u => u.id);
}, { deep: true });

// Comments computation
const allCommentsCount = computed(() => {
  const baseComments = props.reservation.comments?.length ?? 0;
  const resourceComments = props.reservation.resources?.reduce(
    (acc, r) => acc + (r.pivot?.comments?.length ?? 0),
    0
  ) ?? 0;
  return baseComments + resourceComments;
});

const getAllComments = () => {
  let comments = toRaw(props.reservation.comments) ?? [];
  const resources = toRaw(props.reservation.resources) ?? [];

  if (resources.length > 0) {
    resources.forEach((resource) => {
      resource.pivot?.comments?.forEach((comment) => {
        comments.push({
          ...comment,
          comment: `<strong>${resource.name}</strong> ${comment.comment}`,
        });
      });
    });
  }

  comments.sort((a, b) => {
    return new Date(a.created_at).getTime() - new Date(b.created_at).getTime();
  });

  comments = comments.filter(
    (comment, index, self) =>
      index === self.findIndex((c) => c.id === comment.id)
  );

  return comments;
};

// Action handlers
const handleAddResource = () => {
  reservationResourceFormRouteName.value = 'reservationResources.store';
  currentlyUsedCapacity.value = 0;
  reservationResourceForm.reset();
  showReservationResourceCreateModal.value = true;
};

const handleAddUser = () => {
  router.reload({
    only: ["allUsers"],
  });
  showReservationAddUserModal.value = true;
};

const handleSubmitUserForm = () => {
  reservationUserForm.put(
    route("reservations.add-users", {
      reservation: props.reservation.id,
    }),
    {
      onSuccess: () => {
        reservationUserForm.reset();
        selectedUsersList.value = [];
        userMultiSelectRef.value?.reset();
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
</script>
