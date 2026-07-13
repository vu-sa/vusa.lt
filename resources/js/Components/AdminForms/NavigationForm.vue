<template>
  <AdminForm :model="form" label-placement="top" @submit:form="$emit('submit:form', form)" @delete="$emit('delete')">
    <FormElement>
      <template #title>
        Pagrindinė informacija
      </template>
      <div class="grid gap-3 lg:grid-cols-2">
        <FormFieldWrapper id="name" label="Pavadinimas" required>
          <Input v-model="form.name" type="text" placeholder="Įrašyti pavadinimą..." />
        </FormFieldWrapper>
        <FormFieldWrapper id="column" label="Stulpelis" required>
          <Select
            :model-value="form.extra_attributes.column != null ? String(form.extra_attributes.column) : undefined"
            @update:model-value="val => form.extra_attributes.column = Number(val)"
          >
            <SelectTrigger>
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="opt in columnOptions" :key="opt.value" :value="String(opt.value)">
                {{ opt.label }}
              </SelectItem>
            </SelectContent>
          </Select>
        </FormFieldWrapper>
        <FormFieldWrapper id="parent_id" label="Tėvinis elementas">
          <SingleSelect
            v-model="selectedParent"
            :options="parentOptions"
            label-field="label"
            value-field="value"
            placeholder="Pasirinkti tėvinį elementą..."
          />
        </FormFieldWrapper>
      </div>
      <FormFieldWrapper id="link_style" label="Nuorodos stilius" required>
        <Select v-model="form.extra_attributes.type">
          <SelectTrigger>
            <SelectValue placeholder="Pasirinkti nuorodos stilių..." />
          </SelectTrigger>
          <SelectContent>
            <SelectItem
              v-for="opt in linkStyleOptions"
              :key="opt.value"
              :value="opt.value"
              :disabled="opt.disabled"
            >
              {{ opt.label }}
            </SelectItem>
          </SelectContent>
        </Select>
      </FormFieldWrapper>
    </FormElement>
    <FormElement>
      <template #title>
        Nuoroda
      </template>

      <div class="grid grid-cols-2 gap-2">
        <FormFieldWrapper id="linkType" label="Nuorodos tipas">
          <Select
            :model-value="form.linkType"
            @update:model-value="val => { form.linkType = val; handleTypeChange(val); }"
          >
            <SelectTrigger>
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="opt in quickLinkType" :key="opt.value" :value="opt.value">
                <span class="flex items-center gap-2">
                  <component :is="opt.icon" class="h-4 w-4" />
                  {{ opt.label }}
                </span>
              </SelectItem>
            </SelectContent>
          </Select>
        </FormFieldWrapper>
        <FormFieldWrapper v-if="form.linkType !== 'url'" id="page" label="Pasirinkite puslapį">
          <SingleSelect
            v-model="selectedPage"
            :options="typeOptions"
            label-field="label"
            value-field="value"
            placeholder="Pasirinkti puslapį..."
          />
        </FormFieldWrapper>
      </div>
      <FormFieldWrapper id="url" label="Nuoroda" required>
        <div class="flex gap-1">
          <Input v-model="form.url" :disabled="form.linkType !== 'url'" type="text" placeholder="" />
          <!-- link to form.link -->
          <Button variant="outline" size="icon" as="a" :href="form.url" target="_blank">
            <IFluentOpen24Regular />
          </Button>
        </div>
      </FormFieldWrapper>
    </FormElement>
    <template v-if="form.extra_attributes.type !== 'divider'">
      <FluentIconSelect :icon="form.extra_attributes.icon ?? null"
        @update:icon="(value) => form.extra_attributes.icon = value" />
      <FormFieldWrapper id="description" label="Aprašymas">
        <Textarea v-model="form.extra_attributes.description" placeholder="Įrašyti aprašymą..." />
      </FormFieldWrapper>
      <FormFieldWrapper id="small_text" label="Mažas tekstas">
        <Input v-model="form.extra_attributes.small_text" type="text" placeholder="Įrašyti trumpą tekstą, atkreipiantį dėmesį į nuorodą..." />
      </FormFieldWrapper>
      <FormFieldWrapper id="image" label="Foninis paveikslėlis">
        <img v-if="form.extra_attributes.image" class="mr-4 size-20 object-cover" :src="form.extra_attributes.image"
          alt="image">
        <ButtonGroup>
          <TiptapImageButton @submit="form.extra_attributes.image = $event">
            <Button variant="outline" size="sm" type="button">
              Pasirinkti paveikslėlį
            </Button>
          </TiptapImageButton>
          <!-- Remove image button -->
          <Button v-if="form.extra_attributes.image" variant="destructive" size="sm"
            @click="form.extra_attributes.image = null">
            Ištrinti paveikslėlį
          </Button>
        </ButtonGroup>
      </FormFieldWrapper>
    </template>
  </AdminForm>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';

import FluentIconSelect from '../FormItems/FluentIconSelect.vue';

import FormElement from './FormElement.vue';
import FormFieldWrapper from './FormFieldWrapper.vue';
import AdminForm from './AdminForm.vue';

