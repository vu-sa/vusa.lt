<template>
  <AdminForm :model="form" label-placement="top" @submit:form="$emit('submit:form', form)" @delete="$emit('delete')">
    <FormElement>
      <template #title>
        {{ $t("forms.context.main_info") }}
      </template>
      <NFormItem label="Mygtuko tekstas">
        <NInput v-model:value="form.text" type="text" placeholder="Įrašyti tekstą..." />
      </NFormItem>
      <Suspense>
        <FluentIconSelect :icon="form.icon" @update:icon="(value) => form.icon = value" />
      </Suspense>
      <NFormItem label="Ar svarbus?">
        <NSwitch v-model:value="form.is_important" />
      </NFormItem>
      <NFormItem>
        <template #label>
          <div class="inline-flex items-center gap-2">
            <span>Padalinys, kuriam priklauso institucija</span>
            <NButton v-if="quickLink.tenant_id" secondary tag="a" size="tiny" type="primary" round target="_blank" :href="route('quickLinks.edit-order', {
              tenant: quickLink.tenant_id,
              lang: quickLink.lang,
            })
              ">
              Atnaujinti nuorodų tvarką
              <template #icon>
                <NIcon :component="Icons.QUICK_LINK" />
              </template>
            </NButton>
          </div>
        </template>
        <NSelect v-model:value="form.tenant_id" :options="options" placeholder="VU SA X" clearable />
      </NFormItem>
      <NFormItem label="Kurios kalbos puslapyje rodoma?">
        <NSelect v-model:value="form.lang" :options="languageOptions" placeholder="Pasirinkti kalbą..." />
      </NFormItem>
    </FormElement>
    <FormElement>
      <template #title>
        Mygtuko informacija
      </template>
      <template #description>
        Pasirinkus tipą ir objektą, kolkas tipas visada pakeičiamas į
        "Nuoroda" ir sugeneruojama atitinkamo puslapio nuoroda.
      </template>
      <NFormItem label="Nuorodos tipas">
        <NSelect v-model:value="form.type" :options="quickLinksType" :render-label="renderLabel"
          @update:value="handleTypeChange" />
      </NFormItem>
      <NFormItem v-if="form.type !== 'url'" label="Pasirinkite puslapį">
        <NSelect v-model:value="pageSelection" filterable :options="typeOptions" placeholder="Pasirinkti puslapį..."
          @update:value="createQuickLinkLink" />
      </NFormItem>
      <NFormItem :show-feedback="false" label="Nuoroda">
        <NInputGroup>
          <NInput v-model:value="form.link" :disabled="form.type !== 'url'" type="text" placeholder="" />
          <!-- link to form.link -->
          <NButton tag="a" :href="form.link" target="_blank">
            <template #icon>
              <IFluentOpen24Regular />
            </template>
          </NButton>
        </NInputGroup>
      </NFormItem>
    </FormElement>
  </AdminForm>
</template>

<script setup lang="tsx">
import { router, useForm } from "@inertiajs/vue3";
import { computed, ref } from "vue";

import Link24Regular from "~icons/fluent/link24-regular"

import AdminForm from "./AdminForm.vue";
import FormElement from "./FormElement.vue";
import Icons from "@/Types/Icons/regular";
import FluentIconSelect from "../FormItems/FluentIconSelect.vue";

const props = defineProps<{
  quickLink: App.Entities.QuickLink;
  tenantOptions: Record<string, any>[];
  typeOptions: Record<string, any>[];
}>();

defineEmits<{
  (event: "submit:form", form: unknown): void;
  (event: "delete"): void;
}>();

const form = useForm("quickLinks", props.quickLink);
const pageSelection = ref(null);

const options = props.tenantOptions.map((padalinys) => ({
  value: padalinys.id,
  label: padalinys.shortname,
}));

const languageOptions = [
  {
    value: "lt",
    label: "Lietuvių",
  },
  {
    value: "en",
    label: "English",
  },
];

const quickLinksType = [
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
  }
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

const handleTypeChange = (value: string) => {
  router.reload({
    data: { type: value },
    only: ["typeOptions"],
    onSuccess: () => {
      form.link = null;
      pageSelection.value = null;
    },
  });
};

const createQuickLinkLink = (value: string, option) => {
  if (form.type === "url") {
    return;
  }

  let subdomain =
    option.option.tenant?.alias === "vusa"
      ? "www"
      : option.option.tenant?.alias;

  if (form.type === "page") {
    form.link = route("page", {
      lang: option.option.lang,
      subdomain: subdomain,
      permalink: option.option.permalink,
    });
    return;
  }

  if (form.type === "news") {
    form.link = route("news", {
      lang: option.option.lang,
      news: option.option.permalink,
      newsString: "naujiena",
      subdomain: subdomain,
    });
    return;
  }

  if (form.type === "calendarEvent") {
    form.link = route("calendar.event", {
      lang: form.lang as string,
      calendar: option.option.id,
    });
    return;
  }

  if (form.type === "institution") {
    form.link = route("contacts.institution", {
      lang: form.lang as string,
      institution: option.option.id,
      subdomain: subdomain,
    });
    return;
  }

  if (form.type === "category") {
    form.link = route("category", {
      lang: form.lang as string,
      category: option.option.id,
      subdomain: subdomain,
    });
    return;
  }
};

const renderLabel = (option: any) => {
  return (
    <div class="flex items-center">
      <NIcon class="mr-2" component={option.icon} />
      <span>{option.label}</span>
    </div>
  );
};
</script>
