<template>
  <!-- Mobile Card View for Reservation Resources -->
  <div class="space-y-3">
    <!-- Header with sort control -->
    <div v-if="resources.length > 1" class="flex items-center justify-end">
      <Select v-model="sortBy">
        <SelectTrigger class="h-8 w-auto gap-1.5 text-xs">
          <IFluentArrowSort24Regular class="size-3.5" />
          <SelectValue :placeholder="$t('Rūšiuoti')" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="state">{{ $t('forms.fields.state') }}</SelectItem>
          <SelectItem value="name">{{ $t('forms.fields.title') }}</SelectItem>
          <SelectItem value="start_time">{{ $t('entities.reservation.start_time') }}</SelectItem>
          <SelectItem value="end_time">{{ $t('entities.reservation.end_time') }}</SelectItem>
        </SelectContent>
      </Select>
    </div>

    <!-- Selection header when items selected -->
    <Transition
      enter-active-class="transition-all duration-200 ease-out"
      enter-from-class="opacity-0 -translate-y-2"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition-all duration-150 ease-in"
      leave-from-class="opacity-100 translate-y-0"
      leave-to-class="opacity-0 -translate-y-2"
    >
      <div 
        v-if="selectedIds.length > 0 && hasApprovableSelected" 
        class="sticky top-0 z-20 flex items-center justify-between gap-2 rounded-xl border border-zinc-200 bg-white/95 p-3 shadow-lg backdrop-blur-sm dark:border-zinc-700 dark:bg-zinc-900/95"
      >
        <div class="flex items-center gap-2">
          <div class="flex size-8 items-center justify-center rounded-full bg-primary text-sm font-bold text-primary-foreground">
            {{ selectedIds.length }}
          </div>
          <span class="text-sm font-medium">{{ $t('pasirinkta') }}</span>
        </div>
        <div class="flex items-center gap-2">
          <Button size="sm" variant="default" @click="$emit('bulk-approve')">
            <IFluentCheckmark24Filled class="size-4" />
            <span class="sr-only sm:not-sr-only sm:ml-1">{{ $t('Patvirtinti') }}</span>
          </Button>
          <Button size="sm" variant="destructive" @click="$emit('bulk-reject')">
            <IFluentDismiss24Filled class="size-4" />
            <span class="sr-only sm:not-sr-only sm:ml-1">{{ $t('Atmesti') }}</span>
          </Button>
          <Button size="icon-sm" variant="ghost" @click="$emit('clear-selection')">
            <IFluentDismissCircle24Regular class="size-4" />
          </Button>
        </div>
      </div>
    </Transition>

    <!-- Resource Cards -->
    <TransitionGroup
      enter-active-class="transition-all duration-300 ease-out"
      enter-from-class="opacity-0 translate-y-4"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition-all duration-200 ease-in absolute"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0 scale-95"
      move-class="transition-all duration-300 ease-out"
      tag="div"
      class="relative space-y-3"
    >
      <div
        v-for="resource in sortedResources"
        :key="resource.pivot?.id ?? resource.id"
        :class="[
          'group relative rounded-xl border transition-all duration-200',
          getCardClasses(resource),
          isSelected(resource) && 'ring-2 ring-primary ring-offset-2 dark:ring-offset-zinc-900',
        ]"
      >
        <!-- State indicator strip -->
        <div 
          :class="[
            'absolute inset-y-0 left-0 w-1 rounded-l-xl transition-colors',
            getStateStripColor(resource.pivot?.state)
          ]" 
        />

        <!-- Card content -->
        <div class="flex flex-col gap-3 p-4 pl-5">
          <!-- Header: Checkbox + Name + State -->
          <div class="flex items-start gap-3">
            <!-- Selection checkbox - only for approvable and actionable states -->
            <Checkbox
              v-if="canSelectResource(resource)"
              :model-value="isSelected(resource)"
              class="mt-0.5 shrink-0"
              @update:model-value="toggleSelection(resource)"
            />
            <div v-else class="w-5" />

            <!-- Name and tenant -->
            <div class="min-w-0 flex-1">
              <div class="flex items-center gap-2">
                <Link 
                  :href="route('resources.edit', resource.id)" 
                  class="truncate font-medium text-foreground hover:text-primary hover:underline"
                >
                  {{ resource.name }}
                </Link>
                <!-- Info hover for details -->
                <HoverCard v-if="resource.media?.length || resource.description">
                  <HoverCardTrigger as-child>
                    <button class="shrink-0 text-muted-foreground transition-colors hover:text-foreground">
                      <IFluentInfo24Regular class="size-4" />
                    </button>
                  </HoverCardTrigger>
                  <HoverCardContent class="w-72" side="top">
                    <div class="flex flex-col gap-2">
                      <div v-if="resource.media?.length" class="flex flex-wrap gap-1.5">
                        <img
                          v-for="img in resource.media.slice(0, 3)"
                          :key="img.id"
                          :src="img.original_url"
                          :alt="img.name"
                          class="size-12 rounded-md object-cover"
                        />
                        <span v-if="resource.media.length > 3" class="flex size-12 items-center justify-center rounded-md bg-muted text-xs text-muted-foreground">
                          +{{ resource.media.length - 3 }}
                        </span>
                      </div>
                      <p v-if="resource.description" class="line-clamp-3 text-sm text-muted-foreground">
                        {{ resource.description }}
                      </p>
                    </div>
                  </HoverCardContent>
                </HoverCard>
              </div>
              
              <!-- Tenant + managers -->
              <div class="mt-0.5 flex items-center gap-2 text-sm text-muted-foreground">
                <span :class="resource.pivot?.state === 'created' ? 'font-semibold text-amber-600 dark:text-amber-400' : ''">
                  {{ $t(resource.tenant?.shortname ?? '') }}
                </span>
                <UsersAvatarGroup 
                  v-if="(resource as any).managers?.length" 
                  :users="(resource as any).managers" 
                  :max="2" 
                  :size="18" 
                />
              </div>
            </div>

            <!-- State badge with approval timeline hover -->
            <HoverCard :open-delay="200">
              <HoverCardTrigger as-child>
                <div class="shrink-0">
                  <ReservationResourceStateTag
                    :state="resource.pivot?.state ?? 'created'"
                    :state_properties="resource.pivot?.state_properties"
                  />
                </div>
              </HoverCardTrigger>
              <HoverCardContent class="w-80" side="left" align="start">
                <div class="space-y-3">
                  <!-- Progress indicator -->
                  <div class="space-y-1.5">
                    <p class="text-xs font-medium text-muted-foreground">{{ $t('Eiga') }}</p>
                    <StateProgressIndicator :current-state="resource.pivot?.state" />
                  </div>
                  
                  <!-- State description -->
                  <div v-if="(resource.pivot?.state_properties as any)?.description" class="border-t pt-3">
                    <p class="text-xs text-muted-foreground">{{ (resource.pivot?.state_properties as any)?.description }}</p>
                  </div>
                </div>
              </HoverCardContent>
            </HoverCard>
          </div>

          <!-- Date/Quantity row -->
          <div class="flex flex-wrap items-center gap-x-4 gap-y-1.5 text-sm">
            <!-- Quantity -->
            <div class="flex items-center gap-1.5">
              <IFluentStack24Regular class="size-4 text-muted-foreground" />
              <span class="font-medium">{{ resource.pivot?.quantity }}</span>
              <span class="text-muted-foreground">{{ $t('vnt.') }}</span>
            </div>

            <!-- Dates with warning indicators -->
            <div class="flex items-center gap-1.5">
              <IFluentCalendarLtr24Regular class="size-4 text-muted-foreground" />
              <span :class="getDateWarningClass(resource, 'start')">
                {{ formatDate(resource.pivot?.start_time) }}
              </span>
              <IFluentArrowRight24Regular class="size-3 text-muted-foreground" />
              <span :class="getDateWarningClass(resource, 'end')">
                {{ formatDate(resource.pivot?.end_time) }}
              </span>
            </div>

            <!-- Overdue warning -->
            <Badge v-if="isOverdue(resource)" variant="destructive" class="gap-1 text-xs">
              <IFluentWarning24Filled class="size-3" />
              {{ $t('Vėluojama') }}
            </Badge>
          </div>

          <!-- Actions -->
          <div class="flex flex-wrap items-center gap-2 border-t pt-3">
            <!-- Primary action based on state -->
            <template v-if="resource.pivot?.approvable">
              <Button 
                v-if="resource.pivot?.state === 'created'" 
                size="sm" 
                @click="$emit('approve', resource)"
              >
                <IFluentCheckmark24Filled class="size-4" />
                {{ $t('Tvirtinti') }}
              </Button>
              <Button 
                v-else-if="resource.pivot?.state === 'reserved'" 
                size="sm" 
                @click="$emit('approve', resource)"
              >
                <IFluentHandLeft24Regular class="size-4" />
                {{ $t('Išduoti') }}
              </Button>
              <Button 
                v-else-if="resource.pivot?.state === 'lent'" 
                size="sm" 
                @click="$emit('approve', resource)"
              >
                <IFluentArrowUndo24Regular class="size-4" />
                {{ $t('Grąžinti') }}
              </Button>
            </template>

            <!-- Comment button for non-approvable -->
            <Button 
              v-if="!resource.pivot?.approvable && ['created', 'reserved', 'lent'].includes(resource.pivot?.state ?? '')" 
              size="sm" 
              variant="secondary"
              @click="$emit('comment', resource)"
            >
              <IFluentComment24Regular class="size-4" />
              {{ $t('Komentuoti') }}
            </Button>

            <!-- Delete for cancelled/rejected -->
            <Button 
              v-if="['cancelled', 'rejected'].includes(resource.pivot?.state ?? '')"
              size="sm" 
              variant="destructive"
              @click="$emit('delete', resource)"
            >
              <IFluentDelete24Regular class="size-4" />
              {{ $t('Šalinti') }}
            </Button>

            <!-- Secondary actions dropdown -->
            <DropdownMenu v-if="['created', 'reserved'].includes(resource.pivot?.state ?? '')">
              <DropdownMenuTrigger as-child>
                <Button variant="ghost" size="icon-sm">
                  <IFluentMoreVertical24Regular class="size-4" />
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end">
                <DropdownMenuItem @click="$emit('edit', resource)">
                  <IFluentEdit24Regular class="mr-2 size-4" />
                  {{ $t('Redaguoti') }}
                </DropdownMenuItem>
                <DropdownMenuItem 
                  v-if="!resource.pivot?.approvable" 
                  class="text-destructive"
                  @click="$emit('cancel', resource)"
                >
                  <IFluentDismissCircle24Regular class="mr-2 size-4" />
                  {{ $t('Atšaukti rezervaciją') }}
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
          </div>
        </div>
      </div>
    </TransitionGroup>

    <!-- Empty state -->
    <div 
      v-if="resources.length === 0" 
      class="flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-zinc-200 py-12 dark:border-zinc-700"
    >
      <div class="flex size-16 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800">
        <IFluentBox24Regular class="size-8 text-muted-foreground" />
      </div>
      <h3 class="mt-4 text-base font-semibold">{{ $t('Nėra rezervuotų išteklių') }}</h3>
      <p class="mt-1 max-w-xs text-center text-sm text-muted-foreground">
        {{ $t('Pridėkite išteklius prie šios rezervacijos, kad galėtumėte juos valdyti.') }}
      </p>
      <Button class="mt-4" @click="$emit('add-resource')">
        <IFluentAdd24Filled class="size-4" />
        {{ $t('Pridėti išteklių') }}
      </Button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from "laravel-vue-i18n";
