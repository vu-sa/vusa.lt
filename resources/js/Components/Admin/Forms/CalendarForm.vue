<template>
  <NForm :model="form" label-placement="top">
    <NGrid :span="24" :x-gap="24">
      <NFormItemGi label="Kategorija" :span="24">
        <NSelect
          v-model:value="form.category"
          :options="categoryOptions"
          placeholder="Pasirinkti kategoriją..."
          clearable
        />
      </NFormItemGi>

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

      <NFormItemGi label="Pradžios data ir laikas" :span="12" required>
        <NDatePicker
          v-model:formatted-value="form.date"
          placeholder="Pasirinkti laiką..."
          value-format="yyyy-MM-dd HH:mm:ss"
          type="datetime"
        />
      </NFormItemGi>

      <NFormItemGi label="Pagrindinė nuoroda" :span="12">
        <NInput
          v-model:value="form.url"
          type="text"
          placeholder="https://vusa.lt/..."
        />
      </NFormItemGi>

      <template v-if="form.category === 'freshmen-camps'">
        <NFormItemGi label="Facebook nuoroda" :span="12">
          <NInput
            v-model:value="form.attributes.facebook_url"
            type="text"
            placeholder="https://www.facebook.com/events/584152539934772"
          />
        </NFormItemGi>

        <NFormItemGi label="Trukmė" :span="12">
          <NDatePicker
            v-model:value="form.attributes.date_range"
            type="daterange"
            clearable
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
      </template>

      <NFormItemGi label="Aprašymas" :span="24" required>
        <TipTap
          v-model="form.description"
          :search-files="$page.props.search.other"
        />
      </NFormItemGi>
    </NGrid>
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
  NUpload,
  UploadFileInfo,
  UploadInst,
  createDiscreteApi,
} from "naive-ui";
import { ref } from "vue";
import { useForm } from "@inertiajs/inertia-vue3";
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
  console.log(options.fileList);
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
