<template>
  <NForm :model="form" label-placement="top">
    <div class="flex flex-col">
      <FormElement>
        <template #title>Pagrindinė informacija</template>
        <template #description
          ><p>Pagrindinė informacija apie renginį.</p>
          <ul>
            <li>
              <p>
                <strong>Kategorija </strong>keičia spalvą renginių kalendoriuje.
              </p>
            </li>
            <li>
              <p>
                <strong>Organizatorius</strong>, jeigu neįrašytas, bus
                <strong>{{ defaultOrganizer }}</strong>
              </p>
            </li>
          </ul>
        </template>
        <NFormItem :label="$t('forms.fields.title')" required>
          <NInput
            v-if="locale === 'lt'"
            v-model:value="form.title"
            type="text"
            placeholder="Įrašyti pavadinimą..."
          >
            <template #suffix
              ><SimpleLocaleButton v-model:locale="locale"></SimpleLocaleButton
            ></template>
          </NInput>
          <NInput
            v-else
            v-model:value="form.extra_attributes.en.title"
            type="text"
            placeholder="Enter name of event..."
            ><template #suffix
              ><SimpleLocaleButton
                v-model:locale="locale"
              ></SimpleLocaleButton></template
          ></NInput>
        </NFormItem>
        <div class="grid gap-4 lg:grid-cols-2">
          <NFormItem label="Organizatorius">
            <NInput
              v-if="locale === 'lt'"
              v-model:value="form.extra_attributes.organizer"
              :placeholder="defaultOrganizer ?? ''"
              type="text"
              ><template #suffix
                ><SimpleLocaleButton
                  v-model:locale="locale"
                ></SimpleLocaleButton></template
            ></NInput>
            <NInput
              v-else
              v-model:value="form.extra_attributes.en.organizer"
              :disabled="!form.extra_attributes.en.shown"
              :placeholder="defaultOrganizer ?? ''"
              type="text"
              ><template #suffix
                ><SimpleLocaleButton
                  v-model:locale="locale"
                ></SimpleLocaleButton></template></NInput
          ></NFormItem>
          <NFormItem label="Renginio vieta">
            <NInput
              v-if="locale === 'lt'"
              v-model:value="form.location"
              type="text"
              placeholder="AB Imeda poilsiavietė, Kiškiai, Ignalinos raj."
            >
              <template #suffix
                ><SimpleLocaleButton
                  v-model:locale="locale"
                ></SimpleLocaleButton></template
            ></NInput>
            <NInput
              v-else
              v-model:value="form.extra_attributes.en.location"
              :disabled="!form.extra_attributes.en.shown"
              type="text"
              placeholder="AB Imeda recreation area, Kiškiai, Ignalina district"
              ><template #suffix
                ><SimpleLocaleButton
                  v-model:locale="locale"
                ></SimpleLocaleButton></template
            ></NInput>
          </NFormItem>
        </div>
        <NFormItem label="Kategorija">
          <NSelect
            v-model:value="form.category"
            :options="categoryOptions"
            placeholder="Pasirinkti kategoriją..."
            clearable
          />
        </NFormItem>
        <NFormItem label="Viešinimo auditorija">
          <div class="grid w-full grid-cols-2 gap-4">
            <NButton
              strong
              :type="form.extra_attributes.en.shown ? 'primary' : 'default'"
              @click="form.extra_attributes.en.shown = true"
              >Visi studentai
              <template #icon
                ><NIcon :component="Globe24Filled"></NIcon
              ></template>
            </NButton>
            <NButton
              :type="form.extra_attributes.en.shown ? 'default' : 'primary'"
              @click="form.extra_attributes.en.shown = false"
            >
              Tik lietuviškai mokantys studentai</NButton
            >
          </div>
        </NFormItem>
      </FormElement>
      <FormElement>
        <template #title>Laiko informacija</template>
        <template #description>
          <p class="mb-2">
            Jeigu nėra nurodytas pabaigos laikas, ICS kalendoriuje renginys
            rodomas kaip 1 val. trukmės.
          </p>
          <p>
            Renginio pabaigos laiką galima nurodyti tik
            <strong>sukūrus renginį</strong>.
          </p>
        </template>
        <div class="grid gap-4 lg:grid-cols-3">
          <NFormItem label="Renginio pradžia" required>
            <NDatePicker
              v-model:formatted-value="form.date"
              default-time="12:00:00"
              placeholder="Pasirinkti pradžios laiką..."
              value-format="yyyy-MM-dd HH:mm:ss"
              type="datetime"
            />
          </NFormItem>

          <NFormItem label="Renginio pabaiga">
            <NDatePicker
              v-model:formatted-value="form.end_date"
              default-time="12:00:00"
              :disabled="props.modelRoute === 'calendar.store'"
              placeholder="Pasirinkti pabaigos laiką..."
              value-format="yyyy-MM-dd HH:mm:ss"
              type="datetime"
            />
          </NFormItem>
          <NFormItem>
            <template #label>
              <div class="inline-flex items-center gap-2">
                Visos dienos renginys<InfoPopover
                  >ICS kalendoriuje šis renginys bus žymimas kaip
                  <strong>visos dienos</strong> renginys.</InfoPopover
                >
              </div>
            </template>
            <NSwitch v-model:value="form.extra_attributes.all_day" />
          </NFormItem>
        </div>
      </FormElement>
      <FormElement>
        <template #title>Informacija viešinimui</template>
        <NFormItem>
          <template #label
            ><div class="inline-flex items-center gap-2">
              CTO (Call to action) nuoroda<InfoPopover
                >Įvedus nuorodą, renginio aprašyme bus rodomas
                <strong>CTO mygtukas</strong>, kuris nukreips vartotoją į
                nurodytą nuorodą. Dažniausiai šis mygtukas turėtų vesti į
                <strong> pagrindinį renginio puslapį</strong> arba
                <strong>registracijos formą</strong>.</InfoPopover
              >
            </div></template
          >
          <NInput
            v-if="locale === 'lt'"
            v-model:value="form.url"
            type="text"
            placeholder="https://vusa.lt/..."
            ><template #suffix
              ><SimpleLocaleButton
                v-model:locale="locale"
              ></SimpleLocaleButton></template
          ></NInput>
          <NInput
            v-else
            v-model:value="form.extra_attributes.en.url"
            :disabled="!form.extra_attributes.en.shown"
            type="text"
            placeholder="https://vusa.lt/..."
            ><template #suffix
              ><SimpleLocaleButton
                v-model:locale="locale"
              ></SimpleLocaleButton></template
          ></NInput>
        </NFormItem>

        <NFormItem label="Facebook nuoroda">
          <NInput
            v-if="locale === 'lt'"
            v-model:value="form.extra_attributes.facebook_url"
            type="text"
            placeholder="https://www.facebook.com/events/584152539934772"
            ><template #suffix
              ><SimpleLocaleButton
                v-model:locale="locale"
              ></SimpleLocaleButton></template
          ></NInput>
          <NInput
            v-else
            v-model:value="form.extra_attributes.en.facebook_url"
            :disabled="!form.extra_attributes.en.shown"
            type="text"
            placeholder="https://www.facebook.com/events/584152539934772"
            ><template #suffix
              ><SimpleLocaleButton
                v-model:locale="locale"
              ></SimpleLocaleButton></template
          ></NInput>
        </NFormItem>

        <NFormItem label="Youtube video kodas">
          <NInputGroup>
            <NInput
              autosize
              value="https://www.youtube.com/embed/"
              :disabled="true"
            ></NInput>
            <NInput
              v-if="locale === 'lt'"
              v-model:value="form.extra_attributes.video_url"
              type="text"
              placeholder="dQw4w9WgXcQ"
              ><template #suffix
                ><SimpleLocaleButton
                  v-model:locale="locale"
                ></SimpleLocaleButton></template
            ></NInput>
            <NInput
              v-else
              v-model:value="form.extra_attributes.en.video_url"
              :disabled="!form.extra_attributes.en.shown"
              type="text"
              placeholder="dQw4w9WgXcQ"
              ><template #suffix
                ><SimpleLocaleButton
                  v-model:locale="locale"
                ></SimpleLocaleButton></template
            ></NInput>
          </NInputGroup>
        </NFormItem>
      </FormElement>
      <FormElement>
        <template #title>Papildoma informacija</template>
        <NFormItem
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
        </NFormItem>

        <NFormItem label="Aprašymas" :span="6" required>
          <TipTap
            v-if="locale === 'lt'"
            v-model="form.description"
            :search-files="$page.props.search.other"
          />
          <TipTap
            v-else
            v-model="form.extra_attributes.en.description"
            :search-files="$page.props.search.other"
          />
        </NFormItem>
      </FormElement>
    </div>
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
import {
  NButton,
  NDatePicker,
  NForm,
  NFormItem,
  NIcon,
  NInput,
  NInputGroup,
  NSelect,
  NSwitch,
  NUpload,
  type UploadFileInfo,
  type UploadInst,
} from "naive-ui";
import { computed, ref } from "vue";
import { router, useForm, usePage } from "@inertiajs/vue3";

import { Globe24Filled } from "@vicons/fluent";
import DeleteModelButton from "@/Components/Buttons/DeleteModelButton.vue";
import FormElement from "./FormElement.vue";
import InfoPopover from "../Buttons/InfoPopover.vue";
import SimpleLocaleButton from "../Buttons/SimpleLocaleButton.vue";
import TipTap from "@/Components/TipTap/OriginalTipTap.vue";
import UpsertModelButton from "@/Components/Buttons/UpsertModelButton.vue";

const props = defineProps<{
  calendar: CalendarEventForm;
  categories: App.Entities.Category[];
  images?: any;
  modelRoute: string;
  deleteModelRoute?: string;
}>();

const locale = ref("lt");

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
    props.calendar.padalinys?.shortname ?? usePage().props.auth?.user.padalinys
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
  router.post(
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
