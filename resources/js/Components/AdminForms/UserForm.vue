<template>
  <NForm :model="form" label-placement="top">
    <NGrid cols="1 s:4 l:6" responsive="screen" :x-gap="24">
      <NFormItemGi label="Vardas ir Pavardė" :span="2" required>
        <NInput
          v-model:value="form.name"
          type="text"
          placeholder="Įrašyti vardą ir pavardę"
        />
      </NFormItemGi>

      <NFormItemGi label="Studentinis el. paštas" :span="2" required>
        <NInput
          v-model:value="form.email"
          placeholder="vardas.pavarde@padalinys.stud.vu.lt"
        />
      </NFormItemGi>

      <NFormItemGi label="Tel. numeris" :span="2">
        <NInput v-model:value="form.phone" placeholder="+370 612 34 567" />
      </NFormItemGi>

      <NFormItemGi label="Administracinė vusa.lt rolė" :span="2">
        <NSelect
          v-model:value="form.roles"
          :disabled="!$page.props.auth?.user?.isSuperAdmin ?? true"
          :options="rolesOptions"
          clearable
          multiple
          type="text"
          placeholder="Be rolės..."
        />
      </NFormItemGi>

      <NFormItemGi label="Pareigybės" :span="6">
        <NTransfer
          ref="transfer"
          v-model:value="form.duties"
          :options="flattenDutyOptions"
          :render-source-list="renderSourceList"
          source-filterable
        ></NTransfer>
      </NFormItemGi>

      <NFormItemGi label="Nuotrauka" :span="2">
        <UploadImageButtons
          v-model="form.profile_photo_path"
          :path="'contacts'"
        ></UploadImageButtons>
      </NFormItemGi>
    </NGrid>
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
import { Eye16Regular } from "@vicons/fluent";
import {
  NButton,
  NForm,
  NFormItemGi,
  NGrid,
  NIcon,
  NInput,
  NSelect,
  NTransfer,
  NTree,
  type TransferRenderSourceList,
  type TreeOption,
} from "naive-ui";
import { useForm } from "@inertiajs/vue3";

import { h } from "vue";
import DeleteModelButton from "@/Components/Buttons/DeleteModelButton.vue";
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

console.log(dutyOptions);

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
