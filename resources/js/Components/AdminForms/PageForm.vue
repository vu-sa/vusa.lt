<template>
  <AdminForm :model="form" label-placement="top" @submit:form="$emit('submit:form', form)" @delete="$emit('delete')">
    <FormElement>
      <template #title>
        {{ $t("forms.context.main_info") }}
      </template>
      
      <!-- Title -->
      <div class="space-y-2">
        <Label for="title">{{ $t('forms.fields.title') }}</Label>
        <Input
          id="title"
          v-model="form.title"
          type="text"
          placeholder="Įrašyti pavadinimą..."
        />
      </div>

      <!-- Permalink and Category -->
      <div class="grid gap-4 lg:grid-cols-2">
        <div class="space-y-2">
          <Label for="permalink">Nuoroda</Label>
          <Input
            id="permalink"
            :model-value="form.permalink"
            disabled
            type="text"
            placeholder="Sugeneruojama nuoroda..."
            class="bg-muted"
          />
        </div>
        <div class="space-y-2">
          <Label for="category">Kategorija</Label>
          <Select v-model="form.category_id">
            <SelectTrigger id="category">
              <SelectValue placeholder="Pasirinkti kategoriją..." />
            </SelectTrigger>
            <SelectContent>
              <SelectItem
                v-for="category in categories"
                :key="category.id"
                :value="category.id"
              >
                {{ category.name }}
              </SelectItem>
            </SelectContent>
          </Select>
        </div>
      </div>

      <!-- Language and Other Language Page -->
      <div class="grid gap-4 lg:grid-cols-2">
        <div class="space-y-2">
          <Label for="lang">Kalba</Label>
          <Select v-model="form.lang">
            <SelectTrigger id="lang">
              <SelectValue placeholder="Pasirinkti kalbą..." />
            </SelectTrigger>
            <SelectContent>
              <SelectItem
                v-for="lang in languageOptions"
                :key="lang.value"
                :value="lang.value"
              >
                {{ lang.label }}
              </SelectItem>
            </SelectContent>
          </Select>
        </div>
        <div class="space-y-2">
          <Label for="other_lang">Kitos kalbos puslapis</Label>
          <Select v-model="form.other_lang_id" :disabled="rememberKey === 'CreatePage'">
            <SelectTrigger id="other_lang">
              <SelectValue placeholder="Pasirinkti kitos kalbos puslapį..." />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="__none__">
                -- Nepasirinkta --
              </SelectItem>
              <SelectItem
                v-for="page in otherPageOptions"
                :key="page.value"
                :value="page.value"
              >
                {{ page.label }}
              </SelectItem>
            </SelectContent>
          </Select>
        </div>
      </div>

      <!-- Active Status -->
      <div class="flex items-center space-x-2">
        <Switch
          id="is_active"
          :checked="form.is_active"
          @update:checked="form.is_active = $event"
        />
        <Label for="is_active">Aktyvus</Label>
      </div>
    </FormElement>

    <!-- Layout Selection -->
    <FormElement>
      <template #title>
        Išdėstymas
      </template>
      <div class="grid gap-4 md:grid-cols-3">
        <div
          v-for="layoutOption in layoutOptions"
          :key="layoutOption.value"
          :class="[
            'cursor-pointer rounded-lg border-2 p-4 transition-all hover:border-primary/50',
            form.layout === layoutOption.value
              ? 'border-primary bg-primary/5'
              : 'border-border'
          ]"
          @click="form.layout = layoutOption.value"
        >
          <div class="mb-2 flex items-center justify-between">
            <span class="font-medium">{{ layoutOption.label }}</span>
            <div
              :class="[
                'h-4 w-4 rounded-full border-2',
                form.layout === layoutOption.value
                  ? 'border-primary bg-primary'
                  : 'border-muted-foreground'
              ]"
            />
          </div>
          <p class="text-sm text-muted-foreground">{{ layoutOption.description }}</p>
          <!-- Visual representation -->
          <div class="mt-3 flex justify-center">
            <component :is="layoutOption.icon" class="h-16 w-24 text-muted-foreground/50" />
          </div>
        </div>
      </div>
    </FormElement>

    <!-- Highlights Section -->
    <FormElement>
      <template #title>
        Svarbiausi punktai
      </template>
      <p class="mb-4 text-sm text-muted-foreground">
        Pridėkite iki 3 svarbiausių punktų, kurie bus rodomi puslapyje.
      </p>
      <div class="space-y-3">
        <div
          v-for="(_, index) in form.highlights"
          :key="index"
          class="flex items-start gap-2"
        >
          <div class="flex h-9 w-6 items-center justify-center text-muted-foreground">
            {{ index + 1 }}.
          </div>
          <Textarea
            v-model="form.highlights[index]"
            placeholder="Įveskite svarbų punktą..."
            class="min-h-9 flex-1 resize-none"
            rows="1"
          />
          <Button
            type="button"
            variant="ghost"
            size="icon"
            @click="removeHighlight(index)"
          >
            <XIcon class="h-4 w-4" />
          </Button>
        </div>
        <Button
          v-if="form.highlights.length < 3"
          type="button"
          variant="outline"
          size="sm"
          @click="addHighlight"
        >
          <PlusIcon class="mr-2 h-4 w-4" />
          Pridėti punktą
        </Button>
      </div>
    </FormElement>

    <!-- SEO & Metadata -->
    <FormElement>
      <template #title>
        SEO ir metaduomenys
      </template>
      <div class="space-y-4">
        <div class="space-y-2">
          <Label for="meta_description">Meta aprašymas</Label>
          <Textarea
            id="meta_description"
            v-model="form.meta_description"
            placeholder="Trumpas puslapio aprašymas paieškos rezultatams (iki 160 simbolių)..."
            rows="3"
          />
          <p class="text-xs text-muted-foreground">
            {{ (form.meta_description?.length || 0) }}/160 simbolių
          </p>
        </div>
        <div class="grid gap-4 lg:grid-cols-2">
          <div class="space-y-2">
            <Label for="featured_image">Pagrindinė nuotrauka</Label>
            <Input
              id="featured_image"
              v-model="form.featured_image"
              type="text"
              placeholder="Nuotraukos URL (naudojamas socialiniuose tinkluose)..."
            />
          </div>
          <div class="space-y-2">
            <Label>Paskelbimo laikas</Label>
            <DateTimePicker v-model="publishTimeDate" placeholder="Pasirinkite paskelbimo laiką..." />
          </div>
        </div>
      </div>
    </FormElement>

    <RichContentFormElement v-model="form.content.parts" />
  </AdminForm>
