<template>
  <AdminForm :model="form" label-placement="top" @submit:form="$emit('submit:form', form)" @delete="$emit('delete')">
    <!-- Status Header -->
    <template #status-header>
      <FormStatusHeader :is-published="!form.draft" :server-is-published="props.news ? !props.news.draft : undefined"
        :publish-time="publishTimeDate" :links="statusLinks" :is-create show-publish-time
        @update:is-published="form.draft = !$event" @update:publish-time="handlePublishTimeUpdate" />
    </template>

    <!-- Section 1: Title (Essential - the identity of the news) -->
    <FormElement :section-number="1" :is-complete="titleComplete" required>
      <template #title>
        {{ $t('forms.fields.title') }}
      </template>
      <template #subtitle>
        {{ $t('Naujienos antraštė') }}
      </template>

      <div class="space-y-4">
        <FormFieldWrapper id="title" :label="$t('forms.fields.title')" required
          :hint="$t('Pavadinimas bus rodomas naršyklės skirtuke ir paieškos rezultatuose')"
          :char-count="form.title?.length || 0" :max-length="60" :error="form.errors.title"
          :validating="form.validating" :valid="form.valid('title')" :invalid="form.invalid('title')">
          <Input id="title" v-model="form.title" type="text" :placeholder="$t('Įrašyti pavadinimą...')" class="text-lg"
            @change="form.validate('title')" />
        </FormFieldWrapper>

        <!-- Language selector inline with title -->
        <div class="grid gap-4 sm:grid-cols-2">
          <FormFieldWrapper id="lang" :label="$t('Kalba')" required :error="form.errors.lang"
            :valid="form.valid('lang')" :invalid="form.invalid('lang')">
            <Select v-model="form.lang" @update:model-value="form.validate('lang')">
              <SelectTrigger id="lang">
                <SelectValue :placeholder="$t('Pasirinkti kalbą...')" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="opt in languageOptions" :key="opt.value" :value="opt.value">
                  {{ opt.label }}
                </SelectItem>
              </SelectContent>
            </Select>
          </FormFieldWrapper>

          <FormFieldWrapper id="tags" :label="$t('Žymos')" :hint="$t('Pasirinkite temas')">
            <MultiSelect v-model="selectedTags" :options="tagOptions" value-field="value"
              :placeholder="$t('Pasirinkite žymas...')" />
          </FormFieldWrapper>
        </div>
      </div>
    </FormElement>

    <!-- Section 2: Image (Visual identity) -->
    <FormElement :section-number="2" :is-complete="!!form.image" required>
      <template #title>
        {{ $t('Nuotrauka') }}
      </template>
      <template #subtitle>
        {{ $t('Pagrindinė naujienos nuotrauka') }}
      </template>

      <div class="space-y-4">
        <FormFieldWrapper id="image" :label="$t('Nuotrauka')" required :error="form.errors.image"
          :valid="form.valid('image')" :invalid="form.invalid('image')">
          <ImageUpload v-model:url="form.image" mode="immediate" folder="news" cropper :existing-url="news?.image"
            @update:url="form.validate('image')" />
        </FormFieldWrapper>

        <FormFieldWrapper id="image_author" :label="$t('Nuotraukos autorius')"
          :hint="$t('Žmogus arba organizacija, kurie sukūrė nuotrauką')">
          <Input id="image_author" v-model="form.image_author" type="text"
            :placeholder="$t('Žmogus arba organizacija...')" />
        </FormFieldWrapper>
      </div>
    </FormElement>

    <!-- Section 3: Content (Main editing area) -->
    <FormElement :section-number="3" no-sider>
      <template #title>
        {{ $t('Turinys') }}
      </template>
      <template #subtitle>
        {{ $t('Pagrindinė naujienos informacija') }}
      </template>
    </FormElement>

    <RichContentFormElement v-model="form.content.parts" />

    <!-- Section 4: Highlights (Optional but prominent) -->
    <FormElement :section-number="4" :is-complete="form.highlights.length > 0">
      <template #title>
        {{ $t('Akcentai') }}
      </template>
      <template #description>
        <p>{{ $t('Iki 3 pagrindinių minčių, kurios bus išskirtos naujienos puslapyje.') }}</p>
      </template>

      <OrderedListInput v-model="form.highlights" :max="3" :placeholder="$t('Akcentas') + ' {n}...'"
        :empty-text="$t('Dar nepridėta jokių akcentų')" :add-first-text="$t('Pridėti pirmą akcentą')"
        :add-text="$t('Pridėti akcentą')" />
    </FormElement>

    <!-- Section 5: Short description -->
    <FormElement :section-number="5">
      <template #title>
        {{ $t('Įvadinis tekstas') }}
      </template>
      <template #description>
        <p>{{ $t('Šiuo metu naudojamas') }} <strong>{{ $t('tik paieškos rezultatuose') }}</strong>. {{ $t('Maksimalus ženklų skaičius') }}: 200.</p>
      </template>

      <TiptapEditor v-model="form.short" preset="full" :disable-tables="true" :max-characters="200" :html="true" />
    </FormElement>

    <!-- Section 6: Advanced Settings (Collapsible) -->
    <FormElement :section-number="6">
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
          <div class="space-y-4">
            <!-- Layout Selection -->
            <div>
              <Label class="mb-3 block text-sm font-medium">{{ $t('Išdėstymas') }}</Label>
              <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
                <button v-for="layout in layoutOptions" :key="layout.value" type="button"
                  class="group relative overflow-visible rounded-lg border-2 p-3 text-left transition-all duration-200"
                  :class="[
                    form.layout === layout.value
                      ? 'border-vusa-red bg-red-50/50 ring-2 ring-vusa-red/20 dark:bg-red-950/20'
                      : 'border-border hover:border-zinc-300 dark:hover:border-zinc-600'
                  ]" @click="form.layout = layout.value">
                  <div
                    class="absolute -right-1.5 -top-1.5 flex h-5 w-5 items-center justify-center rounded-full bg-vusa-red text-white shadow-md transition-all"
                    :class="form.layout === layout.value ? 'scale-100 opacity-100' : 'scale-75 opacity-0'">
                    <IFluentCheckmark12Regular class="h-2.5 w-2.5" />
                  </div>
                  <div class="mb-2 flex justify-center transition-opacity"
                    :class="form.layout === layout.value ? 'opacity-100' : 'opacity-50 group-hover:opacity-75'">
                    <component :is="layout.icon" class="h-10 w-16" />
                  </div>
                  <div class="text-center">
                    <span class="text-xs font-medium">{{ layout.label }}</span>
                  </div>
                </button>
              </div>
            </div>

            <!-- Other Language News -->
            <FormFieldWrapper id="other_lang" :label="$t('Kitos kalbos naujiena')"
              :hint="$t('Susieti su ta pačia naujiena kita kalba')">
              <Select v-model="otherLangIdString" :disabled="isCreate">
                <SelectTrigger id="other_lang">
                  <SelectValue :placeholder="$t('Pasirinkti kitos kalbos naujieną...')" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="__none__">
                    -- {{ $t('Nepasirinkta') }} --
                  </SelectItem>
                  <SelectItem v-for="opt in otherLangNewsOptions" :key="opt.value" :value="String(opt.value)">
                    {{ opt.label }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </FormFieldWrapper>

            <!-- Permalink -->
            <FormFieldWrapper id="permalink" :label="$t('Nuoroda')"
              :helper-text="$t('Atsargiai: pakeitus nuorodą, sena nuoroda nebeveiks!')" :error="form.errors.permalink"
              :valid="form.valid('permalink')" :invalid="form.invalid('permalink')">
              <div class="flex items-center gap-2">
                <IFluentLink24Regular class="h-4 w-4 shrink-0 text-muted-foreground" />
                <Input id="permalink" v-model="form.permalink" type="text" :placeholder="$t('Sugeneruojama nuoroda')"
                  @change="form.validate('permalink')" />
              </div>
            </FormFieldWrapper>
          </div>
        </CollapsibleContent>
      </Collapsible>
    </FormElement>
  </AdminForm>
</template>

<script setup lang="ts">
import { computed, ref, watch, h } from 'vue';
import { useForm } from '@inertiajs/vue3';

import RichContentFormElement from '../RichContentFormElement.vue';

import AdminForm from './AdminForm.vue';
import FormElement from './FormElement.vue';
import FormFieldWrapper from './FormFieldWrapper.vue';
import FormStatusHeader from './FormStatusHeader.vue';

import { translitLithuanian } from '@/Utils/String';
import { Button } from '@/Components/ui/button';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/Components/ui/collapsible';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { MultiSelect } from '@/Components/ui/multi-select';
import { OrderedListInput } from '@/Components/ui/ordered-list-input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { ImageUpload } from '@/Components/ui/upload';
import TiptapEditor from '@/Components/TipTap/TiptapEditor.vue';
import { newsTemplate } from '@/Types/formTemplates';

// Layout preview icons as simple SVG components
const ModernLayoutIcon = {
  render() {
    return h('svg', { viewBox: '0 0 80 48', fill: 'none', stroke: 'currentColor', strokeWidth: 1 }, [
      h('rect', { x: 4, y: 4, width: 72, height: 20, rx: 2 }),
      h('rect', { x: 4, y: 28, width: 30, height: 4, rx: 1 }),
      h('rect', { x: 4, y: 36, width: 50, height: 2, rx: 1 }),
      h('rect', { x: 4, y: 42, width: 40, height: 2, rx: 1 }),
    ]);
  },
};

const ClassicLayoutIcon = {
  render() {
    return h('svg', { viewBox: '0 0 80 48', fill: 'none', stroke: 'currentColor', strokeWidth: 1 }, [
      h('rect', { x: 4, y: 4, width: 30, height: 24, rx: 2 }),
      h('rect', { x: 40, y: 4, width: 36, height: 4, rx: 1 }),
      h('rect', { x: 40, y: 12, width: 30, height: 2, rx: 1 }),
      h('rect', { x: 40, y: 18, width: 32, height: 2, rx: 1 }),
      h('rect', { x: 4, y: 34, width: 72, height: 2, rx: 1 }),
      h('rect', { x: 4, y: 42, width: 60, height: 2, rx: 1 }),
    ]);
  },
};

const ImmersiveLayoutIcon = {
  render() {
    return h('svg', { viewBox: '0 0 80 48', fill: 'none', stroke: 'currentColor', strokeWidth: 1 }, [
      h('rect', { x: 0, y: 0, width: 80, height: 32, rx: 0, fill: 'currentColor', opacity: 0.1 }),
      h('rect', { x: 16, y: 36, width: 48, height: 4, rx: 1 }),
      h('rect', { x: 20, y: 44, width: 40, height: 2, rx: 1 }),
    ]);
  },
};

const HeadlineLayoutIcon = {
  render() {
    return h('svg', { viewBox: '0 0 80 48', fill: 'none', stroke: 'currentColor', strokeWidth: 1 }, [
      h('rect', { x: 4, y: 4, width: 50, height: 6, rx: 1 }),
      h('rect', { x: 4, y: 14, width: 35, height: 3, rx: 1 }),
      h('rect', { x: 4, y: 22, width: 72, height: 22, rx: 2 }),
    ]);
  },
};

const props = defineProps<{
  news?: App.Entities.News;
  otherLangNews?: App.Entities.News[];
  availableTags?: App.Entities.Tag[];
  rememberKey?: 'CreateNews';
  submitUrl: string;
  submitMethod: 'post' | 'patch';
}>();

defineEmits<{
  (event: 'submit:form', form: unknown): void;
  (event: 'delete'): void;
}>();

const isCreate = computed(() => props.rememberKey === 'CreateNews');

// Advanced settings collapsed by default
const advancedSettingsOpen = ref(false);

const formData = { ...newsTemplate, ...props.news, layout: props.news?.layout || 'modern', highlights: props.news?.highlights || [] } as any;

const form = props.rememberKey
  ? useForm(props.rememberKey, formData).withPrecognition(props.submitMethod, props.submitUrl)
  : useForm(formData).withPrecognition(props.submitMethod, props.submitUrl);

// Set validation timeout to 500ms for faster feedback
form.setValidationTimeout(500);

// Ensure highlights is always an array
if (!Array.isArray(form.highlights)) {
  form.highlights = [];
}

// Section completion states
const titleComplete = computed(() => (form.title?.length || 0) >= 3 && form.lang);

// Status header links
const statusLinks = computed(() => {
  // Need permalink and tenant to construct a valid public URL
  if (!form.permalink || !props.news?.tenant) return [];

  const newsLang = form.lang ?? 'lt';
  const url = route('news', {
    subdomain: props.news.tenant.alias ?? 'www',
    lang: newsLang,
    newsString: newsLang === 'lt' ? 'naujiena' : 'news',
    news: form.permalink,
  });

  return [{ url, label: 'Public' }];
});

// Date picker compatibility - convert string to Date
const publishTimeDate = computed({
  get: () => form.publish_time ? new Date(form.publish_time) : undefined,
  set: (val: Date | undefined) => {
    form.publish_time = val ? val.toISOString() : null;
  },
});

// Handle publish time update from status header
function handlePublishTimeUpdate(val: Date | null) {
  form.publish_time = val ? val.toISOString() : null;
  if (val) {
    form.validate('publish_time');
  }
}

// Handle other_lang_id as string for Select component
const otherLangIdString = computed({
  get: () => form.other_lang_id ? String(form.other_lang_id) : '__none__',
  set: (val: string) => {
    form.other_lang_id = val && val !== '__none__' ? parseInt(val) : null;
  },
});

const otherLangNewsOptions = computed(() => {
  if (isCreate.value) {
    return [];
  }

  return (props.otherLangNews || [])
    .map(news => ({
      value: news.id,
      label: `${news.title} (${news.tenant?.shortname})`,
    }))
    .reverse();
});

const tagOptions = computed(() => {
  return (props.availableTags || []).map((tag) => {
    let label = 'Unknown';
    if (tag.name) {
      if (typeof tag.name === 'object' && !Array.isArray(tag.name)) {
        const nameObj = tag.name as Record<string, string>;
        label = nameObj.lt || nameObj.en || 'Unknown';
      }
      else if (typeof tag.name === 'string') {
        label = tag.name;
      }
    }
    return { label, value: tag.id };
  });
});

const tagOptionsMap = computed(() => {
  const map = new Map<number, { label: string; value: number }>();

  for (const option of tagOptions.value) {
    map.set(option.value, option);
  }

  return map;
});

// Computed to handle tag selection - converts between objects and IDs
const selectedTags = computed({
  get: () => {
    const tagIds = Array.isArray(form.tags) ? form.tags : [];
    const map = tagOptionsMap.value;

    return tagIds
      .map(id => map.get(id))
      .filter((option): option is { label: string; value: number } => Boolean(option));
  },
  set: (items: { label: string; value: number }[]) => {
    form.tags = items.map(item => item.value);
  },
});

const languageOptions = [
  { value: 'lt', label: 'Lietuvių' },
  { value: 'en', label: 'English' },
];

const layoutOptions = [
  {
    value: 'modern',
    label: 'Modernus',
    icon: ModernLayoutIcon,
  },
  {
    value: 'classic',
    label: 'Klasikinis',
    icon: ClassicLayoutIcon,
  },
  {
    value: 'immersive',
    label: 'Įtraukiantis',
    icon: ImmersiveLayoutIcon,
  },
  {
    value: 'headline',
    label: 'Antraštinis',
    icon: HeadlineLayoutIcon,
  },
];

// Auto-generate permalink from title for new news
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
        .replace(/-+$/, '');
    },
  );
}
</script>
