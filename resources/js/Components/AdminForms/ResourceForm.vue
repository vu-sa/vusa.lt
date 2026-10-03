<template>
  <FormPage
    :title="isEditing ? resourceTitle : $t('Naujas išteklius')"
    :bar-title="isEditing ? resourceTitle : undefined"
    :head-title="isEditing ? resourceTitle : $t('Naujas išteklius')"
    :lead="isEditing ? undefined : $t('Pridėk ištekliaus pavadinimą, vietą ir kiekį — nuotraukas ir aprašymą galėsi papildyti vėliau.')"
    :entity-type="ModelEnum.RESOURCE"
    :back-href="route('resources.index')"
    :back-label="capitalize($tChoice('entities.resource.model', 2))"
    :processing="form.processing"
    :dirty="form.isDirty"
    :errors="form.errors"
    :field-ids
    :mode="isEditing ? 'edit' : 'create'"
    :locale="activeLocale"
    :available-locales="['lt', 'en']"
    :missing-locale-counts
    :activity-subject="props.resource.id ? { type: 'resource', id: props.resource.id } : undefined"
    :created-at="'created_at' in props.resource ? props.resource.created_at : undefined"
    :updated-at="'updated_at' in props.resource ? props.resource.updated_at : undefined"
    @update:locale="activeLocale = $event"
    @submit="emit('submit:form', form)"
  >
    <FormFieldWrapper
      id="resource-name"
      :label="`${$t('forms.fields.title')} (${activeLocale.toUpperCase()})`"
      required
      :error="form.errors[`name.${activeLocale}`]"
    >
      <Input
        id="resource-name"
        v-model="form.name[activeLocale]"
        :placeholder="RESOURCE_PLACEHOLDERS.title[activeLocale]"
        :class="['h-11', fieldSurfaceClass]"
      />
    </FormFieldWrapper>

    <FormFieldWrapper
      id="resource-description"
      :label="`${$t('forms.fields.description')} (${activeLocale.toUpperCase()})`"
      required
      :error="form.errors[`description.${activeLocale}`]"
    >
      <Textarea
        id="resource-description"
        v-model="form.description[activeLocale]"
        rows="4"
        :placeholder="RESOURCE_PLACEHOLDERS.description[activeLocale]"
        :class="fieldSurfaceClass"
      />
    </FormFieldWrapper>

    <FormFieldWrapper
      id="resource-media"
      :label="`${$t('reservations.resource.photos')} (${$t('neprivaloma')})`"
    >
      <ImageUpload
        v-model:files="mediaFiles"
        :max="10"
        mode="deferred"
        folder="resources"
        accept="image/jpg,image/jpeg,image/png,image/webp"
        :existing-urls="existingMediaItems"
        @remove:existing="handleRemoveExistingMedia"
      />
    </FormFieldWrapper>

    <!-- Read-only here: the reservations belong to the resource page, which has the full history. -->
    <FormFieldWrapper
      v-if="isEditing && reservations"
      id="resource-reservations"
      :label="$t('reservations.resource.recent')"
    >
      <p v-if="reservations.length === 0" class="text-sm text-muted-foreground">
        {{ $t('reservations.resource.recent_empty') }}
      </p>
      <ul v-else class="divide-y divide-border border-y border-border" data-testid="resource-recent-reservations">
        <ResourceBookingRow v-for="booking in reservations" :key="booking.id" :booking />
      </ul>
      <Link
        v-if="props.resource.id"
        :href="route('resources.show', props.resource.id)"
        class="inline-flex items-center gap-1 text-sm font-semibold text-brand hover:text-foreground"
      >
        {{ $t('reservations.resource.full_history') }}
        <ArrowRight class="size-4" aria-hidden="true" />
      </Link>
    </FormFieldWrapper>

    <template #aside>
      <FormPanel :title="$t('Priskyrimas ir nustatymai')" :icon="Boxes" title-class="text-brand">
        <TenantSelectField
          id="resource-tenant"
          v-model="form.tenant_id"
          :tenants="assignableTenants"
          :error="form.errors.tenant_id"
        />

        <FormFieldWrapper
          id="resource-category"
          :label="`${$t('Kategorija')} (${$t('neprivaloma')})`"
          :error="form.errors.resource_category_id"
        >
          <Select v-model="categoryIdString">
            <SelectTrigger id="resource-category">
              <SelectValue :placeholder="$t('Kategorija')" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="__none__">
                {{ $t('Be kategorijos') }}
              </SelectItem>
              <SelectItem v-for="category in categoriesOptions" :key="category.value" :value="String(category.value)">
                <span class="inline-flex items-center gap-2">
                  <!-- The icon name is stored as data, so it can only be resolved at runtime. -->
                  <Icon v-if="category.icon" :icon="`fluent:${category.icon}`" />
                  {{ category.label }}
                </span>
              </SelectItem>
            </SelectContent>
          </Select>
        </FormFieldWrapper>

        <div class="flex items-start gap-2.5 pt-1">
          <Checkbox
            id="resource-reservable"
            class="mt-0.5"
            :model-value="Boolean(form.is_reservable)"
            @update:model-value="form.is_reservable = $event === true"
          />
          <div class="space-y-0.5">
            <Label for="resource-reservable" class="cursor-pointer text-sm font-normal">
              {{ capitalize($t('entities.reservation.is_reservable')) }}
            </Label>
            <p class="text-xs text-muted-foreground">
              {{ $t('Nerezervuojamo ištekliaus kiti negali užsisakyti.') }}
            </p>
            <p v-if="form.errors.is_reservable" class="text-xs text-destructive">
              {{ form.errors.is_reservable }}
            </p>
          </div>
        </div>
      </FormPanel>

      <FormPanel :title="$t('Vieta ir kiekis')" :icon="MapPin" title-class="text-brand">
        <FormFieldWrapper
          id="resource-location"
          :label="$t('forms.fields.location')"
          required
          :error="form.errors.location"
        >
          <Input id="resource-location" v-model="form.location" placeholder="Naugarduko g. X (VU P), 010 kab." />
        </FormFieldWrapper>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <FormFieldWrapper
            id="capacity"
            :label="$t('forms.fields.quantity')"
            required
            :error="form.errors.capacity"
          >
            <NumberField id="capacity" v-model="form.capacity" :min="1" />
          </FormFieldWrapper>

          <FormFieldWrapper
            id="resource-identifier"
            :label="`${$t('Identifikacinis kodas')} (${$t('neprivaloma')})`"
            :error="form.errors.identifier"
          >
            <Input id="resource-identifier" v-model="form.identifier" placeholder="PRJ-CB-01-K" />
          </FormFieldWrapper>
        </div>
      </FormPanel>
    </template>

    <template v-if="isEditing && enableDelete" #danger-zone>
      <div class="flex items-center justify-between gap-4">
        <div>
          <h4 class="text-sm font-semibold text-destructive">
            {{ $t('Ištrinti išteklių') }}
          </h4>
          <p class="text-xs text-muted-foreground">
            {{ $t('Išteklius bus perkeltas į šiukšlinę; esamos rezervacijos liks istorijoje.') }}
          </p>
        </div>
        <Button type="button" variant="destructive" size="sm" class="u-touch shrink-0" @click="deleteConfirmOpen = true">
          <Trash2 class="mr-1.5 size-4" />
          {{ $t('Ištrinti') }}
        </Button>
      </div>

      <ConfirmDialog
        v-model:open="deleteConfirmOpen"
        :title="$t('Ištrinti išteklių?')"
        :description="$t('Išteklius bus perkeltas į šiukšlinę; esamos rezervacijos liks istorijoje.')"
        :confirm-label="$t('Ištrinti')"
        destructive
        @confirm="emit('delete')"
      />
    </template>
  </FormPage>
