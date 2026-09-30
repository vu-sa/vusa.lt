<template>
  <SheetForm
    v-model:open="open"
    :title="category ? $t('Redaguoti kategoriją') : $t('Nauja kategorija')"
    :description="$t('Kategorijos bendros visiems padaliniams: pagal jas filtruojamos problemos.')"
    :processing="form.processing"
    :dirty="form.isDirty"
    @cancel="reset"
    @submit="submit"
  >
    <div class="space-y-2">
      <Label for="problem-category-name">{{ $t('forms.fields.title') }}</Label>
      <MultiLocaleInput id="problem-category-name" v-model:input="form.name" :placeholder="PROBLEM_CATEGORY_PLACEHOLDERS.name" />
      <p v-if="form.errors['name.lt']" class="text-sm text-status-danger">
        {{ form.errors['name.lt'] }}
      </p>
    </div>

    <div class="space-y-2">
      <Label for="problem-category-description">
        {{ $t('forms.fields.description') }} <span class="text-muted-foreground">({{ $t('neprivaloma') }})</span>
      </Label>
      <MultiLocaleInput
        id="problem-category-description"
        v-model:input="form.description"
        input-type="textarea"
        :placeholder="PROBLEM_CATEGORY_PLACEHOLDERS.description"
      />
      <p class="text-xs text-muted-foreground">
        {{ $t('Aprašymas rodomas problemos formoje, kai renkamasi kategorija.') }}
      </p>
    </div>
  </SheetForm>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { computed, toRaw, watch } from 'vue';

import MultiLocaleInput from '@/Components/FormItems/MultiLocaleInput.vue';
import SheetForm from '@/Components/Patterns/SheetForm.vue';
import { Label } from '@/Components/ui/label';
import { PROBLEM_CATEGORY_PLACEHOLDERS } from '@/Constants/I18n/Placeholders';

export interface ProblemCategoryInput {
  id?: number;
  name: { lt: string; en: string };
  description: { lt: string; en: string } | null;
}

const props = defineProps<{ open: boolean; category?: ProblemCategoryInput | null }>();
const emit = defineEmits<{ 'update:open': [value: boolean]; 'saved': [] }>();

const open = computed({ get: () => props.open, set: (value: boolean) => emit('update:open', value) });
const blank = (): ProblemCategoryInput => ({ name: { lt: '', en: '' }, description: { lt: '', en: '' } });
const form = useForm<ProblemCategoryInput>(blank());

watch(() => props.category, (category) => {
  const { id, name, description } = category ? structuredClone(toRaw(category)) : blank();
  form.defaults({ id, name, description: description ?? { lt: '', en: '' } });
  form.reset();
  form.clearErrors();
}, { immediate: true });

function reset(): void {
  form.reset();
  form.clearErrors();
}

function submit(): void {
  const options = {
    preserveScroll: true,
    onSuccess: () => {
      form.defaults();
      open.value = false;
      emit('saved');
    },
  };

  if (props.category?.id) {
    form.patch(route('problemCategories.update', props.category.id), options);
  }
  else {
    form.post(route('problemCategories.store'), options);
  }
}
</script>
