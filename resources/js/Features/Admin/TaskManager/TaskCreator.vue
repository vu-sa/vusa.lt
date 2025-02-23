<template>
  <NPopover ref="popoverRef" style="width: 300px" placement="top-start" trigger="click" @update-show="getUsers">
    <template #trigger>
      <NButton size="small" quaternary>
        <template #icon>
          <IFluentTaskListSquareAdd24Regular />
        </template>
        {{ $t("forms.add") }}
      </NButton>
    </template>
    <!-- Form title -->
    <NSpin :show="disabled">
      <h4 class="m-2 mb-4 flex items-center gap-2">
        <NIcon :component="Icons.TASK" />
        <span>Nauja užduotis</span>
      </h4>
      <NForm ref="formRef" :disabled="disabled" size="small" class="m-2" :model="model" :rules="rules">
        <NGrid cols="1">
          <NFormItemGi :label="$t('forms.fields.title')" required path="name">
            <NInput v-model:value="model.name" :placeholder="getRandomTaskNamePlaceholder()" />
          </NFormItemGi>
          <!-- <NFormItemGi label="Subjektas">
            <NInput></NInput>
          </NFormItemGi> -->
          <NFormItemGi label="Terminas" path="due_date">
            <NDatePicker v-model:value="model.due_date" />
          </NFormItemGi>
          <NFormItemGi label="Atsakingi žmonės">
            <NCascader v-model:value="model.responsible_people" placeholder="Pasirink arba ieškok iš sąrašo..." multiple
              :check-strategy="'child'" :options="institutions" expand-trigger="hover" :filter="filter" filterable
              :max-tag-count="8" clearable virtual-scroll :show-path="false" />
          </NFormItemGi>
          <NFormItemGi :show-label="false">
            <NCheckbox v-model:checked="model.separate_tasks">
              Ar individualios užduotys?
            </NCheckbox>
          </NFormItemGi>
          <NFormItemGi :show-label="false" :show-feedback="false">
            <NButton type="primary" @click="submit">
              Sukurti
            </NButton>
          </NFormItemGi>
        </NGrid>
      </NForm>
      <template #description>
        Tuojaus...
      </template>
    </NSpin>
  </NPopover>
</template>

<script setup lang="tsx">
import { trans as $t } from "laravel-vue-i18n";
import {
  type CascaderOption,
  type FormInst,
  NPopover,
} from "naive-ui";
import { ref } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import Icons from "@/Types/Icons/filled";

const props = defineProps<{
  taskable?: {
    id: number;
    type: string;
  };
}>();

const model = useForm({
  name: "",
  due_date: new Date().getTime(),
  responsible_people: [],
  separate_tasks: false,
  taskable_id: props.taskable?.id,
  taskable_type: props.taskable?.type,
});

const disabled = ref(true);
const popoverRef = ref<InstanceType<typeof NPopover>>(null);

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
    trigger: "blur-sm",
    message: "Pavadinimas yra privalomas",
  },
  due_date: {
    required: true,
    trigger: "blur-sm",
    type: "number",
    message: "Data yra privaloma",
  },
};

const formRef = ref<FormInst | null>(null);

const getRandomTaskNamePlaceholder = () => {
  // return random task from array of task strings
  let tasks = [
    "Paskambinti koordinatoriui...",
    "Parašyti laišką dėstytojui...",
    "Susitikti su studentu...",
  ];

  return tasks[Math.floor(Math.random() * tasks.length)];
};

const submit = () => {
  formRef.value?.validate((errors) => {
    if (!errors) {
      model.post(route("tasks.store"), {
        preserveScroll: true,
        onSuccess: () => {
          popoverRef.value?.setShow(false);
          model.reset();
        },
      });
    }
  });
};

const parseInstitutions = (
  institutions: App.Entities.Institution[] | undefined,
) => {
  return institutions?.map((institution) => {
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
