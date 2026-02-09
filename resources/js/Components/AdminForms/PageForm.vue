<template>
  <AdminForm :model="form" label-placement="top" @submit:form="$emit('submit:form', form)" @delete="$emit('delete')">
    <!-- Status Header -->
    <template #status-header>
      <FormStatusHeader :is-published="form.is_active" :server-is-published="props.page.is_active" :links="statusLinks"
        :is-create show-publish-time :publish-time="publishTimeDate" @update:is-published="form.is_active = $event"
        @update:publish-time="publishTimeDate = $event" />
    </template>

    <!-- Section 1: Title & Essential Info -->
    <FormElement :section-number="1" :is-complete="mainInfoComplete" required>
      <template #title>
        {{ $t('forms.fields.title') }}
      </template>
      <template #subtitle>
        {{ $t('Puslapio antraštė ir pagrindiniai nustatymai') }}
      </template>

      <div class="space-y-4">
        <!-- Title with character counter -->
        <FormFieldWrapper id="title" :label="$t('forms.fields.title')" required
          :hint="$t('Pavadinimas bus rodomas naršyklės skirtuke ir paieškos rezultatuose')"
          :char-count="form.title?.length || 0" :max-length="60" :error="form.errors.title"
          :validating="form.validating" :valid="form.valid('title')" :invalid="form.invalid('title')">
          <Input id="title" v-model="form.title" type="text" :placeholder="$t('Įrašyti pavadinimą...')" class="text-lg"
            @change="form.validate('title')" />
        </FormFieldWrapper>

        <!-- Category and Language -->
        <div class="grid gap-4 lg:grid-cols-2">
          <FormFieldWrapper id="category" :label="$t('Kategorija')" required :error="form.errors.category_id"
            :valid="form.valid('category_id')" :invalid="form.invalid('category_id')">
            <Select v-model="form.category_id" @update:model-value="form.validate('category_id')">
              <SelectTrigger id="category">
                <SelectValue :placeholder="$t('Pasirinkti kategoriją...')" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="category in categories" :key="category.id" :value="category.id">
                  {{ category.name }}
                </SelectItem>
              </SelectContent>
            </Select>
          </FormFieldWrapper>

          <FormFieldWrapper id="lang" :label="$t('Kalba')" required :error="form.errors.lang"
            :valid="form.valid('lang')" :invalid="form.invalid('lang')">
            <Select v-model="form.lang" @update:model-value="form.validate('lang')">
              <SelectTrigger id="lang">
                <SelectValue :placeholder="$t('Pasirinkti kalbą...')" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="lang in languageOptions" :key="lang.value" :value="lang.value">
                  {{ lang.label }}
                </SelectItem>
              </SelectContent>
            </Select>
          </FormFieldWrapper>
        </div>
      </div>
    </FormElement>

    <!-- Section 2: Content (Main editing area) -->
    <FormElement :section-number="2" no-sider>
      <template #title>
        {{ $t('Turinys') }}
      </template>
      <template #subtitle>
        {{ $t('Pagrindinė puslapio informacija') }}
      </template>
    </FormElement>

    <RichContentFormElement v-model="form.content.parts" />

    <!-- Section 3: Highlights -->
    <FormElement :section-number="3" :is-complete="form.highlights.length > 0">
      <template #title>
        {{ $t('Svarbiausi punktai') }}
      </template>
      <template #description>
        <p>{{ $t('Iki 3 pagrindinių minčių, kurios bus išskirtos puslapyje.') }}</p>
      </template>

      <OrderedListInput v-model="form.highlights" :max="3" input-type="textarea"
        :placeholder="$t('Įveskite svarbų punktą...')" :empty-text="$t('Dar nepridėta jokių punktų')"
        :add-first-text="$t('Pridėti pirmą punktą')" :add-text="$t('Pridėti punktą')" />
    </FormElement>

    <!-- Section 4: Advanced Settings (Collapsible) -->
    <FormElement :section-number="4">
      <template #title>
        {{ $t('Papildomi nustatymai') }}
      </template>

      <Collapsible v-model:open="advancedSettingsOpen" class="w-full">
        <CollapsibleTrigger as-child>
          <Button variant="ghost" class="w-full justify-between p-0 h-auto hover:bg-transparent">
            <span class="text-sm text-muted-foreground">
              {{ advancedSettingsOpen ? $t('Slėpti papildomus nustatymus') : $t('Rodyti papildomus nustatymus') }}
            </span>
            <IFluentChevronDown24Regular class="h-4 w-4 text-muted-foreground transition-transform duration-200"
              :class="{ 'rotate-180': advancedSettingsOpen }" />
          </Button>
        </CollapsibleTrigger>

        <CollapsibleContent class="pt-4">
          <div class="space-y-6">
            <!-- Layout Selection -->
            <div>
              <Label class="mb-3 block text-sm font-medium">{{ $t('Išdėstymas') }}</Label>
              <div class="grid gap-3 md:grid-cols-3">
                <button v-for="layoutOption in layoutOptions" :key="layoutOption.value" type="button"
                  class="group relative overflow-visible rounded-lg border-2 p-3 text-left transition-all duration-200"
                  :class="[
                    form.layout === layoutOption.value
                      ? 'border-vusa-red bg-red-50/50 ring-2 ring-vusa-red/20 dark:bg-red-950/20'
                      : 'border-border hover:border-zinc-300 dark:hover:border-zinc-600'
                  ]" @click="form.layout = layoutOption.value">
                  <div
                    class="absolute -right-1.5 -top-1.5 flex h-5 w-5 items-center justify-center rounded-full bg-vusa-red text-white shadow-md transition-all"
                    :class="form.layout === layoutOption.value ? 'scale-100 opacity-100' : 'scale-75 opacity-0'">
                    <IFluentCheckmark12Regular class="h-2.5 w-2.5" />
                  </div>
                  <div class="mb-2 flex justify-center transition-opacity"
                    :class="form.layout === layoutOption.value ? 'opacity-100' : 'opacity-50 group-hover:opacity-75'">
                    <component :is="layoutOption.icon" class="h-12 w-20" />
                  </div>
                  <div class="text-center">
                    <span class="text-xs font-medium">{{ layoutOption.label }}</span>
                    <p class="mt-0.5 text-xs text-muted-foreground">
                      {{ layoutOption.description }}
                    </p>
                  </div>
                </button>
              </div>
            </div>

            <!-- Permalink -->
            <PermalinkField :permalink="form.permalink" :base-url="pageBaseUrl" :disabled="!isCreate"
              :view-url="!isCreate ? fullPageUrl : undefined"
              :explanation="isCreate ? $t('Nuoroda generuojama automatiškai pagal pavadinimą') : $t('Nuoroda negali būti keičiama esamam puslapiui')"
              @update:permalink="form.permalink = $event" />

            <!-- Other Language Page -->
            <FormFieldWrapper id="other_lang" :label="$t('Kitos kalbos puslapis')"
              :hint="$t('Susieti su to paties turinio puslapiu kita kalba')">
              <Select v-model="form.other_lang_id" :disabled="isCreate">
                <SelectTrigger id="other_lang">
                  <SelectValue :placeholder="$t('Pasirinkti kitos kalbos puslapį...')" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="__none__">
                    -- {{ $t('Nepasirinkta') }} --
                  </SelectItem>
                  <SelectItem v-for="page in otherPageOptions" :key="page.value" :value="page.value">
                    {{ page.label }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </FormFieldWrapper>

            <!-- SEO Section -->
            <div class="space-y-4 pt-2 border-t">
              <h4 class="text-sm font-medium">
                {{ $t('SEO ir metaduomenys') }}
              </h4>

              <!-- SEO Preview -->
              <SEOPreview :title="form.title" :description="form.meta_description" :url="form.permalink"
                :base-url="seoBaseUrl" />

              <!-- Meta description -->
              <FormFieldWrapper id="meta_description" :label="$t('Meta aprašymas')"
                :hint="$t('Trumpas puslapio aprašymas, rodomas paieškos rezultatuose')"
                :char-count="form.meta_description?.length || 0" :max-length="160">
                <Textarea id="meta_description" v-model="form.meta_description"
                  :placeholder="$t('Trumpas puslapio aprašymas paieškos rezultatams...')" rows="3"
                  :class="metaDescriptionClass" />
              </FormFieldWrapper>

              <!-- Featured image -->
              <FormFieldWrapper id="featured_image" :label="$t('Pagrindinė nuotrauka')"
                :hint="$t('Nuotrauka naudojama dalinantis socialiniuose tinkluose')">
                <ImageUpload v-model:url="form.featured_image" mode="immediate" folder="pages" cropper
                  :existing-url="page.featured_image" />
              </FormFieldWrapper>
            </div>
          </div>
        </CollapsibleContent>
      </Collapsible>
    </FormElement>
  </AdminForm>
</template>

<script setup lang="ts">
import { computed, ref, watch, h } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';

import RichContentFormElement from '../RichContentFormElement.vue';

import AdminForm from './AdminForm.vue';
import FormElement from './FormElement.vue';
import FormFieldWrapper from './FormFieldWrapper.vue';
import FormStatusHeader from './FormStatusHeader.vue';
import PermalinkField from './PermalinkField.vue';
import SEOPreview from './SEOPreview.vue';

import { translitLithuanian } from '@/Utils/String';
import { Button } from '@/Components/ui/button';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/Components/ui/collapsible';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { OrderedListInput } from '@/Components/ui/ordered-list-input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Textarea } from '@/Components/ui/textarea';
import { DateTimePicker } from '@/Components/ui/date-picker';
import { ImageUpload } from '@/Components/ui/upload';

