<template>
  <AdminForm :model="form" label-placement="top" @submit:form="$emit('submit:form', form)" @delete="$emit('delete')">
    <!-- Status Header -->
    <template #status-header>
      <FormStatusHeader :is-published="isActiveBoolean" :server-is-published="Boolean(props.institution.is_active)"
        :links="statusLinks" :is-create @update:is-published="isActiveBoolean = $event" />
    </template>

    <!-- Section 1: Main Info -->
    <FormElement :section-number="1" :is-complete="mainInfoComplete" required>
      <template #title>
        {{ $t("forms.context.main_info") }}
      </template>
      <template #subtitle>
        {{ $t('Pagrindiniai institucijos nustatymai') }}
      </template>
      <template #description>
        <p>
          {{ $t('Institucija gali būti bet koks VU SA arba VU organas, pavyzdžiui, padalinys, darbo grupė, studijų programos komitetas ir pan.') }}
        </p>
      </template>

      <div class="space-y-4">
        <FormFieldWrapper id="name" :label="$t('forms.fields.title')" required :error="form.errors.name">
          <MultiLocaleInput v-model:input="form.name" />
        </FormFieldWrapper>

        <div class="grid gap-4 lg:grid-cols-2">
          <FormFieldWrapper id="short_name" :label="$t('forms.fields.short_name')"
            :hint="$t('Trumpas pavadinimas rodomas kai vietos mažai')">
            <MultiLocaleInput v-model:input="form.short_name" />
          </FormFieldWrapper>

          <FormFieldWrapper id="tenant_id" :label="$t('Padalinys')" required
            :hint="$t('Padalinys, kuriam priklauso institucija')" :error="form.errors.tenant_id">
            <Select v-model="tenantIdString">
              <SelectTrigger>
                <SelectValue :placeholder="$t('Pasirinkite padalinį')" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="tenant in assignableTenants" :key="tenant.id" :value="String(tenant.id)">
                  {{ tenant.shortname }}
                </SelectItem>
              </SelectContent>
            </Select>
          </FormFieldWrapper>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
          <FormFieldWrapper id="is_active" :label="$t('Aktyvumo būsena')">
            <div class="flex items-center gap-3 pt-2">
              <Switch v-model="isActiveBoolean" />
              <span class="text-sm text-muted-foreground">
                {{ isActiveBoolean ? $t('Aktyvi institucija') : $t('Neaktyvi institucija') }}
              </span>
            </div>
          </FormFieldWrapper>

          <FormFieldWrapper id="contacts_layout" :label="$t('Kontaktų išdėstymas')"
            :hint="$t('Kaip kontaktai bus rodomi viešoje pusėje')">
            <RadioGroup v-model="form.contacts_layout" class="flex gap-4 pt-2">
              <div class="flex items-center gap-2">
                <RadioGroupItem id="layout-aside" value="aside" />
                <Label for="layout-aside" class="font-normal cursor-pointer">{{ $t('Šone') }}</Label>
              </div>
              <div class="flex items-center gap-2">
                <RadioGroupItem id="layout-below" value="below" />
                <Label for="layout-below" class="font-normal cursor-pointer">{{ $t('Po aprašymu') }}</Label>
              </div>
            </RadioGroup>
          </FormFieldWrapper>
        </div>
      </div>
    </FormElement>

    <!-- Section 2: Type & Additional Info -->
    <FormElement :section-number="2" :is-complete="(form.types?.length ?? 0) > 0">
      <template #title>
        {{ $t('Tipas ir papildoma informacija') }}
      </template>
      <template #subtitle>
        {{ $t('Priklausomai nuo tipo, galima užpildyti papildomą informaciją') }}
      </template>

      <div class="space-y-4">
        <FormFieldWrapper id="types" :label="$t('Institucijos tipas')"
          :hint="$t('Tipas nustato papildomas funkcijas ir rodomą informaciją')">
          <MultiSelect v-model="selectedTypes" :options="institutionTypeOptions"
            :placeholder="$t('Pasirinkite tipus')" />
        </FormFieldWrapper>

        <template v-if="showMoreOptions">
          <Separator class="my-4" />

          <div class="grid gap-4 lg:grid-cols-2">
            <FormFieldWrapper id="image_url" :label="$t('Nuotrauka')">
              <ImageUpload v-model:url="form.image_url" mode="immediate" cropper compress folder="institutions" />
            </FormFieldWrapper>
            <FormFieldWrapper id="logo_url" :label="$t('Logotipas')">
              <ImageUpload v-model:url="form.logo_url" mode="immediate" cropper compress folder="institutions" />
            </FormFieldWrapper>
          </div>

          <FormFieldWrapper id="address" :label="$t('Adresas')">
            <MultiLocaleInput v-model:input="form.address" />
          </FormFieldWrapper>

          <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <InputWithOverlappingLabel v-model="form.email" :label="$t('El. paštas')" type="email"
              placeholder="info@vusa.lt">
              <template #icon>
                <IMdiEmailOutline class="h-4 w-4 text-muted-foreground" />
              </template>
            </InputWithOverlappingLabel>
            <InputWithOverlappingLabel v-model="form.phone" :label="$t('Telefonas')" type="tel" placeholder="+370...">
              <template #icon>
                <IMdiPhone class="h-4 w-4 text-muted-foreground" />
              </template>
            </InputWithOverlappingLabel>
            <InputWithOverlappingLabel v-model="form.website" :label="$t('Svetainė')" type="url"
              placeholder="https://...">
              <template #icon>
                <IFluentGlobe20Regular class="h-4 w-4 text-muted-foreground" />
              </template>
            </InputWithOverlappingLabel>
            <InputWithOverlappingLabel v-model="form.facebook_url" label="Facebook" type="url"
              placeholder="facebook.com/...">
              <template #icon>
                <IMdiFacebook class="h-4 w-4 text-[#1877F2]" />
              </template>
            </InputWithOverlappingLabel>
            <InputWithOverlappingLabel v-model="form.instagram_url" label="Instagram" type="url"
              placeholder="instagram.com/...">
              <template #icon>
                <IMdiInstagram class="h-4 w-4 text-[#E4405F]" />
              </template>
            </InputWithOverlappingLabel>
          </div>
        </template>

        <FormFieldWrapper id="description" :label="$t('Aprašymas')">
          <template #default>
            <div class="space-y-3">
              <div class="flex items-center gap-2">
                <SimpleLocaleButton v-model:locale="locale" />
                <span class="text-sm text-muted-foreground">
                  {{ locale === 'lt' ? $t('Rašote lietuviškai') : $t('Writing in English') }}
                </span>
              </div>
              <TiptapEditor v-if="locale === 'lt'" v-model="form.description.lt" preset="full" :html="true" />
              <TiptapEditor v-else v-model="form.description.en" preset="full" :html="true" />
            </div>
          </template>
        </FormFieldWrapper>
      </div>
    </FormElement>

    <!-- Section 3: Duties -->
    <FormElement :section-number="3" :is-complete="(institution.duties?.length ?? 0) > 0" no-sider>
      <template #title>
        <div class="flex items-center gap-3">
          <span>{{ $t('Pareigybės') }}</span>
          <span v-if="institution.duties?.length" class="text-sm font-normal text-muted-foreground">
            ({{ institution.duties.length }})
          </span>
        </div>
      </template>
      <template #subtitle>
        {{ $t('Institucijos pareigos ir jų nariai') }}
      </template>

      <div v-if="institution.duties && institution.duties.length > 0">
        <!-- View toggle and actions bar -->
        <div class="mb-3 flex items-center justify-between gap-4">
          <div class="flex items-center gap-1 rounded-lg border p-0.5">
            <Button :variant="!dutiesEditMode ? 'secondary' : 'ghost'" size="sm" class="h-7 px-2.5 text-xs"
              @click="dutiesEditMode = false">
              <List class="mr-1.5 h-3.5 w-3.5" />
              {{ $t('Sąrašas') }}
            </Button>
            <Button :variant="dutiesEditMode ? 'secondary' : 'ghost'" size="sm" class="h-7 px-2.5 text-xs"
              @click="dutiesEditMode = true">
              <GripVertical class="mr-1.5 h-3.5 w-3.5" />
              {{ $t('Redagavimas') }}
            </Button>
          </div>

          <SmartLink :href="route('duties.create', { institution_id: institution.id })">
            <Button size="sm" variant="outline" class="h-7 gap-1.5 text-xs">
              <Plus class="h-3.5 w-3.5" />
              {{ $t('Pridėti') }}
            </Button>
          </SmartLink>
        </div>

        <!-- Compact list view (default) -->
        <div v-if="!dutiesEditMode" class="rounded-lg border bg-card">
          <div class="grid gap-0 divide-y lg:grid-cols-2 lg:divide-y-0">
            <div v-for="(duty, idx) in institution.duties" :key="duty.id" class="px-3 lg:odd:border-r"
              :class="[idx >= 2 ? 'lg:border-t' : '']">
              <DutyCard :duty :institution-id="institution.id" compact />
            </div>
          </div>
        </div>

        <!-- Edit view with drag-and-drop -->
        <div v-else class="space-y-3">
          <SortableDutiesTable v-model="form.duties" @update:model-value="dutiesWereReordered = true">
            <template #default="{ model }">
              <DutyCard :duty="(model as any)" :institution-id="institution.id" />
            </template>
          </SortableDutiesTable>

          <div class="flex items-center gap-3">
            <Button :disabled="!dutiesWereReordered" size="sm" @click="saveReorderedDuties">
              <Save class="mr-2 h-4 w-4" />
              {{ $t('Išsaugoti eiliškumą') }}
            </Button>
            <span v-if="dutiesWereReordered" class="text-sm text-amber-600 dark:text-amber-400">
              {{ $t('Yra neišsaugotų pakeitimų') }}
            </span>
          </div>
        </div>
      </div>

      <!-- Empty state -->
      <div v-else
        class="flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-muted py-12 text-center">
        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-muted">
          <Briefcase class="h-6 w-6 text-muted-foreground" />
        </div>
        <h3 class="mt-4 text-sm font-medium">
          {{ $t('Nėra pareigybių') }}
        </h3>
        <p class="mt-1 text-sm text-muted-foreground">
          {{ $t('Ši institucija dar neturi pareigybių.') }}
        </p>
        <SmartLink :href="route('duties.create', { institution_id: institution.id })" class="mt-4">
          <Button size="sm" class="gap-2">
            <Plus class="h-4 w-4" />
            {{ $t('Sukurti pirmą pareigybę') }}
          </Button>
        </SmartLink>
      </div>
    </FormElement>

    <!-- Section 4: Technical Settings -->
    <FormElement :section-number="4">
      <template #title>
        {{ $t('Techniniai nustatymai') }}
      </template>
      <template #subtitle>
        {{ $t('Sistemos nustatymai ir identifikatoriai') }}
      </template>

      <div class="space-y-4">
        <div class="grid gap-4 lg:grid-cols-2">
          <FormFieldWrapper id="alias" :label="$t('Techninė žymė')" :hint="$t('Unikali žymė naudojama URL adresuose')"
            :error="form.errors.alias">
            <Input v-model="form.alias" type="text" placeholder="vu-sa-mif" />
          </FormFieldWrapper>

          <FormFieldWrapper id="meeting_periodicity_days" :label="$t('Susitikimų periodiškumas')"
            :hint="$t('Perrašo tipo nustatymą. Jei nenurodyta, naudojamas tipo arba numatytasis 30 dienų nustatymas.')">
            <div class="flex items-center gap-2">
              <Input v-model.number="form.meeting_periodicity_days" type="number" :min="1" :max="365" placeholder="30"
                class="w-24" />
              <span class="text-sm text-muted-foreground">{{ $t('dienų') }}</span>
            </div>
          </FormFieldWrapper>
        </div>
      </div>
    </FormElement>
  </AdminForm>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import { Briefcase, GripVertical, List, Plus, Save } from 'lucide-vue-next';

