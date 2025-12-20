<template>
  <Form 
    id="agenda-item-form" 
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
        <FormLabel class="flex items-center gap-1">
          {{ $t('Aprašymas') }}
          <FieldTooltip :text="$t('voting_field_description_tooltip')" />
        </FormLabel>
        <FormControl>
          <Textarea 
            v-bind="componentField"
            :placeholder="$t('Aprašykite klausimo kontekstą, svarstymą, pakomentuokite balsavimą...')"
            class="min-h-24"
          />
        </FormControl>
        <FormMessage />
      </FormItem>
    </FormField>

    <!-- Brought by Students Toggle -->
    <FormField name="brought_by_students" v-slot="{ field, handleChange }">
      <FormItem class="flex flex-row items-center justify-between rounded-lg border p-3 shadow-sm">
        <div class="space-y-0.5">
          <FormLabel>{{ $t('Studentų atneštas klausimas') }}</FormLabel>
          <p class="text-xs text-muted-foreground">
            {{ $t('Pažymėkite, jei klausimą į posėdį atnešė studentai') }}
          </p>
        </div>
        <FormControl>
          <Switch 
            :checked="field.value" 
            @update:checked="handleChange"
          />
        </FormControl>
      </FormItem>
    </FormField>

    <!-- Voting Fields Section -->
    <div class="space-y-4 rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
      <div class="flex items-center gap-2">
        <h4 class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $t('Balsavimo duomenys') }}</h4>
        <VotingInfoButton />
      </div>

      <div class="grid grid-cols-1 gap-4">
        <!-- Decision -->
        <FormField name="decision" v-slot="{ field, handleChange }">
          <FormItem>
            <FormLabel class="flex items-center gap-1 text-xs">
              {{ $t('voting_field_decision_label') }}
              <FieldTooltip :text="$t('voting_field_decision_tooltip')" />
            </FormLabel>
            <FormControl>
              <ButtonGroup orientation="horizontal">
                <Button 
                  type="button"
                  variant="outline" 
                  size="sm"
                  :class="[
                    'h-8',
                    field.value === 'positive' && 'bg-green-100 border-green-300 text-green-700 dark:bg-green-900/40 dark:border-green-700 dark:text-green-400'
                  ]"
                  @click="handleChange(field.value === 'positive' ? null : 'positive')">
                  <Check class="h-3 w-3 mr-1" />
                  {{ $t('Priimtas') }}
                </Button>
                <Button 
                  type="button"
                  variant="outline" 
                  size="sm"
                  :class="[
                    'h-8',
                    field.value === 'negative' && 'bg-red-100 border-red-300 text-red-700 dark:bg-red-900/40 dark:border-red-700 dark:text-red-400'
                  ]"
                  @click="handleChange(field.value === 'negative' ? null : 'negative')">
                  <X class="h-3 w-3 mr-1" />
                  {{ $t('Nepriimtas') }}
                </Button>
                <Button 
                  type="button"
                  variant="outline" 
                  size="sm"
                  :class="[
                    'h-8',
                    field.value === 'neutral' && 'bg-zinc-100 border-zinc-300 text-zinc-700 dark:bg-zinc-800 dark:border-zinc-600 dark:text-zinc-300'
                  ]"
                  @click="handleChange(field.value === 'neutral' ? null : 'neutral')">
                  <Minus class="h-3 w-3 mr-1" />
                  {{ $t('Susilaikyta') }}
                </Button>
              </ButtonGroup>
            </FormControl>
          </FormItem>
        </FormField>

        <!-- Student Vote -->
        <FormField name="student_vote" v-slot="{ field, handleChange }">
          <FormItem>
            <FormLabel class="flex items-center gap-1 text-xs">
              {{ $t('voting_field_student_vote_label') }}
              <FieldTooltip :text="$t('voting_field_student_vote_tooltip')" />
            </FormLabel>
            <FormControl>
              <ButtonGroup orientation="horizontal">
                <Button 
                  type="button"
                  variant="outline" 
                  size="sm"
                  :class="[
                    'h-8',
                    field.value === 'positive' && 'bg-green-100 border-green-300 text-green-700 dark:bg-green-900/40 dark:border-green-700 dark:text-green-400'
                  ]"
                  @click="handleChange(field.value === 'positive' ? null : 'positive')">
                  <Check class="h-3 w-3 mr-1" />
                  {{ $t('Pritarė') }}
                </Button>
                <Button 
                  type="button"
                  variant="outline" 
                  size="sm"
                  :class="[
                    'h-8',
                    field.value === 'negative' && 'bg-red-100 border-red-300 text-red-700 dark:bg-red-900/40 dark:border-red-700 dark:text-red-400'
                  ]"
                  @click="handleChange(field.value === 'negative' ? null : 'negative')">
                  <X class="h-3 w-3 mr-1" />
                  {{ $t('Nepritarė') }}
                </Button>
                <Button 
                  type="button"
                  variant="outline" 
                  size="sm"
                  :class="[
                    'h-8',
                    field.value === 'neutral' && 'bg-zinc-100 border-zinc-300 text-zinc-700 dark:bg-zinc-800 dark:border-zinc-600 dark:text-zinc-300'
                  ]"
                  @click="handleChange(field.value === 'neutral' ? null : 'neutral')">
                  <Minus class="h-3 w-3 mr-1" />
                  {{ $t('Susilaikė') }}
                </Button>
              </ButtonGroup>
            </FormControl>
          </FormItem>
        </FormField>

        <!-- Student Benefit -->
        <FormField name="student_benefit" v-slot="{ field, handleChange }">
          <FormItem>
            <FormLabel class="flex items-center gap-1 text-xs">
              {{ $t('voting_field_student_benefit_label') }}
              <FieldTooltip :text="$t('voting_field_student_benefit_tooltip')" />
            </FormLabel>
            <FormControl>
              <ButtonGroup orientation="horizontal">
                <Button 
                  type="button"
                  variant="outline" 
                  size="sm"
                  :class="[
                    'h-8',
                    field.value === 'positive' && 'bg-green-100 border-green-300 text-green-700 dark:bg-green-900/40 dark:border-green-700 dark:text-green-400'
                  ]"
                  @click="handleChange(field.value === 'positive' ? null : 'positive')">
                  <ThumbsUp class="h-3 w-3 mr-1" />
                  {{ $t('Palanku') }}
                </Button>
                <Button 
                  type="button"
                  variant="outline" 
                  size="sm"
                  :class="[
                    'h-8',
                    field.value === 'negative' && 'bg-red-100 border-red-300 text-red-700 dark:bg-red-900/40 dark:border-red-700 dark:text-red-400'
                  ]"
                  @click="handleChange(field.value === 'negative' ? null : 'negative')">
                  <ThumbsDown class="h-3 w-3 mr-1" />
                  {{ $t('Nepalanku') }}
                </Button>
                <Button 
                  type="button"
                  variant="outline" 
                  size="sm"
                  :class="[
                    'h-8',
                    field.value === 'neutral' && 'bg-zinc-100 border-zinc-300 text-zinc-700 dark:bg-zinc-800 dark:border-zinc-600 dark:text-zinc-300'
                  ]"
                  @click="handleChange(field.value === 'neutral' ? null : 'neutral')">
                  <Minus class="h-3 w-3 mr-1" />
                  {{ $t('Neutralu') }}
                </Button>
              </ButtonGroup>
            </FormControl>
          </FormItem>
        </FormField>
      </div>
    </div>

    <Button type="submit">
      {{ $t('Pateikti') }}
    </Button>
  </Form>
