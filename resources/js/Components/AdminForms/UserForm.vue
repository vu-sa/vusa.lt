<template>
  <NForm :model="form" label-placement="top">
    <div class="flex flex-col">
      <FormElement>
        <template #title>Pagrindinė informacija</template>
        <template #description>
          <p class="mb-4">
            Pagrindinė informacija apie naudotoją (dažniausiai, tai bus
            studentas, VU SA narys).
          </p>
          <p>
            Naudotojai iš vusa.lt/mano
            <strong> netrinami bei negalima keisti jų vardų pavardžių. </strong>
            Jeigu pasikeitė koordinatorius, studentų atstovas:
          </p>
          <ol>
            <li>Pašalink pareigybes iš šio profilio</li>
            <li>Sukurk naują naudotojo profilį</li>
            <li>Priskirk jam jo pareigybes</li>
          </ol>
        </template>
        <NFormItem label="Vardas ir Pavardė" required>
          <NInput
            v-model:value="form.name"
            :disabled="user.name !== ''"
            type="text"
            placeholder="Įrašyti vardą ir pavardę"
          />
        </NFormItem>

        <NFormItem required>
          <template #label>
            <div class="inline-flex items-center gap-2">
              <span><strong>Studentinis</strong> el. paštas</span
              ><InfoPopover v-if="isUserEmailMaybeDutyEmail"
                >Jeigu <strong>{{ user.email }}</strong> yra pareigybinis el.
                paštas (ir panašu, kad šiuo atveju taip ir yra 😊), jį reikėtų
                pakeisti į studentinį.</InfoPopover
              >
            </div>
          </template>
          <NInput
            v-model:value="form.email"
            placeholder="vardas.pavarde@padalinys.stud.vu.lt"
          />
        </NFormItem>

        <NFormItem label="Tel. numeris">
          <NInput v-model:value="form.phone" placeholder="+370 612 34 567" />
        </NFormItem>

        <NFormItem label="Nuotrauka">
          <UploadImageButtons
            v-model="form.profile_photo_path"
            :path="'contacts'"
          ></UploadImageButtons>
        </NFormItem>

        <NFormItem
          v-if="$page.props.auth?.user?.isSuperAdmin"
          label="Administracinė vusa.lt rolė"
        >
          <NSelect
            v-model:value="form.roles"
            :options="rolesOptions"
            clearable
            multiple
            type="text"
            placeholder="Be rolės..."
          />
        </NFormItem>
      </FormElement>

      <FormElement>
        <template #title>Platformos naudotojo pareigybės</template>
        <template #description>
          <p>
            Kiekvienas asmuo gali turėti daugiau nei vieną pareigybę, pagal
            kurią gali atlikti veiksmus platformoje, taip pat būti rodomas (-a)
            viešame vusa.lt puslapyje.
          </p>
          <p class="mt-4">
            Pareigybės turėtų būti kuriamos tik tada, jeigu institucijoje tokios
            pareigybės nėra.
          </p>
        </template>
        <NFormItem label="Pareigybės" :span="6">
          <template #label>
            <div class="inline-flex items-center gap-2">
              <span><strong>Pareigybės</strong></span
              ><a target="_blank" :href="route('duties.create')"
                ><NButton text size="tiny"
                  ><template #icon
                    ><NIcon :component="Add24Filled"></NIcon></template
                  >Sukurti naują pareigybę?</NButton
                ></a
              >
            </div>
          </template>
          <NTransfer
            ref="transfer"
            v-model:value="form.duties"
            :options="flattenDutyOptions"
            :render-source-list="renderSourceList"
            source-filterable
          ></NTransfer>
        </NFormItem>
        <NCard
          v-if="user.duties && user.duties.length > 0"
          class="subtle-gray-gradient h-fit"
        >
          <strong>Šiuo metu {{ user.name }} užima šias pareigas:</strong>
          <ul class="list-inside">
            <li
              v-for="duty in user.duties"
              :key="duty.id"
              class="flex-inline gap-2"
            >
              <Link :href="route('duties.edit', { id: duty.id })"
                >{{ duty.name }}
                {{
                  `(nuo ${duty.pivot.start_date} iki ${
                    duty.pivot.end_date ?? "dabar"
                  })`
                }}
                {{ duty.email ? ` (${duty.email})` : "" }}

                <NButton
                  secondary
                  circle
                  size="tiny"
                  @click.prevent="
                    router.visit(route('duties.users.edit', [duty.id, user.id]))
                  "
                >
                  <NIcon>
                    <PersonEdit24Regular />
                  </NIcon>
                </NButton>
              </Link>
            </li>
          </ul>
        </NCard>
      </FormElement>
    </div>
    <div class="flex justify-end gap-2">
      <DeleteModelButton
        v-if="deleteModelRoute"
        :form="form"
        :model-route="deleteModelRoute"
      ></DeleteModelButton>
      <UpsertModelButton :form="form" :model-route="modelRoute" />
    </div>
  </NForm>
