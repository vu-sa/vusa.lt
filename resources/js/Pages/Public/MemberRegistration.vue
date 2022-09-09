<template>
  <Head :title="$t('Prašymas tapti VU SA (arba VU SA PKP) nariu')"></Head>
  <FadeTransition appear>
    <article class="grid grid-cols-3 gap-y-4 px-8 pt-8 last:pb-2 lg:px-32">
      <h1 class="col-span-3 col-start-1">
        {{ $t("Prašymas tapti VU SA (arba VU SA PKP) nariu") }}
      </h1>
      <div class="prose col-span-3 col-start-1">
        <p>
          <strong v-if="$page.props.locale === 'lt'"
            >Kiekvienas VU studentas gali tapti VU SA nariu!
          </strong>
          <strong v-else>Every VU student can become a VU SR member! </strong>
          <span v-if="$page.props.locale === 'lt'"
            >Užsiregistruok ir lauk pakvietimo iš padalinio komandos!
          </span>
          <span v-else>Register and wait for the invite from the team! </span>
          <Link :href="aboutLink">{{ $t("Daugiau apie VU SA") }}</Link
          >.
        </p>

        <p v-if="$page.props.locale === 'lt'">
          Taip pat gali registruotis ir į mūsų programas, klubus ir projektus
          (PKP)!

          <Link :href="pkpLink">Visų mūsų PKP ieškok čia</Link>.
        </p>
        <p v-else>
          You can also register to our programs, clubs and projects (PKP)!

          <Link :href="pkpLink">All of our PKPs can be found here</Link>.
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
              placeholder="Studentas Studentaitis"
              type="text"
              :input-props="{
                autocomplete: 'name',
              }"
            />
          </NFormItem>
          <NFormItem :label="$t('El. paštas')" path="email">
            <NInput
              v-model:value="formValue.email"
              placeholder="studentas.studentaitis@padalinys.stud.vu.lt"
              :input-props="{ type: 'email' }"
            />
          </NFormItem>
          <NFormItem :label="$t('Tel. nr.')" path="phone">
            <NInput
              v-model:value="formValue.phone"
              placeholder="+370 612 34 567"
              :input-props="{ type: 'tel' }"
            />
          </NFormItem>
          <NFormItem
            :label="$t('Kur nori užsiregistruoti?')"
            path="whereToRegister"
          >
            <NSelect
              v-model:value="formValue.whereToRegister"
              :options="registerOptions"
              placeholder="VU SA"
            />
          </NFormItem>
          <NFormItem :label="$t('Studijų kursas')" path="course">
            <NSelect
              v-model:value="formValue.course"
              :options="courseOptions"
              placeholder="3"
            />
          </NFormItem>
          <NFormItem path="acceptGDPR"
            ><NCheckbox v-model:checked="formValue.acceptGDPR">
              <span v-if="$page.props.locale === 'lt'">
                Susipažinau su
                <a
                  target="_blank"
                  href="https://vusa.lt/uploads/Dokumentų šablonai/Asmens_duomenu_tvarkymo_VUSA_tvarkos_aprasas.pdf"
                  @click.stop
                  >Asmens duomenų tvarkymo Vilniaus universiteto Studentų
                  atstovybėje tvarkos aprašu</a
                >
                ir sutinku
              </span>
              <span v-else
                >I have familiarized myself with the
                <a
                  target="_blank"
                  href="https://vusa.lt/uploads/files/EN/VU%20SR%20data%20protection%20description.pdf"
                  @click.stop
                >
                  description of the procedure for the processing of personal
                  data in the Vilnius University's Students' Representation
                </a>
                and I agree</span
              >
            </NCheckbox></NFormItem
          >
          <NFormItem path="acceptDataManagement">
            <NCheckbox v-model:checked="formValue.acceptDataManagement">
              <template v-if="$page.props.locale === 'lt'">
                Sutinku, kad mano pateikti asmens duomenys būtų tvarkomi vidaus
                administravimo tikslu pagal Asmens duomenų tvarkymo Vilniaus
                universiteto Studentų atstovybėje tvarkos aprašą
              </template>
              <template v-else
                >I agree that the personal data provided by me will be processed
                internally for the purpose of administration in accordance with
                Personal data processing description in the Vilnius University's
                Students' Representation</template
              >
            </NCheckbox>
          </NFormItem>
          <div class="text-sm">
            <p v-if="$page.props.locale === 'lt'">
              Duomenų valdytojas yra Vilniaus universiteto Studentų atstovybė.
            </p>
            <p v-else>
              The data manager is the Vilnius University Students'
              Representation.
            </p>
            <ul>
              <li>
                {{
                  `${$t("Adresas")}: ${$t(
                    "Universiteto g. 3, Observatorijos kiemelis"
                  )}, Vilnius`
                }}
              </li>
              <li>
                {{ $t("Tel. nr.") }}:
                <a href="tel:+37052687144">+370 (5) 268 7144</a>
              </li>
              <li>
                {{ $t("El. paštas") }}:
                <a href="mailto:info@vusa.lt">info@vusa.lt</a>
              </li>
            </ul>
            <p v-if="$page.props.locale === 'lt'">
              Jūsų pateikti duomenys bus naudojami susisiekti su jumis.
            </p>
            <p v-else>The data you provide will be used to contact you.</p>

            <p v-if="$page.props.locale === 'lt'">
              Duomenų subjektas turi teisę susipažinti su savo asmens
              duomenimis, teisę reikalauti ištaisyti neteisingus, neišsamius,
              netikslius savo asmens duomenis ir kitas teisės aktais numatytas
              teises. Kilus klausimams ir norint realizuoti savo, kaip duomenų
              subjekto, teises, galite kreiptis į
              <a href="mailto:dap@vusa.lt">dap@vusa.lt</a>.
            </p>
            <p v-else>
              The data subject has the right to access his personal data, the
              right to demand correction of incorrect, incomplete, inaccurate
              personal data and other legal acts rights. If you have questions
              and want to realize your own data rights, you can write to
              <a href="mailto:dap@vusa.lt">dap@vusa.lt</a>.
            </p>
          </div>
          <NButton type="primary" @click="handleValidateClick">
            {{ $t("Pateikti") }}
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
import { trans as $t, getActiveLanguage } from "laravel-vue-i18n";
import {
  FormInst,
  FormItemRule,
  FormRules,
  FormValidationError,
  NButton,
  NCheckbox,
  NForm,
  NFormItem,
  NInput,
  NSelect,
  createDiscreteApi,
} from "naive-ui";
import { Head, Link, usePage } from "@inertiajs/inertia-vue3";
import { Method } from "@inertiajs/inertia";
import { computed, ref } from "vue";
import { useForm } from "@inertiajs/inertia-vue3";
import route from "ziggy-js";

