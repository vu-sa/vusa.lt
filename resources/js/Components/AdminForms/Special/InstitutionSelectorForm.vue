<template>
  <Form class="flex flex-col gap-4" @submit="onSubmit" :validation-schema="schema" v-slot="{ errors }">
    <SuggestionAlert :show-alert="showAlert" @alert-closed="showAlert = false">
      <p v-if="$page.props.app.locale === 'lt'">
        Viena svarbiausių veiklų atstovavime yra
        <strong>dalinimasis informacija</strong>, tada kai ji pasirodo!
      </p>
      <p v-else>
        One of the most important activities in representation is
        <strong>sharing information</strong> when it appears!
      </p>
      <p class="mt-4">
        {{ $t('Būtent') }}
        <ModelChip>
          <template #icon>
            <Icons.MEETING />
          </template>{{ $t('posėdžiai') }}
        </ModelChip>
        <template v-if="$page.props.app.locale === 'lt'">
          "
          ir jų informacija yra labai svarbi – kad galėtume atstovauti studentams geriausiai, kaip tik tai įmanoma!
        </template>
        <template v-else>
          and their information is very important – so we can represent students as best as possible!
        </template>
      </p>
      <p class="mt-4">
        <strong>{{ $t('Pradėkim') }}! 💪</strong>
      </p>
    </SuggestionAlert>
    
    <div class="space-y-4">
      <FormField name="institution_id" v-slot="{ componentField }">
        <FormItem>
        <FormLabel class="flex items-center gap-1">
          <component :is="Icons.INSTITUTION" class="h-4 w-4" />
          {{ $t("Institucija") }}
        </FormLabel>
        <Select v-bind="componentField"
        >
          <FormControl>

          <SelectTrigger class="min-w-[280px]">
            <SelectValue :placeholder="$t('Pasirink instituciją')" />
          </SelectTrigger>
          </FormControl>

          <SelectContent>
            <SelectItem v-for="institution in institutions" :key="institution.value" :value="institution.value">
              {{ institution.label }}
            </SelectItem>
          </SelectContent>
        </Select>
        <FormMessage />
</FormItem>
      </FormField>

      <Button type="submit">
        {{ $t("Toliau") }}...
      </Button>
    </div>
  </Form>
</template>

<script setup lang="ts">
import { computed, ref, inject } from "vue";
import { usePage } from "@inertiajs/vue3";
import { trans as $t } from "laravel-vue-i18n";
import { useForm, Form } from "vee-validate";
import { toTypedSchema } from '@vee-validate/zod';
import * as z from 'zod';

import Icons from "@/Types/Icons/filled";
import ModelChip from "@/Components/Tag/ModelChip.vue";
import SuggestionAlert from "@/Components/Alerts/SuggestionAlert.vue";

// Import Shadcn components
import { Button } from "@/Components/ui/button";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/Components/ui/select";
import {
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from "@/Components/ui/form";
import FormControl from "@/Components/ui/form/FormControl.vue";

const emit = defineEmits<{
  (e: "submit", data: string): void;
}>();

const showAlert = ref(true);

// Access shared form state from parent component
const formState = inject('meetingFormState');

// Define the validation schema using zod
const schema = toTypedSchema(z.object({
  institution_id: z.string({
    required_error: $t("Institucija yra privaloma"),
  }),
}));

// Set initial value from state if available
const initialValues = {
  institution_id: formState?.institution_id || ''
};

const institutions = computed(() => {
  return usePage()
    .props.auth?.user?.current_duties?.map((duty) => {
      if (!duty.institution) {
        return;
      }

      return {
        label: duty.institution?.name,
        value: duty.institution?.id,
      };
    })
    // filter unique
    .filter((institution) => institution !== undefined).filter(
      (value, index, self) =>
        self.findIndex((t) => t?.value === value?.value) === index
    );
});

const onSubmit = ({ institution_id }: { institution_id: string }) => {
  if (institution_id) {
    // Update state
    if (formState) {
      formState.institution_id = institution_id;
    }
    emit("submit", institution_id);
  }
};
</script>
