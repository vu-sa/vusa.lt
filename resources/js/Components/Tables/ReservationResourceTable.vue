<template>
  <div class="relative">
    <!-- Floating Bulk Actions Bar - positioned absolutely to avoid layout shift -->
    <Transition
      enter-active-class="transition-all duration-200 ease-out"
      enter-from-class="opacity-0 -translate-y-2 scale-95"
      enter-to-class="opacity-100 translate-y-0 scale-100"
      leave-active-class="transition-all duration-150 ease-in"
      leave-from-class="opacity-100 translate-y-0 scale-100"
      leave-to-class="opacity-0 -translate-y-2 scale-95"
    >
      <div 
        v-if="!isMobile && selectedRowIds.length > 0 && hasApprovableSelected" 
        class="absolute -top-12 left-0 z-20 inline-flex items-center gap-2 rounded-lg border border-zinc-200 bg-white px-3 py-2 shadow-lg dark:border-zinc-700 dark:bg-zinc-800"
      >
        <div class="flex size-6 items-center justify-center rounded-full bg-zinc-900 text-xs font-bold text-white dark:bg-zinc-100 dark:text-zinc-900">
          {{ selectedRowIds.length }}
        </div>
        <span class="text-sm font-medium text-zinc-600 dark:text-zinc-400">{{ $t('pasirinkta') }}</span>
        <div class="mx-1 h-4 w-px bg-zinc-200 dark:bg-zinc-700" />
        <Button size="sm" :disabled="bulkLoading" @click="showBulkApproveDialog = true">
          <Checkmark24Regular class="size-4" />
          {{ $t('Patvirtinti') }}
        </Button>
        <Button size="sm" variant="destructive" :disabled="bulkLoading" @click="showBulkRejectDialog = true">
          <DismissCircle24Regular class="size-4" />
          {{ $t('Atmesti') }}
        </Button>
        <Button size="icon-sm" variant="ghost" @click="clearSelection">
          <Dismiss24Regular class="size-4" />
        </Button>
      </div>
    </Transition>

    <!-- Mobile Card View -->
    <ReservationResourceCard
      v-if="isMobile"
      :resources="reservationResources"
      :selected-ids="selectedRowIds"
      :has-approvable-selected="hasApprovableSelected"
      @update:selected-ids="handleCardSelectionChange"
      @approve="handleApprovalAction"
      @comment="handleCommentAction"
      @delete="handlePivotDelete"
      @edit="handleEditClick"
      @cancel="handleReservationResourceCancel"
      @bulk-approve="showBulkApproveDialog = true"
      @bulk-reject="showBulkRejectDialog = true"
      @clear-selection="clearSelection"
      @add-resource="$emit('add-resource')"
    />

    <!-- Desktop Table View -->
    <DataTable 
      v-else
      :columns 
      :data="reservationResources" 
      :pagination="false" 
      :get-row-id="(row) => String(row.pivot?.id ?? row.id)"
      :enable-row-selection="canSelectRow"
      :enable-multi-row-selection="true"
      :enable-row-selection-column="hasAnyApprovable"
      :row-selection-state="rowSelection"
      :row-class-name="getRowClassName"
      :show-selection-count="false"
      @update:rowSelection="handleRowSelectionChange"
    />

    <!-- Empty state for desktop -->
    <div 
      v-if="!isMobile && (reservation?.resources?.length ?? 0) === 0" 
      class="flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-zinc-200 py-16 dark:border-zinc-700"
    >
      <div class="flex size-20 items-center justify-center rounded-full bg-gradient-to-br from-zinc-100 to-zinc-50 dark:from-zinc-800 dark:to-zinc-900">
        <Box24Regular class="size-10 text-muted-foreground" />
      </div>
      <h3 class="mt-5 text-lg font-semibold">{{ $t('Nėra rezervuotų išteklių') }}</h3>
      <p class="mt-1.5 max-w-sm text-center text-sm text-muted-foreground">
        {{ $t('Pridėkite išteklius prie šios rezervacijos, kad galėtumėte juos valdyti.') }}
      </p>
      <Button class="mt-5" @click="$emit('add-resource')">
        <Add24Filled class="size-4" />
        {{ $t('Pridėti išteklių') }}
      </Button>
    </div>
  </div>

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
import { useBreakpoints, breakpointsTailwind } from "@vueuse/core";

