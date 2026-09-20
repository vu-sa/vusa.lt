<template>
  <FormPage
    :title="isEditing ? resourceTitle : $t('Naujas išteklius')"
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
    @update:locale="activeLocale = $event"
    @submit="emit('submit:form', form)"
  >
    <FormSection
      :title="$t('Kas tai?')"
      :description="$t('Pavadinimas ir aprašymas, pagal kuriuos kiti ras išteklių.')"
    >
      <div class="space-y-1.5">
        <Label for="resource-name" class="text-sm font-medium">
          {{ $t('forms.fields.title') }} ({{ activeLocale.toUpperCase() }}) *
        </Label>
        <Input id="resource-name" v-model="form.name[activeLocale]" :placeholder="RESOURCE_PLACEHOLDERS.title[activeLocale]" />
        <p v-if="form.errors[`name.${activeLocale}`]" class="text-xs text-destructive">
          {{ form.errors[`name.${activeLocale}`] }}
        </p>
      </div>

      <div class="space-y-1.5">
        <Label for="resource-description" class="text-sm font-medium">
          {{ $t('forms.fields.description') }} ({{ activeLocale.toUpperCase() }}) *
        </Label>
        <Textarea
          id="resource-description"
          v-model="form.description[activeLocale]"
          rows="4"
          :placeholder="RESOURCE_PLACEHOLDERS.description[activeLocale]"
        />
        <p v-if="form.errors[`description.${activeLocale}`]" class="text-xs text-destructive">
          {{ form.errors[`description.${activeLocale}`] }}
        </p>
      </div>

      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div class="space-y-1.5">
          <Label for="resource-tenant" class="text-sm font-medium">
            {{ capitalize($tChoice('entities.tenant.model', 1)) }} *
          </Label>
          <Select v-model="tenantIdString">
            <SelectTrigger id="resource-tenant">
              <SelectValue placeholder="VU SA X" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="tenant in assignableTenants" :key="tenant.id" :value="String(tenant.id)">
                {{ tenant.shortname }}
              </SelectItem>
            </SelectContent>
          </Select>
          <p v-if="form.errors.tenant_id" class="text-xs text-destructive">
            {{ form.errors.tenant_id }}
          </p>
        </div>

        <div class="space-y-1.5">
          <div class="flex items-center justify-between">
            <Label for="resource-category" class="text-sm font-medium">{{ $t('Kategorija') }}</Label>
            <span class="text-xs text-muted-foreground">{{ $t('(neprivaloma)') }}</span>
          </div>
          <Select v-model="categoryIdString">
            <SelectTrigger id="resource-category">
              <SelectValue :placeholder="$t('Kategorija')" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="category in categoriesOptions" :key="category.value" :value="String(category.value)">
                <span class="inline-flex items-center gap-2">
                  <!-- The icon name is stored as data, so it can only be resolved at runtime. -->
                  <Icon v-if="category.icon" :icon="`fluent:${category.icon}`" />
                  {{ category.label }}
                </span>
              </SelectItem>
            </SelectContent>
          </Select>
          <p v-if="form.errors.resource_category_id" class="text-xs text-destructive">
            {{ form.errors.resource_category_id }}
          </p>
        </div>
      </div>
    </FormSection>

    <FormSection
      :title="$t('Kur jis ir kiek jo yra?')"
      :description="$t('Pagal tai sistema tikrina, ar užtenka išteklių pasirinktam laikui.')"
    >
      <div class="space-y-1.5">
        <Label for="resource-location" class="text-sm font-medium">{{ $t('forms.fields.location') }} *</Label>
        <Input id="resource-location" v-model="form.location" placeholder="Naugarduko g. X (VU P), 010 kab." />
        <p v-if="form.errors.location" class="text-xs text-destructive">
          {{ form.errors.location }}
        </p>
      </div>

      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div class="space-y-1.5">
          <Label for="capacity" class="text-sm font-medium">{{ $t('forms.fields.quantity') }} *</Label>
          <NumberField id="capacity" v-model="form.capacity" :min="1" />
          <p v-if="form.errors.capacity" class="text-xs text-destructive">
            {{ form.errors.capacity }}
          </p>
        </div>

        <div class="space-y-1.5">
          <Label for="resource-identifier" class="text-sm font-medium">{{ $t('Identifikacinis kodas') }}</Label>
          <Input id="resource-identifier" v-model="form.identifier" placeholder="PRJ-CB-01-K" />
          <p class="text-xs text-muted-foreground">
            {{ $t('(neprivaloma)') }}
          </p>
          <p v-if="form.errors.identifier" class="text-xs text-destructive">
            {{ form.errors.identifier }}
          </p>
        </div>
      </div>

      <div class="flex items-start gap-2.5">
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
    </FormSection>

    <FormSection :title="$t('forms.fields.media')" :description="$t('Nuotraukos padeda kitiems atpažinti išteklių.')">
      <MdSuspenseWrapper directory="resources" :locale="$page.props.app.locale" file="description" />
      <ImageUpload
        v-model:files="mediaFiles"
        :max="10"
        mode="deferred"
        folder="resources"
        accept="image/jpg,image/jpeg,image/png,image/webp"
        :existing-urls="existingMediaItems"
        @remove:existing="handleRemoveExistingMedia"
      />
    </FormSection>

    <FormSection
      v-if="isEditing && reservations"
      :title="$t('Rezervacijų istorija')"
      :description="$t('Kas ir kada šį išteklių rezervavo.')"
    >
      <ResourceReservationsTable :reservations />
    </FormSection>

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
import { capitalize, computed, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import { Trash2 } from 'lucide-vue-next';

import FormPage from '@/Components/Layouts/FormPage.vue';
import ConfirmDialog from '@/Components/Patterns/ConfirmDialog.vue';
import FormSection from '@/Components/Patterns/FormSection.vue';
import ResourceReservationsTable from '@/Components/Tables/ResourceReservationsTable.vue';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { NumberField } from '@/Components/ui/number-field';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Textarea } from '@/Components/ui/textarea';
import { ImageUpload } from '@/Components/ui/upload';
import { RESOURCE_PLACEHOLDERS } from '@/Constants/I18n/Placeholders';
import MdSuspenseWrapper from '@/Features/MarkdownGetterFromDocs/MdSuspenseWrapper.vue';
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
  /** Edit only: every reservation this resource is on, newest first. */
  reservations?: App.Entities.Reservation[];
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

// Shadcn Select requires string values
const tenantIdString = computed({
  get: () => (form.tenant_id != null ? String(form.tenant_id) : ''),
  set: (val: string) => { form.tenant_id = val ? Number(val) : null; },
});

const categoryIdString = computed({
  get: () => (form.resource_category_id != null ? String(form.resource_category_id) : ''),
  set: (val: string) => { form.resource_category_id = val ? Number(val) : null; },
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
