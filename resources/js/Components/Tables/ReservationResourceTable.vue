<template>
  <DataTable :columns :data="reservation?.resources ?? []" :pagination="false" :get-row-id="(row) => row.pivot?.id ?? row.id" />

  <Dialog v-model:open="showStateChangeModal">
    <DialogContent class="max-w-2xl">
      <DialogHeader>
        <DialogTitle>{{ $page.props.app.locale === 'lt' ? 'Palikti komentarą arba naujinti būseną' : 'Leave a comment or update state' }}</DialogTitle>
      </DialogHeader>
      <div class="relative w-full">
        <InfoText>
          <template v-if="$page.props.app.locale === 'lt'">
            Palik trumpą komentarą
          </template>
          <template v-else>
            Leave a short comment
          </template>
        </InfoText>

        <CommentTipTap v-model:text="commentText" class="mt-4" rounded-top :loading
          :enable-approve="selectedReservationResource?.approvable" :submit-text="$t('Komentuoti')"
          :approve-text :reject-text="capitalize($t('state.decision.reject'))" :disabled="false"
          @submit:comment="submitComment" />
      </div>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="tsx">
import type { ColumnDef } from "@tanstack/vue-table";
import { trans as $t, transChoice as $tChoice } from "laravel-vue-i18n";
import { Link, router, usePage } from "@inertiajs/vue3";
import { computed, ref } from "vue";

import InfoText from "../SmallElements/InfoText.vue";
import ReservationResourceStateTag from "../Tag/ReservationResourceStateTag.vue";
import UsersAvatarGroup from "../Avatars/UsersAvatarGroup.vue";

import Delete16Regular from "~icons/fluent/delete16-regular";
import DismissCircle24Regular from "~icons/fluent/dismiss-circle24-regular";
import InfoIcon from "~icons/fluent/info-24-regular";
import { Button } from "@/Components/ui/button";
import { DataTable } from "@/Components/ui/data-table";
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/Components/ui/dialog";
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from "@/Components/ui/dropdown-menu";
import { HoverCard, HoverCardContent, HoverCardTrigger } from "@/Components/ui/hover-card";
import { RESERVATION_DATE_TIME_FORMAT } from "@/Constants/DateTimeFormats";
import { capitalize } from "@/Utils/String";
import { formatStaticTime } from "@/Utils/IntlTime";
import CommentTipTap from "@/Features/Admin/CommentViewer/CommentTipTap.vue";

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

