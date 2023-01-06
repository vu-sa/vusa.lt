<template>
  <NForm :model="form" label-placement="top">
    <NTabs animated type="card" pane-class="overflow-x-auto">
      <NTabPane display-directive="show" name="lt" tab="🇱🇹">
        <NGrid cols="1 s:4 l:6" responsive="screen" :x-gap="24">
          <NFormItemGi label="Pavadinimas" :span="2" required>
            <NInput
              v-model:value="form.title"
              type="text"
              placeholder="Įrašyti pavadinimą..."
            />
          </NFormItemGi>

          <NFormItemGi label="Renginio vieta" :span="2">
            <NInput
              v-model:value="form.location"
              type="text"
              placeholder="AB Imeda poilsiavietė, Kiškiai, Ignalinos raj."
            />
          </NFormItemGi>

          <NFormItemGi label="Pradžios data ir laikas" :span="1" required>
            <NDatePicker
              v-model:formatted-value="form.date"
              default-time="12:00:00"
              placeholder="Pasirinkti laiką..."
              value-format="yyyy-MM-dd HH:mm:ss"
              type="datetime"
            />
          </NFormItemGi>

          <NFormItemGi label="Pabaigos data ir laikas" :span="1">
            <NDatePicker
              v-model:formatted-value="form.end_date"
              default-time="12:00:00"
              :disabled="props.modelRoute === 'calendar.store'"
              :is-date-disabled="disabledEndDate"
              placeholder="Pasirinkti laiką..."
              value-format="yyyy-MM-dd HH:mm:ss"
              type="datetime"
            />
          </NFormItemGi>

          <NFormItemGi label="Visos dienos renginys" :span="2">
            <NSwitch v-model:value="form.extra_attributes.all_day" />
          </NFormItemGi>

          <NFormItemGi label="Kategorija" :span="2">
            <NSelect
              v-model:value="form.category"
              :options="categoryOptions"
              placeholder="Pasirinkti kategoriją..."
              clearable
            />
          </NFormItemGi>

          <NFormItemGi label="CTO (Call to action) nuoroda" :span="2">
            <NInput
              v-model:value="form.url"
              type="text"
              placeholder="https://vusa.lt/..."
            />
          </NFormItemGi>

          <NFormItemGi label="Organizatorius" :span="2">
            <NInput
              v-model:value="form.extra_attributes.organizer"
              :placeholder="`Nieko neįrašius, organizatorius bus ${defaultOrganizer}`"
              type="text"
            />
          </NFormItemGi>

          <NFormItemGi label="Facebook nuoroda" :span="2">
            <NInput
              v-model:value="form.extra_attributes.facebook_url"
              type="text"
              placeholder="https://www.facebook.com/events/584152539934772"
            />
          </NFormItemGi>

          <NFormItemGi label="Youtube video kodas" :span="2">
            <NInput
              v-model:value="form.extra_attributes.video_url"
              type="text"
              placeholder="dQw4w9WgXcQ"
            />
          </NFormItemGi>

          <NFormItemGi
            v-if="modelRoute === 'calendar.update'"
            label="Įkelti paveikslėlius (pirmas bus panaudotas, kaip pagrindinis. Jeigu metama klaida, prieš tai sumažinkite paveikslėlius)"
            :span="6"
          >
            <NUpload
              ref="upload"
              accept="image/jpg, image/jpeg, image/png"
              list-type="image-card"
              :default-file-list="images"
              multiple
              @change="handleUploadChange"
              @remove="handleUploadRemove"
            >
              Įkelti paveikslėlius
            </NUpload>
          </NFormItemGi>

          <NFormItemGi label="Aprašymas" :span="6" required>
            <TipTap
              v-model="form.description"
              :search-files="$page.props.search.other"
            />
          </NFormItemGi>
        </NGrid>
      </NTabPane>
      <NTabPane display-directive="show" name="en" tab="🇬🇧">
        <NGrid cols="1 s:4 l:6" responsive="screen" :x-gap="24">
          <NFormItemGi
            label="Renginys arba informacija prieinama ne tik LT studentams"
            :span="2"
          >
            <NSwitch v-model:value="form.extra_attributes.en.shown" />
          </NFormItemGi>

          <NFormItemGi label="Pavadinimas" :span="2">
            <NInput
              v-model:value="form.extra_attributes.en.title"
              :disabled="!form.extra_attributes.en.shown"
              type="text"
              placeholder="Įrašyti pavadinimą..."
            />
          </NFormItemGi>
          <NFormItemGi label="Renginio vieta" :span="2">
            <NInput
              v-model:value="form.extra_attributes.en.location"
              :disabled="!form.extra_attributes.en.shown"
              type="text"
              placeholder="AB Imeda poilsiavietė, Kiškiai, Ignalinos raj."
            />
          </NFormItemGi>

          <NFormItemGi label="CTO (Call to action) nuoroda" :span="2">
            <NInput
              v-model:value="form.extra_attributes.en.url"
              :disabled="!form.extra_attributes.en.shown"
              type="text"
              placeholder="https://vusa.lt/..."
            />
          </NFormItemGi>

          <NFormItemGi label="Organizatorius" :span="2">
            <NInput
              v-model:value="form.extra_attributes.en.organizer"
              :disabled="!form.extra_attributes.en.shown"
              :placeholder="`Nieko neįrašius, organizatorius bus ${defaultOrganizer}`"
              type="text"
            />
          </NFormItemGi>

          <NFormItemGi label="Facebook nuoroda" :span="2">
            <NInput
              v-model:value="form.extra_attributes.en.facebook_url"
              :disabled="!form.extra_attributes.en.shown"
              type="text"
              placeholder="https://www.facebook.com/events/584152539934772"
            />
          </NFormItemGi>

          <NFormItemGi label="Youtube video kodas" :span="2">
            <NInput
              v-model:value="form.extra_attributes.en.video_url"
              :disabled="!form.extra_attributes.en.shown"
              type="text"
              placeholder="dQw4w9WgXcQ"
            />
          </NFormItemGi>
          <NFormItemGi
            v-show="form.extra_attributes.en.shown"
            label="Aprašymas"
            :span="6"
          >
            <TipTap
              v-model="form.extra_attributes.en.description"
              :search-files="$page.props.search.other"
            />
          </NFormItemGi>
        </NGrid>
      </NTabPane>
    </NTabs>
    <div class="flex justify-end gap-2">
      <DeleteModelButton
        v-if="deleteModelRoute"
        :form="form"
        :model-route="deleteModelRoute"
      ></DeleteModelButton>
      <UpsertModelButton :form="form" :model-route="modelRoute" :images="images"
        >Sukurti</UpsertModelButton
      >
    </div>
  </NForm>