import { computed, ref } from "vue";
import { Link, usePage } from "@inertiajs/vue3";

import ReservationResourceStateTag from "@/Components/Tag/ReservationResourceStateTag.vue";
import StateProgressIndicator from "@/Components/SmallElements/StateProgressIndicator.vue";
import UsersAvatarGroup from "@/Components/Avatars/UsersAvatarGroup.vue";

import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import { Checkbox } from "@/Components/ui/checkbox";
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from "@/Components/ui/dropdown-menu";
import { HoverCard, HoverCardContent, HoverCardTrigger } from "@/Components/ui/hover-card";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/Components/ui/select";
import { RESERVATION_DATE_TIME_FORMAT } from "@/Constants/DateTimeFormats";
import { formatStaticTime } from "@/Utils/IntlTime";

// Icons
import IFluentAdd24Filled from "~icons/fluent/add-24-filled";
import IFluentArrowRight24Regular from "~icons/fluent/arrow-right-24-regular";
import IFluentArrowSort24Regular from "~icons/fluent/arrow-sort-24-regular";
import IFluentArrowUndo24Regular from "~icons/fluent/arrow-undo-24-regular";
import IFluentBox24Regular from "~icons/fluent/box-24-regular";
import IFluentCalendarLtr24Regular from "~icons/fluent/calendar-ltr-24-regular";
import IFluentCheckmark24Filled from "~icons/fluent/checkmark-24-filled";
import IFluentComment24Regular from "~icons/fluent/comment-24-regular";
import IFluentDelete24Regular from "~icons/fluent/delete-24-regular";
import IFluentDismiss24Filled from "~icons/fluent/dismiss-24-filled";
import IFluentDismissCircle24Regular from "~icons/fluent/dismiss-circle-24-regular";
import IFluentEdit24Regular from "~icons/fluent/edit-24-regular";
import IFluentHandLeft24Regular from "~icons/fluent/hand-left-24-regular";
import IFluentInfo24Regular from "~icons/fluent/info-24-regular";
import IFluentMoreVertical24Regular from "~icons/fluent/more-vertical-24-regular";
import IFluentStack24Regular from "~icons/fluent/stack-24-regular";
import IFluentWarning24Filled from "~icons/fluent/warning-24-filled";

