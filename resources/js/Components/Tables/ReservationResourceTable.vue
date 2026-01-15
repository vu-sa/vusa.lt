<template>
  <!-- Bulk actions inline bar - no layout shift -->
  <div v-if="selectedRowIds.length > 0 && hasApprovableSelected" class="mb-2 inline-flex items-center gap-2 rounded-md border bg-muted/50 px-3 py-1.5 text-sm">
    <span class="font-medium">{{ selectedRowIds.length }}</span>
    <Button size="xs" variant="default" :disabled="bulkLoading" @click="showBulkApproveDialog = true">
      <Checkmark24Regular class="mr-1 size-3" />
      {{ $t('Patvirtinti') }}
    </Button>
    <Button size="xs" variant="destructive" :disabled="bulkLoading" @click="showBulkRejectDialog = true">
      <DismissCircle24Regular class="mr-1 size-3" />
      {{ $t('Atmesti') }}
    </Button>
    <Button size="xs" variant="ghost" @click="clearSelection">
      {{ $t('Išvalyti') }}
    </Button>
  </div>

  <DataTable 
    :columns 
    :data="reservation?.resources ?? []" 
    :pagination="false" 
    :get-row-id="(row) => String(row.pivot?.id ?? row.id)"
    :enable-row-selection="hasAnyApprovable"
    :enable-multi-row-selection="true"
    :enable-row-selection-column="hasAnyApprovable"
    :row-selection-state="rowSelection"
    @update:rowSelection="handleRowSelectionChange"
  />

  <!-- Comment modal (without approval actions) -->
  <Dialog v-model:open="showCommentModal">
    <DialogContent class="max-w-2xl">
      <DialogHeader>
        <DialogTitle>{{ $page.props.app.locale === 'lt' ? 'Palikti komentarą' : 'Leave a comment' }}</DialogTitle>
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

        <CommentTipTap 
          v-model:text="commentText" 
          class="mt-4" 
          rounded-top 
          :loading="commentLoading"
          :submit-text="$t('Komentuoti')"
          :disabled="false"
          @submit:comment="submitComment" 
        />
      </div>
    </DialogContent>
  </Dialog>

  <!-- Approval modal -->
  <Dialog v-model:open="showApprovalModal">
    <DialogContent class="max-w-md">
      <DialogHeader>
        <DialogTitle>{{ approvalModalTitle }}</DialogTitle>
      </DialogHeader>
      <div class="space-y-4">
        <InfoText v-if="selectedReservationResource">
          <template v-if="$page.props.app.locale === 'lt'">
            Išteklius: <strong>{{ getResourceName(selectedReservationResource) }}</strong>
            <span v-if="selectedReservationResource.quantity > 1" class="ml-1">({{ selectedReservationResource.quantity }} vnt.)</span>
          </template>
          <template v-else>
            Resource: <strong>{{ getResourceName(selectedReservationResource) }}</strong>
            <span v-if="selectedReservationResource.quantity > 1" class="ml-1">({{ selectedReservationResource.quantity }} pcs.)</span>
          </template>
        </InfoText>

        <ApprovalActions
          approvable-type="reservation_resource"
          :approvable-id="String(selectedReservationResource?.id ?? '')"
          :allow-approve="true"
          :allow-reject="selectedReservationResource?.state === 'created'"
          :allow-cancel="['created', 'reserved'].includes(selectedReservationResource?.state ?? '')"
          :show-notes="true"
          :show-quantity="selectedReservationResource?.state === 'created' && (selectedReservationResource?.quantity ?? 1) > 1"
          :max-quantity="selectedReservationResource?.quantity ?? 1"
          :step="1"
          @success="handleApprovalSuccess"
        />
      </div>
    </DialogContent>
  </Dialog>

  <!-- Bulk Approve Dialog with notes -->
  <Dialog v-model:open="showBulkApproveDialog">
    <DialogContent class="max-w-lg">
      <DialogHeader>
        <DialogTitle>{{ $t('Patvirtinti') }} {{ getApprovableSelectedIds().length }} {{ $t('resursus') }}</DialogTitle>
      </DialogHeader>
      <div class="space-y-4">
        <!-- Actions breakdown by state -->
        <div class="space-y-3 rounded-md border bg-muted/30 p-3 text-sm">
          <template v-for="(resources, state) in selectedResourcesByState" :key="state">
            <div v-if="resources.length > 0" class="space-y-1">
              <div class="font-medium text-muted-foreground">{{ getApproveActionLabel(state) }}</div>
              <ul class="ml-4 space-y-0.5">
                <li v-for="(resource, idx) in resources" :key="idx" class="flex items-center gap-2">
                  <span class="size-1.5 rounded-full bg-primary" />
                  <span>{{ resource.name }}</span>
                  <span v-if="resource.quantity > 1" class="text-muted-foreground">({{ resource.quantity }} vnt.)</span>
                </li>
              </ul>
            </div>
          </template>
        </div>

        <div class="space-y-2">
          <label class="text-sm font-medium">{{ $t('Pastabos') }} ({{ $t('neprivaloma') }})</label>
          <Textarea v-model="bulkNotes" :placeholder="$t('Įveskite pastabas...')" rows="2" />
        </div>
        <div class="flex justify-end gap-2">
          <Button variant="ghost" @click="showBulkApproveDialog = false">{{ $t('Atšaukti') }}</Button>
          <Button :disabled="bulkLoading" @click="handleBulkApprove">
            <span v-if="bulkLoading" class="i-svg-spinners-90-ring-with-bg mr-2 size-4" />
            {{ $t('Patvirtinti') }}
          </Button>
        </div>
      </div>
    </DialogContent>
  </Dialog>

  <!-- Bulk Reject Dialog with notes -->
  <Dialog v-model:open="showBulkRejectDialog">
    <DialogContent class="max-w-lg">
      <DialogHeader>
        <DialogTitle>{{ $t('Atmesti') }} {{ getApprovableSelectedIds().length }} {{ $t('resursus') }}</DialogTitle>
      </DialogHeader>
      <div class="space-y-4">
        <!-- Resources that will be rejected -->
        <div class="space-y-3 rounded-md border bg-destructive/5 p-3 text-sm">
          <div class="font-medium text-destructive">{{ getRejectActionLabel() }}</div>
          <ul class="ml-4 space-y-0.5">
            <template v-for="(resources, state) in selectedResourcesByState" :key="state">
              <li v-for="(resource, idx) in resources" :key="`${state}-${idx}`" class="flex items-center gap-2">
                <span class="size-1.5 rounded-full bg-destructive" />
                <span>{{ resource.name }}</span>
                <span v-if="resource.quantity > 1" class="text-muted-foreground">({{ resource.quantity }} vnt.)</span>
              </li>
            </template>
          </ul>
        </div>

        <div class="space-y-2">
          <label class="text-sm font-medium">{{ $t('Pastabos') }} ({{ $t('neprivaloma') }})</label>
          <Textarea v-model="bulkNotes" :placeholder="$t('Įveskite atmetimo priežastį...')" rows="2" />
        </div>
        <div class="flex justify-end gap-2">
          <Button variant="ghost" @click="showBulkRejectDialog = false">{{ $t('Atšaukti') }}</Button>
          <Button variant="destructive" :disabled="bulkLoading" @click="handleBulkReject">
            <span v-if="bulkLoading" class="i-svg-spinners-90-ring-with-bg mr-2 size-4" />
            {{ $t('Atmesti') }}
          </Button>
        </div>
      </div>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="tsx">