const columns = computed<ColumnDef<App.Entities.Resource>[]>(() => [
  {
    accessorKey: 'name',
    header: () => $t("forms.fields.title"),
    size: 200,
    minSize: 120,
    maxSize: 250,
    cell: ({ row }) => {
      const resource = row.original;
      const hasDetails = resource.media?.length || resource.description;
      
      if (!hasDetails) {
        return (
          <Link href={route("resources.edit", resource.id)} class="block max-w-[200px] truncate text-primary hover:underline" title={resource.name}>
            {resource.name}
          </Link>
        );
      }
      
      return (
        <HoverCard>
          <HoverCardTrigger asChild>
            <div class="inline-flex max-w-[200px] items-center gap-1">
              <Link href={route("resources.edit", resource.id)} class="truncate text-primary hover:underline" title={resource.name}>
                {resource.name}
              </Link>
              <InfoIcon class="size-4 shrink-0 text-muted-foreground" />
            </div>
          </HoverCardTrigger>
          <HoverCardContent class="w-80">
            <div class="flex flex-col gap-3">
              {resource.media?.length > 0 && (
                <div class="flex flex-wrap gap-2">
                  {resource.media.slice(0, 3).map((image) => (
                    <img
                      key={image.id}
                      src={image.original_url}
                      alt={image.name}
                      class="h-16 w-auto rounded object-cover"
                    />
                  ))}
                  {resource.media.length > 3 && (
                    <span class="text-sm text-muted-foreground self-center">
                      +{resource.media.length - 3}
                    </span>
                  )}
                </div>
              )}
              {resource.description && (
                <p class="text-sm text-muted-foreground line-clamp-3">{resource.description}</p>
              )}
            </div>
          </HoverCardContent>
        </HoverCard>
      );
    },
  },
  {
    accessorKey: 'pivot.quantity',
    header: () => $t("forms.fields.quantity"),
    cell: ({ row }) => row.original.pivot?.quantity,
    size: 80,
  },
  {
    accessorKey: 'tenant.shortname',
    header: () => capitalize($tChoice("entities.tenant.model", 1)),
    size: 150,
    cell: ({ row }) => (
      <div class="inline-flex items-center gap-2">
        <span class={row.original.pivot?.state === "created" ? "font-bold text-vusa-red" : ""}>
          {$t(row.original.tenant?.shortname ?? '')}
        </span>
        <UsersAvatarGroup users={row.original.managers} class="ml-2" size={24} max={2} />
      </div>
    ),
  },
  {
    accessorKey: 'pivot.start_time',
    header: () => capitalize($t("entities.reservation.start_time")),
    size: 150,
    cell: ({ row }) => (
      <span class={row.original.pivot?.state === "reserved" ? "font-bold text-vusa-red" : ""}>
        {formatStaticTime(
          new Date(row.original.pivot?.start_time ?? ''),
          RESERVATION_DATE_TIME_FORMAT,
          usePage().props.app.locale
        )}
      </span>
    ),
  },
  {
    accessorKey: 'pivot.end_time',
    header: () => capitalize($t("entities.reservation.end_time")),
    size: 150,
    cell: ({ row }) => (
      <span class={row.original.pivot?.state === "lent" ? "font-bold text-vusa-red" : ""}>
        {formatStaticTime(
          new Date(row.original.pivot?.end_time ?? ''),
          RESERVATION_DATE_TIME_FORMAT,
          usePage().props.app.locale
        )}
      </span>
    ),
  },
  {
    accessorKey: 'pivot.state',
    header: () => $t("forms.fields.state"),
    size: 120,
    cell: ({ row }) => (
      <ReservationResourceStateTag
        state={row.original.pivot?.state}
        state_properties={row.original.pivot?.state_properties}
      />
    ),
  },
  {
    id: 'actions',
    header: () => $t("Veiksmai"),
    size: 180,
    cell: ({ row }) => {
      const resource = row.original;
      const pivotState = resource.pivot?.state;
      return (
        <div class="flex items-center gap-2">
          {["created", "reserved", "lent"].includes(pivotState ?? '') && (
            <Button
              size="sm"
              variant={resource.pivot?.approvable ? "default" : "secondary"}
              onClick={() => handleStateChange(resource)}
            >
              {resource.pivot?.approvable ? $t("Keisti būseną") : $t("Komentuoti")}
            </Button>
          )}
          {["cancelled", "rejected"].includes(pivotState ?? '') && (
            <Button
              size="sm"
              variant="destructive"
              onClick={() => handlePivotDelete(resource)}
            >
              <Delete16Regular />
            </Button>
          )}
          {["created", "reserved"].includes(pivotState ?? '') && (
            <DropdownMenu>
              <DropdownMenuTrigger asChild>
                <Button variant="ghost" size="icon-sm">
                  <span class="sr-only">More options</span>
                  <span class="i-fluent-more-vertical-24-regular size-4" />
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end">
                <DropdownMenuItem onClick={() => handleEditClick(resource)}>
                  {$t("Redaguoti")}
                </DropdownMenuItem>
                <DropdownMenuItem onClick={() => handleReservationResourceCancel(resource)}>
                  <DismissCircle24Regular class="mr-2 size-4" />
                  {$t("Atšaukti rezervaciją")}
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
          )}
        </div>
      );
    },
  },
]);

const handleEditClick = (row: App.Entities.Resource) => {
  emit('edit:reservationResource', row.pivot)
};

const commentText = ref("");
const loading = ref(false);

const handleStateChange = (row: App.Entities.Resource) => {
  selectedReservationResource.value = row.pivot ?? null;
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
      comment,
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