import InfoText from "../SmallElements/InfoText.vue";
import ReservationResourceCard from "./ReservationResourceCard.vue";
import ReservationResourceStateTag from "../Tag/ReservationResourceStateTag.vue";
import StateProgressIndicator from "../SmallElements/StateProgressIndicator.vue";
import UsersAvatarGroup from "../Avatars/UsersAvatarGroup.vue";

import Add24Filled from "~icons/fluent/add-24-filled";
import Box24Regular from "~icons/fluent/box-24-regular";
import Checkmark24Regular from "~icons/fluent/checkmark-24-regular";
import Delete16Regular from "~icons/fluent/delete16-regular";
import Dismiss24Regular from "~icons/fluent/dismiss-24-regular";
import DismissCircle24Regular from "~icons/fluent/dismiss-circle24-regular";
import InfoIcon from "~icons/fluent/info-24-regular";
import Warning24Filled from "~icons/fluent/warning-24-filled";
import { ApprovalActions } from "@/Features/Admin/Approvals";
import { Badge } from "@/Components/ui/badge";
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

const props = defineProps<{
  reservation: App.Entities.Reservation & { approvable: boolean };
}>();

const emit = defineEmits<{
  'edit:reservationResource': [reservationResource: App.Entities.ReservationResource];
  'add-resource': [];
}>()

const selectedReservationResource =
  defineModel<App.Entities.ReservationResource | null>(
    "selectedReservationResource"
  );

// Mobile detection
const breakpoints = useBreakpoints(breakpointsTailwind);
const isMobile = breakpoints.smaller('md');

// Modal states
const showCommentModal = ref(false);
const showApprovalModal = ref(false);
const showBulkApproveDialog = ref(false);
const showBulkRejectDialog = ref(false);
const bulkNotes = ref("");

// Resources - use directly from props (DataTable handles sorting)
const reservationResources = computed(() => props.reservation?.resources ?? []);

// Row selection for bulk actions
const rowSelection = ref<RowSelectionState>({});

const selectedRowIds = computed(() => Object.keys(rowSelection.value).filter(key => rowSelection.value[key]));

const hasAnyApprovable = computed(() => 
  props.reservation?.resources?.some(r => r.pivot?.approvable) ?? false
);

// Function-based row selection - disable for rejected/returned/cancelled rows
const canSelectRow = (row: any) => {
  const state = row.original?.pivot?.state;
  // Only allow selection for rows that can still be acted upon
  return ['created', 'reserved', 'lent'].includes(state) && row.original?.pivot?.approvable;
};

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

const handleCardSelectionChange = (newIds: string[]) => {
  const newSelection: RowSelectionState = {};
  newIds.forEach(id => { newSelection[id] = true; });
  rowSelection.value = newSelection;
};