import type { ColumnDef, RowSelectionState } from "@tanstack/vue-table";
import { trans as $t, transChoice as $tChoice } from "laravel-vue-i18n";
import { Link, router, usePage } from "@inertiajs/vue3";
import { computed, ref } from "vue";

import InfoText from "../SmallElements/InfoText.vue";
import ReservationResourceStateTag from "../Tag/ReservationResourceStateTag.vue";
import UsersAvatarGroup from "../Avatars/UsersAvatarGroup.vue";

import Checkmark24Regular from "~icons/fluent/checkmark-24-regular";
import Delete16Regular from "~icons/fluent/delete16-regular";
import DismissCircle24Regular from "~icons/fluent/dismiss-circle24-regular";
import InfoIcon from "~icons/fluent/info-24-regular";
import { Button } from "@/Components/ui/button";
import { DataTable } from "@/Components/ui/data-table";
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/Components/ui/dialog";
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from "@/Components/ui/dropdown-menu";
import { HoverCard, HoverCardContent, HoverCardTrigger } from "@/Components/ui/hover-card";
import { Textarea } from "@/Components/ui/textarea";
import { RESERVATION_DATE_TIME_FORMAT } from "@/Constants/DateTimeFormats";
import { capitalize } from "@/Utils/String";
import { formatStaticTime } from "@/Utils/IntlTime";
import CommentTipTap from "@/Features/Admin/CommentViewer/CommentTipTap.vue";
import { ApprovalActions } from "@/Features/Admin/Approvals";

