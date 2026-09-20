<template>
  <FormPage
    :title="isEditing ? institutionTitle : $t('Nauja institucija')"
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
    @update:locale="activeLocale = $event"
    @submit="emit('submit:form', form)"
  >
    <FormSection
      :title="$t('Kas tai?')"
      :description="$t('Institucija gali būti bet koks VU SA arba VU organas: padalinys, darbo grupė, studijų programos komitetas ir pan.')"
    >
      <div class="space-y-1.5">
        <Label for="institution-name" class="text-sm font-medium">
          {{ $t('Pavadinimas') }} ({{ activeLocale.toUpperCase() }}) *
        </Label>
        <Input id="institution-name" v-model="form.name[activeLocale]" />
        <p v-if="form.errors[`name.${activeLocale}`]" class="text-xs text-destructive">
          {{ form.errors[`name.${activeLocale}`] }}
        </p>
      </div>

      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div class="space-y-1.5">
          <div class="flex items-center justify-between">
            <Label for="institution-short-name" class="text-sm font-medium">
              {{ $t('forms.fields.short_name') }} ({{ activeLocale.toUpperCase() }})
            </Label>
            <span class="text-xs text-muted-foreground">{{ $t('(neprivaloma)') }}</span>
          </div>
          <Input id="institution-short-name" v-model="form.short_name[activeLocale]" />
          <p class="text-xs text-muted-foreground">
            {{ $t('Trumpas pavadinimas rodomas, kai vietos mažai.') }}
          </p>
          <p v-if="form.errors[`short_name.${activeLocale}`]" class="text-xs text-destructive">
            {{ form.errors[`short_name.${activeLocale}`] }}
          </p>
        </div>

        <div class="space-y-1.5">
          <Label for="institution-tenant" class="text-sm font-medium">
            {{ $t('Padalinys') }} *
          </Label>
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
          <p v-if="form.errors.tenant_id" class="text-xs text-destructive">
            {{ form.errors.tenant_id }}
          </p>
        </div>
      </div>

      <div class="flex items-start gap-2.5">
        <Checkbox
          id="institution-active"
          class="mt-0.5"
          :model-value="isActive"
          @update:model-value="isActive = $event === true"
        />
        <div class="space-y-0.5">
          <Label for="institution-active" class="cursor-pointer text-sm font-normal">
            {{ $t('Aktyvi institucija') }}
          </Label>
          <p class="text-xs text-muted-foreground">
            {{ $t('Neaktyvios institucijos nebelaukiamos posėdžių ir nerodomos viešame sąraše.') }}
          </p>
        </div>
      </div>
    </FormSection>

    <FormSection
      :title="$t('Kokia tai institucija?')"
      :description="$t('Tipas nustato, kokią papildomą informaciją galima užpildyti.')"
      :badge="$t('Matoma vusa.lt')"
      public-marker
    >
      <div class="space-y-1.5">
        <Label for="institution-types" class="text-sm font-medium">
          {{ $t('Institucijos tipas') }}
        </Label>
        <MultiSelect
          id="institution-types"
          v-model="selectedTypes"
          :options="institutionTypeOptions"
          :placeholder="$t('Pasirinkite tipus')"
        />
        <!-- The scope decides whether the contact fields below appear at all; without it stated
             they would simply vanish for no visible reason. -->
        <div v-if="resolvedScope" class="flex flex-wrap items-center gap-2 pt-1">
          <InstitutionScopeBadge :scope="resolvedScope" />
          <span class="text-xs text-muted-foreground">
            {{ showContactFields
              ? $t('forms.helpers.governance_scope_internal_fields')
              : $t('forms.helpers.governance_scope_external_fields') }}
          </span>
        </div>
        <p v-if="form.errors.types" class="text-xs text-destructive">
          {{ form.errors.types }}
        </p>
      </div>

      <div class="space-y-2">
        <Label class="text-sm font-medium">
          {{ $t('Aprašymas') }} ({{ activeLocale.toUpperCase() }})
        </Label>
        <TiptapEditor v-if="activeLocale === 'lt'" v-model="form.description.lt" preset="full" html />
        <TiptapEditor v-else v-model="form.description.en" preset="full" html />
        <p v-if="form.errors[`description.${activeLocale}`]" class="text-xs text-destructive">
          {{ form.errors[`description.${activeLocale}`] }}
        </p>
      </div>
    </FormSection>

    <FormSection
      v-if="showContactFields"
      :title="$t('Kaip su ja susisiekti?')"
      :description="$t('Nuotrauka, logotipas ir kontaktai rodomi institucijos puslapyje vusa.lt.')"
      :badge="$t('Matoma vusa.lt')"
      public-marker
    >
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div class="space-y-1.5">
          <Label class="text-sm font-medium">{{ $t('Nuotrauka') }}</Label>
          <ImageUpload
            v-model:url="form.image_url"
            v-model:focal-point-value="form.image_focal_point"
            mode="immediate"
            cropper
            compress
            focal-point
            folder="institutions"
          />
        </div>
        <div class="space-y-1.5">
          <Label class="text-sm font-medium">{{ $t('Logotipas') }}</Label>
          <ImageUpload v-model:url="form.logo_url" mode="immediate" cropper compress folder="institutions" />
        </div>
      </div>

      <div class="space-y-1.5">
        <Label for="institution-address" class="text-sm font-medium">
          {{ $t('Adresas') }} ({{ activeLocale.toUpperCase() }})
        </Label>
        <Input id="institution-address" v-model="form.address[activeLocale]" />
      </div>

      <div class="space-y-1.5">
        <Label for="institution-hours" class="text-sm font-medium">
          {{ $t('Darbo laikas') }} ({{ activeLocale.toUpperCase() }})
        </Label>
        <Textarea id="institution-hours" v-model="form.working_hours[activeLocale]" rows="3" />
      </div>

      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div class="space-y-1.5">
          <Label for="institution-email" class="text-sm font-medium">{{ $t('El. paštas') }}</Label>
          <Input id="institution-email" v-model="form.email" type="email" placeholder="info@vusa.lt" />
          <p v-if="form.errors.email" class="text-xs text-destructive">
            {{ form.errors.email }}
          </p>
        </div>
        <div class="space-y-1.5">
          <Label for="institution-phone" class="text-sm font-medium">{{ $t('Telefonas') }}</Label>
          <Input id="institution-phone" v-model="form.phone" type="tel" placeholder="+370…" />
        </div>
        <div class="space-y-1.5">
          <Label for="institution-website" class="text-sm font-medium">{{ $t('Svetainė') }}</Label>
          <Input id="institution-website" v-model="form.website" type="url" placeholder="https://…" />
          <p v-if="form.errors.website" class="text-xs text-destructive">
            {{ form.errors.website }}
          </p>
        </div>
        <div class="space-y-1.5">
          <Label for="institution-facebook" class="text-sm font-medium">Facebook</Label>
          <Input id="institution-facebook" v-model="form.facebook_url" type="url" placeholder="facebook.com/…" />
        </div>
        <div class="space-y-1.5">
          <Label for="institution-instagram" class="text-sm font-medium">Instagram</Label>
          <Input id="institution-instagram" v-model="form.instagram_url" type="url" placeholder="instagram.com/…" />
        </div>
      </div>
    </FormSection>

    <template #advanced>
      <div class="space-y-1.5">
        <Label for="institution-alias" class="text-sm font-medium">{{ $t('Techninė žymė') }}</Label>
        <Input id="institution-alias" v-model="form.alias" type="text" placeholder="vu-sa-mif" />
        <p class="text-xs text-muted-foreground">
          {{ $t('Unikali žymė naudojama URL adresuose.') }}
        </p>
        <p v-if="form.errors.alias" class="text-xs text-destructive">
          {{ form.errors.alias }}
        </p>
      </div>

      <div class="space-y-1.5">
        <Label for="institution-periodicity" class="text-sm font-medium">{{ $t('Susitikimų periodiškumas') }}</Label>
        <div class="flex items-center gap-2">
          <Input
            id="institution-periodicity"
            v-model.number="form.meeting_periodicity_days"
            type="number"
            :min="1"
            :max="365"
            placeholder="30"
            class="w-24"
          />
          <span class="text-sm text-muted-foreground">{{ $t('dienų') }}</span>
        </div>
        <p class="text-xs text-muted-foreground">
          {{ $t('Perrašo tipo nustatymą. Jei nenurodyta, naudojamas tipo arba numatytasis 30 dienų nustatymas.') }}
        </p>
      </div>
    </template>

    <template v-if="isEditing && enableDelete" #danger-zone>
      <div class="flex items-center justify-between gap-4">
        <div>
          <h4 class="text-sm font-semibold text-destructive">
            {{ $t('Ištrinti instituciją') }}
          </h4>
          <p class="text-xs text-muted-foreground">
            {{ $t('Institucija bus perkelta į šiukšlinę; pareigybės ir posėdžiai liks.') }}
          </p>
        </div>
        <Button type="button" variant="destructive" size="sm" class="u-touch shrink-0" @click="deleteConfirmOpen = true">
          <Trash2 class="mr-1.5 size-4" />
          {{ $t('Ištrinti') }}
        </Button>
      </div>

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
import { computed, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Trash2 } from 'lucide-vue-next';

import InstitutionScopeBadge from '@/Components/Institutions/InstitutionScopeBadge.vue';
import FormPage from '@/Components/Layouts/FormPage.vue';
import ConfirmDialog from '@/Components/Patterns/ConfirmDialog.vue';
import FormSection from '@/Components/Patterns/FormSection.vue';
import TiptapEditor from '@/Components/TipTap/TiptapEditor.vue';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { MultiSelect } from '@/Components/ui/multi-select';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Textarea } from '@/Components/ui/textarea';
import { ImageUpload } from '@/Components/ui/upload';
import { InstitutionScope, ModelEnum } from '@/Types/enums';

type Translated = { lt: string; en: string };

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

// Error keys that are not the id of the field they belong to.
const fieldIds = {
  'name.lt': 'institution-name',
  'name.en': 'institution-name',
  'short_name.lt': 'institution-short-name',
  'short_name.en': 'institution-short-name',
  'tenant_id': 'institution-tenant',
  'types': 'institution-types',
  'alias': 'institution-alias',
  'email': 'institution-email',
  'website': 'institution-website',
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