const props = defineProps<{
  resources: App.Entities.Resource[];
  selectedIds: string[];
  hasApprovableSelected: boolean;
}>();

const emit = defineEmits<{
  'update:selectedIds': [ids: string[]];
  'approve': [resource: App.Entities.Resource];
  'comment': [resource: App.Entities.Resource];
  'delete': [resource: App.Entities.Resource];
  'edit': [resource: App.Entities.Resource];
  'cancel': [resource: App.Entities.Resource];
  'bulk-approve': [];
  'bulk-reject': [];
  'clear-selection': [];
  'add-resource': [];
}>();

const locale = computed(() => usePage().props.app.locale);

// Sorting
const sortBy = ref<string>('state');

const stateOrder: Record<string, number> = {
  'created': 0,
  'reserved': 1,
  'lent': 2,
  'returned': 3,
  'rejected': 4,
  'cancelled': 5,
};

const sortedResources = computed(() => {
  const resources = [...props.resources];
  
  return resources.sort((a, b) => {
    switch (sortBy.value) {
      case 'state':
        const stateA = stateOrder[a.pivot?.state ?? ''] ?? 99;
        const stateB = stateOrder[b.pivot?.state ?? ''] ?? 99;
        return stateA - stateB;
      case 'name':
        return (a.name ?? '').localeCompare(b.name ?? '');
      case 'start_time':
        return new Date(a.pivot?.start_time ?? '').getTime() - new Date(b.pivot?.start_time ?? '').getTime();
      case 'end_time':
        return new Date(a.pivot?.end_time ?? '').getTime() - new Date(b.pivot?.end_time ?? '').getTime();
      default:
        return 0;
    }
  });
});