const props = defineProps<{
  reservation: App.Entities.Reservation & { approvable: boolean };
}>();

const emit = defineEmits<{
  'edit:reservationResource': [reservationResource: App.Entities.ReservationResource]
}>()

const selectedReservationResource =
  defineModel<App.Entities.ReservationResource | null>(
    "selectedReservationResource"
  );

// Modal states
const showCommentModal = ref(false);
const showApprovalModal = ref(false);
const showBulkApproveDialog = ref(false);
const showBulkRejectDialog = ref(false);
const bulkNotes = ref("");

// Row selection for bulk actions
const rowSelection = ref<RowSelectionState>({});

const selectedRowIds = computed(() => Object.keys(rowSelection.value).filter(key => rowSelection.value[key]));

const hasAnyApprovable = computed(() => 
  props.reservation?.resources?.some(r => r.pivot?.approvable) ?? false
);

const hasApprovableSelected = computed(() => {
  const resources = props.reservation?.resources ?? [];
  return selectedRowIds.value.some(id => {
    const resource = resources.find(r => String(r.pivot?.id ?? r.id) === String(id));
    return resource?.pivot?.approvable && ['created', 'reserved', 'lent'].includes(resource.pivot?.state ?? '');
  });
});

const handleRowSelectionChange = (newSelection: RowSelectionState) => {
  rowSelection.value = newSelection;
};

const clearSelection = () => {
  rowSelection.value = {};
};

// Get resource name helper
const getResourceName = (pivot: App.Entities.ReservationResource) => {
  const resources = props.reservation?.resources ?? [];
  const resource = resources.find(r => r.pivot?.id === pivot.id);
  return resource?.name ?? '';
};

// Approval modal title based on state
const approvalModalTitle = computed(() => {
  const locale = usePage().props.app.locale;
  const state = selectedReservationResource.value?.state;
  
  if (state === 'reserved') {
    return locale === 'lt' ? 'Patvirtinti išdavimą' : 'Confirm handover';
  }
  if (state === 'lent') {
    return locale === 'lt' ? 'Patvirtinti grąžinimą' : 'Confirm return';
  }
  return locale === 'lt' ? 'Tvirtinti rezervaciją' : 'Approve reservation';
});

// Get selected resources grouped by state for bulk action preview
const selectedResourcesByState = computed(() => {
  const resources = props.reservation?.resources ?? [];
  const approvableIds = getApprovableSelectedIds();
  
  const grouped: Record<string, Array<{ name: string; quantity: number }>> = {
    created: [],
    reserved: [],
    lent: [],
  };
  
  approvableIds.forEach(id => {
    const resource = resources.find(r => String(r.pivot?.id ?? r.id) === String(id));
    if (resource?.pivot) {
      const state = resource.pivot.state ?? '';
      if (grouped[state]) {
        grouped[state].push({
          name: resource.name ?? '',
          quantity: resource.pivot.quantity ?? 1,
        });
      }
    }
  });
  
  return grouped;
});

// Get action label for each state transition
const getApproveActionLabel = (state: string) => {
  const locale = usePage().props.app.locale;
  if (state === 'created') {
    return locale === 'lt' ? 'Bus patvirtinta rezervacija →' : 'Reservation will be approved →';
  }
  if (state === 'reserved') {
    return locale === 'lt' ? 'Bus išduota →' : 'Will be handed over →';
  }
  if (state === 'lent') {
    return locale === 'lt' ? 'Bus grąžinta →' : 'Will be returned →';
  }
  return '';
};

