<template>
  <NForm :model="form" label-placement="top">
    <NTabs animated type="card">
      <NTabPane display-directive="show" name="lt" tab="🇱🇹">
        <NGrid :span="24" :x-gap="24">
          <NFormItemGi label="Pavadinimas" :span="12" required>
            <NInput
              v-model:value="form.title"
              type="text"
              placeholder="Įrašyti pavadinimą..."
            />
          </NFormItemGi>

          <NFormItemGi label="Renginio vieta" :span="12">
            <NInput
              v-model:value="form.location"
              type="text"
              placeholder="AB Imeda poilsiavietė, Kiškiai, Ignalinos raj."
            />
          </NFormItemGi>

          <NFormItemGi label="Pradžios data ir laikas" :span="6" required>
            <NDatePicker
              v-model:formatted-value="form.date"
              default-time="12:00:00"
              placeholder="Pasirinkti laiką..."
              value-format="yyyy-MM-dd HH:mm:ss"
              type="datetime"
            />
          </NFormItemGi>

          <NFormItemGi label="Pabaigos data ir laikas" :span="6">
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

          <NFormItemGi label="Visos dienos renginys" :span="6">
            <NSwitch v-model:value="form.attributes.all_day" />
          </NFormItemGi>

          <NFormItemGi label="Kategorija" :span="6">
            <NSelect
              v-model:value="form.category"
              :options="categoryOptions"
              placeholder="Pasirinkti kategoriją..."
              clearable
            />
          </NFormItemGi>

          <NFormItemGi label="CTO (Call to action) nuoroda" :span="12">
            <NInput
              v-model:value="form.url"
              type="text"
              placeholder="https://vusa.lt/..."
            />
          </NFormItemGi>

          <NFormItemGi label="Organizatorius" :span="12">
            <NInput
              v-model:value="form.attributes.organizer"
              :placeholder="`Nieko neįrašius, organizatorius bus ${defaultOrganizer}`"
              type="text"
            />
          </NFormItemGi>

          <NFormItemGi label="Facebook nuoroda" :span="12">
            <NInput
              v-model:value="form.attributes.facebook_url"
              type="text"
              placeholder="https://www.facebook.com/events/584152539934772"
            />
          </NFormItemGi>

          <NFormItemGi label="Youtube video kodas" :span="12">
            <NInput
              v-model:value="form.attributes.video_url"
              type="text"
              placeholder="dQw4w9WgXcQ"
            />
          </NFormItemGi>

          <NFormItemGi
            v-if="modelRoute === 'calendar.update'"
            label="Įkelti paveikslėlius (pirmas bus panaudotas, kaip pagrindinis. Jeigu metama klaida, prieš tai sumažinkite paveikslėlius)"
            :span="24"
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

          <NFormItemGi label="Aprašymas" :span="24" required>
            <TipTap
              v-model="form.description"
              :search-files="$page.props.search.other"
            />
          </NFormItemGi>
        </NGrid>
      </NTabPane>
      <NTabPane display-directive="show" name="en" tab="🇬🇧">
        <NGrid :span="24" :x-gap="24">
          <NFormItemGi
            label="Renginys arba informacija prieinama ne tik LT studentams"
            :span="24"
          >
            <NSwitch v-model:value="form.attributes.en.shown" />
          </NFormItemGi>

          <NFormItemGi label="Pavadinimas" :span="12">
            <NInput
              v-model:value="form.attributes.en.title"
              type="text"
              placeholder="Įrašyti pavadinimą..."
            />
          </NFormItemGi>
          <NFormItemGi label="Renginio vieta" :span="12">
            <NInput
              v-model:value="form.attributes.en.location"
              type="text"
              placeholder="AB Imeda poilsiavietė, Kiškiai, Ignalinos raj."
            />
          </NFormItemGi>

          <NFormItemGi label="CTO (Call to action) nuoroda" :span="12">
            <NInput
              v-model:value="form.attributes.en.url"
              type="text"
              placeholder="https://vusa.lt/..."
            />
          </NFormItemGi>

          <NFormItemGi label="Organizatorius" :span="12">
            <NInput
              v-model:value="form.attributes.en.organizer"
              :placeholder="`Nieko neįrašius, organizatorius bus ${defaultOrganizer}`"
              type="text"
            />
          </NFormItemGi>

          <NFormItemGi label="Facebook nuoroda" :span="12">
            <NInput
              v-model:value="form.attributes.en.facebook_url"
              type="text"
              placeholder="https://www.facebook.com/events/584152539934772"
            />
          </NFormItemGi>

          <NFormItemGi label="Youtube video kodas" :span="12">
            <NInput
              v-model:value="form.attributes.en.video_url"
              type="text"
              placeholder="dQw4w9WgXcQ"
            />
          </NFormItemGi>
          <NFormItemGi label="Aprašymas" :span="24">
            <TipTap
              v-model="form.attributes.en.description"
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

<script lang="ts">
const { message } = createDiscreteApi(["message"]);
</script>

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
  UploadFileInfo,
  UploadInst,
  createDiscreteApi,
} from "naive-ui";
import { computed, ref } from "vue";
import { useForm, usePage } from "@inertiajs/inertia-vue3";
import route from "ziggy-js";

import DeleteModelButton from "@/Components/Admin/Buttons/DeleteModelButton.vue";
import TipTap from "@/Components/TipTap.vue";
import UpsertModelButton from "@/Components/Admin/Buttons/UpsertModelButton.vue";

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

if (form.attributes !== null) {
  form.attributes.date_range = convertDateRange(form.attributes.date_range);
}

const defaultOrganizer = computed(() => {
  return (
    props.calendar.padalinys?.shortname ?? usePage().props.value.user.padalinys
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

// if no attributes are provided, create an empty object
if (!form.attributes || form.attributes.length === 0) {
  form.attributes = {};
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
      onSuccess: () => {
        message.success("Paveikslėlis sėkmingai ištrintas!");
      },
    }
  );
};
</script>