</template>

<script setup lang="ts">
import { Icon } from '@iconify/vue';
import { Link, useForm } from '@inertiajs/vue3';
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import { ArrowRight, Boxes, MapPin, Trash2 } from 'lucide-vue-next';
import { capitalize, computed, ref, watch } from 'vue';

import FormFieldWrapper from './FormFieldWrapper.vue';
import TenantSelectField from './TenantSelectField.vue';

import FormPage from '@/Components/Layouts/FormPage.vue';
import { ConfirmDialog, FormPanel } from '@/Components/Patterns';
import ResourceBookingRow, { type ResourceBooking } from '@/Components/Reservations/ResourceBookingRow.vue';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { fieldSurfaceClass } from '@/Components/ui/control';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { NumberField } from '@/Components/ui/number-field';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Textarea } from '@/Components/ui/textarea';
import { ImageUpload } from '@/Components/ui/upload';
import { RESOURCE_PLACEHOLDERS } from '@/Constants/I18n/Placeholders';
import type { ResourceCreationTemplate, ResourceMediaEntry } from '@/Pages/Admin/Reservations/CreateResource.vue';
import type { ResourceEditType } from '@/Pages/Admin/Reservations/EditResource.vue';
import { ModelEnum } from '@/Types/enums';

const props = defineProps<{
  resource: ResourceCreationTemplate | ResourceEditType;
  categories: App.Entities.ResourceCategory[];
  assignableTenants: App.Entities.Tenant[];
  /** Create mode when set: keeps the draft across a failed submit. */
  rememberKey?: 'CreateResource';
  enableDelete?: boolean;
  /** Edit only: the newest few reservations, shaped as on the resource page. */
  reservations?: ResourceBooking[];
}>();

