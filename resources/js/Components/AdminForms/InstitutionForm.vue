<template>
  <FormPage
    :title="isEditing ? institutionTitle : $t('Nauja institucija')"
    :bar-title
    :head-title="isEditing ? institutionTitle : $t('Nauja institucija')"
    :lead="isEditing ? undefined : $t('Sukurk institucijos įrašą; pareigybes, kadencijas ir sekretorius pridėsi jos puslapyje.')"
    :entity-type="ModelEnum.INSTITUTION"
    :back-href="isEditing ? route('institutions.show', institution.id) : route('institutions.index')"
    :back-label="isEditing ? $t('Į instituciją') : $t('Institucijos')"
    :processing="form.processing"
    :dirty="form.isDirty"
    :errors="form.errors"
    :field-ids
    :mode="isEditing ? 'edit' : 'create'"
    :locale="activeLocale"
    :available-locales="['lt', 'en']"
    :missing-locale-counts
    :created-at="isEditing ? (source.created_at as string | undefined) : undefined"
    :updated-at="isEditing ? (source.updated_at as string | undefined) : undefined"
    :activity-subject="isEditing && institution?.id ? { type: 'institution', id: institution.id } : undefined"
    @update:locale="activeLocale = $event"
    @submit="emit('submit:form', form)"
  >
    <template v-if="isEditing" #title-status>
      <StatusBadge :status="institutionStatus" />
    </template>

    <FormSection
      :title="$t('Kas tai?')"
      :description="$t('Institucija gali būti bet koks VU SA arba VU organas: padalinys, darbo grupė, studijų programos komitetas ir pan.')"
    >
      <FormFieldWrapper
        id="institution-name"
        :label="`${$t('Pavadinimas')} (${activeLocale.toUpperCase()})`"
        required
        :error="form.errors[`name.${activeLocale}`]"
      >
        <Input id="institution-name" v-model="form.name[activeLocale]" :class="['h-11', fieldSurfaceClass]" />
      </FormFieldWrapper>

      <FormFieldWrapper
        id="institution-short-name"
        :label="`${$t('forms.fields.short_name')} (${activeLocale.toUpperCase()}) (${$t('neprivaloma')})`"
        :hint="$t('Trumpas pavadinimas rodomas, kai vietos mažai.')"
        :error="form.errors[`short_name.${activeLocale}`]"
      >
        <Input id="institution-short-name" v-model="form.short_name[activeLocale]" :class="['h-11', fieldSurfaceClass]" />
      </FormFieldWrapper>

      <FormFieldWrapper
        id="institution-description"
        :label="`${$t('Aprašymas')} (${activeLocale.toUpperCase()})`"
        :error="form.errors[`description.${activeLocale}`]"
      >
        <TiptapEditor :key="activeLocale" v-model="form.description[activeLocale]" tools="description" html />
      </FormFieldWrapper>
    </FormSection>

    <FormSection
      v-if="showContactFields"
      :title="$t('Kaip su ja susisiekti?')"
      :description="$t('Nuotrauka, logotipas ir kontaktai rodomi institucijos puslapyje vusa.lt.')"
      :badge="$t('Matoma vusa.lt')"
      public-marker
    >
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <FormFieldWrapper id="institution-image" :label="$t('Nuotrauka')">
          <ImageUpload
            v-model:url="form.image_url"
            v-model:focal-point-value="form.image_focal_point"
            mode="immediate"
            cropper
            compress
            focal-point
            folder="institutions"
          />
        </FormFieldWrapper>

        <FormFieldWrapper id="institution-logo" :label="$t('Logotipas')">
          <ImageUpload v-model:url="form.logo_url" mode="immediate" cropper compress folder="institutions" />
        </FormFieldWrapper>
      </div>

      <FormFieldWrapper
        id="institution-address"
        :label="`${$t('Adresas')} (${activeLocale.toUpperCase()})`"
      >
        <Input id="institution-address" v-model="form.address[activeLocale]" :class="['h-11', fieldSurfaceClass]" />
      </FormFieldWrapper>

      <FormFieldWrapper
        id="institution-hours"
        :label="`${$t('Darbo laikas')} (${activeLocale.toUpperCase()})`"
      >
        <Textarea id="institution-hours" v-model="form.working_hours[activeLocale]" rows="3" :class="fieldSurfaceClass" />
      </FormFieldWrapper>

      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <FormFieldWrapper id="institution-email" :label="$t('El. paštas')" :error="form.errors.email">
          <Input id="institution-email" v-model="form.email" type="email" placeholder="info@vusa.lt" :class="['h-11', fieldSurfaceClass]" />
        </FormFieldWrapper>

        <FormFieldWrapper id="institution-phone" :label="$t('Telefonas')">
          <Input id="institution-phone" v-model="form.phone" type="tel" placeholder="+370…" :class="['h-11', fieldSurfaceClass]" />
        </FormFieldWrapper>

        <FormFieldWrapper id="institution-website" :label="$t('Svetainė')" :error="form.errors.website">
          <Input id="institution-website" v-model="form.website" type="url" placeholder="https://…" :class="['h-11', fieldSurfaceClass]" />
        </FormFieldWrapper>

        <FormFieldWrapper id="institution-facebook" :label="$t('Facebook')">
          <Input id="institution-facebook" v-model="form.facebook_url" type="url" placeholder="facebook.com/…" :class="['h-11', fieldSurfaceClass]" />
        </FormFieldWrapper>

        <FormFieldWrapper id="institution-instagram" :label="$t('Instagram')">
          <Input id="institution-instagram" v-model="form.instagram_url" type="url" placeholder="instagram.com/…" :class="['h-11', fieldSurfaceClass]" />
        </FormFieldWrapper>
      </div>
    </FormSection>

    <template #aside>
      <FormPanel :title="$t('Būsena ir padalinys')" :icon="Building2" title-class="text-brand">
        <FormToggleRow
          v-model="isActive"
          :label="$t('Aktyvi institucija')"
          :hint="$t('Neaktyvios institucijos nebelaukiamos posėdžių ir nerodomos viešame sąraše.')"
        />

        <FormFieldWrapper id="institution-tenant" :label="$t('Padalinys')" required :error="form.errors.tenant_id">
          <Select v-model="tenantIdString">
            <SelectTrigger id="institution-tenant">
              <SelectValue :placeholder="$t('Pasirinkite padalinį')" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="tenant in assignableTenants" :key="tenant.id" :value="String(tenant.id)">
                {{ tenant.shortname }}
              </SelectItem>
            </SelectContent>
          </Select>
        </FormFieldWrapper>
      </FormPanel>

      <FormPanel :title="$t('Tipas ir valdymas')" :icon="Layers" title-class="text-brand">
        <FormFieldWrapper id="institution-types" :label="$t('Institucijos tipas')" :error="form.errors.types">
          <MultiSelect
            id="institution-types"
            v-model="selectedTypes"
            :options="institutionTypeOptions"
            :placeholder="$t('Pasirinkite tipus')"
          />
          <div v-if="resolvedScope" class="flex flex-wrap items-center gap-2 pt-1">
            <InstitutionScopeBadge :scope="resolvedScope" />
            <span class="text-xs text-muted-foreground">
              {{ showContactFields
                ? $t('forms.helpers.governance_scope_internal_fields')
                : $t('forms.helpers.governance_scope_external_fields') }}
            </span>
          </div>
        </FormFieldWrapper>
      </FormPanel>
    </template>

    <template #advanced>
      <FormFieldWrapper id="institution-alias" :label="$t('Techninė žymė')" :hint="$t('Unikali žymė naudojama URL adresuose.')" :error="form.errors.alias">
        <Input id="institution-alias" v-model="form.alias" type="text" placeholder="vu-sa-mif" :class="fieldSurfaceClass" />
      </FormFieldWrapper>

      <FormFieldWrapper
        id="institution-periodicity"
        :label="$t('Susitikimų periodiškumas')"
        :hint="$t('Perrašo tipo nustatymą. Jei nenurodyta, naudojamas tipo arba numatytasis 30 dienų nustatymas.')"
      >
        <div class="flex items-center gap-2">
          <Input
            id="institution-periodicity"
            v-model.number="form.meeting_periodicity_days"
            type="number"
            :min="1"
            :max="365"
            placeholder="30"
            :class="['w-24', fieldSurfaceClass]"
          />
          <span class="text-sm text-muted-foreground">{{ $t('dienų') }}</span>
        </div>
      </FormFieldWrapper>
    </template>

    <template v-if="isEditing && enableDelete" #danger-zone>
      <Button
        type="button"
        variant="outline"
        class="border-destructive/40 text-destructive hover:border-destructive hover:bg-destructive hover:text-destructive-foreground pointer-coarse:min-h-11"
        @click="deleteConfirmOpen = true"
      >
        <Trash2 class="size-4" />
        {{ $t('Ištrinti instituciją') }}
      </Button>

      <ConfirmDialog
        v-model:open="deleteConfirmOpen"
        :title="$t('Ištrinti instituciją?')"
        :description="$t('Institucija bus perkelta į šiukšlinę; pareigybės ir posėdžiai liks.')"
        :confirm-label="$t('Ištrinti')"
        destructive
        @confirm="emit('delete')"
      />
    </template>
  </FormPage>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Building2, CircleCheck, CircleSlash, Layers, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import InstitutionScopeBadge from '@/Components/Institutions/InstitutionScopeBadge.vue';