</template>

<script setup lang="ts">
import { computed, watch, h } from "vue";
import { useForm, usePage } from "@inertiajs/vue3";
import latinize from "latinize";
import { PlusIcon, XIcon } from "lucide-vue-next";

import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/Components/ui/select";
import { Switch } from "@/Components/ui/switch";
import { Textarea } from "@/Components/ui/textarea";
import { DateTimePicker } from "@/Components/ui/date-picker";

import FormElement from "./FormElement.vue";
import RichContentFormElement from "../RichContentFormElement.vue";
import AdminForm from "./AdminForm.vue";

const props = defineProps<{
  categories: App.Entities.Category[];
  page: App.Entities.Page;
  otherLangPages?: App.Entities.Page[];
  rememberKey?: "CreatePage"
}>();

defineEmits<{
  (event: "submit:form", form: unknown): void;
  (event: "delete"): void;
}>();

// Initialize form with page data
const form = props.rememberKey
  ? useForm(props.rememberKey, { ...props.page, layout: props.page.layout || 'default', highlights: props.page.highlights || [], meta_description: props.page.meta_description || '', featured_image: props.page.featured_image || '', publish_time: props.page.publish_time || null } as any)
  : useForm({ ...props.page, layout: props.page.layout || 'default', highlights: props.page.highlights || [], meta_description: props.page.meta_description || '', featured_image: props.page.featured_image || '', publish_time: props.page.publish_time || null } as any);

// Ensure highlights is always an array
if (!Array.isArray(form.highlights)) {
  form.highlights = [];
}

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
    description: 'Klasikinis išdėstymas su šonine juosta',
    icon: DefaultLayoutIcon,
  },
  {
    value: 'wide',
    label: 'Platus',
    description: 'Visą plotį naudojantis išdėstymas',
    icon: WideLayoutIcon,
  },
  {
    value: 'focused',
    label: 'Susikaupęs',
    description: 'Centruotas turinys lengvam skaitymui',
    icon: FocusedLayoutIcon,
  },
];

const otherPageOptions = computed(() => {
  if (props.rememberKey === "CreatePage") {
    return [];
  }

  if (props.otherLangPages === undefined) {
    return [];
  }

  return props.otherLangPages
    .map((page) => ({
      value: page.id,
      label: `${page.title} (${page.tenant?.shortname})`,
    }))
    .reverse();
});

const languageOptions = [
  { value: "lt", label: "Lietuvių" },
  { value: "en", label: "English" },
];

// Date/time picker compatibility
const publishTimeDate = computed({
  get: () => form.publish_time ? new Date(form.publish_time) : undefined,
  set: (val: Date | undefined) => {
    form.publish_time = val ? val.toISOString() : null;
  }
});

// Highlights management
function addHighlight() {
  if (form.highlights.length < 3) {
    form.highlights.push('');
  }
}

function removeHighlight(index: number) {
  form.highlights.splice(index, 1);
}

function updateContents() {
  form.content = usePage().props.flash.data?.content;
}

// Watch form.title and update form.permalink for new pages
if (props.rememberKey == "CreatePage") {
  watch(
    () => form.title,
    (title) => {
      let latinizedTitle = latinize(title);
      form.permalink = latinizedTitle
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, "-")
        .replace(/-+/g, "-")
        .replace(/^-+/, "")
        .replace(/-+$/, "")
        .substring(0, 30);
    }
  );
}

// Handle other_lang_id sentinel value
watch(
  () => form.other_lang_id,
  (value) => {
    if (value === '__none__') {
      form.other_lang_id = null;
    }
  }
);
</script>
