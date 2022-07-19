<template>
  <PublicLayout title="Sąžiningai atsiskaitymo registravimo forma">
    <PageArticle>
      <template #title
        >Egzamino ar kolokviumo stebėjimo registracijos forma</template
      >
      <div class="prose">
        <!-- <strong class="text-red-600">
          Registracijos forma šiuo metu yra uždaryta, greitu metu ją vėl atidarysime.
          Prašome kreiptis į
          <a href="mailto:saziningai@vusa.lt">saziningai@vusa.lt</a> dėl stebėjimo
          registracijos.
        </strong> -->
        <p>
          Prašome atsiskaitymą registruoti likus bent 3 d.d. iki jo pradžios,
          kad būtų laiku surasti stebėtojai. Kitu atveju, kreipkitės į
          <a href="mailto:saziningai@vusa.lt">saziningai@vusa.lt</a>
        </p>
        <NForm
          ref="formRef"
          :label-width="80"
          :model="formValue"
          :rules="rules"
          size="medium"
        >
          <NFormItem label="Vardas ir pavardė" path="name">
            <NInput v-model:value="formValue.name" placeholder="" type="text" />
          </NFormItem>
          <NFormItem label="El. paštas" path="email">
            <NInput
              v-model:value="formValue.email"
              placeholder=""
              type="email"
            />
          </NFormItem>
          <NFormItem label="Telefono numeris" path="phone">
            <NInput v-model:value="formValue.phone" placeholder="" type="tel" />
          </NFormItem>
          <NFormItem label="Atsiskaitymo pobūdis" path="type">
            <NSelect
              v-model:value="formValue.type"
              :options="examTypes"
              placeholder=""
            />
          </NFormItem>
          <NFormItem label="Atsiskaitymą laikančiųjų padalinys" path="unit">
            <NSelect
              v-model:value="formValue.unit"
              :options="padaliniaiOptions"
              placeholder=""
            />
          </NFormItem>
          <NFormItem
            label="Atsiskaitomo dalyko pavadinimas"
            path="subject_name"
          >
            <NInput v-model:value="formValue.subject_name" placeholder="" />
          </NFormItem>
          <NFormItem
            label="Atsiskaitymo vieta: padalinys ir auditorija"
            path="place"
          >
            <NInput
              v-model:value="formValue.place"
              type="textarea"
              placeholder=""
            />
          </NFormItem>
          <NFormItem
            label="Atsiskaitymą laikančių studentų skaičius"
            path="holders"
          >
            <NInputNumber
              v-model:value="formValue.holders"
              :min="1"
              placeholder="30"
            />
          </NFormItem>
          <NFormItem
            label="Reikalingas stebėtojų skaičius"
            path="students_need"
          >
            <NInputNumber
              v-model:value="formValue.students_need"
              :min="1"
              placeholder="3"
            />
          </NFormItem>
          <NFormItem label="Atsiskaitymo srautai" path="flows">
            <NDynamicInput
              v-model:value="formValue.flows"
              :min="1"
              :max="4"
              :on-create="onCreate"
            >
              <template #create-button-default> Pridėti srautą</template>
              <template #default="{ value }">
                <NDatePicker
                  v-model:formatted-value="value.time"
                  value-format="yyyy-MM-dd HH:mm"
                  type="datetime"
                  placeholder="Pasirinkti srauto laiką..."
                  clearable
                >
                </NDatePicker>
              </template>
            </NDynamicInput>
          </NFormItem>
          <NFormItem
            class="mt-4"
            label="Atsiskaitymo trukmė (jei laikoma srautais, parašyti srautų skaičių ir kiek laiko skiriama vienam srautui)"
            path="duration"
          >
            <NInput
              v-model:value="formValue.duration"
              type="textarea"
              placeholder=""
            />
          </NFormItem>
          <NFormItem path="acceptGDPR"
            ><NCheckbox
              v-model:checked="formValue.acceptGDPR"
              :label="labelGDPR"
            ></NCheckbox
          ></NFormItem>
          <NFormItem path="acceptDataManagement">
            <NCheckbox
              v-model:checked="formValue.acceptDataManagement"
              :label="labelAcceptDataManagement"
            ></NCheckbox>
          </NFormItem>
          <p>
            Duomenų valdytojas yra Vilniaus universiteto Studentų atstovybė
            (adresas: Universiteto g. 3, Observatorijos kiemelis, Vilnius,
            tel.:, el. paštas: info@vusa.lt). Jūsų pateikti duomenys bus
            naudojami susisiekti su jumis.
          </p>
          <p>
            Duomenų subjektas turi teisę susipažinti su savo asmens duomenimis,
            teisę reikalauti ištaisyti neteisingus, neišsamius, netikslius savo
            asmens duomenis ir kitas teisės aktais numatytas teises. Kilus
            klausimams ir norint realizuoti savo, kaip duomenų subjekto, teises,
            galite kreiptis į
            <a href="mailto:dap@vusa.lt">dap@vusa.lt</a>.
          </p>
          <NFormItem>
            <FormSubmitButton
              submit-route="saziningaiExamRegistration.store"
              :form-ref="formRef"
              :form-value="formValue"
              @reset-form="resetForm"
            >
              Pateikti
            </FormSubmitButton>
          </NFormItem>
        </NForm>
      </div>
    </PageArticle>
  </PublicLayout>