import FormPage from '@/Components/Layouts/FormPage.vue';
import ConfirmDialog from '@/Components/Patterns/ConfirmDialog.vue';
import FormPanel from '@/Components/Patterns/FormPanel.vue';
import FormSection from '@/Components/Patterns/FormSection.vue';
import FormToggleRow from '@/Components/Patterns/FormToggleRow.vue';
import StatusBadge from '@/Components/Patterns/StatusBadge.vue';
import TiptapEditor from '@/Components/TipTap/TiptapEditor.vue';
import { Button } from '@/Components/ui/button';
import { fieldSurfaceClass } from '@/Components/ui/control';
import { Input } from '@/Components/ui/input';
import { MultiSelect } from '@/Components/ui/multi-select';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Textarea } from '@/Components/ui/textarea';
import { ImageUpload } from '@/Components/ui/upload';
import type { StatusPresentation } from '@/Constants/statuses';
import { InstitutionScope, ModelEnum } from '@/Types/enums';

interface Translated { lt: string; en: string }

const props = defineProps<{
  institution: App.Entities.Institution;
  institutionTypes: App.Entities.Type[];
  assignableTenants: Array<App.Entities.Tenant>;
  /** Create mode when set: keeps the draft across a failed submit. */
  rememberKey?: string;
  enableDelete?: boolean;
}>();