import MultiLocaleInput from '../FormItems/MultiLocaleInput.vue';
import SimpleLocaleButton from '../Buttons/SimpleLocaleButton.vue';
import SmartLink from '../Public/SmartLink.vue';
import SortableDutiesTable from '../Tables/SortableDutiesTable.vue';

import AdminForm from './AdminForm.vue';
import DutyCard from './DutyCard.vue';
import FormElement from './FormElement.vue';
import FormFieldWrapper from './FormFieldWrapper.vue';
import FormStatusHeader from './FormStatusHeader.vue';

import TiptapEditor from '@/Components/TipTap/TiptapEditor.vue';
import { Button } from '@/Components/ui/button';
import { Input, InputWithOverlappingLabel } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { MultiSelect } from '@/Components/ui/multi-select';
import { RadioGroup, RadioGroupItem } from '@/Components/ui/radio-group';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Separator } from '@/Components/ui/separator';
import { Switch } from '@/Components/ui/switch';
import { ImageUpload } from '@/Components/ui/upload';

const props = defineProps<{
  institution: App.Entities.Institution;
  institutionTypes: App.Entities.Type[];
  assignableTenants: Array<App.Entities.Tenant>;
  rememberKey?: string;
}>();

defineEmits<{
  (event: 'submit:form', form: unknown): void;
  (event: 'delete'): void;
}>();

