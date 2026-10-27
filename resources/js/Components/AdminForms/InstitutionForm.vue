<template>
  <FormPage
    :title="isEditing ? institutionTitle : $t('Nauja institucija')"
    :bar-title
    :head-title="barTitle"
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
            v-model:image="form.image_media"
            mode="media"
            cropper
            :compress="false"
            focal-point
          />
        </FormFieldWrapper>

        <FormFieldWrapper id="institution-logo" :label="$t('Logotipas')">
          <ImageUpload v-model:image="form.logo_media" mode="media" cropper :compress="false" />
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
        <ActivityStatusField
          id="institution-active"
          v-model="isActive"
          :error="form.errors.is_active"
          :hint="$t('forms.helpers.institution_active_hint')"
        />
        <Alert v-if="isEditing && institution.is_active && !isActive && deactivation" data-testid="institution-deactivation-warning">
          <AlertDescription class="space-y-2">
            <p>{{ $t('forms.helpers.institution_deactivation_consequence', { count: deactivation.endingCount, date: formatDate(deactivation.effectiveEndDate) }) }}</p>
            <p>{{ $t('forms.helpers.institution_reactivation_hint') }}</p>
            <template v-if="deactivation.blockers.length">
              <p>{{ $t('forms.helpers.institution_deactivation_blocked') }}</p>
              <ul class="list-disc pl-4">
                <li v-for="(blocker, index) in deactivation.blockers" :key="index">
                  {{ blocker.duty }} · {{ formatDate(blocker.startDate) }}
                </li>
              </ul>
              <Button as-child variant="link" size="sm">
                <Link :href="route('institutions.show', institution.id)">
                  {{ $t('forms.helpers.institution_resolve_terms') }}
                </Link>
              </Button>
            </template>
          </AlertDescription>
        </Alert>

        <TenantSelectField
          id="institution-tenant"
          v-model="form.tenant_id"
          :tenants="assignableTenants"
          required
          :error="form.errors.tenant_id"
        />
      </FormPanel>

      <FormPanel :title="$t('Tipas ir valdymas')" :icon="Layers" title-class="text-brand">
        <FormFieldWrapper id="institution-types" :label="$t('Institucijos tipas')" :error="form.errors.types">
          <TypeTreeMultiSelect
            id="institution-types"
            v-model="form.types"
            :types="institutionTypes"
            :placeholder="$t('Pasirink tipus')"
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
      <div v-if="isEditing && aliasBaseUrl" class="flex flex-col gap-2">
        <PermalinkField
          id="institution-alias"
          :permalink="form.alias"
          :base-url="aliasBaseUrl"
          :view-url="publicUrl"
          :disabled="aliasPinned"
          :explanation="$t('Padalinio ir centrinio biuro nuorodos nekeičiamos.')"
          :warning="aliasWarning"
          :invalid="Boolean(form.errors.alias)"
          @update:permalink="form.alias = $event"
        />
        <p v-if="form.errors.alias" class="text-xs text-destructive">
          {{ form.errors.alias }}
        </p>
        <PermalinkPreviewHint
          v-if="renamedWithUntouchedAlias"
          :preview="aliasPreview.preview.value"
          :is-checking="aliasPreview.isChecking.value"
        />
        <PublicUrlHistoryCard
          :urls="publicUrls"
          :destroy-route="(id) => route('institutions.publicUrls.destroy', [institution.id, id])"
        />
      </div>
      <FormFieldWrapper
        v-else
        id="institution-alias"
        :label="`${$t('Nuoroda')} (${$t('neprivaloma')})`"
        :hint="$t('Mažosios raidės, skaičiai ir brūkšneliai. Palik tuščią – sukursime iš pavadinimo.')"
        :error="form.errors.alias"
      >
        <Input id="institution-alias" v-model="form.alias" type="text" :class="fieldSurfaceClass" />
        <PermalinkPreviewHint
          v-if="!form.alias"
          :preview="aliasPreview.preview.value"
          :is-checking="aliasPreview.isChecking.value"
        />
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
import { Link, useForm } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Building2, Layers, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';

import ActivityStatusField from './ActivityStatusField.vue';

