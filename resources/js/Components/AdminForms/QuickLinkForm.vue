<template>
  <AdminForm :model="form" label-placement="top" @submit:form="$emit('submit:form', form)" @delete="$emit('delete')">
    <!-- Section 1: Basic Information -->
    <FormElement :section-number="1" :is-complete="basicInfoComplete" required>
      <template #title>
        {{ $t('Pagrindinė informacija') }}
      </template>
      <template #subtitle>
        {{ $t('Mygtuko tekstas, ikona ir kalba') }}
      </template>

      <div class="space-y-4">
        <FormFieldWrapper id="text" :label="$t('Mygtuko tekstas')" required :error="form.errors.text">
          <Input id="text" v-model="form.text" type="text" :placeholder="$t('Įrašyti tekstą...')" />
        </FormFieldWrapper>

        <div class="grid gap-4 sm:grid-cols-2">
          <FormFieldWrapper id="icon" :label="$t('Ikona')">
            <Suspense>
              <FluentIconSelect :icon="form.icon" @update:icon="(value) => form.icon = value" />
            </Suspense>
          </FormFieldWrapper>

          <FormFieldWrapper id="lang" :label="$t('Kalba')" required>
            <ToggleGroup v-model="form.lang" type="single" class="justify-start">
              <ToggleGroupItem value="lt" class="gap-2">
                <img src="https://hatscripts.github.io/circle-flags/flags/lt.svg" class="h-4 w-4 rounded-full">
                Lietuvių
              </ToggleGroupItem>
              <ToggleGroupItem value="en" class="gap-2">
                <img src="https://hatscripts.github.io/circle-flags/flags/gb.svg" class="h-4 w-4 rounded-full">
                English
              </ToggleGroupItem>
            </ToggleGroup>
          </FormFieldWrapper>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
          <div class="space-y-2">
            <FormFieldWrapper id="tenant_id" :label="$t('Padalinys')"
              :hint="$t('Padalinys, kuriam priklauso nuoroda')">
              <SingleSelect v-model="selectedTenant" :options="tenantOptions" value-field="value" label-field="label"
                :placeholder="$t('Pasirinkti padalinį...')" />
            </FormFieldWrapper>
            <Button v-if="form.tenant_id" variant="secondary" size="sm" as="a"
              :href="route('quickLinks.index', { tenant: form.tenant_id, lang: form.lang ?? 'lt' })">
              <component :is="QuickLinkIcon" class="h-4 w-4" />
              {{ $t('Tvarkyti eiliškumą') }}
            </Button>
          </div>

          <FormFieldWrapper id="is_important" :label="$t('Ar svarbus?')"
            :hint="$t('Ar rodyti mygtuką kaip svarbų?')">
            <Switch v-model="form.is_important" />
          </FormFieldWrapper>
        </div>
      </div>
    </FormElement>

    <!-- Section 2: Link Target -->
    <FormElement :section-number="2" :is-complete="linkTargetComplete" required>
      <template #title>
        {{ $t('Nuorodos tikslas') }}
      </template>
      <template #description>
        <p>{{ $t('Pasirinkite, į kur puslapį ar turinį veda ši nuoroda. Pasirinkus tipą ir objektą, nuoroda sugeneruojama automatiškai.') }}</p>
      </template>

      <div class="space-y-4">
        <FormFieldWrapper id="type" :label="$t('Nuorodos tipas')" required>
          <ToggleGroup v-model="form.type" type="single" class="justify-start flex-wrap"
            @update:model-value="handleTypeChange">
            <ToggleGroupItem v-for="opt in quickLinksType" :key="opt.value" :value="opt.value" class="gap-2">
              <component :is="opt.icon" class="h-4 w-4" />
              {{ opt.label }}
            </ToggleGroupItem>
          </ToggleGroup>
        </FormFieldWrapper>

        <FormFieldWrapper v-if="form.type !== 'url'" id="page" :label="$t('Pasirinkite objektą')" required>
          <SingleSelect v-model="selectedPage" :options="typeOptions" label-field="label" value-field="value"
            :placeholder="$t('Pasirinkti puslapį...')" />
        </FormFieldWrapper>

        <FormFieldWrapper id="link" :label="$t('Nuoroda')" required :error="form.errors.link"
          :helper-text="linkHelperText">
          <div class="flex gap-1">
            <Input id="link" v-model="form.link" :disabled="form.type !== 'url'" type="text"
              :placeholder="$t('Nuoroda...')" />
            <Button variant="outline" size="icon" as="a" :href="form.link" target="_blank">
              <IFluentOpen24Regular />
            </Button>
          </div>
        </FormFieldWrapper>
      </div>
    </FormElement>
  </AdminForm>