// Row styling based on state - subtle left border indicator instead of full background
const getRowClassName = (row: App.Entities.Resource) => {
  const state = row.pivot?.state;
  const isOverdue = state === 'lent' && new Date(row.pivot?.end_time ?? '') < new Date();
  
  // Using subtle left border instead of full background color
  switch (state) {
    case 'created':
      return 'border-l-2 border-l-amber-400 dark:border-l-amber-500';
    case 'reserved':
      return 'border-l-2 border-l-blue-400 dark:border-l-blue-500';
    case 'lent':
      return isOverdue 
        ? 'border-l-2 border-l-red-500 bg-red-50/20 dark:border-l-red-400 dark:bg-red-950/10'
        : 'border-l-2 border-l-emerald-400 dark:border-l-emerald-500';
    case 'returned':
      return 'border-l-2 border-l-zinc-300 dark:border-l-zinc-600 opacity-60';
    case 'rejected':
    case 'cancelled':
      return 'border-l-2 border-l-zinc-300 dark:border-l-zinc-600 opacity-50';
    default:
      return '';
  }
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
          <HoverCardContent class="w-80 border-border/50 bg-card/95 shadow-lg backdrop-blur-sm">
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
    size: 180,
    cell: ({ row }) => (
      <div class="flex min-w-0 items-center gap-1.5">
        <span class={["shrink-0", row.original.pivot?.state === "created" ? "font-semibold text-vusa-red" : ""].filter(Boolean).join(' ')}>
          {$t(row.original.tenant?.shortname ?? '')}
        </span>
        <div class="shrink-0">
          <UsersAvatarGroup users={row.original.managers} size={20} max={2} />
        </div>
      </div>
    ),
  },
  {
    accessorKey: 'pivot.start_time',
    header: () => capitalize($t("entities.reservation.start_time")),
    size: 150,
    cell: ({ row }) => {
      const resource = row.original;
      const startDate = new Date(resource.pivot?.start_time ?? '');
      const isPickupOverdue = resource.pivot?.state === "reserved" && startDate < new Date();
      
      return (
        <div class="flex items-center gap-1.5">
          <span class={isPickupOverdue ? "font-semibold text-amber-600 dark:text-amber-400" : ""}>
            {formatStaticTime(startDate, RESERVATION_DATE_TIME_FORMAT, usePage().props.app.locale)}
          </span>
          {isPickupOverdue && (
            <span class="text-xs text-amber-600 dark:text-amber-400" title={$t('Laukiama atsiėmimo')}>
              ⏳
            </span>
          )}
        </div>
      );
    },
  },
  {
    accessorKey: 'pivot.end_time',
    header: () => capitalize($t("entities.reservation.end_time")),
    size: 170,
    cell: ({ row }) => {
      const resource = row.original;
      const endDate = new Date(resource.pivot?.end_time ?? '');
      const isOverdue = resource.pivot?.state === "lent" && endDate < new Date();
      
      return (
        <div class="flex items-center gap-1.5">
          <span class={isOverdue ? "font-semibold text-red-600 dark:text-red-400" : ""}>
            {formatStaticTime(endDate, RESERVATION_DATE_TIME_FORMAT, usePage().props.app.locale)}
          </span>
          {isOverdue && (
            <Badge variant="destructive" class="h-5 gap-0.5 px-1.5 text-[10px] font-medium">
              <Warning24Filled class="size-3" />
              {$t('Vėluojama')}
            </Badge>
          )}
        </div>
      );
    },
  },
  {
    accessorKey: 'pivot.state',
    header: () => $t("forms.fields.state"),
    size: 140,
    cell: ({ row }) => {
      const resource = row.original;
      const stateDescription = resource.pivot?.state_properties?.description;
      const approvals = (resource.pivot as any)?.approvals ?? [];
      const hasApprovals = approvals.length > 0;
      
      return (
        <HoverCard openDelay={200}>
          <HoverCardTrigger asChild>
            <div class="cursor-help">
              <ReservationResourceStateTag
                state={resource.pivot?.state ?? 'created'}
                state_properties={resource.pivot?.state_properties}
              />
            </div>
          </HoverCardTrigger>
          <HoverCardContent class="w-80 border-border/50 bg-card/95 shadow-lg backdrop-blur-sm" side="left" align="start">
            <div class="space-y-3">
              {/* Progress indicator */}
              <div class="space-y-1.5">
                <p class="text-xs font-medium text-muted-foreground">{$t('Eiga')}</p>
                <StateProgressIndicator currentState={resource.pivot?.state} />
              </div>
              
              {/* Approval history */}
              {hasApprovals && (
                <div class="border-t pt-3 space-y-2">
                  <p class="text-xs font-medium text-muted-foreground">{$t('Patvirtinimai')}</p>
                  <div class="space-y-1.5 max-h-32 overflow-y-auto">
                    {approvals.map((approval: any) => (
                      <div key={approval.id} class="flex items-start gap-2 text-xs">
                        <div class={[
                          "mt-0.5 size-1.5 shrink-0 rounded-full",
                          approval.decision === 'approved' ? 'bg-green-500' : 
                          approval.decision === 'rejected' ? 'bg-red-500' : 'bg-amber-500'
                        ].join(' ')} />
                        <div class="min-w-0 flex-1">
                          <div class="flex items-baseline justify-between gap-1">
                            <span class="font-medium truncate">{approval.user?.name ?? $t('Nežinomas')}</span>
                            <span class="shrink-0 text-muted-foreground text-[10px]">
                              {formatStaticTime(new Date(approval.created_at), 'MM-dd HH:mm', usePage().props.app.locale)}
                            </span>
                          </div>
                          {approval.notes && (
                            <p class="text-muted-foreground mt-0.5 line-clamp-2">{approval.notes}</p>
                          )}
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              )}
              
              {/* State description */}
              {stateDescription && (
                <div class="border-t pt-3">
                  <p class="text-xs text-muted-foreground">{stateDescription}</p>
                </div>
              )}
            </div>
          </HoverCardContent>
        </HoverCard>
      );
    },
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