import { operatingStatuses } from '@/Constants/statuses';
import StatusBadge from '@/Components/Patterns/StatusBadge.vue';
import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import PermalinkField from '@/Components/AdminForms/PermalinkField.vue';
import PermalinkPreviewHint from '@/Components/AdminForms/PermalinkPreviewHint.vue';
import PublicUrlHistoryCard from '@/Components/AdminForms/PublicUrlHistoryCard.vue';
import TenantSelectField from '@/Components/AdminForms/TenantSelectField.vue';
import InstitutionScopeBadge from '@/Components/Institutions/InstitutionScopeBadge.vue';
import FormPage from '@/Components/Layouts/FormPage.vue';
import ConfirmDialog from '@/Components/Patterns/ConfirmDialog.vue';
import FormPanel from '@/Components/Patterns/FormPanel.vue';
import FormSection from '@/Components/Patterns/FormSection.vue';
import TiptapEditor from '@/Components/TipTap/TiptapEditor.vue';
import { Button } from '@/Components/ui/button';
import { Alert, AlertDescription } from '@/Components/ui/alert';
import { fieldSurfaceClass } from '@/Components/ui/control';
import { Input } from '@/Components/ui/input';
import { TypeTreeMultiSelect } from '@/Components/AdminForms/TypeTree';
import { Textarea } from '@/Components/ui/textarea';
import { ImageUpload } from '@/Components/ui/upload';
import type { ImageData } from '@/Types/media';
import { getTranslatedValue } from '@/Composables/useTranslatedTitle';
import { useDateFormatter } from '@/Composables/useDateFormatter';
import { usePermalinkPreview } from '@/Composables/usePermalinkPreview';
import { InstitutionScope, ModelEnum } from '@/Types/enums';

interface Translated { lt: string; en: string }

export interface InstitutionDeactivationSummary {
  endingCount: number;
  blockers: Array<{ duty: string; startDate: string }>;
  effectiveEndDate: string;
}

const props = defineProps<{
  institution: App.Entities.Institution;
  institutionTypes: App.Entities.InstitutionType[];
  assignableTenants: Array<App.Entities.Tenant>;
  deactivation?: InstitutionDeactivationSummary;
  /** Create mode when set: keeps the draft across a failed submit. */
  rememberKey?: string;
  enableDelete?: boolean;
}>();

const emit = defineEmits<{
  (event: 'submit:form', form: unknown): void;
  (event: 'delete'): void;
}>();

const isEditing = computed(() => !props.rememberKey);
const { formatDate } = useDateFormatter();
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
  image_media: (source.image_media ?? null) as ImageData | null,
  logo_media: (source.logo_media ?? null) as ImageData | null,
  email: (source.email ?? '') as string,
  phone: (source.phone ?? '') as string,
  website: (source.website ?? '') as string,
  facebook_url: (source.facebook_url ?? '') as string,
  instagram_url: (source.instagram_url ?? '') as string,
  alias: (source.alias ?? '') as string,
  meeting_periodicity_days: (source.meeting_periodicity_days ?? null) as number | null,
});

const form = props.rememberKey ? useForm(props.rememberKey, initial()) : useForm(initial());

const savedAlias = (source.alias ?? '') as string;
const savedName = asTranslated(source.name).lt;
const publicUrl = (source.public_url ?? undefined) as string | undefined;
const publicUrls = (source.public_urls ?? []) as Array<{ id: number; url: string; locale: string }>;
const aliasPinned = Boolean(source.alias_pinned);
const aliasBaseUrl = publicUrl && savedAlias ? publicUrl.slice(0, publicUrl.lastIndexOf('/')) : '';

// Mirrors UpdateInstitutionRequest: a rename carries an untouched alias along with it.
const renamedWithUntouchedAlias = computed(() =>
  isEditing.value && !aliasPinned && form.alias === savedAlias && form.name.lt.trim() !== '' && form.name.lt !== savedName,
);

const aliasPreview = usePermalinkPreview(
  'institution',
  () => (!isEditing.value && !form.alias) || renamedWithUntouchedAlias.value ? form.name.lt : '',
  () => 'lt',
  () => ({ tenant_id: form.tenant_id, institution_id: isEditing.value ? props.institution.id : null }),
);

const aliasWarning = computed(() => (form.alias !== savedAlias || renamedWithUntouchedAlias.value
  ? $t('Pakeitus nuorodą, sena nuoroda ir toliau nukreips į šį puslapį — nebereikalingas senas nuorodas galėsi ištrinti.')
  : undefined));

const institutionTitle = computed(() => form.name.lt || form.name.en || '');
const barTitle = computed(() => (isEditing.value ? (getTranslatedValue(props.institution.name) || $t('Institucija')) : $t('Nauja institucija')));

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

const institutionStatus = computed(() => operatingStatuses[isActive.value ? 'active' : 'inactive']);

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
