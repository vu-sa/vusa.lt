<template>
  <!-- <PublicLayout title="Sąžiningai atsiskaitymo registravimo forma"> -->
  <Head title="Sąžiningai atsiskaitymo registravimo forma"></Head>
  <FadeTransition appear>
    <article class="grid grid-cols-3 gap-y-4 pt-8 last:pb-2">
      <h1 class="col-span-3 col-start-1">
        Egzamino ar kolokviumo stebėjimo registracijos forma
      </h1>
      <div class="prose col-span-3 col-start-1 dark:prose-invert">
        <!-- <strong class="text-red-600">
            Registracijos forma šiuo metu yra uždaryta, greitu metu ją vėl atidarysime.
            Prašome kreiptis į
            <a href="mailto:saziningai@vusa.lt">saziningai@vusa.lt</a> dėl stebėjimo
            registracijos.
          </strong> -->
        <p>
          Prašome registruoti atsiskaitymus, kurie vyks nuo
          <strong>{{ date3DaysToFutureLT }}</strong> (bent 3 darbo dienos iki jo
          pradžios), kad būtų laiku surasti stebėtojai. Kitu atveju, kreipkitės
          į <a href="mailto:saziningai@vusa.lt">saziningai@vusa.lt</a> 🎓
        </p>
        <NForm
          ref="formRef"
          :label-width="80"
          :model="formValue"
          :rules="rules"
          size="medium"
        >
          <NFormItem :label="$t('Vardas ir pavardė')" path="name">
            <NInput
              v-model:value="formValue.name"
              placeholder=""
              type="text"
              :input-props="{
                autocomplete: 'name',
              }"
            />
          </NFormItem>
          <NFormItem label="El. paštas" path="email">
            <NInput
              v-model:value="formValue.email"
              placeholder=""
              :input-props="{ type: 'email' }"
            />
          </NFormItem>
          <NFormItem label="Telefono numeris" path="phone">
            <NInput
              v-model:value="formValue.phone"
              placeholder=""
              :input-props="{ type: 'tel' }"
            />
          </NFormItem>
          <NFormItem label="Atsiskaitymo pobūdis" path="exam_type">
            <NSelect
              v-model:value="formValue.exam_type"
              :options="examTypes"
              placeholder="Koliokviumas arba egzaminas"
            />
          </NFormItem>
          <NFormItem
            label="Atsiskaitymą laikančiųjų padalinys"
            path="padalinys_id"
          >
            <NSelect
              v-model:value="formValue.padalinys_id"
              :options="padaliniaiOptions"
              :placeholder="padalinysPlaceholder"
            />
          </NFormItem>
          <NFormItem
            label="Atsiskaitomo dalyko pavadinimas"
            path="subject_name"
          >
            <NInput
              v-model:value="formValue.subject_name"
              :placeholder="subjectNamePlaceholder"
            />
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
            path="exam_holders"
          >
            <NInputNumber
              v-model:value="formValue.exam_holders"
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
          <NFormItem
            label="Atsiskaitymo srautai (pasirinkite iki 4)"
            path="flows"
          >
            <NDynamicInput
              v-model:value="formValue.flows"
              :min="1"
              :max="4"
              @create="onCreate"
            >
              <template #create-button-default> Pridėti srautą</template>
              <template #default="{ value }">
                <NDatePicker
                  v-model:formatted-value="value.start_time"
                  value-format="yyyy-MM-dd HH:mm"
                  :first-day-of-week="0"
                  :is-date-disabled="disableUnallowedDate"
                  :format="'yyyy-MM-dd HH:mm'"
                  type="datetime"
                  placeholder="Pasirinkti srauto laiką..."
                  clearable
                  :actions="['confirm']"
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
            ><NCheckbox v-model:checked="formValue.acceptGDPR">
              Susipažinau su
              <a
                target="_blank"
                href="https://vusa.lt/uploads/Dokumentų šablonai/Asmens_duomenu_tvarkymo_VUSA_tvarkos_aprasas.pdf"
                @click.stop
                >Asmens duomenų tvarkymo Vilniaus universiteto Studentų
                atstovybėje tvarkos aprašu</a
              >
              ir sutinku</NCheckbox
            ></NFormItem
          >
          <NFormItem path="acceptDataManagement">
            <NCheckbox v-model:checked="formValue.acceptDataManagement"
              >Sutinku, kad mano pateikti asmens duomenys būtų tvarkomi vidaus
              administravimo tikslu pagal Asmens duomenų tvarkymo Vilniaus
              universiteto Studentų atstovybėje tvarkos aprašą</NCheckbox
            >
          </NFormItem>
          <div class="prose-sm">
            <p>
              Duomenų valdytojas yra Vilniaus universiteto Studentų atstovybė.
            </p>
            <ul>
              <li>
                Adresas: Universiteto g. 3, Observatorijos kiemelis, Vilnius,
              </li>
              <li>
                Telefono numeris: <a href="tel:852687144">+37052687144</a>,
              </li>
              <li>
                El. paštas:
                <a href="mailto:info@vusa.lt">info@vusa.lt</a>
              </li>
            </ul>
            <p>Jūsų pateikti duomenys bus naudojami susisiekti su jumis.</p>

            <p>
              Duomenų subjektas turi teisę susipažinti su savo asmens
              duomenimis, teisę reikalauti ištaisyti neteisingus, neišsamius,
              netikslius savo asmens duomenis ir kitas teisės aktais numatytas
              teises. Kilus klausimams ir norint realizuoti savo, kaip duomenų
              subjekto, teises, galite kreiptis į
              <a href="mailto:dap@vusa.lt">dap@vusa.lt</a>.
            </p>
          </div>
          <NButton type="primary" @click="handleValidateClick">
            Pateikti
          </NButton>
        </NForm>
      </div>
    </article>
  </FadeTransition>
  <!-- </PublicLayout> -->
