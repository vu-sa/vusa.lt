<template>
  <div class="flex flex-col gap-5">
    <RCSectionOptions v-model="options" />

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
      <Field>
        <FieldLabel>{{ $t('rich-content.event_list_mode') }}</FieldLabel>
        <ToggleGroup v-model="options.mode" type="single" class="justify-start">
          <ToggleGroupItem value="upcoming">{{ $t('rich-content.event_list_mode_upcoming') }}</ToggleGroupItem>
          <ToggleGroupItem value="range">{{ $t('rich-content.event_list_mode_range') }}</ToggleGroupItem>
          <ToggleGroupItem value="year">{{ $t('rich-content.event_list_mode_year') }}</ToggleGroupItem>
        </ToggleGroup>
      </Field>
      <Field>
        <FieldLabel>{{ $t('rich-content.style') }}</FieldLabel>
        <ToggleGroup v-model="options.style" type="single" class="justify-start">
          <ToggleGroupItem value="cards">{{ $t('rich-content.event_list_style_cards') }}</ToggleGroupItem>
          <ToggleGroupItem value="list">{{ $t('rich-content.event_list_style_list') }}</ToggleGroupItem>
        </ToggleGroup>
      </Field>
    </div>

    <Field v-if="options.mode === 'year'">
      <FieldLabel>{{ $t('rich-content.year') }}</FieldLabel>
      <NumberField :model-value="options.year ?? currentYear" :min="2000" :max="currentYear + 2"
        @update:model-value="options.year = $event" />
    </Field>

    <div v-else-if="options.mode === 'range'" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
      <Field>
        <FieldLabel>{{ $t('rich-content.date_from') }}</FieldLabel>
        <Input v-model="options.dateFrom" type="date" />
      </Field>
      <Field>
        <FieldLabel>{{ $t('rich-content.date_to') }}</FieldLabel>
        <Input v-model="options.dateTo" type="date" />
      </Field>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
      <Field>
        <FieldLabel>{{ $t('rich-content.category_alias') }}</FieldLabel>
        <Input v-model="options.categoryAlias" type="text" placeholder="freshmen-camps" />
      </Field>
      <Field>
        <FieldLabel>{{ $t('rich-content.limit') }}</FieldLabel>
        <NumberField :model-value="options.limit ?? 12" :min="1" :max="24"
          @update:model-value="options.limit = $event" />
      </Field>
      <Field>
        <FieldLabel>{{ $t('rich-content.empty_message') }}</FieldLabel>
        <Input v-model="options.emptyMessage" type="text" placeholder="No events found..." />
      </Field>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
      <Field>
        <FieldLabel>{{ $t('rich-content.tenant_scope') }}</FieldLabel>
        <ToggleGroup :model-value="tenantScopeToggle" type="single" class="justify-start" @update:model-value="onTenantScopeChange">
          <ToggleGroupItem value="current">{{ $t('rich-content.tenant_scope_current') }}</ToggleGroupItem>
          <ToggleGroupItem value="all">{{ $t('rich-content.tenant_scope_all') }}</ToggleGroupItem>
        </ToggleGroup>
      </Field>
      <Field>
        <FieldLabel>{{ $t('rich-content.group_by') }}</FieldLabel>
        <ToggleGroup v-model="options.groupBy" type="single" class="justify-start">
          <ToggleGroupItem value="none">{{ $t('rich-content.group_by_none') }}</ToggleGroupItem>
          <ToggleGroupItem value="tenant">{{ $t('rich-content.group_by_tenant') }}</ToggleGroupItem>
        </ToggleGroup>
      </Field>
    </div>

    <template v-if="options.groupBy === 'tenant'">
      <Field>
        <FieldLabel>{{ $t('rich-content.tenant_label_style') }}</FieldLabel>
        <ToggleGroup :model-value="options.tenantLabelStyle ?? 'full'" type="single" class="justify-start"
          @update:model-value="(value) => { if (value) options.tenantLabelStyle = value as 'full' | 'faculty'; }">
          <ToggleGroupItem value="full">{{ $t('rich-content.tenant_label_style_full') }}</ToggleGroupItem>
          <ToggleGroupItem value="faculty">{{ $t('rich-content.tenant_label_style_faculty') }}</ToggleGroupItem>
        </ToggleGroup>
        <p class="text-xs text-zinc-500 dark:text-zinc-400">
          {{ $t('rich-content.tenant_label_style_hint') }}
        </p>
      </Field>

      <Field v-if="(options.tenantLabelStyle ?? 'full') === 'full'">
        <FieldLabel>{{ $t('rich-content.tenant_label_prefix') }}</FieldLabel>
        <Input v-model="options.tenantLabelPrefix" type="text" placeholder="VU " />
      </Field>
    </template>
  </div>
</template>

<script setup lang="ts">
/**
 * `event-list` editor. Entirely option-driven — there is no author-written
 * `json_content` (see `EventListResolver`, modeled on
 * `PublicPageController::summerCamps()`).
 */
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import type { EventList } from '@/Types/contentParts';
import RCSectionOptions from '../Editor/RCSectionOptions.vue';
import { Field, FieldLabel } from '@/Components/ui/field';
import { Input } from '@/Components/ui/input';
import { NumberField } from '@/Components/ui/number-field';
import { ToggleGroup, ToggleGroupItem } from '@/Components/ui/toggle-group';

defineModel<EventList['json_content']>({ required: true });
const options = defineModel<EventList['options']>('options', { required: true });

const currentYear = new Date().getFullYear();

const tenantScopeToggle = computed(() => (options.value.tenantScope === 'all' ? 'all' : 'current'));

function onTenantScopeChange(value: unknown) {
  options.value.tenantScope = value === 'all' ? 'all' : 'current';
}
</script>
