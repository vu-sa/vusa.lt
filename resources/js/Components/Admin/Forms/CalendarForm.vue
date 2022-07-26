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

        <NFormItemGi label="Video (Youtube) nuoroda" :span="12">
          <NInput
            v-model:value="form.attributes.video_url"
            type="text"
            placeholder="https://www.youtube.com/watch?v=dQw4w9WgXcQ"
          />
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
      <UpsertModelButton :form="form" :model-route="modelRoute"
        >Sukurti</UpsertModelButton
      >
    </div>
  </NForm>
</template>

<script setup lang="ts">
import {
  NDatePicker,
  NForm,
  NFormItemGi,
  NGrid,
  NInput,
  NSelect,
} from "naive-ui";
import { useForm } from "@inertiajs/inertia-vue3";

import DeleteModelButton from "@/Components/Admin/Buttons/DeleteModelButton.vue";
import TipTap from "@/Components/TipTap.vue";
import UpsertModelButton from "@/Components/Admin/Buttons/UpsertModelButton.vue";

const props = defineProps<{
  calendar: CalendarEventForm;
  categories: App.Models.Category[];
  modelRoute: string;
  deleteModelRoute?: string;
}>();

const form = useForm("calendar", props.calendar);

// if no attributes are provided, create an empty object
if (!form.attributes) {
  form.attributes = {};
}

// map category options to array
const categoryOptions = props.categories.map((category) => ({
  label: category.name,
  value: category.alias,
}));
</script>
