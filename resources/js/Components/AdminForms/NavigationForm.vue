<template>
    <NCard>
      <TransitionGroup ref="el" tag="div">
        <div v-for="content, index in contents" :key="content?.id ?? content?.key"
          class="relative grid w-full grid-cols-[24px,_1fr] gap-4 border border-zinc-300 p-3 shadow-sm first:rounded-t-lg last:rounded-b-lg dark:border-zinc-700/40 dark:bg-zinc-800/5">
          <NButton class="handle" style="height: 100%;" quaternary size="small">
            <template #icon>
              <NIcon :component="ReOrderDotsVertical24Regular" />
            </template>
          </NButton>
          <div class="flex flex-col gap-2">
            <NInput v-model:value="content.name" type="text" placeholder="Įrašyti pavadinimą..." />
            <NDynamicInput v-model:value="content.children" @create="onCreateChildren" show-sort-button>
              <template #create-button-default>
                Sukurti
              </template>
              <template #default="{ value }">
                <div
                  class="flex w-full flex-col gap-3 rounded-lg border border-zinc-200/60 bg-zinc-50/30 p-4 dark:border-zinc-800/50 dark:bg-zinc-800/20">
                <div class="grid grid-cols-[1fr,_1fr,_200px] gap-2">
                  <NFormItem required label="Pavadinimas" :show-feedback="false">
                    <NInput v-model:value="value.name" type="text" placeholder="Įrašyti pavadinimą..." />
                  </NFormItem>
                  <NFormItem :show-feedback="false" required label="Nuorodos stilius">
                    <NSelect
                      v-model:value="value.type"
                      :options="linkStyles(value)"
                      placeholder="Pasirinkti nuorodos stilių..."
                    />
                  </NFormItem>
                  <NFormItem required label="Stulpelis" :show-feedback="false">
                    <NSelect v-model:value="value.column" :options="columnOptions" />
                  </NFormItem>
                </div>
                <template v-if="value.type !== 'divider'">

                  <NFormItem label="Ikona" :show-feedback="false">
                    <template #label>
                      Ikona. <span class="text-zinc-400"> Ikonų ieškokite <a target="_blank" class="font-bold underline" href="https://icon-sets.iconify.design/fluent/">čia</a>. Suradę reikiamą ikoną pasirinkite iš sąrašo. </span>
                    </template>
                    <NSelect filterable clearable v-model:value="value.icon" :options="iconOptions ?? []" />
                  </NFormItem>

                  <div class="grid grid-cols-2 gap-2">
                    <NFormItem :show-feedback="false" label="Nuorodos tipas">
                      <NSelect
                      v-model:value="value.linkType"
                      :options="mainPageType"
                      :render-label="renderLabel"
                      @update:value="(changedValue) => handleTypeChange(changedValue, value)"
                    />
                    </NFormItem>
                    <NFormItem v-if="value.linkType !== 'url'" :show-feedback="false" label="Pasirinkite puslapį">
                      <NSelect
                      v-model:value="value.pageSelection"
                      filterable
                      :options="typeOptions"
                      placeholder="Pasirinkti puslapį..."
                      @update:value="(changedValue, option) => createMainPageLink(changedValue, option, value)"
                    />
                    </NFormItem>
                  </div>
                  <NFormItem required :show-feedback="false" label="Nuoroda">
                    <NInputGroup>
                      <NInput
                      v-model:value="value.url"
                      :disabled="value.linkType !== 'url'"
                      type="text"
                      placeholder=""
                    />
                      <!-- link to form.link -->
                      <NButton tag="a" :href="value.url" target="_blank">
                        <template #icon>
                          <NIcon :component="Open24Regular" />
                        </template>
                      </NButton>
                    </NInputGroup>
                  </NFormItem>
                  <NFormItem label="Aprašymas" :show-feedback="false">
                    <NInput v-model:value="value.description" type="textarea" placeholder="Įrašyti aprašymą..." />
                  </NFormItem>
                  <NFormItem label="Foninis paveikslėlis" :show-feedback="false">
                    <TiptapImageButton v-model:show-modal="showModal" @submit="value.image = $event" />
                    <!-- Remove image button -->
                    <NButton v-if="value.image" @click="value.image = null" type="error" size="small">
                      Ištrinti paveikslėlį
                    </NButton>
                    <img v-if="value.image" class="size-20 ml-4 object-cover" :src="value.image" alt="image" />
                  </NFormItem>
                </template>
                </div>
              </template>
            </NDynamicInput>
          </div>
        </div>
      </TransitionGroup>
      <div class="mb-6 mt-2 flex w-full gap-2">
        <NButton type="primary" @click="createMenuTrigger">
          Pridėti navigacijos elementą
        </NButton>
        <NButton @click="saveNavigation" type="success">
          Išsaugoti
        </NButton>
      </div>
    </NCard>
