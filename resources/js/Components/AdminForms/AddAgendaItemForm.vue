<template>
  <Form 
    id="add-agenda-item-form" 
    :validation-schema="formSchema" 
    :initial-values="initialValues"
    @submit="handleSubmit"
    class="space-y-4"
  >
    <FormField name="title" v-slot="{ componentField }">
      <FormItem>
        <FormLabel>
          {{ $t('Klausimo pavadinimas') }}
          <span class="text-destructive">*</span>
        </FormLabel>
        <FormControl>
          <Input 
            v-bind="componentField"
            :placeholder="$t('Studijų tinklelio peržiūra')"
          />
        </FormControl>
        <FormMessage />
      </FormItem>
    </FormField>

    <FormField name="description" v-slot="{ componentField }">
      <FormItem>
        <FormLabel>
          {{ $t('Aprašymas') }}
          <span class="text-muted-foreground text-xs ml-1">({{ $t('neprivaloma') }})</span>
        </FormLabel>
        <FormControl>
          <Textarea 
            v-bind="componentField"
            :placeholder="$t('Trumpas klausimo aprašymas...')"
            class="min-h-20"
          />
        </FormControl>
        <FormMessage />
      </FormItem>
    </FormField>

    <div class="flex justify-end gap-2 pt-2">
      <Button type="submit" :disabled="loading">
        <Plus class="h-4 w-4 mr-2" />
        {{ $t('Pridėti') }}
      </Button>
    </div>
  </Form>
</template>

<script setup lang="ts">
import { trans as $t } from "laravel-vue-i18n";
import { toTypedSchema } from "@vee-validate/zod";
import * as z from "zod";
import { Form } from "vee-validate";
import { Plus } from "lucide-vue-next";

import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Textarea } from "@/Components/ui/textarea";
import {
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from "@/Components/ui/form";

const props = defineProps<{
  loading?: boolean;
  meetingId: string;
}>();

const emit = defineEmits<{
  (e: "submit", data: { meeting_id: string; title: string; description?: string }): void;
}>();

const formSchema = toTypedSchema(
  z.object({
    title: z.string().min(1, $t("validation.required", { attribute: $t("Klausimo pavadinimas") })),
    description: z.string().optional().nullable(),
  })
);

const initialValues = {
  title: "",
  description: "",
};

const handleSubmit = (values: { title: string; description?: string | null }) => {
  emit("submit", {
    meeting_id: props.meetingId,
    title: values.title,
    description: values.description || undefined,
  });
};
</script>
