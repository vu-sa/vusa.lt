<template>
  <AdminForm :model="form" label-placement="top" @submit:form="$emit('submit:form', form)" @delete="$emit('delete')">
    <FormElement>
      <template #title>
        Pagrindinė informacija
      </template>
      <div class="grid gap-3 lg:grid-cols-2">
        <NFormItem required label="Pavadinimas">
          <NInput v-model:value="form.name" type="text" placeholder="Įrašyti pavadinimą..." />
        </NFormItem>
        <NFormItem required label="Stulpelis">
          <NSelect v-model:value="form.extra_attributes.column" :options="columnOptions" />
        </NFormItem>
        <NFormItem label="Tėvinis elementas">
          <NSelect v-model:value="form.parent_id" filterable
            :options="parentElements.map((element) => ({ value: element.id, label: element.name }))"
            placeholder="Pasirinkti tėvinį elementą..." clearable />
        </NFormItem>
      </div>
      <NFormItem :show-feedback="false" required label="Nuorodos stilius">
        <NSelect v-model:value="form.extra_attributes.type" :options="linkStyles(value)"
          placeholder="Pasirinkti nuorodos stilių..." />
      </NFormItem>
    </FormElement>
    <FormElement>
      <template #title>
        Nuoroda
      </template>

      <div class="grid grid-cols-2 gap-2">
        <NFormItem label="Nuorodos tipas">
          <NSelect v-model:value="form.linkType" :options="mainPageType" :render-label="renderLabel"
            @update:value="(changedValue) => handleTypeChange(changedValue)" />
        </NFormItem>
        <NFormItem v-if="form.linkType !== 'url'" label="Pasirinkite puslapį">
          <NSelect v-model:value="form.pageSelection" filterable :options="typeOptions"
            placeholder="Pasirinkti puslapį..."
            @update:value="(changedValue, option) => createMainPageLink(changedValue, option)" />
        </NFormItem>
      </div>
      <NFormItem required label="Nuoroda">
        <NInputGroup>
          <NInput v-model:value="form.url" :disabled="form.linkType !== 'url'" type="text" placeholder="" />
          <!-- link to form.link -->
          <NButton tag="a" :href="form.url" target="_blank">
            <template #icon>
              <IFluentOpen24Regular />
            </template>
          </NButton>
        </NInputGroup>
      </NFormItem>
    </FormElement>
    <template v-if="form.type !== 'divider'">
      <FluentIconSelect :icon="form.extra_attributes.icon"
        @update:icon="(value) => form.extra_attributes.icon = value" />
      <NFormItem label="Aprašymas">
        <NInput v-model:value="form.extra_attributes.description" type="textarea" placeholder="Įrašyti aprašymą..." />
      </NFormItem>
      <NFormItem label="Foninis paveikslėlis">
        <img v-if="form.extra_attributes.image" class="mr-4 size-20 object-cover" :src="form.extra_attributes.image"
          alt="image">
        <NButtonGroup>
          <TiptapImageButton v-model:show-modal="showModal" @submit="form.extra_attributes.image = $event" />
          <!-- Remove image button -->
          <NButton v-if="form.extra_attributes.image" type="error" size="small"
            @click="form.extra_attributes.image = null">
            Ištrinti paveikslėlį
          </NButton>
        </NButtonGroup>
      </NFormItem>
    </template>
  </AdminForm>
</template>

<script setup lang="tsx">
import { computed, ref } from "vue";
import { router, useForm, usePage } from "@inertiajs/vue3"

import Link24Regular from "~icons/fluent/link24-regular";

import FluentIconSelect from "../FormItems/FluentIconSelect.vue";
import FormElement from "./FormElement.vue";
import Icons from "@/Types/Icons/regular";
import TiptapImageButton from "@/Components/TipTap/TiptapImageButton.vue";
import AdminForm from "./AdminForm.vue";

const props = defineProps<{
  navigation: App.Entities.Navigation;
  parentElements: App.Entities.Navigation[];
  typeOptions: any
}>();

defineEmits<{
  (event: "submit:form", form: unknown): void;
  (event: "delete"): void;
}>();

const form = useForm("navigation", props.navigation);

const showModal = ref(false);

const linkStyles = (value) => [
  {
    value: 'link',
    label: 'Nuoroda',
  },
  {
    value: 'block-link',
    label: 'Nuoroda bloke',
  },
  {
    value: 'category-link',
    label: 'Kategorijos nuoroda',
  },
  {
    value: 'full-height-background-link',
    label: 'Pilno aukščio foninis nuorodos blokas',
    disabled: !form?.extra_attributes?.image,
  },
  {
    value: 'divider',
    label: 'Skirtukas',
  },
]

const columnOptions = [
  { value: 1, label: "1" },
  { value: 2, label: "2" },
  { value: 3, label: "3" },
]

const currentLang = usePage().props.app.locale;

const mainPageType = [
  {
    value: "url",
    label: "Nuoroda",
    icon: Link24Regular,
  },
  {
    value: "page",
    label: "Turinio puslapis",
    icon: Icons.PAGE,
  },
  {
    value: "news",
    label: "Naujiena",
    icon: Icons.NEWS,
  },
  {
    value: "calendarEvent",
    label: "Įvykis",
    icon: Icons.CALENDAR,
  },
  {
    value: "institution",
    label: "Institucija",
    icon: Icons.INSTITUTION,
  },
  {
    value: "category",
    label: "Kategorija",
    icon: Icons.CATEGORY,
  },
  // {
  //   value: "special-page",
  //   label: "Specialus puslapis",
  //   icon: Icons.PAGE,
  // },
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

const renderLabel = (option: any) => {
  return (
    <div class="flex items-center">
      <NIcon class="mr-2" component={option.icon} />
      <span>{option.label}</span>
    </div>
  );
};

const handleTypeChange = (changedValue: string) => {

  if (changedValue === "url") {
    return;
  }

  router.reload({
    data: { type: changedValue },
    only: ["typeOptions"],
    onSuccess: () => {
      form.pageSelection = null;
    },
  });
};

const createMainPageLink = (changedValue: string, option) => {
  if (form.linkType === "url") {
    return;
  }

  let subdomain =
    option.option.tenant?.alias === "vusa"
      ? "www"
      : option.option.tenant?.alias;

  if (form.linkType === "page") {
    form.url = route("page", {
      lang: option.option.lang,
      subdomain: subdomain,
      permalink: option.option.permalink,
    });
    return;
  }

  if (form.linkType === "news") {
    form.url = route("news", {
      lang: option.option.lang,
      news: option.option.permalink,
      newsString: "naujiena",
      subdomain: subdomain,
    });
    return;
  }

  if (form.linkType === "calendarEvent") {
    form.url = route("calendar.event", {
      lang: currentLang.value as string,
      calendar: option.option.id,
    });
    return;
  }

  if (form.linkType === "institution") {
    form.url = route("contacts.institution", {
      lang: currentLang.value as string,
      institution: option.option.id,
      subdomain: subdomain,
    });
    return;
  }

  if (form.linkType === "category") {
    form.url = route("category", {
      lang: currentLang.value as string,
      category: option.option.alias,
      subdomain: subdomain,
    });
    return;
  }
};
</script>