</template>

<script setup lang="ts">
import { trans as $t } from "laravel-vue-i18n";
import { toTypedSchema } from '@vee-validate/zod';
import { z } from 'zod';
import { Check, X, Minus, ThumbsUp, ThumbsDown } from "lucide-vue-next";

import { Button } from "@/Components/ui/button";
import { ButtonGroup } from "@/Components/ui/button-group";
import { Input } from "@/Components/ui/input";
import { Textarea } from "@/Components/ui/textarea";
import { Switch } from "@/Components/ui/switch";
import { Form, FormField, FormItem, FormLabel, FormControl, FormMessage } from "@/Components/ui/form";
import FieldTooltip from "@/Components/ui/FieldTooltip.vue";
import VotingInfoButton from "@/Components/AgendaItems/VotingInfoButton.vue";

const emit = defineEmits<{
  (e: "submit", form: any): void;
}>();

const props = defineProps<{
  agendaItem: App.Entities.AgendaItem | Record<string, any>;
}>();

const formSchema = toTypedSchema(z.object({
  title: z.string().min(1, { message: $t('Klausimo pavadinimas yra privalomas') }),
  description: z.string().optional().nullable(),
  brought_by_students: z.boolean().optional().default(false),
  decision: z.enum(['positive', 'negative', 'neutral']).optional().nullable(),
  student_vote: z.enum(['positive', 'negative', 'neutral']).optional().nullable(),
  student_benefit: z.enum(['positive', 'negative', 'neutral']).optional().nullable(),
}));

const initialValues = {
  title: props.agendaItem.title || '',
  description: props.agendaItem.description || '',
  brought_by_students: props.agendaItem.brought_by_students || false,
  decision: props.agendaItem.decision || null,
  student_vote: props.agendaItem.student_vote || null,
  student_benefit: props.agendaItem.student_benefit || null,
};

const handleSubmit = (values: any) => {
  emit("submit", values);
};
</script>
