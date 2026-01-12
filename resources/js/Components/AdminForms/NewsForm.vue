<template>
  <AdminForm :model="form" label-placement="top" @submit:form="$emit('submit:form', form)" @delete="$emit('delete')">
    <!-- Section 1: Main Info -->
    <FormElement :section-number="1" :is-complete="mainInfoComplete" required>
      <template #title>
        {{ $t("forms.context.main_info") }}
      </template>
      <template #subtitle>
        {{ $t('Pagrindiniai naujienos nustatymai') }}
      </template>
      <template #description>
        <p><strong>{{ $t('Nuoroda') }}</strong> {{ $t('susiformuoja automatiškai pagal pavadinimą.') }}</p>
      </template>

      <div class="space-y-4">
        <!-- Title with character counter -->
        <FormFieldWrapper
          id="title"
          :label="$t('forms.fields.title')"
          required
          :hint="$t('Pavadinimas bus rodomas naršyklės skirtuke ir paieškos rezultatuose')"
          :char-count="form.title?.length || 0"
          :max-length="60"
          :error="form.errors.title"
          :validating="form.validating"
          :valid="form.valid('title')"
          :invalid="form.invalid('title')"
        >
          <Input id="title" v-model="form.title" type="text" :placeholder="$t('Įrašyti pavadinimą...')" @change="form.validate('title')" />
        </FormFieldWrapper>

        <!-- Language, Publish time, Draft status -->
        <div class="grid gap-4 lg:grid-cols-3">
          <FormFieldWrapper id="lang" :label="$t('Kalba')" required :error="form.errors.lang" :valid="form.valid('lang')" :invalid="form.invalid('lang')">
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

          <FormFieldWrapper id="publish_time" :label="$t('Paskelbimo laikas')" required :error="form.errors.publish_time" :valid="form.valid('publish_time')" :invalid="form.invalid('publish_time')">
            <DateTimePicker v-model="publishTimeDate" @update:model-value="form.validate('publish_time')" />
          </FormFieldWrapper>

          <!-- Draft status -->
          <div class="flex items-end pb-2">
            <div
              class="flex w-full items-center gap-3 rounded-lg border p-3 transition-colors"
              :class="form.draft ? 'border-amber-200 bg-amber-50/50 dark:border-amber-800 dark:bg-amber-950/30' : 'border-green-200 bg-green-50/50 dark:border-green-800 dark:bg-green-950/30'"
            >
              <Switch
                id="draft"
                v-model="form.draft"
              />
              <div class="flex-1">
                <Label for="draft" class="font-medium">
                  {{ form.draft ? $t('Juodraštis') : $t('Paskelbta') }}
                </Label>
                <p class="text-xs" :class="form.draft ? 'text-amber-700 dark:text-amber-400' : 'text-green-700 dark:text-green-400'">
                  {{ form.draft ? $t('Naujiena nerodoma viešai') : $t('Naujiena matoma visiems') }}
                </p>
              </div>
              <span
                class="shrink-0 rounded-full px-2 py-0.5 text-xs font-medium"
                :class="form.draft ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-400' : 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-400'"
              >
                {{ form.draft ? $t('Juodraštis') : $t('Aktyvus') }}
              </span>
            </div>
          </div>
        </div>

        <!-- Tags -->
        <FormFieldWrapper id="tags" :label="$t('Žymos')" :hint="$t('Pasirinkite temas, susijusias su naujiena')">
          <MultiSelect
            v-model="selectedTags"
            :options="tagOptions"
            value-field="value"
            :placeholder="$t('Pasirinkite žymas...')"
          />
        </FormFieldWrapper>

        <!-- Other Language News -->
        <FormFieldWrapper
          id="other_lang"
          :label="$t('Kitos kalbos naujiena')"
          :hint="$t('Susieti su ta pačia naujiena kita kalba')"
        >
          <Select v-model="otherLangIdString" :disabled="isCreate">
            <SelectTrigger id="other_lang">
              <SelectValue :placeholder="$t('Pasirinkti kitos kalbos naujieną...')" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="__none__">-- {{ $t('Nepasirinkta') }} --</SelectItem>
              <SelectItem v-for="opt in otherLangNewsOptions" :key="opt.value" :value="String(opt.value)">
                {{ opt.label }}
              </SelectItem>
            </SelectContent>
          </Select>
        </FormFieldWrapper>

        <!-- Permalink -->
        <FormFieldWrapper
          id="permalink"
          :label="$t('Nuoroda')"
          :helper-text="$t('Atsargiai: pakeitus nuorodą, sena nuoroda nebeveiks!')"
          :error="form.errors.permalink"
          :valid="form.valid('permalink')"
          :invalid="form.invalid('permalink')"
        >
          <div class="flex items-center gap-2">
            <IFluentLink24Regular class="h-4 w-4 shrink-0 text-muted-foreground" />
            <Input id="permalink" v-model="form.permalink" type="text" :placeholder="$t('Sugeneruojama nuoroda')" @change="form.validate('permalink')" />
          </div>
        </FormFieldWrapper>
      </div>
    </FormElement>

    <!-- Section 2: Layout Selection -->
    <FormElement :section-number="2" :is-complete="!!form.layout">
      <template #title>
        {{ $t('Išdėstymas') }}
      </template>
      <template #subtitle>
        {{ $t('Pasirinkite naujienos vaizdą') }}
      </template>
      <template #description>
        <p>{{ $t('Pasirinkite, kaip naujiena bus rodoma viešoje svetainėje.') }}</p>
      </template>

      <div class="grid grid-cols-2 gap-4 overflow-visible pt-3 md:grid-cols-4">
        <button
          v-for="layout in layoutOptions"
          :key="layout.value"
          type="button"
          class="group relative overflow-visible rounded-xl border-2 p-4 text-left transition-all duration-200"
          :class="[
            form.layout === layout.value
              ? 'border-vusa-red bg-red-50/50 ring-2 ring-vusa-red/20 dark:bg-red-950/20'
              : 'border-border hover:border-zinc-300 dark:hover:border-zinc-600'
          ]"
          @click="form.layout = layout.value"
        >
          <!-- Selected indicator -->
          <div
            class="absolute -right-2 -top-2 flex h-6 w-6 items-center justify-center rounded-full bg-vusa-red text-white shadow-md transition-all"
            :class="form.layout === layout.value ? 'scale-100 opacity-100' : 'scale-75 opacity-0'"
          >
            <IFluentCheckmark12Regular class="h-3 w-3" />
          </div>

          <!-- Layout preview -->
          <div
            class="mb-3 flex justify-center transition-opacity"
            :class="form.layout === layout.value ? 'opacity-100' : 'opacity-50 group-hover:opacity-75'"
          >
            <component :is="layout.icon" class="h-12 w-20" />
          </div>

          <div class="text-center">
            <span class="text-sm font-medium">{{ layout.label }}</span>
            <p class="mt-1 text-xs text-muted-foreground">{{ layout.description }}</p>
          </div>
        </button>
      </div>
    </FormElement>

    <!-- Section 3: Highlights -->
    <FormElement :section-number="3" :is-complete="form.highlights.length > 0">
      <template #title>
        {{ $t('Akcentai') }}
      </template>
      <template #description>
        <p>{{ $t('Iki 3 pagrindinių minčių, kurios bus išskirtos naujienos puslapyje.') }}</p>
      </template>

      <div class="space-y-3">
        <!-- Empty state -->
        <div
          v-if="form.highlights.length === 0"
          class="flex flex-col items-center justify-center rounded-lg border-2 border-dashed p-8 text-center"
        >
          <IFluentTextBulletListLtr24Regular class="mb-2 h-8 w-8 text-muted-foreground/50" />
          <p class="text-sm text-muted-foreground">{{ $t('Dar nepridėta jokių akcentų') }}</p>
          <Button
            type="button"
            variant="outline"
            size="sm"
            class="mt-3"
            @click="addHighlight"
          >
            <IFluentAdd24Regular class="mr-2 h-4 w-4" />
            {{ $t('Pridėti pirmą akcentą') }}
          </Button>
        </div>

        <!-- Highlight items -->
        <template v-else>
          <div
            v-for="(_, index) in form.highlights"
            :key="index"
            class="flex items-start gap-3"
          >
            <div class="flex h-9 w-8 shrink-0 items-center justify-center rounded-lg bg-muted text-sm font-medium text-muted-foreground">
              {{ index + 1 }}
            </div>
            <Input
              v-model="form.highlights[index]"
              :placeholder="$t('Akcentas') + ` ${index + 1}...`"
              class="flex-1"
            />
            <Button
              type="button"
              variant="ghost"
              size="icon"
              class="shrink-0 text-muted-foreground hover:text-red-600"
              @click="removeHighlight(index)"
            >
              <IFluentDelete24Regular class="h-4 w-4" />
            </Button>
          </div>

          <Button
            v-if="form.highlights.length < 3"
            type="button"
            variant="outline"
            size="sm"
            @click="addHighlight"
          >
            <IFluentAdd24Regular class="mr-2 h-4 w-4" />
            {{ $t('Pridėti akcentą') }}
          </Button>
        </template>
      </div>
    </FormElement>

    <!-- Section 4: Image -->
    <FormElement :section-number="4" :is-complete="!!form.image" required>
      <template #title>
        {{ $t('Nuotrauka') }}
      </template>
      <template #subtitle>
        {{ $t('Pagrindinė naujienos nuotrauka') }}
      </template>

      <div class="space-y-4">
        <FormFieldWrapper id="image" :label="$t('Nuotrauka')" required :error="form.errors.image" :valid="form.valid('image')" :invalid="form.invalid('image')">
          <ImageUpload
            v-model:url="form.image"
            mode="immediate"
            folder="news"
            cropper
            :existing-url="news?.image"
            @update:url="form.validate('image')"
          />
        </FormFieldWrapper>

        <FormFieldWrapper
          id="image_author"
          :label="$t('Nuotraukos autorius')"
          :hint="$t('Žmogus arba organizacija, kurie sukūrė nuotrauką')"
        >
          <Input
            id="image_author"
            v-model="form.image_author"
            type="text"
            :placeholder="$t('Žmogus arba organizacija...')"
          />
        </FormFieldWrapper>
      </div>
    </FormElement>

    <!-- Section 5: Short description -->
    <FormElement :section-number="5">
      <template #title>
        {{ $t('Įvadinis tekstas') }}
      </template>
      <template #description>
        <p>{{ $t('Šiuo metu naudojamas') }} <strong>{{ $t('tik paieškos rezultatuose') }}</strong>. {{ $t('Maksimalus ženklų skaičius') }}: 200.</p>
      </template>

      <TipTap v-model="form.short" disable-tables :max-characters="200" html />
    </FormElement>

    <!-- Section 6: Content -->
    <FormElement :section-number="6" no-sider>
      <template #title>
        {{ $t('Turinys') }}
      </template>
    </FormElement>

    <RichContentFormElement v-model="form.content.parts" />
  </AdminForm>
