<template>
  <AdminForm :model="form" @submit:form="$emit('submit:form', form)" @delete="$emit('delete')">
    <!-- Section 1: Basic info -->
    <FormElement :section-number="1">
      <template #title>{{ $t("forms.context.main_info") }}</template>
      <template #description>
        <p>{{ $t("Individualaus studijų komplekto pagrindinė informacija.") }}</p>
      </template>

      <FormFieldWrapper id="name" :label="$t('forms.fields.title')" required>
        <MultiLocaleInput v-model:input="form.name" />
      </FormFieldWrapper>

      <FormFieldWrapper id="description" :label="$t('forms.fields.description')">
        <MultiLocaleInput v-model:input="form.description" input-type="textarea" />
      </FormFieldWrapper>

      <div class="grid gap-4 lg:grid-cols-3">
        <FormFieldWrapper id="tenant_id" :label="$t('Padalinys')" required>
          <Select v-model="tenantIdString">
            <SelectTrigger>
              <SelectValue :placeholder="$t('Pasirinkite padalinį')" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="tenant in tenants" :key="tenant.id" :value="String(tenant.id)">
                {{ tenant.shortname }}
              </SelectItem>
            </SelectContent>
          </Select>
        </FormFieldWrapper>

        <FormFieldWrapper id="order" :label="$t('Eilės nr.')">
          <Input v-model="form.order" type="number" min="0" />
        </FormFieldWrapper>

        <FormFieldWrapper id="is_visible" :label="$t('Matomas')">
          <div class="flex items-center gap-2 pt-2">
            <Switch :model-value="form.is_visible" @update:model-value="form.is_visible = $event" />
            <span class="text-sm text-muted-foreground">
              {{ form.is_visible ? $t('Taip') : $t('Ne') }}
            </span>
          </div>
        </FormFieldWrapper>
      </div>
    </FormElement>

    <!-- Section 2: Courses -->
    <FormElement :section-number="2">
      <template #title>{{ $t("Dalykai") }}</template>
      <template #description>
        <p>{{ $t("Pridėkite dalykus, kurie sudaro šį individualų studijų komplektą.") }}</p>
      </template>

      <div class="space-y-4">
        <div v-for="(course, index) in form.courses" :key="course._key" class="rounded-lg border p-4 space-y-4">
          <div class="flex items-start justify-between gap-2">
            <span class="text-sm font-medium text-muted-foreground">{{ $t("Dalykas") }} #{{ index + 1 }}</span>
            <Button variant="ghost" size="icon" type="button" @click="removeCourse(index)">
              <Trash2Icon class="h-4 w-4 text-destructive" />
            </Button>
          </div>

          <FormFieldWrapper :id="`course-name-${index}`" :label="$t('forms.fields.title')" required>
            <MultiLocaleInput v-model:input="course.name" />
          </FormFieldWrapper>

          <div class="grid gap-4 sm:grid-cols-4">
            <FormFieldWrapper :id="`course-semester-${index}`" :label="$t('Semestras')">
              <Select v-model="course.semester">
                <SelectTrigger>
                  <SelectValue :placeholder="$t('Pasirinkite')" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="autumn">{{ $t("Rudens") }}</SelectItem>
                  <SelectItem value="spring">{{ $t("Pavasario") }}</SelectItem>
                </SelectContent>
              </Select>
            </FormFieldWrapper>

            <FormFieldWrapper :id="`course-credits-${index}`" :label="$t('Kreditai')">
              <Input v-model="course.credits" type="number" min="0" step="0.5" />
            </FormFieldWrapper>

            <FormFieldWrapper :id="`course-order-${index}`" :label="$t('Eilės nr.')">
              <Input v-model="course.order" type="number" min="0" />
            </FormFieldWrapper>

            <FormFieldWrapper :id="`course-visible-${index}`" :label="$t('Matomas')">
              <div class="flex items-center gap-2 pt-2">
                <Switch :model-value="course.is_visible" @update:model-value="course.is_visible = $event" />
              </div>
            </FormFieldWrapper>
          </div>
        </div>

        <Button variant="outline" type="button" class="gap-2" @click="addCourse">
          <PlusIcon class="h-4 w-4" />
          {{ $t("Pridėti dalyką") }}
        </Button>
      </div>
    </FormElement>

    <!-- Section 3: Lecturer Reviews -->
    <FormElement :section-number="3">
      <template #title>{{ $t("Dėstytojų atsiliepimai") }}</template>
      <template #description>
        <p>{{ $t("Pridėkite dėstytojų atsiliepimus apie kursus.") }}</p>
      </template>

      <div class="space-y-4">
        <div v-for="(review, index) in form.reviews" :key="review._key" class="rounded-lg border p-4 space-y-4">
          <div class="flex items-start justify-between gap-2">
            <span class="text-sm font-medium text-muted-foreground">{{ $t("Atsiliepimas") }} #{{ index + 1 }}</span>
            <Button variant="ghost" size="icon" type="button" @click="removeReview(index)">
              <Trash2Icon class="h-4 w-4 text-destructive" />
            </Button>
          </div>

          <FormFieldWrapper :id="`review-course-${index}`" :label="$t('Dalykas')" required>
            <Select v-model="review.study_set_course_id">
              <SelectTrigger>
                <SelectValue :placeholder="$t('Pasirinkite dalyką')" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="course in savedCourses" :key="course.id" :value="course.id">
                  {{ getCourseName(course) }}
                </SelectItem>
              </SelectContent>
            </Select>
          </FormFieldWrapper>

          <FormFieldWrapper :id="`review-lecturer-${index}`" :label="$t('Dėstytojas')" required>
            <MultiLocaleInput v-model:input="review.lecturer" />
          </FormFieldWrapper>

          <FormFieldWrapper :id="`review-comment-${index}`" :label="$t('Komentaras')">
            <MultiLocaleInput v-model:input="review.comment" input-type="textarea" />
          </FormFieldWrapper>

          <FormFieldWrapper :id="`review-visible-${index}`" :label="$t('Matomas')">
            <div class="flex items-center gap-2">
              <Switch :model-value="review.is_visible" @update:model-value="review.is_visible = $event" />
            </div>
          </FormFieldWrapper>
        </div>

        <Button variant="outline" type="button" class="gap-2" :disabled="savedCourses.length === 0" @click="addReview">
          <PlusIcon class="h-4 w-4" />
          {{ $t("Pridėti atsiliepimą") }}
        </Button>
        <p v-if="savedCourses.length === 0" class="text-xs text-muted-foreground">
          {{ $t("Pirmiausia pridėkite ir išsaugokite bent vieną dalyką.") }}
        </p>
      </div>
    </FormElement>
  </AdminForm>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { useForm, usePage } from "@inertiajs/vue3";