const getRejectActionLabel = () => {
  const locale = usePage().props.app.locale;
  return locale === 'lt' ? 'Bus atmesta' : 'Will be rejected';
};

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
    size: 200,
    cell: ({ row }) => {
      const resource = row.original;
      const pivotState = resource.pivot?.state;
      const isApprovable = resource.pivot?.approvable;
      
      return (
        <div class="flex items-center gap-2">
          {/* Approval button for approvable resources */}
          {["created", "reserved", "lent"].includes(pivotState ?? '') && isApprovable && (
            <Button
              size="sm"
              variant="default"
              onClick={() => handleApprovalAction(resource)}
            >
              {pivotState === 'reserved' && $t("Išduoti")}
              {pivotState === 'lent' && $t("Grąžinti")}
              {pivotState === 'created' && $t("Tvirtinti")}
            </Button>
          )}
          
          {/* Comment button for non-approvable resources */}
          {["created", "reserved", "lent"].includes(pivotState ?? '') && !isApprovable && (
            <Button
              size="sm"
              variant="secondary"
              onClick={() => handleCommentAction(resource)}
            >
              {$t("Komentuoti")}
            </Button>
          )}
          
          {/* Delete button for cancelled/rejected */}
          {["cancelled", "rejected"].includes(pivotState ?? '') && (
            <Button
              size="sm"
              variant="destructive"
              onClick={() => handlePivotDelete(resource)}
            >
              <Delete16Regular />
            </Button>
          )}
          
          {/* More options dropdown */}
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
                {!isApprovable && (
                  <DropdownMenuItem onClick={() => handleReservationResourceCancel(resource)}>
                    <DismissCircle24Regular class="mr-2 size-4" />
                    {$t("Atšaukti rezervaciją")}
                  </DropdownMenuItem>
                )}
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
const commentLoading = ref(false);
const bulkLoading = ref(false);

// Open approval modal for a specific resource
const handleApprovalAction = (row: App.Entities.Resource) => {
  selectedReservationResource.value = row.pivot ?? null;
  showApprovalModal.value = true;
};

// Open comment modal for non-approvable resources
const handleCommentAction = (row: App.Entities.Resource) => {
  selectedReservationResource.value = row.pivot ?? null;
  showCommentModal.value = true;
};

// Handle successful approval action
const handleApprovalSuccess = () => {
  showApprovalModal.value = false;
  selectedReservationResource.value = null;
  clearSelection();
};

// Handle bulk approve
const handleBulkApprove = () => {
  const approvableIds = getApprovableSelectedIds().map(id => String(id));
  if (approvableIds.length === 0) return;
  
  bulkLoading.value = true;
  router.post(
    route("approvals.bulkStore"),
    {
      approvable_type: "reservation_resource",
      approvable_ids: approvableIds,
      decision: "approved",
      notes: bulkNotes.value || null,
      step: 1,
    },
    {
      preserveScroll: true,
      onSuccess: () => {
        bulkLoading.value = false;
        showBulkApproveDialog.value = false;
        bulkNotes.value = "";
        clearSelection();
      },
      onError: () => {
        bulkLoading.value = false;
      },
    }
  );
};

// Handle bulk reject
const handleBulkReject = () => {
  const approvableIds = getApprovableSelectedIds().map(id => String(id));
  if (approvableIds.length === 0) return;
  
  bulkLoading.value = true;
  router.post(
    route("approvals.bulkStore"),
    {
      approvable_type: "reservation_resource",
      approvable_ids: approvableIds,
      decision: "rejected",
      notes: bulkNotes.value || null,
      step: 1,
    },
    {
      preserveScroll: true,
      onSuccess: () => {
        bulkLoading.value = false;
        showBulkRejectDialog.value = false;
        bulkNotes.value = "";
        clearSelection();
      },
      onError: () => {
        bulkLoading.value = false;
      },
    }
  );
};

// Get approvable selected IDs (only those that can be approved)
const getApprovableSelectedIds = () => {
  const resources = props.reservation?.resources ?? [];
  return selectedRowIds.value.filter(id => {
    const resource = resources.find(r => String(r.pivot?.id ?? r.id) === String(id));
    return resource?.pivot?.approvable && ['created', 'reserved', 'lent'].includes(resource.pivot?.state ?? '');
  });
};

// Cancel reservation via approval system
const handleReservationResourceCancel = (row: App.Entities.Resource) => {
  selectedReservationResource.value = row.pivot ?? null;
  
  router.post(
    route("approvals.store"),
    {
      approvable_type: "reservation_resource",
      approvable_id: String(row.pivot?.id ?? ''),
      decision: "cancelled",
      step: 1,
    },
    {
      preserveScroll: true,
      onSuccess: () => {
        selectedReservationResource.value = null;
      },
    }
  );
};

// Submit comment (without approval)
const submitComment = (comment = commentText.value) => {
  commentLoading.value = true;
  router.post(
    route("users.comments.store", usePage().props.auth?.user.id as number),
    {
      commentable_type: "reservation_resource",
      commentable_id: selectedReservationResource.value?.id,
      comment,
    },
    {
      preserveScroll: true,
      onSuccess: () => {
        commentLoading.value = false;

        if (!usePage().props.flash.error) {
          showCommentModal.value = false;
          selectedReservationResource.value = null;
          commentText.value = "";
        }
      },
      onError: () => {
        commentLoading.value = false;
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
