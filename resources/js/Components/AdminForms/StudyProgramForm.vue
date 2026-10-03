<template>
  <FormPage
    :title="isCreate ? $t('Nauja studijų programa') : (title || $t('Studijų programa'))"
    :bar-title="isCreate ? undefined : (title || undefined)"
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
          <Select v-model="form.degree">
            <SelectTrigger id="degree">
              <SelectValue :placeholder="$t('forms.placeholders.select_degree')" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="opt in degreeOptions" :key="opt.value" :value="opt.value">
                {{ opt.label }}
              </SelectItem>
            </SelectContent>
          </Select>
        </FormFieldWrapper>

        <FormFieldWrapper
          id="tenant_id"
          :label="$t('forms.fields.tenant')"
          required
          :error="form.errors.tenant_id"
        >
          <Select v-model="tenantIdString">
            <SelectTrigger id="tenant_id">
              <SelectValue :placeholder="$t('forms.placeholders.select_tenant')" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="opt in tenantOptions" :key="opt.value" :value="String(opt.value)">
                {{ opt.label }}
              </SelectItem>
            </SelectContent>
          </Select>
        </FormFieldWrapper>
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

import MultiLocaleInput from '@/Components/FormItems/MultiLocaleInput.vue';
import FormPage from '@/Components/Layouts/FormPage.vue';
import { ConfirmDialog, FormPanel } from '@/Components/Patterns';
import FormSection from '@/Components/Patterns/FormSection.vue';
import { Button } from '@/Components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
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

const tenantOptions = computed(() => (props.tenants ?? []).map(tenant => ({
  label: tenant.shortname,
  value: tenant.id,
})));

// Shadcn Select requires string values
const tenantIdString = computed({
  get: () => (form.tenant_id != null ? String(form.tenant_id) : ''),
  set: (val: string) => { form.tenant_id = val ? Number(val) : null; },
});
</script>