import { resolveTenantSubdomain } from '@/Composables/useTenantSubdomain';
import TiptapImageButton from '@/Components/TipTap/TiptapImageButton.vue';
import Link24Regular from '~icons/fluent/link24-regular';
import { Button } from '@/Components/ui/button';
import { ButtonGroup } from '@/Components/ui/button-group';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import { SingleSelect } from '@/Components/ui/single-select';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { CalendarIcon, CategoryIcon, InstitutionIcon, NewsIcon, PageIcon } from '@/Components/icons';

const props = defineProps<{
  navigation: App.Entities.Navigation;
  parentElements: App.Entities.Navigation[];
  typeOptions: any;
  rememberKey?: 'CreateNavigation';
}>();

defineEmits<{
  (event: 'submit:form', form: unknown): void;
  (event: 'delete'): void;
}>();

const form = props.rememberKey
  ? useForm(props.rememberKey, props.navigation)
  : useForm(props.navigation);

const linkStyleOptions = computed(() => [
  {
    value: 'link',
    label: 'Nuoroda',
    disabled: false,
  },
  {
    value: 'block-link',
    label: 'Nuoroda bloke',
    disabled: false,
  },
  {
    value: 'category-link',
    label: 'Kategorijos nuoroda',
    disabled: false,
  },
  {
    value: 'full-height-background-link',
    label: 'Pilno aukščio foninis nuorodos blokas',
    disabled: !form?.extra_attributes?.image,
  },
  {
    value: 'divider',
    label: 'Skirtukas',
    disabled: false,
  },
]);

const columnOptions = [
  { value: 1, label: '1' },
  { value: 2, label: '2' },
  { value: 3, label: '3' },
];

const parentOptions = computed(() => [
  { value: '__none__', label: '-- Nėra --' },
  ...props.parentElements.map(e => ({ value: String(e.id), label: e.name })),
]);

// Bridge: SingleSelect operates on full objects, form stores parent_id
const selectedParent = computed({
  get: () => parentOptions.value.find(p => p.value === String(form.parent_id ?? '__none__')) ?? parentOptions.value[0],
  set: (val: { value: string; label: string } | null) => {
    form.parent_id = val?.value === '__none__' ? null : val?.value ?? null;
  },
});

// Bridge: SingleSelect operates on full objects, form stores pageSelection
const selectedPage = computed({
  get: () => typeOptions.value.find(opt => String(opt.value) === form.pageSelection) ?? null,
  set: (val: { value: string | number; label: string; option: any } | null) => {
    form.pageSelection = val?.value ? String(val.value) : null;
    handlePageSelection(form.pageSelection);
  },
});

const currentLang = usePage().props.app.locale;

const quickLinkType = [
  {
    value: 'url',
    label: 'Nuoroda',
    icon: Link24Regular,
  },
  {
    value: 'page',
    label: 'Turinio puslapis',
    icon: PageIcon,
  },
  {
    value: 'news',
    label: 'Naujiena',
    icon: NewsIcon,
  },
  {
    value: 'calendarEvent',
    label: 'Įvykis',
    icon: CalendarIcon,
  },
  {
    value: 'institution',
    label: 'Institucija',
    icon: InstitutionIcon,
  },
  {
    value: 'category',
    label: 'Kategorija',
    icon: CategoryIcon,
  },
];

const typeOptions = computed(() => {
  if (!props.typeOptions) {
    return [];
  }

  return props.typeOptions.map((option) => {
    return {
      value: option.id,
      label: option.title ?? option.name,
      option,
    };
  });
});

const handleTypeChange = (changedValue: string) => {
  if (changedValue === 'url') {
    return;
  }

  router.reload({
    data: { type: changedValue },
    only: ['typeOptions'],
    onSuccess: () => {
      form.pageSelection = null;
    },
  });
};

const handlePageSelection = (value: string) => {
  form.pageSelection = value;

  if (form.linkType === 'url') {
    return;
  }

  const selectedOption = typeOptions.value.find(opt => String(opt.value) === value);
  if (!selectedOption) return;

  const optionData = selectedOption.option;

  const subdomain = resolveTenantSubdomain(optionData.tenant?.id);

  if (form.linkType === 'page') {
    form.url = route('page', {
      lang: optionData.lang,
      subdomain,
      permalink: optionData.permalink,
    });
    return;
  }

  if (form.linkType === 'news') {
    form.url = route('news', {
      lang: optionData.lang,
      news: optionData.permalink,
      newsString: 'naujiena',
      subdomain,
    });
    return;
  }

  if (form.linkType === 'calendarEvent') {
    form.url = route('calendar.event', {
      lang: currentLang.value as string,
      calendar: optionData.id,
    });
    return;
  }

  if (form.linkType === 'institution') {
    form.url = route('contacts.institution', {
      lang: currentLang.value as string,
      institution: optionData.id,
      subdomain,
    });
    return;
  }

  if (form.linkType === 'category') {
    form.url = route('category', {
      lang: currentLang.value as string,
      category: optionData.alias,
      subdomain,
    });
  }
};
</script>