</template>

<script setup lang="ts">
import { computed, watch, h } from "vue";
import { useForm } from "@inertiajs/vue3";
import latinize from "latinize";

import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/Components/ui/select";
import { Switch } from "@/Components/ui/switch";
import { MultiSelect } from "@/Components/ui/multi-select";
import DateTimePicker from "@/Components/ui/date-picker/DateTimePicker.vue";

import FormElement from "./FormElement.vue";
import FormFieldWrapper from "./FormFieldWrapper.vue";
import RichContentFormElement from "../RichContentFormElement.vue";
import TipTap from "@/Components/TipTap/OriginalTipTap.vue";
import AdminForm from "./AdminForm.vue";
import { ImageUpload } from "@/Components/ui/upload";
import { newsTemplate } from "@/Types/formTemplates";

// Layout preview icons as simple SVG components
const ModernLayoutIcon = {
  render() {
    return h('svg', { viewBox: '0 0 80 48', fill: 'none', stroke: 'currentColor', strokeWidth: 1 }, [
      h('rect', { x: 4, y: 4, width: 72, height: 20, rx: 2 }),
      h('rect', { x: 4, y: 28, width: 30, height: 4, rx: 1 }),
      h('rect', { x: 4, y: 36, width: 50, height: 2, rx: 1 }),
      h('rect', { x: 4, y: 42, width: 40, height: 2, rx: 1 }),
    ]);
  }
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
  }
};

