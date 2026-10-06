<template>
  <FormPage
    :title="isCreate ? $t('Nauja studijų programa') : (title || $t('Studijų programa'))"
    :bar-title="isCreate ? $t('Nauja studijų programa') : getTranslatedValue(studyProgram.name, undefined, $t('Studijų programa'))"
    :entity-type="ModelEnum.STUDY_PROGRAM"
    :back-href="route('studyPrograms.index')"
    :back-label="$t('Studijų programos')"
    :processing="form.processing"
    :dirty="form.isDirty"
    :errors="form.errors"
    :mode="isCreate ? 'create' : 'edit'"
    :available-locales="[]"
    :activity-subject="!isCreate && studyProgram.id ? { type: 'study_program', id: studyProgram.id } : undefined"
    :created-at="studyProgram?.created_at"
    :updated-at="studyProgram?.updated_at"
    @submit="emit('submit:form', form)"
  >
    <FormSection
      :title="$t('forms.context.main_info')"
      :description="$t('forms.helpers.study_program_main_info')"
    >
      <FormFieldWrapper
        id="name"
        :label="$t('forms.fields.title')"
        required
        :error="form.errors.name || form.errors['name.lt'] || form.errors['name.en']"
      >
        <MultiLocaleInput v-model:input="form.name" />
      </FormFieldWrapper>
    </FormSection>

    <template #aside>
      <FormPanel :title="$t('Priskyrimas')" :icon="GraduationCap" title-class="text-brand">
        <FormFieldWrapper
          id="degree"
          :label="$t('forms.fields.degree')"
          required
          :error="form.errors.degree"
        >
          <NativeSelect
            id="degree"
            v-model="form.degree"
            :options="degreeOptions"
            :placeholder="$t('forms.placeholders.select_degree')"
          />
        </FormFieldWrapper>

        <TenantSelectField
          id="tenant_id"
          v-model="form.tenant_id"
          :tenants
          :label="$t('forms.fields.tenant')"
          required
          :error="form.errors.tenant_id"
        />
      </FormPanel>
    </template>

    <template v-if="enableDelete && !isCreate" #danger-zone>
      <div class="flex items-center justify-between gap-4">
        <div>
          <h4 class="text-sm font-semibold text-destructive">
            {{ $t('Ištrinti studijų programą') }}
          </h4>
          <p class="text-xs text-muted-foreground">
            {{ $t('Studijų programa bus perkelta į šiukšliadėžę.') }}
          </p>
        </div>
        <Button
          type="button"
          variant="destructive"
          size="sm"
          class="u-touch shrink-0"
          @click="deleteConfirmOpen = true"
        >
          <Trash2 class="mr-1.5 size-4" />
          {{ $t('Ištrinti') }}
        </Button>
      </div>

      <ConfirmDialog
        v-model:open="deleteConfirmOpen"
        :title="$t('Ištrinti studijų programą?')"
        :description="$t('Studijų programa bus perkelta į šiukšliadėžę.')"
        :confirm-label="$t('Ištrinti')"
        destructive
        @confirm="emit('delete')"
      />
    </template>
  </FormPage>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { GraduationCap, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

import FormFieldWrapper from './FormFieldWrapper.vue';
import TenantSelectField from './TenantSelectField.vue';

import MultiLocaleInput from '@/Components/FormItems/MultiLocaleInput.vue';
import FormPage from '@/Components/Layouts/FormPage.vue';
import { ConfirmDialog, FormPanel } from '@/Components/Patterns';
import FormSection from '@/Components/Patterns/FormSection.vue';
import { Button } from '@/Components/ui/button';
import { NativeSelect } from '@/Components/ui/native-select';
import { getTranslatedValue } from '@/Composables/useTranslatedTitle';
import { getDegreeOptions } from '@/Utils/Degrees';
import { ModelEnum } from '@/Types/enums';

const props = defineProps<{
  studyProgram: App.Entities.StudyProgram;
  tenants: Array<App.Entities.Tenant>;
  rememberKey?: 'CreateStudyProgram';
  enableDelete?: boolean;
}>();

const emit = defineEmits<{
  (event: 'submit:form', form: unknown): void;
  (event: 'delete'): void;
}>();

const isCreate = computed(() => !props.studyProgram?.id);
const deleteConfirmOpen = ref(false);

const form = props.rememberKey ? useForm(props.rememberKey, props.studyProgram) : useForm(props.studyProgram);

const title = computed(() => getTranslatedValue(form.name));

const degreeOptions = getDegreeOptions();
</script>