const props = defineProps<{
  categories: App.Entities.Category[];
  page: App.Entities.Page;
  otherLangPages?: App.Entities.Page[];
  rememberKey?: 'CreatePage';
  submitUrl: string;
  submitMethod: 'post' | 'patch';
  /** Public URL for preview button */
  publicUrl?: string;
}>();

defineEmits<{
  (event: 'submit:form', form: unknown): void;
  (event: 'delete'): void;
}>();

const isCreate = computed(() => props.rememberKey === 'CreatePage');

// Advanced settings collapsed by default
const advancedSettingsOpen = ref(false);

// Initialize form with page data
const formData = {
  ...props.page,
  layout: props.page.layout || 'default',
  highlights: props.page.highlights || [],
  meta_description: props.page.meta_description || '',
  featured_image: props.page.featured_image || '',
  publish_time: props.page.publish_time || null,
} as any;

const form = props.rememberKey
  ? useForm(props.rememberKey, formData).withPrecognition(props.submitMethod, props.submitUrl)
  : useForm(formData).withPrecognition(props.submitMethod, props.submitUrl);

// Set validation timeout to 500ms for faster feedback
form.setValidationTimeout(500);

// Ensure highlights is always an array
if (!Array.isArray(form.highlights)) {
  form.highlights = [];
}