</template>

<script setup lang="tsx">
import { Add24Filled, Eye16Regular } from "@vicons/fluent";
import { Link, router } from "@inertiajs/vue3";
import {
  NButton,
  NCard,
  NForm,
  NFormItem,
  NIcon,
  NInput,
  NSelect,
  NTransfer,
  NTree,
  type TransferRenderSourceList,
  type TreeOption,
} from "naive-ui";
import { PersonEdit24Regular } from "@vicons/fluent";
import { computed, h } from "vue";
import { useForm } from "@inertiajs/vue3";

import DeleteModelButton from "@/Components/Buttons/DeleteModelButton.vue";
import FormElement from "./FormElement.vue";
import InfoPopover from "../Buttons/InfoPopover.vue";
import UploadImageButtons from "@/Components/Buttons/UploadImageButtons.vue";
import UpsertModelButton from "@/Components/Buttons/UpsertModelButton.vue";

const props = defineProps<{
  user: App.Entities.User;
  roles: App.Entities.Role[];
  padaliniaiWithDuties: App.Entities.Padalinys[];
  modelRoute: string;
  deleteModelRoute?: string;
}>();

const form = useForm("user", props.user);

const dutyOptions: TreeOption[] = props.padaliniaiWithDuties.map(
  (padalinys) => ({
    label: padalinys.shortname,
    value: padalinys.id,
    checkboxDisabled: true,
    children: padalinys.institutions?.map((institution) => ({
      label: institution.name,
      value: institution.id,
      checkboxDisabled: true,
      children: institution.duties?.map((duty) => ({
        label: duty.name,
        value: duty.id,
      })),
    })),
  })
);

// check if email contains "vusa.lt"
const isUserEmailMaybeDutyEmail = computed(() => {
  return props.user.email.includes("vusa.lt");
});

const renderLabel = ({ option }: { option: TreeOption }) => {
  // jsx element
  // if value is integer then it's a padalinys and doesn't have additional button
  if (typeof option.value === "number") {
    return <span>{option.label}</span>;
  }

  // jsx element with button
  // ! assumption that if checkbox is enabled then it's a duty
  return (
    <span class="inline-flex items-center gap-2">
      {option.label}
      <a
        target="_blank"
        href={
          option.checkboxDisabled
            ? route("institutions.edit", option.value)
            : route("duties.edit", option.value)
        }
      >
        <NButton size="tiny" text>
          {{
            icon: <NIcon component={Eye16Regular} />,
          }}
        </NButton>
      </a>
    </span>
  );
};

const flattenDutyOptions = dutyOptions.flatMap((padalinys) =>
  padalinys.children?.flatMap((institution) =>
    institution.children?.map((duty) => duty)
  )
);

const rolesOptions = props.roles.map((role) => ({
  label: role.name,
  value: role.id,
}));

form.duties = props.user.duties?.map((duty) => duty.id);

// tsx render Ntree
const renderSourceList: TransferRenderSourceList = ({ onCheck, pattern }) => {
  return h(NTree, {
    style: "margin: 0 4px;",
    keyField: "value",
    checkable: true,
    selectable: false,
    blockLine: true,
    virtualScroll: true,
    renderLabel: renderLabel,
    data: dutyOptions,
    pattern,
    checkedKeys: form.duties,
    onUpdateCheckedKeys: (checkedKeys: Array<string | number>) => {
      onCheck(checkedKeys);
    },
  });
};
</script>