</template>

<script setup lang="tsx">
import { Link24Regular, Open24Regular, ReOrderDotsVertical24Regular } from "@vicons/fluent";
import { NButton, NCard, NDynamicInput, NFormItem, NIcon, NInput, NInputGroup, NSelect } from "naive-ui";
import { computed, reactive, ref } from "vue";
import { router } from "@inertiajs/vue3"
import { useSortable } from "@vueuse/integrations/useSortable";

import Icons from "@/Types/Icons/regular";
import TiptapImageButton from "@/Components/TipTap/TiptapImageButton.vue";

const props = defineProps<{
  navigation: App.Entities.Navigation;
  typeOptions?: any
}>();

const el = ref(null);
const contents = reactive(props.navigation);
const showModal = ref(false);

const linkStyles = (value) => [
  {
    value: 'link',
    label: 'Nuoroda',
  },
  { value: 'block-link',
    label: 'Nuoroda bloke',
  },
  { value: 'category-link',
    label: 'Kategorijos nuoroda',
  },
  {
    value: 'full-height-background-link',
    label: 'Pilno aukščio foninis nuorodos blokas',
    disabled: !value.image,
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

const currentLang = ref("lt");

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

const getIconOptions = async () => {
  const icons = fetch("https://api.iconify.design/collection?prefix=fluent");

  const data = await icons;

  const iconData = await data.json()

  return iconData;
};

const icons = await getIconOptions();

const iconOptions = computed(() => {
  return icons.uncategorized?.map((icon) => {
    return {
      value: icon,
      label: icon,
    };
  })
});

const renderLabel = (option: any) => {
  return (
    <div class="flex items-center">
      <NIcon class="mr-2" component={option.icon} />
      <span>{option.label}</span>
    </div>
  );
};

const handleTypeChange = (changedValue: string, value) => {
  router.reload({
    data: { type: changedValue },
    only: ["typeOptions"],
    onSuccess: () => {
      value.pageSelection = null;
    },
  });
};

const createMainPageLink = (changedValue: string, option, value) => {
  console.log(changedValue, option, value);
  if (value.linkType === "url") {
    return;
  }

  let subdomain =
    option.option.padalinys?.alias === "vusa"
      ? "www"
      : option.option.padalinys?.alias;

  if (value.linkType === "page") {
    value.url = route("page", {
      lang: option.option.lang,
      subdomain: subdomain,
      permalink: option.option.permalink,
    });
    return;
  }

  if (value.linkType === "news") {
    value.url = route("news", {
      lang: option.option.lang,
      news: option.option.permalink,
      newsString: "naujiena",
      subdomain: subdomain,
    });
    return;
  }

  if (value.linkType === "calendarEvent") {
    value.url = route("calendar.event", {
      lang: currentLang.value as string,
      calendar: option.option.id,
    });
    return;
  }

  if (value.linkType === "institution") {
    value.url = route("contacts.institution", {
      lang: currentLang.value as string,
      institution: option.option.id,
      subdomain: subdomain,
    });
    return;
  }
};

const createMenuTrigger = () => {
  contents.push({
    id: null,
    parent_id: 0,
    name: "",
    lang: currentLang.value,
    url: "",
    is_active: true,
    children: [],
  });
};

const onCreateChildren = (index) => {
  // Create a child item on menu trigger

  return {
    id: null,
    parent_id: null,
    name: "",
    lang: currentLang.value,
    url: "",
    is_active: true,
    type: "block-link",
    linkType: "url",
    pageSelection: null,
    column: 1,
  }

};

useSortable(el, contents, {
  handle: ".handle", animation: 100,
});

const saveNavigation = () => {
  router.post(route("navigation.updateAll"), {
    navigation: contents,
  });
};
</script>
