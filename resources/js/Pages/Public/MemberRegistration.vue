<template>
  <Head title="Prašymas tapti VU SA nariu"></Head>
  <FadeTransition appear>
    <article class="grid grid-cols-3 gap-y-4 px-8 pt-8 last:pb-2 lg:px-32">
      <h1 class="col-span-3 col-start-1">Prašymas tapti VU SA nariu</h1>
      <div class="prose col-span-3 col-start-1">
        <p>
          <strong>Kiekvienas VU studentas gali tapti VU SA nariu!</strong>
          Užsiregistruok ir lauk pakvietimo iš padalinio komandos!
          <Link :href="route('page', { permalink: 'apie' })"
            >Daugiau apie VU SA</Link
          >
        </p>

        <p>
          Taip pat gali registruotis ir į mūsų programas, klubus ir projektus
          (PKP)!

          <Link
            :href="route('page', { permalink: 'programos-klubai-projektai' })"
            >Visų mūsų PKP ieškok čia</Link
          >.
        </p>

        <NForm
          ref="formRef"
          :label-width="80"
          :model="formValue"
          :rules="rules"
          size="medium"
        >
          <NFormItem label="Vardas ir pavardė" path="name">
            <NInput
              v-model:value="formValue.name"
              placeholder="Studentas Studentaitis"
              type="text"
              :input-props="{
                autocomplete: 'name',
              }"
            />
          </NFormItem>
          <NFormItem label="El. paštas" path="email">
            <NInput
              v-model:value="formValue.email"
              placeholder="studentas.studentaitis@padalinys.stud.vu.lt"
              :input-props="{ type: 'email' }"
            />
          </NFormItem>
          <NFormItem label="Telefono numeris" path="phone">
            <NInput
              v-model:value="formValue.phone"
              placeholder="+370 612 34 567"
              :input-props="{ type: 'tel' }"
            />
          </NFormItem>
          <NFormItem label="Kur nori užsiregistruoti?" path="whereToRegister">
            <NSelect
              v-model:value="formValue.whereToRegister"
              :options="registerOptions"
              placeholder="VU SA"
            />
          </NFormItem>
          <NFormItem label="Tavo studijų kursas?" path="course">
            <NSelect
              v-model:value="formValue.course"
              :options="courseOptions"
              placeholder="3"
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
          <div class="text-sm">
            <p>
              Duomenų valdytojas yra Vilniaus universiteto Studentų atstovybė.
            </p>
            <ul>
              <li>
                Adresas: Universiteto g. 3, Observatorijos kiemelis, Vilnius
              </li>
              <li>
                Telefono numeris: <a href="tel:852687144">+37052687144</a>
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

<script lang="ts">
import PublicLayout from "@/Components/Public/Layouts/PublicLayout.vue";

export default {
  layout: PublicLayout,
};
</script>

<script setup lang="ts">
import {
  FormInst,
  FormItemRule,
  FormRules,
  FormValidationError,
  NButton,
  NCheckbox,
  NDatePicker,
  NDynamicInput,
  NForm,
  NFormItem,
  NInput,
  NInputNumber,
  NSelect,
  createDiscreteApi,
} from "naive-ui";
import { Head, Link } from "@inertiajs/inertia-vue3";
import { Inertia, Method } from "@inertiajs/inertia";
import { computed, ref } from "vue";
import { useForm } from "@inertiajs/inertia-vue3";
import route from "ziggy-js";

import FadeTransition from "@/Components/Public/Utils/FadeTransition.vue";

const props = defineProps<{
  padaliniaiOptions: Array<App.Models.Padalinys>;
}>();

// formRefs are needed by Naive UI
const formRef = ref<FormInst | null>(null);
const { message } = createDiscreteApi(["message"]);

const formBlueprint: SaziningaiExamForm = {
  name: null,
  // if number, it's VU SA padalinys. if string, it's PKP
  whereToRegister: null,
  course: null,
  email: null,
  phone: null,
  acceptGDPR: false,
  acceptDataManagement: false,
};

// useForm saves the form value to a remembered state.
const formValue = useForm("SaziningaiExam", formBlueprint);

const padaliniaiOptions = props.padaliniaiOptions.map((padalinys) => ({
  value: padalinys.id,
  label: `VU SA ${padalinys.fullname.split("atstovybė ")[1]}`,
  //   label: padalinys.fullname,
}));

const registerOptions = [
  {
    type: "group",
    label: "VU SA PKP",
    key: "pkp",
    children: [
      { label: "HEMA", value: "hema" },
      { label: "Jaunųjų energetikų klubas (VU JEK)", value: "jek" },
    ],
  },
  {
    type: "group",
    label: "VU SA padaliniai",
    key: "padaliniai",
    children: padaliniaiOptions,
  },
];

// create courseOptions from one to six
const courseOptions = Array.from({ length: 6 }, (_, i) => ({
  value: i + 1,
  label: i + 1,
}));

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
  whereToRegister: {
    required: true,
    trigger: ["blur", "change"],
    validator(rule: FormItemRule, value: string | number) {
      if (!value) {
        return new Error("Pasirinkite, į kur norite registruotis");
      }
      return true;
    },
  },
  course: {
    message: "Pasirinkite savo studijų kursą",
    trigger: ["blur", "change"],
    type: "number",
  },

  acceptGDPR: {
    required: true,
    message: "Turite sutikti su GDPR taisyklėmis",
    trigger: ["blur", "change"],
    validator(rule, value) {
      return value;
    },
  },
  acceptDataManagement: {
    required: true,
    message: "Turite sutikti su duomenų tvarkymu",
    trigger: ["blur", "change"],
    // * Error if not defined
    validator(rule, value) {
      return value;
    },
  },
};

const handleValidateClick = (e: MouseEvent) => {
  e.preventDefault();
  formRef.value?.validate((errors: Array<FormValidationError> | undefined) => {
    if (!errors) {
      formValue.submit(Method.POST, route("memberRegistration.store"), {
        onSuccess: () => {
          formValue.reset();
          message.success(
            `Sėkmingai užsiregistravai! Greitu metu susisieksime su tavimi.`,
            { duration: 15000 }
          );
        },
      });
    } else {
      message.error("Užpildykite visus laukelius.");
    }
  });
};
</script>
