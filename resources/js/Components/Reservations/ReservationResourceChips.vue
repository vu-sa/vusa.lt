<template>
  <TooltipProvider>
    <div class="flex flex-wrap items-center gap-1" data-slot="reservation-resource-chips">
      <template v-for="resource in shown" :key="resource.pivot.id">
        <!-- Foreign items stay visible — they explain why an approval covers only part of a row —
             but are muted so it is obvious they are not this administrator's to act on. -->
        <Tooltip v-if="isForeign(resource)">
          <TooltipTrigger as-child>
            <Badge variant="outline" size="tiny" class="cursor-help text-muted-foreground opacity-60">
              <ResourceLabel :item="resource" />
            </Badge>
          </TooltipTrigger>
          <TooltipContent>
            {{ $t('reservations.dashboard.not_yours', { tenant: resource.tenant?.shortname ?? '—' }) }}
          </TooltipContent>
        </Tooltip>
        <Badge v-else variant="secondary" size="tiny">
          <ResourceLabel :item="resource" />
        </Badge>
      </template>

      <Tooltip v-if="hidden.length > 0">
        <TooltipTrigger as-child>
          <Badge variant="outline" size="tiny" class="cursor-help text-muted-foreground">
            +{{ hidden.length }}
          </Badge>
        </TooltipTrigger>
        <TooltipContent class="max-w-xs">
          <ul class="flex flex-col gap-0.5">
            <li v-for="resource in hidden" :key="resource.pivot.id">
              {{ resource.name }}{{ resource.pivot.quantity > 1 ? ` ×${resource.pivot.quantity}` : '' }}
            </li>
          </ul>
        </TooltipContent>
      </Tooltip>
    </div>
  </TooltipProvider>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { computed, defineComponent, h, type PropType } from 'vue';

import { Badge } from '@/Components/ui/badge';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/Components/ui/tooltip';
import type { ReservationResource } from '@/Utils/ReservationStatus';

const props = withDefaults(defineProps<{
  resources: ReservationResource[];
  /** In the administrator's view, items of other tenants are muted; the requester sees everything as theirs. */
  mutedForeign?: boolean;
  /** Beyond this the chips roll up into a "+N" with the rest on hover. */
  max?: number;
}>(), {
  max: 4,
});

const shown = computed(() => props.resources.slice(0, props.max));
const hidden = computed(() => props.resources.slice(props.max));

const isForeign = (resource: ReservationResource) => props.mutedForeign && !resource.pivot.approvable;

/** Name plus a quantity when more than one is reserved; kept inline so it reads as one chip. */
const ResourceLabel = defineComponent({
  props: { item: { type: Object as PropType<ReservationResource>, required: true } },
  setup: props => () => h('span', { class: 'flex items-center gap-1' }, [
    h('span', { class: 'max-w-[110px] truncate' }, props.item.name),
    props.item.pivot.quantity > 1
      ? h('span', { class: 'text-muted-foreground' }, `×${props.item.pivot.quantity}`)
      : null,
  ]),
});
</script>