const isSelected = (resource: App.Entities.Resource) => {
  const id = String(resource.pivot?.id ?? resource.id);
  return props.selectedIds.includes(id);
};

// Only allow selection for approvable resources in actionable states
const canSelectResource = (resource: App.Entities.Resource) => {
  const state = resource.pivot?.state;
  return resource.pivot?.approvable && ['created', 'reserved', 'lent'].includes(state ?? '');
};

const toggleSelection = (resource: App.Entities.Resource) => {
  const id = String(resource.pivot?.id ?? resource.id);
  const newSelection = isSelected(resource)
    ? props.selectedIds.filter(s => s !== id)
    : [...props.selectedIds, id];
  emit('update:selectedIds', newSelection);
};

const formatDate = (dateString?: string | null) => {
  if (!dateString) return '';
  return formatStaticTime(new Date(dateString), RESERVATION_DATE_TIME_FORMAT, locale.value);
};

const isOverdue = (resource: App.Entities.Resource) => {
  if (!resource.pivot?.end_time) return false;
  if (!['lent'].includes(resource.pivot?.state ?? '')) return false;
  return new Date(resource.pivot.end_time) < new Date();
};

const getCardClasses = (resource: App.Entities.Resource) => {
  const state = resource.pivot?.state;
  // More subtle styling - white/neutral background with just the left border for color
  switch (state) {
    case 'returned':
      return 'bg-background border-zinc-200 opacity-60 dark:border-zinc-700';
    case 'rejected':
    case 'cancelled':
      return 'bg-background border-zinc-200 opacity-50 dark:border-zinc-700';
    default:
      return 'bg-background border-zinc-200 dark:border-zinc-700 hover:shadow-sm';
  }
};

const getStateStripColor = (state?: string) => {
  switch (state) {
    case 'created': return 'bg-amber-400 dark:bg-amber-500';
    case 'reserved': return 'bg-blue-400 dark:bg-blue-500';
    case 'lent': return 'bg-emerald-400 dark:bg-emerald-500';
    case 'returned': return 'bg-zinc-400 dark:bg-zinc-500';
    case 'rejected':
    case 'cancelled': return 'bg-red-400 dark:bg-red-500';
    default: return 'bg-zinc-300 dark:bg-zinc-600';
  }
};

const getDateWarningClass = (resource: App.Entities.Resource, type: 'start' | 'end') => {
  const state = resource.pivot?.state;
  
  // Show start date warning for reserved state (awaiting pickup)
  if (type === 'start' && state === 'reserved') {
    const startDate = new Date(resource.pivot?.start_time ?? '');
    if (startDate < new Date()) {
      return 'font-semibold text-amber-600 dark:text-amber-400';
    }
  }
  
  // Show end date warning for lent state (overdue)
  if (type === 'end' && state === 'lent') {
    const endDate = new Date(resource.pivot?.end_time ?? '');
    if (endDate < new Date()) {
      return 'font-semibold text-red-600 dark:text-red-400';
    }
  }
  
  return '';
};
</script>
