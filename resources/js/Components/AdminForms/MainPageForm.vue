<template>
  <NForm :model="form" label-placement="top">
    <div class="flex flex-col">
      <FormElement>
        <template #title> {{ $t("forms.context.main_info") }} </template>
        <NFormItem label="Mygtuko tekstas">
          <NInput
            v-model:value="form.text"
            type="text"
            placeholder="Įrašyti tekstą..."
          />
        </NFormItem>
        <NFormItem label="Padalinys, kuriam priklauso institucija" :span="2">
          <NSelect
            v-model:value="form.padalinys_id"
            :options="padaliniaiOptions"
            placeholder="VU SA X"
            clearable
          />
        </NFormItem>
        <NFormItem label="Kurios kalbos puslapyje rodoma?">
          <NSelect
            v-model:value="form.lang"
            :options="languageOptions"
            placeholder="Pasirinkti kalbą..."
          />
        </NFormItem>
      </FormElement>
      <FormElement>
        <template #title>Mygtuko informacija</template>
        <template #description
          >Pasirinkus tipą ir objektą, kolkas tipas visada pakeičiamas į
          "Nuoroda" ir sugeneruojama atitinkamo puslapio nuoroda.</template
        >
        <NFormItem label="Nuorodos tipas">
          <NSelect
            v-model:value="form.type"
            :options="mainPageType"
            :render-label="renderLabel"
            @update:value="handleTypeChange"
          ></NSelect>
        </NFormItem>
        <NFormItem v-if="form.type !== 'url'" label="Pasirinkite puslapį">
          <NSelect
            v-model:value="pageSelection"
            filterable
            :options="typeOptions"
            placeholder="Pasirinkti puslapį..."
            @update:value="createMainPageLink"
          />
        </NFormItem>
        <NFormItem label="Nuoroda">
          <NInput
            v-model:value="form.link"
            :disabled="form.type !== 'url'"
            type="text"
            placeholder=""
          />
        </NFormItem>
      </FormElement>
    </div>
    <div class="flex justify-end gap-2">
      <DeleteModelButton
        v-if="deleteModelRoute"
        :form="form"
        :model-route="deleteModelRoute"
      />
      <UpsertModelButton :form="form" :model-route="modelRoute" />
    </div>
  </NForm>
</template>

<script setup lang="tsx">
import { NForm, NFormItem, NIcon, NInput, NSelect } from "naive-ui";
import { computed, ref } from "vue";
import { router, useForm } from "@inertiajs/vue3";

import { Link24Regular } from "@vicons/fluent";
import DeleteModelButton from "@/Components/Buttons/DeleteModelButton.vue";
import FormElement from "./FormElement.vue";
import Icons from "@/Types/Icons/regular";
import UpsertModelButton from "@/Components/Buttons/UpsertModelButton.vue";

const props = defineProps<{
  mainPage: App.Entities.MainPage;
  padaliniaiOptions: Record<string, any>[];
  typeOptions: Record<string, any>[];
  modelRoute: string;
  deleteModelRoute?: string;
}>();

const form = useForm("mainPage", props.mainPage);
const pageSelection = ref(null);

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

const createMainPageLink = (value: string, option) => {
  if (form.type === "url") {
    return;
  }

  console.log(option.option);

  let subdomain =
    option.option.padalinys?.alias === "vusa"
      ? "www"
      : option.option.padalinys?.alias;

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