import { trans as $t } from "laravel-vue-i18n";
import { PlusIcon, Trash2Icon } from "lucide-vue-next";

import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/Components/ui/select";
import { Input } from "@/Components/ui/input";
import { Button } from "@/Components/ui/button";
import { Switch } from "@/Components/ui/switch";
import FormElement from "./FormElement.vue";
import FormFieldWrapper from "./FormFieldWrapper.vue";
import AdminForm from "./AdminForm.vue";
import MultiLocaleInput from "@/Components/FormItems/MultiLocaleInput.vue";

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
  name: { lt: string; en: string };
  description: { lt: string; en: string };
  order: number;
  is_visible: boolean;
  tenant_id: number | null;
  courses: CourseForm[];
  reviews: ReviewForm[];
}

const props = defineProps<{
  studySet: StudySetFormData;
  tenants: Array<{ id: number; shortname: string }>;
  rememberKey?: string;
}>();

defineEmits<{
  (event: "submit:form", form: unknown): void;
  (event: "delete"): void;
}>();

let keyCounter = 0;
const generateKey = () => `item-${++keyCounter}`;

const initialData: StudySetFormData = {
  ...props.studySet,
  courses: (props.studySet.courses || []).map((c) => ({ ...c, _key: c.id || generateKey() })),
  reviews: (props.studySet.reviews || []).map((r) => ({ ...r, _key: r.id || generateKey() })),
};

const form = props.rememberKey
  ? useForm(props.rememberKey, initialData)
  : useForm(initialData);

const tenantIdString = computed({
  get: () => form.tenant_id != null ? String(form.tenant_id) : '',
  set: (val: string) => { form.tenant_id = val ? Number(val) : null; },
});

const savedCourses = computed(() =>
  form.courses.filter((c) => c.id)
);

const getCourseName = (course: CourseForm) => {
  const locale = usePage().props.app.locale as 'lt' | 'en';
  return course.name[locale] || course.name.lt || course.name.en || '—';
};

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
