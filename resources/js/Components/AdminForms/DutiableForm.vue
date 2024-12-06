<template>
  <AdminForm :model="form" label-placement="top" @submit:form="$emit('submit:form', form)" @delete="$emit('delete')">
    <FormElement>
      <template #title>
        Pareigybės ėjimo laikotarpis
      </template>
      <template #description>
        Šioje vietoje nustatomas pareigybės ėjimo laikotarpis. Kai žymima
        pareigybės laikotarpio ėjimo pabaiga, pareigybės pavadinimas bus
        giminizuotas pagal vardą ir pavardę.
      </template>
      <NFormItem label="Pareigų ėjimo pradžia" required>
        <NDatePicker v-model:value="form.start_date" type="date" />
      </NFormItem>
      <NFormItem label="Pareigų ėjimo pabaiga" required>
        <NDatePicker v-model:value="form.end_date" clearable type="date" />
      </NFormItem>
    </FormElement>
    <FormElement>
      <template #title>
        Papildoma informacija
      </template>
      <NFormItem>
        <template #label>
          <span class="inline-flex gap-1">
            <span> Papildomas paštas </span>
            <InfoPopover>Rodomas šis paštas vietoje vartotojo pašto</InfoPopover>
          </span>
        </template>
        <NInput v-model:value="form.additional_email" placeholder="petras.petraitis@vusa.lt" />
      </NFormItem>
      <NFormItem>
        <template #label>
          <div class="inline-flex gap-1">
            <span>Papildoma nuotrauka</span>
            <InfoPopover>
              Ši nuotrauka leidžia vienam asmeniui turėti daugiau negu vieną
              nuotrauką, kuri rodoma, kai puslapyje asmuo vaizduojamas su šia
              pareigybe.
            </InfoPopover>
          </div>
        </template>
        <UploadImageWithCropper v-model:url="form.additional_photo" folder="contacts" />
      </NFormItem>
      <NFormItem>
        <template #label>
          <span class="inline-flex gap-1">
            <span> Studijų programa </span>
            <InfoPopover>Kai aktualu, galima pasirinkti studijų programą, kurią rodo prie įrašo</InfoPopover>
          </span>
        </template>
        <NSelect v-model:value="form.study_program_id" filterable :render-label="renderStudyProgramLabel"
          :options="studyPrograms" value-field="id" placeholder="Studijų programa" />
      </NFormItem>
      <NFormItem>
        <template #label>
          <div class="inline-flex items-center gap-2">
            Aprašymas
            <SimpleLocaleButton v-model:locale="locale" />
          </div>
        </template>
        <TipTap v-if="locale === 'lt'" v-model="form.description.lt" html />
        <TipTap v-else-if="locale === 'en'" v-model="form.description.en" html />
      </NFormItem>
    </FormElement>
    <FormElement>
      <template #title>
        Įvardžiai
      </template>
      <template #description>
        <p>
          Asmens įvardžius gali nusistatyti pats asmuo, prisijungęs prie mano.vusa.lt, savo nustatymų lange. Taip pat
          galima nustatyti
          <Link :href="route('users.edit', dutiable?.dutiable_id)">
          čia
          </Link>.
        </p>
        <p>
          Pareigos pavadinimas yra rodomas taip: <strong> {{ shownDutyName }}</strong>
        </p>
      </template>
      <NFormItem>
        <template #label>
          <span class="inline-flex gap-1">
            <span>
              Pareigos pavadinimo galūnės
              <strong>negiminizavimas</strong>
            </span>
            <InfoPopover>Išjungia automatinį šios kontakto pareigybės giminizavimą pagal
              vardą ir pavardę. Bus naudojamas originalus pareigybės
              pavadinimas.</InfoPopover>
          </span>
        </template>
        <NSwitch v-model:value="form.use_original_duty_name" />
      </NFormItem>
    </FormElement>
  </AdminForm>
</template>

<script setup lang="ts">
import { Link, useForm, usePage } from "@inertiajs/vue3";
import {
  NDatePicker,
  NFormItem,
  NInput,
  NSelect,
  NSwitch,
  NTag,
  type SelectOption,
} from "naive-ui";
import { computed, h, ref } from "vue";
import TipTap from "@/Components/TipTap/OriginalTipTap.vue";

import { changeDutyNameEndings } from "@/Utils/String";
import FormElement from "./FormElement.vue";
import InfoPopover from "../Buttons/InfoPopover.vue";
import SimpleLocaleButton from "../Buttons/SimpleLocaleButton.vue";
import UploadImageWithCropper from "../Buttons/UploadImageWithCropper.vue";
import AdminForm from "./AdminForm.vue";

const props = defineProps<{
  dutiable: App.Entities.Dutiable;
  studyPrograms: App.Entities.StudyProgram[];
}>();

defineEmits<{
  (event: "submit:form", form: unknown): void;
  (event: "delete"): void;
}>();

const form = useForm("dutiable", props.dutiable);

if (Array.isArray(form.description)) {
  form.description = { lt: "", en: "" };
}

const locale = ref("lt");

// Show study program name simply but add "degree" in a tag
const renderStudyProgramLabel = (option: SelectOption) => {
  return h("div", { class: "flex items-center gap-2" },
    [
      option.name,
      option?.degree ? h(NTag, { size: "tiny" }, `${option?.degree}`) : null,
    ]);
};

const shownDutyName = computed(() => {
  return changeDutyNameEndings(props.dutiable.dutiable,
    props.dutiable.duty.name,
    usePage().props.app.locale, props.dutiable.dutiable.pronouns, form.use_original_duty_name)
}
);

</script>
