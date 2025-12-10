<template>
  <AdminForm :model="form" label-placement="top" @submit:form="$emit('submit:form', form)" @delete="$emit('delete')">
    <FormElement>
      <template #title>
        {{ $t("forms.context.main_info") }}
      </template>
      <template #description>
        <strong>Nuoroda</strong> susiformuoja automatiškai pagal pavadinimą. Pabandykite pakeisti pavadinimą, jeigu
        tokia nuoroda jau egzistuoja.
      </template>
      
      <div class="space-y-4">
        <div class="space-y-2">
          <Label for="title" class="flex items-center gap-1">
            {{ $t('forms.fields.title') }} <span class="text-red-500">*</span>
          </Label>
          <Input id="title" v-model="form.title" type="text" placeholder="Įrašyti pavadinimą..." />
        </div>

        <div class="grid lg:grid-cols-3 gap-4">
          <div class="space-y-2">
            <Label for="lang">Kalba <span class="text-red-500">*</span></Label>
            <Select v-model="form.lang">
              <SelectTrigger>
                <SelectValue placeholder="Pasirinkti kalbą..." />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="opt in languageOptions" :key="opt.value" :value="opt.value">
                  {{ opt.label }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div class="space-y-2">
            <Label>Naujienos paskelbimo laikas <span class="text-red-500">*</span></Label>
            <DateTimePicker v-model="publishTimeDate" />
          </div>

          <div class="flex items-end pb-2">
            <div class="flex items-center space-x-2">
              <Switch id="draft" :checked="form.draft === 1 || form.draft === true" @update:checked="form.draft = $event ? 1 : 0" />
              <Label for="draft">Ar juodraštis?</Label>
            </div>
          </div>
        </div>

        <div class="space-y-2">
          <Label>Žymos</Label>
          <MultiSelect
            v-model="form.tags"
            :options="tagOptions"
            placeholder="Pasirinkite žymas..."
          />
        </div>

        <div class="space-y-2">
          <Label>Kitos kalbos puslapis</Label>
          <Select v-model="otherLangIdString" :disabled="rememberKey === 'CreateNews'">
            <SelectTrigger>
              <SelectValue placeholder="Pasirinkti kitos kalbos puslapį... (tik tada, kai jau sukūrėte puslapį)" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="__none__">-- Nepasirinkta --</SelectItem>
              <SelectItem v-for="opt in otherLangNewsOptions" :key="opt.value" :value="String(opt.value)">
                {{ opt.label }}
              </SelectItem>
            </SelectContent>
          </Select>
        </div>

        <div class="space-y-2">
          <Label for="permalink" class="flex items-center gap-2">
            <IFluentLink24Regular class="h-4 w-4" />
            Nuoroda
          </Label>
          <Input id="permalink" v-model="form.permalink" type="text" placeholder="Sugeneruojama nuoroda" />
          <p class="text-xs text-amber-600 dark:text-amber-400">
            Atsargiai: pakeitus nuorodą, sena nuoroda nebeveiks!
          </p>
        </div>
      </div>
    </FormElement>

    <!-- Layout Selection -->
    <FormElement>
      <template #title>Išdėstymas</template>
      <template #description>
        Pasirinkite, kaip naujiena bus rodoma viešoje svetainėje.
      </template>
      
      <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
        <button
          v-for="layout in layoutOptions"
          :key="layout.value"
          type="button"
          class="relative rounded-lg border-2 p-4 text-left transition-all hover:border-zinc-400 dark:hover:border-zinc-500"
          :class="form.layout === layout.value ? 'border-vusa-red bg-red-50 dark:bg-red-950/20' : 'border-zinc-200 dark:border-zinc-700'"
          @click="form.layout = layout.value"
        >
          <div class="mb-2 flex items-center justify-between">
            <span class="font-medium">{{ layout.label }}</span>
            <div v-if="form.layout === layout.value" class="h-2 w-2 rounded-full bg-vusa-red" />
          </div>
          <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ layout.description }}</p>
          <!-- Layout preview icon -->
          <div class="mt-3 flex justify-center">
            <component :is="layout.icon" class="h-12 w-20 text-zinc-400" />
          </div>
        </button>
      </div>
    </FormElement>

    <!-- Highlights -->
    <FormElement>
      <template #title>Akcentai</template>
      <template #description>
        Iki 3 pagrindinių minčių, kurios bus išskirtos naujienos puslapyje.
      </template>
      
      <div class="space-y-3">
        <div v-for="(_, index) in form.highlights" :key="index" class="flex gap-2">
          <Input 
            v-model="form.highlights[index]" 
            :placeholder="`Akcentas ${index + 1}...`"
            class="flex-1"
          />
          <Button variant="ghost" size="icon" @click="removeHighlight(index)">
            <IFluentDelete24Regular class="h-4 w-4" />
          </Button>
        </div>
        <Button 
          v-if="form.highlights.length < 3" 
          variant="outline" 
          size="sm" 
          type="button"
          @click="addHighlight"
        >
          <IFluentAdd24Regular class="mr-2 h-4 w-4" />
          Pridėti akcentą
        </Button>
      </div>
    </FormElement>

    <FormElement>
      <template #title>Nuotrauka</template>
      
      <div class="space-y-4">
        <div class="space-y-2">
          <Label>Nuotrauka <span class="text-red-500">*</span></Label>
          <UploadImageWithCropper v-model:url="form.image" folder="news" />
        </div>
        <div class="space-y-2">
          <Label for="image_author">Nuotraukos autorius</Label>
          <Input id="image_author" v-model="form.image_author" type="text" placeholder="Žmogus arba organizacija.." />
        </div>
      </div>
    </FormElement>

    <FormElement>
      <template #title>Įvadinis tekstas</template>
      <template #description>
        <p>Šiuo metu naudojamas <strong>tik paieškos rezultatuose</strong>. Maksimalus ženklų skaičius: 200.</p>
      </template>
      <TipTap v-model="form.short" disable-tables :max-characters="200" html />
    </FormElement>

    <h4 class="mb-4 text-3xl font-bold">Turinys</h4>
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
import RichContentFormElement from "../RichContentFormElement.vue";
import TipTap from "@/Components/TipTap/OriginalTipTap.vue";
import UploadImageWithCropper from "../Buttons/UploadImageWithCropper.vue";
import AdminForm from "./AdminForm.vue";
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
}>();

const form = props.rememberKey
  ? useForm(props.rememberKey, { ...newsTemplate, ...props.news, layout: props.news?.layout || 'modern', highlights: props.news?.highlights || [] } as any)
  : useForm({ ...newsTemplate, ...props.news, layout: props.news?.layout || 'modern', highlights: props.news?.highlights || [] } as any);

// Ensure highlights is always an array
if (!Array.isArray(form.highlights)) {
  form.highlights = [];
}

defineEmits<{
  (event: "submit:form", form: unknown): void;
  (event: "delete"): void;
}>();

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
  if (!form.id) {
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

if (props.rememberKey === "CreateNews") {
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