import FadeTransition from "@/Components/Public/Utils/FadeTransition.vue";

const props = defineProps<{
  padaliniaiOptions: Array<App.Models.Padalinys>;
}>();

const aboutLink = computed(() =>
  route("main.page", {
    lang: usePage().props.value.locale,
    permalink: usePage().props.value.locale === "lt" ? "apie" : "about",
  })
);

const pkpLink = computed(() =>
  route("main.page", {
    lang: usePage().props.value.locale,
    permalink:
      usePage().props.value.locale === "lt"
        ? "programos-klubai-projektai"
        : "programs-clubs-projects",
  })
);

// formRefs are needed by Naive UI
const formRef = ref<FormInst | null>(null);
const { message } = createDiscreteApi(["message"]);

const formBlueprint = {
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

const padaliniaiOptions = computed(() =>
  props.padaliniaiOptions.map((padalinys) => ({
    value: padalinys.id,
    label: `${$t("VU SA")} ${getActiveLanguage() === "en" ? "in" : ""} ${$t(
      padalinys.fullname.split("atstovybė ")[1]
    )}`,
  }))
);

const registerOptions = computed(() => [
  {
    type: "group",
    label: $t("VU SA PKP"),
    key: "pkp",
    children: [
      {
        label: `HEMA (${$t("Istorinių Europos kovos menų klubas")})`,
        value: "hema",
      },
      { label: `${$t("Jaunųjų energetikų klubas")} (VU JEK)`, value: "jek" },
    ],
  },
  {
    type: "group",
    label: $t("VU SA padaliniai"),
    key: "padaliniai",
    children: padaliniaiOptions.value,
  },
]);

// create courseOptions from one to six
const courseOptions = Array.from({ length: 6 }, (_, i) => ({
  value: i + 1,
  label: i + 1,
}));

const rules: FormRules = {
  name: {
    required: true,
    trigger: "blur",
    validator(rule: FormItemRule, value: string) {
      if (!value) {
        return new Error(
          $t("validation.required", { attribute: $t("Vardas ir pavardė") })
        );
      }
    },
  },
  email: {
    required: true,
    trigger: "blur",
    validator(rule: FormItemRule, value: string) {
      if (!value) {
        return new Error(
          $t("validation.required", { attribute: $t("El. paštas") })
        );
      }
      if (!/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i.test(value)) {
        return new Error(
          $t("validation.email", { attribute: $t("El. paštas") })
        );
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
        return new Error(
          $t("validation.required", { attribute: $t("Tel. nr.") })
        );
      }
      if (!/^\+?[0-9\s]*$/i.test(value)) {
        return new Error($t("Neteisingas telefono numerio formatas"));
      }
      return true;
    },
  },
  whereToRegister: {
    required: true,
    trigger: ["blur", "change"],
    validator(rule: FormItemRule, value: string | number) {
      if (!value) {
        return new Error($t("Pasirinkite, į kur norite registruotis"));
      }
      return true;
    },
  },
  course: {
    message: $t("validation.required", { attribute: "course" }),
    trigger: ["blur", "change"],
    type: "number",
  },

  acceptGDPR: {
    required: true,
    trigger: ["blur", "change"],
    validator(rule, value) {
      if (!value) {
        return new Error($t("Turite sutikti su GDPR taisyklėmis"));
      }
    },
  },
  acceptDataManagement: {
    required: true,
    trigger: ["blur", "change"],
    // * Error if not defined
    validator(rule, value) {
      if (!value) {
        return new Error($t("Turite sutikti su duomenų tvarkymu"));
      }
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
            $t(
              "Sėkmingai užsiregistravai! Greitu metu susisieksime su tavimi."
            ),
            { duration: 15000 }
          );
        },
      });
    } else {
      message.error($t("Užpildykite visus privalomus laukelius"));
    }
  });
};
</script>
