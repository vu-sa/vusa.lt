<template>
  <NPopover
    style="width: 400px"
    placement="top-start"
    trigger="click"
    @update-show="getUsers"
  >
    <template #trigger>
      <NButton size="small" quaternary
        ><template #icon
          ><NIcon :component="TaskListSquareAdd24Regular"></NIcon
        ></template>
        Pridėti
      </NButton>
    </template>
    <!-- Form title -->
    <NSpin :show="disabled">
      <NForm
        ref="formRef"
        :disabled="disabled"
        class="m-2"
        :model="model"
        :rules="rules"
      >
        <NGrid cols="1">
          <NFormItemGi label="Pavadinimas" required path="name">
            <NInput v-model:value="model.name"></NInput>
          </NFormItemGi>
          <!-- <NFormItemGi label="Subjektas">
            <NInput></NInput>
          </NFormItemGi> -->
          <NFormItemGi label="Terminas" path="due_date">
            <NDatePicker v-model:value="model.due_date"></NDatePicker>
          </NFormItemGi>
          <NFormItemGi label="Atsakingi žmonės">
            <NCascader
              v-model:value="model.responsible_people"
              multiple
              :check-strategy="'child'"
              :options="institutions"
              expand-trigger="hover"
              :filter="filter"
              filterable
              :max-tag-count="8"
              clearable
              virtual-scroll
              :show-path="false"
            />
          </NFormItemGi>
          <NFormItemGi :show-label="false">
            <NCheckbox v-model:checked="model.separate_tasks"
              >Ar individualios užduotys?
            </NCheckbox>
          </NFormItemGi>
          <NFormItemGi :show-label="false" :show-feedback="false">
            <NButton type="primary" @click="submit">Sukurti</NButton>
          </NFormItemGi>
        </NGrid>
      </NForm>
      <template #description>Tuojaus...</template>
    </NSpin>
  </NPopover>
</template>

<script setup lang="tsx">
import {
  type CascaderOption,
  type FormInst,
  NButton,
  NCascader,
  NCheckbox,
  NDatePicker,
  NForm,
  NFormItemGi,
  NGrid,
  NIcon,
  NInput,
  NPopover,
  NSpin,
} from "naive-ui";
import { TaskListSquareAdd24Regular } from "@vicons/fluent";
import { ref } from "vue";
import { router, useForm } from "@inertiajs/vue3";

const props = defineProps<{
  taskable?: {
    id: number;
    type: string;
  };
}>();

const model = useForm("task", {
  name: "",
  due_date: new Date().getTime(),
  responsible_people: [],
  separate_tasks: false,
  taskable_id: props.taskable?.id,
  taskable_type: props.taskable?.type,
});

const disabled = ref(true);

const institutions = ref<App.Entities.Institution[] | []>([]);

const getUsers = () => {
  if (institutions.value.length === 0)
    router.reload({
      only: ["taskableInstitutions"],
      preserveState: true,
      onSuccess: (page) => {
        institutions.value = parseInstitutions(page.props.taskableInstitutions);
        disabled.value = false;
      },
    });
};

const filter = (pattern: string, option: CascaderOption) => {
  return option.label?.toLowerCase().includes(pattern.toLowerCase());
};

const rules = {
  name: {
    required: true,
    trigger: "blur",
    message: "Pavadinimas yra privalomas",
  },
  due_date: {
    required: true,
    trigger: "blur",
    type: "number",
    message: "Data yra privaloma",
  },
};

const formRef = ref<FormInst | null>(null);

const submit = () => {
  formRef.value?.validate((errors) => {
    if (!errors) {
      console.log(model);
      model.post(route("tasks.store"), {
        preserveScroll: true,
        onSuccess: () => {
          model.reset();
        },
      });
    }
  });
};

const parseInstitutions = (institutions: App.Entities.Institution[]) => {
  return institutions.map((institution) => {
    return {
      label: institution.name,
      value: institution.id,
      children: institution.users.map((user) => ({
        label: user.name,
        value: user.id,
      })),
    };
  });
};
</script>