const emit = defineEmits<{
  (event: 'submit:form', form: unknown): void;
  (event: 'delete'): void;
}>();

const isEditing = computed(() => !props.rememberKey);
const activeLocale = ref<'lt' | 'en'>('lt');
const deleteConfirmOpen = ref(false);

// A never-set translatable arrives as null (or `[]` for an empty JSON object).
const asTranslated = (value: unknown): Translated => {
  if (typeof value === 'string') {
    return { lt: value, en: '' };
  }

  const text = (value && typeof value === 'object' && !Array.isArray(value) ? value : {}) as Partial<Translated>;

  return { lt: text.lt ?? '', en: text.en ?? '' };
};

const source = props.institution as unknown as Record<string, unknown>;

const initial = () => ({
  name: asTranslated(source.name),
  short_name: asTranslated(source.short_name),
  description: asTranslated(source.description),
  address: asTranslated(source.address),
  working_hours: asTranslated(source.working_hours),
  tenant_id: (source.tenant_id ?? null) as number | null,
  is_active: source.is_active === undefined ? 1 : (source.is_active ? 1 : 0),
  types: (Array.isArray(source.types) ? source.types : []) as number[],
  image_url: (source.image_url ?? null) as string | null,
  image_focal_point: (source.image_focal_point ?? null) as string | null,
  logo_url: (source.logo_url ?? null) as string | null,
  email: (source.email ?? '') as string,
  phone: (source.phone ?? '') as string,
  website: (source.website ?? '') as string,
  facebook_url: (source.facebook_url ?? '') as string,
  instagram_url: (source.instagram_url ?? '') as string,
  alias: (source.alias ?? '') as string,
  meeting_periodicity_days: (source.meeting_periodicity_days ?? null) as number | null,
});