const emit = defineEmits<{
  (event: 'submit:form', form: unknown): void;
  (event: 'delete'): void;
}>();

const isEditing = computed(() => !props.rememberKey);
const activeLocale = ref<'lt' | 'en'>('lt');
const deleteConfirmOpen = ref(false);

const resourceTitle = computed(() => props.resource.name?.[activeLocale.value] || props.resource.name?.lt || '');

const categoriesOptions = computed(() => props.categories.map(category => ({
  value: category.id,
  label: category.name,
  icon: category.icon,
})));

const form = props.rememberKey ? useForm(props.rememberKey, props.resource) : useForm(props.resource);

// Error keys that are not the id of the field they belong to.
const fieldIds = {
  'name.lt': 'resource-name',
  'name.en': 'resource-name',
  'description.lt': 'resource-description',
  'description.en': 'resource-description',
  'tenant_id': 'resource-tenant',
  'resource_category_id': 'resource-category',
  'location': 'resource-location',
  'identifier': 'resource-identifier',
};

const missingLocaleCounts = computed(() => ({
  lt: (form.name.lt ? 0 : 1) + (form.description.lt ? 0 : 1),
  en: (form.name.en ? 0 : 1) + (form.description.en ? 0 : 1),
}));

const categoryIdString = computed({
  get: () => (form.resource_category_id != null ? String(form.resource_category_id) : '__none__'),
  set: (val: string) => {
    form.resource_category_id = val === '__none__' || !val ? null : Number(val);
  },
});

// Deferred upload files
const mediaFiles = ref<File[]>([]);

// Existing media (edit mode only) — shown as previews in ImageUpload
const existingMediaItems = computed(() => props.resource.media.filter((item): item is Extract<ResourceMediaEntry, { id: string | number }> => 'id' in item));

function handleRemoveExistingMedia(item: { id: string | number }) {
  form.media = form.media.filter(image => !('id' in image && image.id === item.id));
}

// Sync newly added files into the form, keeping already-retained existing media intact
watch(mediaFiles, (files) => {
  const retained = form.media.filter(image => !('file' in image));
  form.media = [...retained, ...files.map(file => ({ file, status: 'pending' as const }))];
});
</script>