</template>

<script setup lang="ts">
import { Inertia } from "@inertiajs/inertia";
import {
  NDatePicker,
  NForm,
  NFormItemGi,
  NGrid,
  NInput,
  NSelect,
  NSwitch,
  NTabPane,
  NTabs,
  NUpload,
  type UploadFileInfo,
  type UploadInst,
} from "naive-ui";
import { computed, ref } from "vue";
import { useForm, usePage } from "@inertiajs/inertia-vue3";

import DeleteModelButton from "@/Components/Buttons/DeleteModelButton.vue";
import TipTap from "@/Components/TipTap/OriginalTipTap.vue";
import UpsertModelButton from "@/Components/Buttons/UpsertModelButton.vue";

const props = defineProps<{
  calendar: CalendarEventForm;
  categories: App.Models.Category[];
  images?: any;
  modelRoute: string;
  deleteModelRoute?: string;
}>();

const form = useForm("calendar", props.calendar);

// create disabled end date if start date is not set

if (props.modelRoute === "calendar.update") {
  const date = new Date(form.date.replace(/-/g, "/"));

  const disabledEndDate = (ts) => {
    return !ts || date > ts + 1000 * 60 * 60 * 24;
  };
}

// convert date_range array of string to number

const convertDateRange = (form_date_range: Array<string | number>) => {
  if (form_date_range === undefined) return undefined;
  // if form_date_range is null, return undefined
  if (form_date_range === null) return undefined;
  form_date_range = form_date_range.map((string: string) => {
    return parseInt(string);
  });
  return form_date_range;
};

if (form.extra_attributes !== null) {
  form.extra_attributes.date_range = convertDateRange(
    form.extra_attributes.date_range
  );
}

const defaultOrganizer = computed(() => {
  return (
    props.calendar.padalinys?.shortname ??
    usePage().props.value.auth.user.padalinys
  );
});

const images = ref<UploadFileInfo[]>([]);

// add images from props to images ref
if (props.images !== undefined) {
  props.images.forEach((image) => {
    images.value.push({
      id: image.id,
      name: image.name,
      status: "finished",
      url: image.original_url,
    });
  });
}

const uploadRef = ref<UploadInst | null>(null);
const upload = uploadRef;

// if no extra_attributes are provided, create an empty object
if (!form.extra_attributes || form.extra_attributes.length === 0) {
  form.extra_attributes = {};
}
// map category options to array
const categoryOptions = props.categories.map((category) => ({
  label: category.name,
  value: category.alias,
}));

const handleUploadChange = (options: {
  file: UploadFileInfo;
  fileList: Array<UploadFileInfo>;
  event?: Event;
}) => {
  images.value = options.fileList;
};

const handleUploadRemove = (options: {
  file: UploadFileInfo;
  fileList: Array<UploadFileInfo>;
  event?: Event;
}) => {
  if (options.file.status === "pending") return;
  Inertia.post(
    route("calendar.destroyMedia", {
      calendar: form.id,
      media: options.file.id,
    }),
    {},
    {
      preserveScroll: true,
    }
  );
};
</script>