const ImmersiveLayoutIcon = {
  render() {
    return h('svg', { viewBox: '0 0 80 48', fill: 'none', stroke: 'currentColor', strokeWidth: 1 }, [
      h('rect', { x: 0, y: 0, width: 80, height: 32, rx: 0, fill: 'currentColor', opacity: 0.1 }),
      h('rect', { x: 16, y: 36, width: 48, height: 4, rx: 1 }),
      h('rect', { x: 20, y: 44, width: 40, height: 2, rx: 1 }),
    ]);
  }
};

const HeadlineLayoutIcon = {
  render() {
    return h('svg', { viewBox: '0 0 80 48', fill: 'none', stroke: 'currentColor', strokeWidth: 1 }, [
      h('rect', { x: 4, y: 4, width: 50, height: 6, rx: 1 }),
      h('rect', { x: 4, y: 14, width: 35, height: 3, rx: 1 }),
      h('rect', { x: 4, y: 22, width: 72, height: 22, rx: 2 }),
    ]);
  }
};

const props = defineProps<{
  news?: App.Entities.News;
  otherLangNews?: App.Entities.News[];
  availableTags?: App.Entities.Tag[];
  rememberKey?: 'CreateNews';
  submitUrl: string;
  submitMethod: 'post' | 'patch';
}>();