const locale = ref('lt');
const dutiesWereReordered = ref(false);
const dutiesEditMode = ref(false);

const isCreate = computed(() => !!props.rememberKey);

const form = props.rememberKey
  ? useForm(props.rememberKey, props.institution as any)
  : useForm(props.institution as any);

// Section completion states
const mainInfoComplete = computed(() =>
  (form.name?.lt?.length || 0) >= 2 && form.tenant_id,
);

// Status header links - Institution has both public and admin views
const statusLinks = computed(() => {
  if (isCreate.value || !props.institution.id) return [];

  const page = usePage();
  const links: { url: string; label: string }[] = [];

  // Admin view link
  links.push({
    url: route('institutions.show', { institution: props.institution.id }),
    label: 'Admin',
  });

  // Public view link (by ID - always works)
  const tenant = props.assignableTenants.find(t => t.id === props.institution.tenant_id);
  links.push({
    url: route('contacts.institution', {
      institution: props.institution.id,
      subdomain: tenant?.alias || 'www',
      lang: page.props.app?.locale || 'lt',
    }),
    label: 'Public',
  });

  return links;
});

// Boolean computed for is_active Switch (database stores as tinyint 0/1)
const isActiveBoolean = computed({
  get: () => Boolean(form.is_active),
  set: (val: boolean) => {
    form.is_active = val ? 1 : 0;
  },
});