</template>

<script setup lang="ts">
// import { Inertia } from "@inertiajs/inertia";
import {
  NCheckbox,
  NDatePicker,
  NDynamicInput,
  NForm,
  NFormItem,
  NInput,
  NInputNumber,
  NSelect,
} from "naive-ui";
import { h, ref } from "vue";
import { useRemember } from "@inertiajs/inertia-vue3";
import FormSubmitButton from "@/Components/Public/FormSubmitButton.vue";
import PageArticle from "../../Components/Public/PageArticle.vue";
import PublicLayout from "@/Layouts/PublicLayout.vue";

const props = defineProps({
  padaliniaiOptions: Array,
});

// const { message } = createDiscreteApi(["message"]);
const formRef = ref(null);

const labelGDPR = h("label", {}, [
  "Susipažinau su ",
  h(
    "a",
    {
      target: "_blank",
      href: "https://vusa.lt/uploads/Dokumentų šablonai/Asmens_duomenu_tvarkymo_VUSA_tvarkos_aprasas.pdf",
      onClick: (e) => {
        e.stopPropagation();
      },
    },
    "Asmens duomenų tvarkymo Vilniaus universiteto Studentų atstovybėje tvarkos aprašu"
  ),
  " ir sutinku.",
]);

const labelAcceptDataManagement = h(
  "label",
  {},
  "Sutinku, kad mano pateikti asmens duomenys būtų tvarkomi vidaus administravimo tikslu pagal Asmens duomenų tvarkymo Vilniaus universiteto Studentų atstovybėje tvarkos aprašą."
);

const resetForm = () => {
  Object.keys(formValue.value).forEach((i) => (formValue.value[i] = null));
};

const formValue = useRemember(
  ref({
    name: null,
    email: null,
    phone: null,
    type: null,
    unit: null,
    subject_name: null,
    duration: null,
    place: null,
    holders: null,
    students_need: null,
    flows: [
      {
        time: null,
      },
    ],
    acceptGDPR: false,
    acceptDataManagement: false,
  })
);

const rules = {
  name: {
    required: true,
    message: "Įrašykite savo vardą ir pavardę",
    trigger: "blur",
  },
  email: {
    required: true,
    trigger: "blur",
    validator(rule, value) {
      if (!value) {
        return new Error("Įrašykite savo el. paštą");
      }
      if (!/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i.test(value)) {
        return new Error("Neteisingas el. pašto formatas");
      }
      return true;
    },
  },

  phone: {
    required: true,
    // message: "Įrašykite savo telefono numerį",
    trigger: "blur",
    // validate phone number with + sign and spaces
    validator(rule, value) {
      if (!value) {
        return new Error("Įrašykite savo telefono numerį");
      }
      if (!/^\+?[0-9\s]*$/i.test(value)) {
        return new Error("Neteisingas telefono numerio formatas");
      }
      return true;
    },
  },

  type: {
    required: true,
    message: "Pasirinkite atsiskaitymo pobūdį.",
    trigger: "blur",
  },
  unit: {
    required: true,
    message: "Pasirinkite atsiskaitymą laikančiųjų padalinį",
    trigger: ["blur", "change"],
    type: "number",
  },
  subject_name: {
    required: true,
    message: "Atsiskaitomo dalyko pavadinimas",
    trigger: "blur",
  },
  duration: {
    required: true,
    message: "Atsiskaitymo data ir laikas",
    trigger: "blur",
  },
  place: {
    required: true,
    message: "Atsiskaitymo vieta: padalinys ir auditorija",
    trigger: "blur",
  },
  holders: {
    required: true,
    message: "Atsiskaitymą laikančių studentų skaičius",
    trigger: "blur",
    type: "number",
  },
  students_need: {
    required: true,
    message: "Reikalingas stebėtojų skaičius",
    trigger: "blur",
    type: "number",
  },
  flows: {
    required: true,
    trigger: "blur",
    // check if any item in array is empty
    validator(rule, value) {
      if (!value) {
        return new Error("Įveskite laiką ir laiką");
      }
      if (value.some((item) => !item.time)) {
        return new Error("Įveskite laiką arba pašalinkite srautą");
      }
      return true;
    },
  },

  acceptGDPR: {
    required: true,
    message: "Turite sutikti su GDPR taisyklėmis",
    trigger: "blur",
    validator(rule, value) {
      return value;
    },
  },
  acceptDataManagement: {
    required: true,
    message: "Turite sutikti su duomenų tvarkymu",
    trigger: "blur",
    // validate if false, then error
    validator(rule, value) {
      return value;
    },
  },

  // flows: {
  //   required: true,
  //   message: "Atsiskaitymą laikančių studentų skaičius",
  //   trigger: "blur",
  // },
};

const onCreate = () => {
  return {
    // return time now in format yyyy-MM-dd HH:mm
    time: null,
  };
};

const examTypes = [
  {
    value: "koliokviumas",
    label: "Koliokviumas",
  },
  {
    value: "egzaminas",
    label: "Egzaminas",
  },
];

// map padaliniaiOptions to options for NSelect
const padaliniaiOptions = props.padaliniaiOptions.map((padalinys) => ({
  value: padalinys.id,
  label: padalinys.shortname_vu,
}));
</script>