// URL helpers - use the page's tenant and app URL from config
const pageBaseUrl = computed(() => {
  const appUrl = usePage().props.app?.url ?? 'https://vusa.lt';
  // Extract domain from URL (remove protocol)
  const domain = appUrl.replace(/^https?:\/\//, '');

  const { tenant } = props.page;
  if (tenant?.alias) {
    return `${tenant.alias}.${domain}`;
  }
  return `www.${domain}`;
});

const seoBaseUrl = computed(() => pageBaseUrl.value);

// Construct full page URL using route helper
const fullPageUrl = computed(() => {
  if (!props.page.id || !form.permalink || !props.page.tenant) return undefined;

  const pageLang = form.lang ?? 'lt';
  return route('page', {
    subdomain: props.page.tenant.alias ?? props.page.tenant.alias === 'vusa' ? 'www' : props.page.tenant.alias,
    lang: pageLang,
    permalink: form.permalink,
  });
});

// Section completion states
const mainInfoComplete = computed(() =>
  (form.title?.length || 0) >= 3 && form.category_id && form.lang,
);

// Status header links
const statusLinks = computed(() => {
  if (!fullPageUrl.value) return [];
  return [{ url: fullPageUrl.value, label: 'Public' }];
});

// Meta description styling based on length
const metaDescriptionClass = computed(() => {
  const len = form.meta_description?.length || 0;
  if (len > 160) return 'border-red-300 dark:border-red-700 focus:border-red-500';
  if (len >= 120 && len <= 160) return 'border-green-300 dark:border-green-700 focus:border-green-500';
  return '';
});

// Layout icons as simple SVG representations
const DefaultLayoutIcon = () => h('svg', { viewBox: '0 0 96 64', fill: 'none', stroke: 'currentColor', strokeWidth: 2 }, [
  h('rect', { x: 8, y: 8, width: 80, height: 12, rx: 2 }),
  h('rect', { x: 8, y: 24, width: 56, height: 32, rx: 2 }),
  h('rect', { x: 68, y: 24, width: 20, height: 32, rx: 2 }),
]);

const WideLayoutIcon = () => h('svg', { viewBox: '0 0 96 64', fill: 'none', stroke: 'currentColor', strokeWidth: 2 }, [
  h('rect', { x: 4, y: 8, width: 88, height: 12, rx: 2 }),
  h('rect', { x: 4, y: 24, width: 88, height: 32, rx: 2 }),
]);

const FocusedLayoutIcon = () => h('svg', { viewBox: '0 0 96 64', fill: 'none', stroke: 'currentColor', strokeWidth: 2 }, [
  h('rect', { x: 16, y: 8, width: 64, height: 12, rx: 2 }),
  h('rect', { x: 16, y: 24, width: 64, height: 32, rx: 2 }),
]);

const layoutOptions = [
  {
    value: 'default',
    label: 'Standartinis',
    description: 'Su šonine juosta',
    icon: DefaultLayoutIcon,
  },
  {
    value: 'wide',
    label: 'Platus',
    description: 'Visas plotis',
    icon: WideLayoutIcon,
  },
  {
    value: 'focused',
    label: 'Susikaupęs',
    description: 'Centruotas',
    icon: FocusedLayoutIcon,
  },
];

const otherPageOptions = computed(() => {
  if (isCreate.value) {
    return [];
  }

  if (props.otherLangPages === undefined) {
    return [];
  }

  return props.otherLangPages
    .map(page => ({
      value: page.id,
      label: `${page.title} (${page.tenant?.shortname})`,
    }))
    .reverse();
});

const languageOptions = [
  { value: 'lt', label: 'Lietuvių' },
  { value: 'en', label: 'English' },
];

// Date/time picker compatibility
const publishTimeDate = computed({
  get: () => form.publish_time ? new Date(form.publish_time) : undefined,
  set: (val: Date | undefined) => {
    form.publish_time = val ? val.toISOString() : null;
  },
});

// Watch form.title and update form.permalink for new pages
if (isCreate.value) {
  watch(
    () => form.title,
    (title) => {
      const latinizedTitle = translitLithuanian(String(title || ''));
      form.permalink = latinizedTitle
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/-+/g, '-')
        .replace(/^-+/, '')
        .replace(/-+$/, '')
        .substring(0, 30);
    },
  );
}

// Handle other_lang_id sentinel value
watch(
  () => form.other_lang_id,
  (value) => {
    if (value === '__none__') {
      form.other_lang_id = null;
    }
  },
);
</script>
