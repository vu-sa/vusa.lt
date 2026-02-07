<template>
  <AdminForm :model="form" label-placement="top" @submit:form="$emit('submit:form', form)" @delete="$emit('delete')">
    <FormElement>
      <template #title>
        {{ $t("forms.context.main_info") }}
      </template>
      <FormFieldWrapper id="text" label="Mygtuko tekstas">
        <Input v-model="form.text" type="text" placeholder="Įrašyti tekstą..." />
      </FormFieldWrapper>
      <Suspense>
        <FluentIconSelect :icon="form.icon" @update:icon="(value) => form.icon = value" />
      </Suspense>
      <FormFieldWrapper id="is_important" label="Ar svarbus?">
        <Switch :checked="form.is_important" @update:checked="val => form.is_important = val" />
      </FormFieldWrapper>
      <div class="space-y-2">
        <Label class="inline-flex items-center gap-2">
          Padalinys, kuriam priklauso institucija
          <Button v-if="quickLink.tenant_id" variant="secondary" size="xs" as="a" target="_blank" :href="route('quickLinks.edit-order', {
            tenant: quickLink.tenant_id,
            lang: quickLink.lang,
          })">
            <component :is="Icons.QUICK_LINK" class="h-4 w-4" />
            Atnaujinti nuorodų tvarką
          </Button>
        </Label>
        <Select
          :model-value="form.tenant_id != null ? String(form.tenant_id) : undefined"
          @update:model-value="val => form.tenant_id = val === '__none__' ? null : val"
        >
          <SelectTrigger>
            <SelectValue placeholder="VU SA X" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="__none__">-- Nėra --</SelectItem>
            <SelectItem v-for="opt in options" :key="opt.value" :value="String(opt.value)">
              {{ opt.label }}
            </SelectItem>
          </SelectContent>
        </Select>
      </div>
      <FormFieldWrapper id="lang" label="Kurios kalbos puslapyje rodoma?">
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
      </FormFieldWrapper>
    </FormElement>
    <FormElement>
      <template #title>
        Mygtuko informacija
      </template>
      <template #description>
        Pasirinkus tipą ir objektą, kolkas tipas visada pakeičiamas į
        "Nuoroda" ir sugeneruojama atitinkamo puslapio nuoroda.
      </template>
      <FormFieldWrapper id="type" label="Nuorodos tipas">
        <Select
          :model-value="form.type"
          @update:model-value="val => { form.type = val; handleTypeChange(val); }"
        >
          <SelectTrigger>
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            <SelectItem v-for="opt in quickLinksType" :key="opt.value" :value="opt.value">
              <span class="flex items-center gap-2">
                <component :is="opt.icon" class="h-4 w-4" />
                {{ opt.label }}
              </span>
            </SelectItem>
          </SelectContent>
        </Select>
      </FormFieldWrapper>
      <FormFieldWrapper v-if="form.type !== 'url'" id="page" label="Pasirinkite puslapį">
        <Select :model-value="pageSelection ?? undefined" @update:model-value="handlePageSelection">
          <SelectTrigger>
            <SelectValue placeholder="Pasirinkti puslapį..." />
          </SelectTrigger>
          <SelectContent>
            <SelectItem v-for="opt in typeOptions" :key="opt.value" :value="String(opt.value)">
              {{ opt.label }}
            </SelectItem>
          </SelectContent>
        </Select>
      </FormFieldWrapper>
      <FormFieldWrapper id="link" label="Nuoroda">
        <div class="flex gap-1">
          <Input v-model="form.link" :disabled="form.type !== 'url'" type="text" placeholder="" />
          <!-- link to form.link -->
          <Button variant="outline" size="icon" as="a" :href="form.link" target="_blank">
            <IFluentOpen24Regular />
          </Button>
        </div>
      </FormFieldWrapper>
    </FormElement>
  </AdminForm>
</template>

<script setup lang="ts">
import { router, useForm } from "@inertiajs/vue3";
import { computed, ref } from "vue";

import Link24Regular from "~icons/fluent/link24-regular";

import AdminForm from "./AdminForm.vue";
import FormElement from "./FormElement.vue";
import FormFieldWrapper from "./FormFieldWrapper.vue";
import Icons from "@/Types/Icons/regular";
import FluentIconSelect from "../FormItems/FluentIconSelect.vue";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import { Switch } from "@/Components/ui/switch";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/Components/ui/select";

const props = defineProps<{
  quickLink: App.Entities.QuickLink;
  tenantOptions: Record<string, any>[];
  typeOptions: Record<string, any>[];
  rememberKey?: "CreateQuickLink";
}>();

defineEmits<{
  (event: "submit:form", form: unknown): void;
  (event: "delete"): void;
}>();

const form = props.rememberKey
  ? useForm(props.rememberKey, props.quickLink)
  : useForm(props.quickLink);

const pageSelection = ref<string | null>(null);

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

const handlePageSelection = (value: string) => {
  pageSelection.value = value;

  if (form.type === "url") {
    return;
  }

  const selectedOption = typeOptions.value.find(opt => String(opt.value) === value);
  if (!selectedOption) return;

  const optionData = selectedOption.option;

  let subdomain =
    optionData.tenant?.alias === "vusa"
      ? "www"
      : optionData.tenant?.alias;

  if (form.type === "page") {
    form.link = route("page", {
      lang: optionData.lang,
      subdomain: subdomain,
      permalink: optionData.permalink,
    });
    return;
  }

  if (form.type === "news") {
    form.link = route("news", {
      lang: optionData.lang,
      news: optionData.permalink,
      newsString: "naujiena",
      subdomain: subdomain,
    });
    return;
  }

  if (form.type === "calendarEvent") {
    form.link = route("calendar.event", {
      lang: form.lang as string,
      calendar: optionData.id,
    });
    return;
  }

  if (form.type === "institution") {
    form.link = route("contacts.institution", {
      lang: form.lang as string,
      institution: optionData.id,
      subdomain: subdomain,
    });
    return;
  }

  if (form.type === "category") {
    form.link = route("category", {
      lang: form.lang as string,
      category: optionData.id,
      subdomain: subdomain,
    });
    return;
  }
};
</script>
