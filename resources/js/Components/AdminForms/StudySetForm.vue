<template>
  <FormPage
    :title="isCreate ? $t('Naujas individualių studijų komplektas') : (getTranslatedValue(form.name) || $t('Komplektas'))"
    :bar-title="isCreate ? $t('Naujas individualių studijų komplektas') : (getTranslatedValue(form.name) || undefined)"
    :entity-type="ModelEnum.STUDY_SET"
    :back-href="route('studySets.index')"
    :back-label="$t('Individualių studijų komplektai')"
    :processing="form.processing"
    :dirty="form.isDirty"
    :errors="form.errors"
    :field-ids
    :mode="isCreate ? 'create' : 'edit'"
    :locale="activeLocale"
    :available-locales="['lt', 'en']"
    :missing-locale-counts
    :created-at="!isCreate ? props.studySet.created_at : undefined"
    :updated-at="!isCreate ? props.studySet.updated_at : undefined"
    :activity-subject="!isCreate && props.studySet.id ? { type: 'study_set', id: String(props.studySet.id) } : undefined"
    @update:locale="activeLocale = $event"
    @submit="$emit('submit:form', form)"
  >
    <template v-if="!isCreate" #title-status>
      <StatusBadge :status="form.is_visible ? bannerStatuses.active : bannerStatuses.inactive" />
    </template>

    <FormSection :title="$t('forms.context.main_info')" :description="$t('Individualaus studijų komplekto pagrindinė informacija.')">
      <FormFieldWrapper
        id="name"
        :label="`${$t('forms.fields.title')} (${activeLocale.toUpperCase()})`"
        required
        :error="form.errors[`name.${activeLocale}`]"
      >
        <Input id="name" v-model="form.name[activeLocale]" :class="['h-11', fieldSurfaceClass]" />
      </FormFieldWrapper>

      <FormFieldWrapper
        id="description"
        :label="`${$t('forms.fields.description')} (${activeLocale.toUpperCase()})`"
        :error="form.errors[`description.${activeLocale}`]"
      >
        <Textarea id="description" v-model="form.description[activeLocale]" :class="fieldSurfaceClass" />
      </FormFieldWrapper>
    </FormSection>

    <FormSection
      :title="$t('Dalykai')"
      :description="$t('Pridėkite dalykus, kurie sudaro šį individualų studijų komplektą.')"
      :badge="String(form.courses.length)"
    >
      <ol v-if="form.courses.length" class="divide-y divide-border border-y border-border">
        <li v-for="(course, index) in form.courses" :key="course._key" class="space-y-4 py-4">
          <div class="flex items-center justify-between gap-2">
            <span class="text-xs font-bold uppercase tracking-[0.18em] text-muted-foreground">{{ $t("Dalykas") }} #{{ index + 1 }}</span>
            <Button
              variant="ghost"
              size="icon"
              type="button"
              class="pointer-coarse:size-11"
              :aria-label="$t('Pašalinti')"
              @click="removeCourse(index)"
            >
              <Trash2 class="size-4 text-destructive" />
            </Button>
          </div>

          <FormFieldWrapper
            :id="`course-name-${index}`"
            :label="`${$t('forms.fields.title')} (${activeLocale.toUpperCase()})`"
            required
            :error="form.errors[`courses.${index}.name.${activeLocale}`]"
          >
            <Input :id="`course-name-${index}`" v-model="course.name[activeLocale]" :class="['h-11', fieldSurfaceClass]" />
          </FormFieldWrapper>

          <div class="grid gap-4 sm:grid-cols-4">
            <FormFieldWrapper :id="`course-semester-${index}`" :label="$t('Semestras')">
              <Select v-model="course.semester">
                <SelectTrigger :id="`course-semester-${index}`">
                  <SelectValue :placeholder="$t('Pasirinkite')" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="autumn">
                    {{ $t("Rudens") }}
                  </SelectItem>
                  <SelectItem value="spring">
                    {{ $t("Pavasario") }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </FormFieldWrapper>

            <FormFieldWrapper :id="`course-credits-${index}`" :label="$t('Kreditai')">
              <Input :id="`course-credits-${index}`" v-model="course.credits" type="number" min="0" step="0.5" :class="fieldSurfaceClass" />
            </FormFieldWrapper>

            <FormFieldWrapper :id="`course-order-${index}`" :label="$t('Eilės nr.')">
              <Input :id="`course-order-${index}`" v-model="course.order" type="number" min="0" :class="fieldSurfaceClass" />
            </FormFieldWrapper>

            <FormFieldWrapper :id="`course-visible-${index}`" :label="$t('Matomas')">
              <div class="flex items-center gap-2 pt-2">
                <Switch :id="`course-visible-${index}`" :model-value="course.is_visible" @update:model-value="course.is_visible = $event" />
              </div>
            </FormFieldWrapper>
          </div>
        </li>
      </ol>

      <Button variant="outline" type="button" class="pointer-coarse:min-h-11" @click="addCourse">
        <Plus class="size-4" />
        {{ $t("Pridėti dalyką") }}
      </Button>
    </FormSection>

    <FormSection
      :title="$t('Dėstytojų atsiliepimai')"
      :description="$t('Pridėkite dėstytojų atsiliepimus apie kursus.')"
      :badge="String(form.reviews.length)"
    >
      <ol v-if="form.reviews.length" class="divide-y divide-border border-y border-border">
        <li v-for="(review, index) in form.reviews" :key="review._key" class="space-y-4 py-4">
          <div class="flex items-center justify-between gap-2">
            <span class="text-xs font-bold uppercase tracking-[0.18em] text-muted-foreground">{{ $t("Atsiliepimas") }} #{{ index + 1 }}</span>
            <Button
              variant="ghost"
              size="icon"
              type="button"
              class="pointer-coarse:size-11"
              :aria-label="$t('Pašalinti')"
              @click="removeReview(index)"
            >
              <Trash2 class="size-4 text-destructive" />
            </Button>
          </div>

          <FormFieldWrapper :id="`review-course-${index}`" :label="$t('Dalykas')" required>
            <Select v-model="review.study_set_course_id">
              <SelectTrigger :id="`review-course-${index}`">
                <SelectValue :placeholder="$t('Pasirinkite dalyką')" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="course in savedCourses" :key="course.id" :value="course.id">
                  {{ getCourseName(course) }}
                </SelectItem>
              </SelectContent>
            </Select>
          </FormFieldWrapper>

          <FormFieldWrapper
            :id="`review-lecturer-${index}`"
            :label="`${$t('Dėstytojas')} (${activeLocale.toUpperCase()})`"
            required
          >
            <Input :id="`review-lecturer-${index}`" v-model="review.lecturer[activeLocale]" :class="['h-11', fieldSurfaceClass]" />
          </FormFieldWrapper>

          <FormFieldWrapper :id="`review-comment-${index}`" :label="`${$t('Komentaras')} (${activeLocale.toUpperCase()})`">
            <Textarea :id="`review-comment-${index}`" v-model="review.comment[activeLocale]" :class="fieldSurfaceClass" />
          </FormFieldWrapper>

          <FormFieldWrapper :id="`review-visible-${index}`" :label="$t('Matomas')">
            <Switch :id="`review-visible-${index}`" :model-value="review.is_visible" @update:model-value="review.is_visible = $event" />
          </FormFieldWrapper>
        </li>
      </ol>

      <div class="space-y-2">
        <Button variant="outline" type="button" class="pointer-coarse:min-h-11" :disabled="savedCourses.length === 0" @click="addReview">
          <Plus class="size-4" />
          {{ $t("Pridėti atsiliepimą") }}
        </Button>
        <p v-if="savedCourses.length === 0" class="text-xs text-muted-foreground">
          {{ $t("Pirmiausia pridėkite ir išsaugokite bent vieną dalyką.") }}
        </p>
      </div>
    </FormSection>

    <template #aside>
      <FormPanel :title="$t('Nustatymai')" :icon="SlidersHorizontal" title-class="text-brand">
        <FormToggleRow
          v-model="form.is_visible"
          :label="$t('Matomas')"
          :hint="$t('Ar šis komplektas rodomas viešai studentams.')"
        />

        <FormFieldWrapper id="tenant_id" :label="$t('Padalinys')" required :error="form.errors.tenant_id">
          <Select v-model="tenantIdString">
            <SelectTrigger id="tenant_id">
              <SelectValue :placeholder="$t('Pasirinkite padalinį')" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="tenant in tenants" :key="tenant.id" :value="String(tenant.id)">
                {{ tenant.shortname }}
              </SelectItem>
            </SelectContent>
          </Select>
        </FormFieldWrapper>

        <FormFieldWrapper id="order" :label="$t('Eilės nr.')" :error="form.errors.order">
          <Input id="order" v-model.number="form.order" type="number" min="0" :class="fieldSurfaceClass" />
        </FormFieldWrapper>
      </FormPanel>
    </template>

    <template v-if="enableDelete && !isCreate" #danger-zone>
      <Button
        variant="outline"
        size="sm"
        type="button"
        class="border-destructive/40 text-destructive hover:border-destructive hover:bg-destructive hover:text-destructive-foreground pointer-coarse:min-h-11"
        @click="isDeleteDialogOpen = true"
      >
        <Trash2 class="size-4" />
        {{ $t('Ištrinti komplektą') }}
      </Button>
    </template>
  </FormPage>

  <ConfirmDialog
    v-model:open="isDeleteDialogOpen"
    :title="$t('Ištrinti komplektą?')"
    :description="$t('Komplektas bus perkeltas į šiukšliadėžę.')"
    :confirm-label="$t('Ištrinti')"
    destructive
    @confirm="$emit('delete')"
  />
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Plus, SlidersHorizontal, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

import FormFieldWrapper from './FormFieldWrapper.vue';

import FormPage from '@/Components/Layouts/FormPage.vue';
import { ConfirmDialog } from '@/Components/Patterns';
import FormPanel from '@/Components/Patterns/FormPanel.vue';
import FormSection from '@/Components/Patterns/FormSection.vue';
import FormToggleRow from '@/Components/Patterns/FormToggleRow.vue';
import StatusBadge from '@/Components/Patterns/StatusBadge.vue';
import { Button } from '@/Components/ui/button';
import { fieldSurfaceClass } from '@/Components/ui/control';
import { Input } from '@/Components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Switch } from '@/Components/ui/switch';
import { Textarea } from '@/Components/ui/textarea';
import { getTranslatedValue } from '@/Composables/useTranslatedTitle';
import { bannerStatuses } from '@/Constants/statuses';
import { ModelEnum } from '@/Types/enums';

interface CourseForm {
  id?: string;
  _key: string;
  name: { lt: string; en: string };
  semester: string;
  credits: number;
  order: number;
  is_visible: boolean;
}

interface ReviewForm {
  id?: string;
  _key: string;
  study_set_course_id: string;
  lecturer: { lt: string; en: string };
  comment: { lt: string; en: string };
  is_visible: boolean;
}

interface StudySetFormData {
  id?: string | number;
  name: { lt: string; en: string };
  description: { lt: string; en: string };
  order: number;
  is_visible: boolean;
  tenant_id: number | null;
  courses: CourseForm[];
  reviews: ReviewForm[];
  created_at?: string;
  updated_at?: string;
}

const props = defineProps<{
  studySet: StudySetFormData;
  tenants: Array<{ id: number; shortname: string }>;
  rememberKey?: string;
  enableDelete?: boolean;
}>();

defineEmits<{
  (event: 'submit:form', form: unknown): void;
  (event: 'delete'): void;
}>();

const isCreate = computed(() => props.rememberKey === 'CreateStudySet' || !props.studySet.id);
const isDeleteDialogOpen = ref(false);
const activeLocale = ref<'lt' | 'en'>('lt');

const fieldIds = {
  'name.lt': 'name',
  'name.en': 'name',
  'description.lt': 'description',
  'description.en': 'description',
  'tenant_id': 'tenant_id',
  'order': 'order',
};

let keyCounter = 0;
const generateKey = () => `item-${++keyCounter}`;

/** Nullable translatable columns arrive as `null`; the fields bind `.lt` / `.en` directly. */
const asTranslations = (value: unknown): { lt: string; en: string } => ({
  lt: '',
  en: '',
  ...(value && typeof value === 'object' ? value : {}),
});

const initialData: StudySetFormData = {
  ...props.studySet,
  name: asTranslations(props.studySet.name),
  description: asTranslations(props.studySet.description),
  courses: (props.studySet.courses || []).map(c => ({ ...c, name: asTranslations(c.name), _key: c.id || generateKey() })),
  reviews: (props.studySet.reviews || []).map(r => ({
    ...r,
    lecturer: asTranslations(r.lecturer),
    comment: asTranslations(r.comment),
    _key: r.id || generateKey(),
  })),
};

const form = props.rememberKey
  ? useForm(props.rememberKey, initialData)
  : useForm(initialData);

const missingLocaleCounts = computed(() => {
  const required = [
    form.name,
    ...form.courses.map(course => course.name),
    ...form.reviews.map(review => review.lecturer),
  ];

  return {
    lt: required.filter(value => !value?.lt).length,
    en: required.filter(value => !value?.en).length,
  };
});

const tenantIdString = computed({
  get: () => form.tenant_id != null ? String(form.tenant_id) : '',
  set: (val: string) => { form.tenant_id = val ? Number(val) : null; },
});

const savedCourses = computed(() =>
  form.courses.filter(c => c.id),
);

const getCourseName = (course: CourseForm) => getTranslatedValue(course.name, undefined, '—');

const addCourse = () => {
  form.courses.push({
    _key: generateKey(),
    name: { lt: '', en: '' },
    semester: 'autumn',
    credits: 5,
    order: form.courses.length,
    is_visible: true,
  });
};

const removeCourse = (index: number) => {
  form.courses.splice(index, 1);
};

const addReview = () => {
  form.reviews.push({
    _key: generateKey(),
    study_set_course_id: '',
    lecturer: { lt: '', en: '' },
    comment: { lt: '', en: '' },
    is_visible: true,
  });
};

const removeReview = (index: number) => {
  form.reviews.splice(index, 1);
};
</script>