// Handle tenant_id as string for Select component
const tenantIdString = computed({
  get: () => form.tenant_id ? String(form.tenant_id) : '',
  set: (val: string) => {
    form.tenant_id = val ? parseInt(val) : null;
  },
});

// Institution type options for MultiSelect
const institutionTypeOptions = computed(() =>
  props.institutionTypes.map(type => ({
    label: type.title,
    value: type.id,
  })),
);

// Computed to handle type selection - converts between objects and IDs
const selectedTypes = computed({
  get: () => {
    const typeIds = Array.isArray(form.types) ? form.types : [];
    return typeIds
      .map((id: number) => institutionTypeOptions.value.find(opt => opt.value === id))
      .filter((opt: { label: string; value: number } | undefined): opt is { label: string; value: number } => Boolean(opt));
  },
  set: (items: { label: string; value: number }[]) => {
    form.types = items.map(item => item.value);
  },
});

const showMoreOptions = computed(() => {
  // HACK: manually added types to check
  const typesToCheck = ['pkp', 'padaliniai'];
  const typeIds = props.institutionTypes
    ?.filter(type => type.slug && typesToCheck.includes(type.slug))
    .map(type => type.id);

  return form.types?.some((type: number) => typeIds.includes(type));
});

const saveReorderedDuties = () => {
  const newDuties = form.duties.map((duty: App.Entities.Duty, index: number) => {
    duty.order = index;
    return duty;
  });
  router.post(
    route('institutions.reorderDuties'),
    {
      duties: newDuties,
    },
    {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => {
        dutiesWereReordered.value = false;
      },
    },
  );
};
</script>
