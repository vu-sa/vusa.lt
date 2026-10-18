<template>
  <DropdownMenu>
    <DropdownMenuTrigger as-child>
      <Button size="sm" variant="outline" voice="sentence" class="pointer-coarse:h-11" data-tour="gantt-filter-trigger">
        <SlidersHorizontal aria-hidden="true" />
        {{ $t('Filtrai') }}
      </Button>
    </DropdownMenuTrigger>
    <DropdownMenuContent align="end" class="w-64">
      <DropdownMenuLabel>{{ $t('Rodymo nustatymai') }}</DropdownMenuLabel>
      <DropdownMenuSeparator />

      <DropdownMenuCheckboxItem
        :model-value="showOnlyWithActivity"
        @update:model-value="(val: boolean) => $emit('update:showOnlyWithActivity', val)"
        @select.prevent
      >
        {{ $t('Rodyti tik aktyvius') }}
      </DropdownMenuCheckboxItem>
      <DropdownMenuCheckboxItem
        :model-value="showOnlyWithPublicMeetings"
        @update:model-value="(val: boolean) => $emit('update:showOnlyWithPublicMeetings', val)"
        @select.prevent
      >
        {{ $t('Rodyti tik viešas institucijas') }}
      </DropdownMenuCheckboxItem>
      <!-- Off by default: everything VU SA sits in is drawn unless the user narrows it. -->
      <DropdownMenuCheckboxItem
        :model-value="hideInternalInstitutions"
        @update:model-value="(val: boolean) => $emit('update:hideInternalInstitutions', val)"
        @select.prevent
      >
        {{ $t('Slėpti VU SA darinius') }}
      </DropdownMenuCheckboxItem>

      <DropdownMenuSeparator />

      <DropdownMenuCheckboxItem
        :model-value="showDutyMembers"
        @update:model-value="(val: boolean) => $emit('update:showDutyMembers', val)"
        @select.prevent
      >
        {{ $t('Rodyti narius') }}
      </DropdownMenuCheckboxItem>

      <DropdownMenuCheckboxItem
        v-if="showDutyMembers && showActivityStatusOption"
        :model-value="showActivityStatus"
        @update:model-value="(val: boolean) => $emit('update:showActivityStatus', val)"
        @select.prevent
      >
        {{ $t('Rodyti aktyvumo žymėjimą') }}
      </DropdownMenuCheckboxItem>

      <DropdownMenuCheckboxItem
        v-if="showActivityRequestsOption"
        :model-value="showActivityRequests"
        data-slot="gantt-activity-requests-toggle"
        @update:model-value="(val: boolean) => $emit('update:showActivityRequests', val)"
        @select.prevent
      >
        {{ $t('activity_requests.gantt.show') }}
      </DropdownMenuCheckboxItem>

      <DropdownMenuCheckboxItem
        v-if="hasRelatedInstitutions"
        :model-value="showRelatedInstitutions"
        @update:model-value="(val: boolean) => $emit('update:showRelatedInstitutions', val)"
        @select.prevent
      >
        <span class="flex items-center gap-1.5">
          <Link2 class="size-4 text-muted-foreground" aria-hidden="true" />
          {{ $t('Rodyti susijusias institucijas') }}
        </span>
      </DropdownMenuCheckboxItem>

      <template v-if="showReset">
        <DropdownMenuSeparator />
        <div class="p-1">
          <Button size="sm" variant="ghost" voice="sentence" class="w-full justify-start text-muted-foreground" @click="$emit('reset')">
            <RotateCcw class="mr-2 size-4" />
            {{ $t('Atstatyti filtrus') }}
          </Button>
        </div>
      </template>
    </DropdownMenuContent>
  </DropdownMenu>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { Link2, RotateCcw, SlidersHorizontal } from '@lucide/vue';

import { Button } from '@/Components/ui/button';
import {
  DropdownMenu,
  DropdownMenuCheckboxItem,
  DropdownMenuContent,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';

/** What the meetings timeline shows. Padaliniai and grouping live in their own controls. */
withDefaults(defineProps<{
  showOnlyWithActivity: boolean;
  showOnlyWithPublicMeetings: boolean;
  hideInternalInstitutions?: boolean;
  showDutyMembers: boolean;
  showActivityStatus?: boolean;
  showActivityStatusOption?: boolean;
  showActivityRequests?: boolean;
  /** Only for people who may see activity requests */
  showActivityRequestsOption?: boolean;
  showRelatedInstitutions?: boolean;
  hasRelatedInstitutions?: boolean;
  showReset?: boolean;
}>(), {
  showReset: true,
  showRelatedInstitutions: true,
  hasRelatedInstitutions: false,
  showActivityStatus: false,
  showActivityStatusOption: false,
  showActivityRequests: true,
  showActivityRequestsOption: false,
  hideInternalInstitutions: false,
});

defineEmits<{
  'update:showOnlyWithActivity': [value: boolean];
  'update:showOnlyWithPublicMeetings': [value: boolean];
  'update:hideInternalInstitutions': [value: boolean];
  'update:showDutyMembers': [value: boolean];
  'update:showActivityStatus': [value: boolean];
  'update:showActivityRequests': [value: boolean];
  'update:showRelatedInstitutions': [value: boolean];
  'reset': [];
}>();
</script>
