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
        <NFormItem>
          <template #label>
            <div class="inline-flex items-center gap-2">
              <span>Padalinys, kuriam priklauso institucija</span>
              <NButton
                v-if="modelRoute === 'mainPage.update'"
                secondary
                tag="a"
                size="tiny"
                type="primary"
                round
                target="_blank"
                :href="
                  route('mainPage.edit-order', {
                    padalinys: mainPage.padalinys_id,
                    lang: mainPage.lang,
                  } as RouteParamsWithQueryOverload)
                "
              >
                Atnaujinti nuorodų tvarką
                <template #icon>
                  <NIcon :component="Icons.MAIN_PAGE" />
                </template>
              </NButton>
            </div>
          </template>
          <NSelect
            v-model:value="form.padalinys_id"
            :options="options"
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
      <NFormItem :show-feedback="false" v-if="form.type !== 'url'" label="Pasirinkite puslapį">
          <NSelect
            v-model:value="pageSelection"
            filterable
            :options="typeOptions"
            placeholder="Pasirinkti puslapį..."
            @update:value="createMainPageLink"
          />
        </NFormItem>
        <NFormItem :show-feedback="false" label="Nuoroda">
          <NInputGroup>
            <NInput
              v-model:value="form.link"
              :disabled="form.type !== 'url'"
              type="text"
              placeholder=""
            />
            <!-- link to form.link -->
            <NButton tag="a" :href="form.link" target="_blank">
              <template #icon>
                <NIcon :component="Open24Regular" />
              </template>
            </NButton>
          </NInputGroup>
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
import { Link, router, useForm } from "@inertiajs/vue3";
import {
  NButton,
  NForm,
  NFormItem,
  NIcon,
  NInput,
  NInputGroup,
  NSelect,
} from "naive-ui";
import { computed, ref } from "vue";

import { Link24Regular, Open24Regular } from "@vicons/fluent";
import DeleteModelButton from "@/Components/Buttons/DeleteModelButton.vue";
import FormElement from "./FormElement.vue";
import Icons from "@/Types/Icons/regular";
import UpsertModelButton from "@/Components/Buttons/UpsertModelButton.vue";
import type { RouteParamsWithQueryOverload } from "ziggy-js";

const props = defineProps<{
  mainPage: App.Entities.MainPage;
  padaliniaiOptions: Record<string, any>[];
  typeOptions: Record<string, any>[];
  modelRoute: string;
  deleteModelRoute?: string;
}>();

const form = useForm("mainPage", props.mainPage);
const pageSelection = ref(null);

const options = props.padaliniaiOptions.map((padalinys) => ({
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
