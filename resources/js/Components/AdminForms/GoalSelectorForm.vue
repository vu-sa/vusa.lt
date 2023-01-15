<template>
  <NSpin :show="loading">
    <NForm ref="formRef" :rules="rules" :model="model">
      <NGrid cols="1"
        ><NFormItemGi label="Tikslo pavadinimas" required path="title">
          <!-- TODO: Dabar neveikia sukūrimas tikslo iš šios vietos -->
          <NSelect
            v-model:value="model.id"
            :options="goals"
            tag
            filterable
            label-field="title"
            value-field="id"
            placeholder="60 laisvų kreditų implementavimas Gyvybės mokslų centre"
            ><template #action>
              <span
                class="prose-sm prose-gray text-xs text-zinc-400 dark:prose-invert"
                >Gali sukurti ir savo tikslą! Įrašyk +
                <NTag size="tiny">Enter</NTag></span
              >
            </template></NSelect
          >
        </NFormItemGi>
        <NFormItemGi :show-label="false">
          <NButton
            :loading="loading"
            :disabled="!model.id"
            type="primary"
            @click.prevent="$emit('submit', model)"
            >Pridėti</NButton
          >
        </NFormItemGi>
      </NGrid>
    </NForm>
    <template #description> Krauname tikslus. 😊 </template>
  </NSpin>
</template>

<script setup lang="tsx">
import { Inertia } from "@inertiajs/inertia";
import {
  NButton,
  NForm,
  NFormItemGi,
  NGrid,
  NSelect,
  NSpin,
  NTag,
} from "naive-ui";
import { ref } from "vue";
import { useForm } from "@inertiajs/inertia-vue3";

defineEmits<{
  (e: "submit", form: any): void;
}>();

const props = defineProps<{
  goals?: App.Entities.Goal[] | [];
  current: App.Entities.Goal | null;
  loading: boolean;
}>();

const rules = {
  id: {
    required: true,
    message: "Privaloma pasirinkti klausimo grupę",
  },
};

const model = useForm({
  id: props.current?.id || null,
});

const formRef = ref(null);
</script>