</template>

<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import FluentIconSelect from '../FormItems/FluentIconSelect.vue';

import AdminForm from './AdminForm.vue';
import FormElement from './FormElement.vue';
import FormFieldWrapper from './FormFieldWrapper.vue';

import Link24Regular from '~icons/fluent/link24-regular';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Switch } from '@/Components/ui/switch';
import { SingleSelect } from '@/Components/ui/single-select';
import { ToggleGroup, ToggleGroupItem } from '@/Components/ui/toggle-group';
import { CalendarIcon, CategoryIcon, InstitutionIcon, NewsIcon, PageIcon, QuickLinkIcon } from '@/Components/icons';

const props = defineProps<{
  quickLink: App.Entities.QuickLink;
  tenantOptions: Record<string, any>[];
  typeOptions?: Record<string, any>[];
  rememberKey?: 'CreateQuickLink';
}>();

defineEmits<{
  (event: 'submit:form', form: unknown): void;
  (event: 'delete'): void;
}>();

const isCreate = computed(() => props.rememberKey === 'CreateQuickLink');

const form = props.rememberKey
  ? useForm(props.rememberKey, props.quickLink)
  : useForm(props.quickLink);

const pageSelection = ref<string | null>(null);

// Bridge: SingleSelect operates on full objects, pageSelection stores string ID
const selectedPage = computed({
  get: () => typeOptions.value.find(opt => String(opt.value) === pageSelection.value) ?? null,
  set: (val: { value: string | number; label: string; option: any } | null) => {
    pageSelection.value = val?.value ? String(val.value) : null;
    handlePageSelection(pageSelection.value);
  },
});

const tenantOptions = computed(() =>
  props.tenantOptions.map(padalinys => ({
    value: padalinys.id,
    label: padalinys.shortname,
  })),
);

const selectedTenant = computed({
  get: () => tenantOptions.value.find(opt => String(opt.value) === String(form.tenant_id)) ?? null,
  set: (val: { value: string | number; label: string } | null) => {
    form.tenant_id = val?.value ?? null;
  },
});

const quickLinksType = computed(() => [
  {
    value: 'url',
    label: $t('Nuoroda'),
    icon: Link24Regular,
  },
  {
    value: 'page',
    label: $t('Turinio puslapis'),
    icon: PageIcon,
  },
  {
    value: 'news',
    label: $t('Naujiena'),
    icon: NewsIcon,
  },
  {
    value: 'calendarEvent',
    label: $t('Įvykis'),
    icon: CalendarIcon,
  },
  {
    value: 'institution',
    label: $t('Institucija'),
    icon: InstitutionIcon,
  },
  {
    value: 'category',
    label: $t('Kategorija'),
    icon: CategoryIcon,
  },
]);

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

// Section completion states
const basicInfoComplete = computed(() =>
  (form.text?.length || 0) >= 1 && Boolean(form.lang),
);

const linkTargetComplete = computed(() =>
  Boolean(form.type && form.link?.length > 0),
);

const linkHelperText = computed(() => {
  if (form.type === 'url') {
    return $t('Įveskite norimą nuorodą');
  }
  return $t('Nuoroda sugeneruota automatiškai pagal pasirinktą objektą');
});

const handleTypeChange = (value: string) => {
  form.defaults();
  router.reload({
    data: { type: value },
    only: ['typeOptions'],
    onSuccess: () => {
      pageSelection.value = null;
    },
  });
};

const handlePageSelection = (value: string | null) => {
  if (!value || form.type === 'url') {
    return;
  }

  const selectedOption = typeOptions.value.find(opt => String(opt.value) === value);
  if (!selectedOption) return;

  const optionData = selectedOption.option;

  const subdomain
    = optionData.tenant?.alias === 'vusa'
      ? 'www'
      : optionData.tenant?.alias;

  if (form.type === 'page') {
    form.link = route('page', {
      lang: optionData.lang,
      subdomain,
      permalink: optionData.permalink,
    });
    return;
  }

  if (form.type === 'news') {
    form.link = route('news', {
      lang: optionData.lang,
      news: optionData.permalink,
      newsString: 'naujiena',
      subdomain,
    });
    return;
  }

  if (form.type === 'calendarEvent') {
    form.link = route('calendar.event', {
      lang: form.lang as string,
      calendar: optionData.id,
    });
    return;
  }

  if (form.type === 'institution') {
    form.link = route('contacts.institution', {
      lang: form.lang as string,
      institution: optionData.id,
      subdomain,
    });
    return;
  }

  if (form.type === 'category') {
    form.link = route('category', {
      lang: form.lang as string,
      category: optionData.id,
      subdomain,
    });
  }
};
</script>