const form = props.rememberKey ? useForm(props.rememberKey, initial()) : useForm(initial());

const institutionTitle = computed(() => form.name.lt || form.name.en || '');
const barTitle = computed(() => (isEditing.value ? (institutionTitle.value || $t('Institucija')) : $t('Nauja institucija')));

// Error keys that are not the id of the field they belong to.
const fieldIds = {
  'name.lt': 'institution-name',
  'name.en': 'institution-name',
  'short_name.lt': 'institution-short-name',
  'short_name.en': 'institution-short-name',
  'description.lt': 'institution-description',
  'description.en': 'institution-description',
  'tenant_id': 'institution-tenant',
  'types': 'institution-types',
  'address.lt': 'institution-address',
  'address.en': 'institution-address',
  'working_hours.lt': 'institution-hours',
  'working_hours.en': 'institution-hours',
  'alias': 'institution-alias',
  'email': 'institution-email',
  'website': 'institution-website',
  'phone': 'institution-phone',
};

const missingLocaleCounts = computed(() => ({
  lt: form.name.lt.trim() === '' ? 1 : 0,
  en: form.name.en.trim() === '' ? 1 : 0,
}));

// The database stores is_active as tinyint 0/1.
const isActive = computed({
  get: () => Boolean(form.is_active),
  set: (value: boolean) => {
    form.is_active = value ? 1 : 0;
  },
});

const institutionStatus = computed<StatusPresentation>(() => ({
  label: isActive.value ? $t('Aktyvi') : $t('Neaktyvi'),
  role: isActive.value ? 'success' : 'neutral',
  icon: isActive.value ? CircleCheck : CircleSlash,
}));

const tenantIdString = computed({
  get: () => (form.tenant_id ? String(form.tenant_id) : ''),
  set: (value: string) => {
    form.tenant_id = value ? parseInt(value) : null;
  },
});

const institutionTypeOptions = computed(() =>
  props.institutionTypes.map(type => ({ label: type.title, value: type.id })),
);

const selectedTypes = computed({
  get: () => form.types
    .map(id => institutionTypeOptions.value.find(option => option.value === id))
    .filter((option): option is { label: string; value: number } => Boolean(option)),
  set: (items: { label: string; value: number }[]) => {
    form.types = items.map(item => item.value);
  },
});

/**
 * Mirrors InstitutionScopeResolver: the nearest type in the parent chain that declares a
 * governance_scope wins. Resolved client-side because `institutionTypes` already carries the
 * whole tree, and the answer has to update as the user ticks types on and off.
 */
const resolveGovernanceScope = (typeId: number): string | null => {
  const seen = new Set<number>();
  let current = props.institutionTypes?.find(type => type.id === typeId);

  while (current && !seen.has(current.id)) {
    seen.add(current.id);
    const scope = current.extra_attributes?.governance_scope;

    if (scope) {
      return String(scope);
    }

    if (current.parent_id == null) {
      return null;
    }

    const parentId = current.parent_id;
    current = props.institutionTypes?.find(type => type.id === parentId);
  }

  return null;
};

/** Contact details, logos and addresses belong to bodies VU SA runs itself. */
const showContactFields = computed(() =>
  form.types.some(typeId => resolveGovernanceScope(typeId) === InstitutionScope.Vusa),
);

/** External wins when types disagree, exactly as InstitutionScopeResolver::forInstitution does. */
const resolvedScope = computed<string | null>(() => {
  const scopes = form.types
    .map(typeId => resolveGovernanceScope(typeId))
    .filter((scope): scope is string => scope !== null);

  if (scopes.length === 0) {
    return null;
  }

  return scopes.find(scope => scope !== InstitutionScope.Vusa) ?? InstitutionScope.Vusa;
});
</script>