</template>

<script setup lang="ts">
import {
  type FormInst,
  type FormItemRule,
  type FormRules,
  type FormValidationError,
  NButton,
  NCheckbox,
  NDynamicInput,
  NForm,
  NFormItem,
  NInput,
  NInputNumber,
  NSelect,
  createDiscreteApi,
} from "naive-ui";
import { Head, router, useForm, usePage } from "@inertiajs/vue3";
import { computed, defineAsyncComponent, ref } from "vue";

import FadeTransition from "@/Components/Transitions/FadeTransition.vue";

const NDatePicker = defineAsyncComponent(() =>
  import("naive-ui/es/date-picker").then((module) => module.NDatePicker),
);

const props = defineProps<{
  padaliniaiOptions: Array<App.Entities.Padalinys>;
}>();

// formRefs are needed by Naive UI
const formRef = ref<FormInst | null>(null);
const { message } = createDiscreteApi(["message"]);

const formBlueprint: SaziningaiExamForm = {
  name: null,
  email: null,
  phone: null,
  exam_type: null,
  padalinys_id: null,
  subject_name: null,
  duration: null,
  place: null,
  exam_holders: null,
  students_need: null,
  flows: [],
  acceptGDPR: false,
  acceptDataManagement: false,
};

// useForm saves the form value to a remembered state.
const formValue = useForm("SaziningaiExam", formBlueprint);

const rules: FormRules = {
  name: {
    required: true,
    message: "Įrašykite savo vardą ir pavardę",
    trigger: "blur",
  },
  email: {
    required: true,
    trigger: "blur",
    validator(rule: FormItemRule, value: string) {
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
    type: "number",
    // validate phone number with + sign and spaces
    validator(rule: FormItemRule, value: string) {
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
  padalinys_id: {
    required: true,
    message: "Pasirinkite atsiskaitymą laikančiųjų padalinį",
    trigger: ["blur", "change"],
    type: "number",
  },
  subject_name: {
    required: true,
    message: "Užpildykite laukelį",
    trigger: "blur",
  },
  duration: {
    required: true,
    message: "Užpildykite laukelį",
    trigger: "blur",
  },
  place: {
    required: true,
    message: "Užpildykite laukelį",
    trigger: "blur",
  },
  exam_holders: {
    required: true,
    message: "Užpildykite laukelį",
    trigger: "blur",
    type: "number",
  },
  students_need: {
    required: true,
    message: "Užpildykite laukelį",
    trigger: "blur",
    type: "number",
  },
  flows: {
    required: true,
    trigger: "blur",
    // check if any item in array is empty
    validator(
      rule: unknown,
      value: Array<Pick<App.Entities.SaziningaiExamFlow, "start_time">>,
    ) {
      if (!value || value.length === 0) {
        return new Error("Įveskite bent vieno atsiskaitymo atsiskaitymo laiką");
      }
      if (value.some((item) => !item.start_time)) {
        return new Error("Pasirinkite laiką arba pašalinkite srautą");
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
    // * Error if not defined
    validator(rule, value) {
      return value;
    },
  },
};

const onCreate = () => {
  return {
    // return time now in format yyyy-MM-dd HH:mm
    start_time: null,
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

const handleValidateClick = (e: MouseEvent) => {
  e.preventDefault();
  formRef.value?.validate((errors: Array<FormValidationError> | undefined) => {
    if (!errors) {
      router.post(
        route("saziningaiExamRegistration.store", {
          lang: usePage().props.app.locale,
        }),
        formValue,
        {
          onSuccess: () => {
            message.success(
              `Ačiū už atsiskaitymo „${formValue.subject_name}“ užregistravimą!`,
            );
            formValue.reset();
          },
        },
      );
    } else {
      message.error("Užpildykite visus laukelius.");
    }
  });
};

////////////////////////////////////////////////////////////////////////////////
/// fun with placeholders
const subjectNamePlaceholder = computed(() => {
  // generate 5 subject titles
  const subjectTitles = [
    "Matematikos teorija",
    "Fizika",
    "Organinė chemija",
    "Molekulinė biologija",
    "Algoritmų istorija",
  ];
  // return random
  return subjectTitles[Math.floor(Math.random() * subjectTitles.length)];
});

const padalinysPlaceholder = computed(() => {
  // return random label from padaliniaiOptions
  return padaliniaiOptions[Math.floor(Math.random() * padaliniaiOptions.length)]
    .label;
});

///////
// Various time options

// generate date three working days from now, weekends excluded
const date3DaysToFuture = computed(() => {
  const date = new Date();
  const day = date.getDay();
  let daysToAdd = 0;
  switch (day) {
    case 3:
    case 4:
    case 5:
      daysToAdd = 4;
      break;
    case 6:
      daysToAdd = 3;
      break;
    default:
      daysToAdd = 2;
      break;
  }

  date.setDate(date.getDate() + daysToAdd + 1);
  // date set language to Lithuanian and format in MM dd
  return date;
});

const date3DaysToFutureLT = computed(() => {
  return date3DaysToFuture.value.toLocaleDateString("lt-LT", {
    day: "numeric",
    month: "long",
  });
});

const disableUnallowedDate = (ts: number) => {
  // date3daystofuture to timestamp minus one day
  return ts < date3DaysToFuture.value.getTime() - 24 * 60 * 60 * 1000;
};
</script>