const isCreate = computed(() => props.rememberKey === 'CreateNews');

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

defineEmits<{
  (event: "submit:form", form: unknown): void;
  (event: "delete"): void;
}>();

// Section completion states
const mainInfoComplete = computed(() =>
  (form.title?.length || 0) >= 3 && form.lang && form.publish_time
);

// Date picker compatibility - convert string to Date
const publishTimeDate = computed({
  get: () => form.publish_time ? new Date(form.publish_time) : undefined,
  set: (val: Date | undefined) => {
    form.publish_time = val ? val.toISOString() : null;
  }
});

// Handle other_lang_id as string for Select component
const otherLangIdString = computed({
  get: () => form.other_lang_id ? String(form.other_lang_id) : '__none__',
  set: (val: string) => {
    form.other_lang_id = val && val !== '__none__' ? parseInt(val) : null;
  }
});

const otherLangNewsOptions = computed(() => {
  if (isCreate.value) {
    return [];
  }

  return (props.otherLangNews || [])
    .map((news) => ({
      value: news.id,
      label: `${news.title} (${news.tenant?.shortname})`,
    }))
    .reverse();
});

const tagOptions = computed(() => {
  return (props.availableTags || []).map(tag => {
    let label = 'Unknown';
    if (tag.name) {
      if (typeof tag.name === 'object' && !Array.isArray(tag.name)) {
        const nameObj = tag.name as Record<string, string>;
        label = nameObj.lt || nameObj.en || 'Unknown';
      } else if (typeof tag.name === 'string') {
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
    // Convert form.tags (array of IDs) to array of option objects using Map for efficient lookup
    const tagIds = Array.isArray(form.tags) ? form.tags : [];
    const map = tagOptionsMap.value;

    return tagIds
      .map(id => map.get(id))
      .filter((option): option is { label: string; value: number } => Boolean(option));
  },
  set: (items: { label: string; value: number }[]) => {
    // Convert selected objects to array of IDs
    form.tags = items.map(item => item.value);
  }
});

const languageOptions = [
  { value: "lt", label: "Lietuvių" },
  { value: "en", label: "English" },
];

const layoutOptions = [
  {
    value: 'modern',
    label: 'Modernus',
    description: 'Švarus, didelis šriftas, fokusuotas į skaitomumą',
    icon: ModernLayoutIcon,
  },
  {
    value: 'classic',
    label: 'Klasikinis',
    description: 'Tradicinis naujienų straipsnio vaizdas',
    icon: ClassicLayoutIcon,
  },
  {
    value: 'immersive',
    label: 'Įtraukiantis',
    description: 'Žurnalo stilius su dideliu vaizdu',
    icon: ImmersiveLayoutIcon,
  },
  {
    value: 'headline',
    label: 'Antraštinis',
    description: 'Pavadinimas virš nuotraukos',
    icon: HeadlineLayoutIcon,
  },
];

// Highlights management
const addHighlight = () => {
  if (form.highlights.length < 3) {
    form.highlights.push('');
  }
};

const removeHighlight = (index: number) => {
  form.highlights.splice(index, 1);
};

if (isCreate.value) {
  watch(
    () => form.title,
    (title) => {
      let latinizedTitle = latinize(String(title || ''));
      form.permalink = latinizedTitle
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, "-")
        .replace(/-+/g, "-")
        .replace(/^-+/, "")
        .replace(/-+$/, "")
    }
  );
}
</script>
