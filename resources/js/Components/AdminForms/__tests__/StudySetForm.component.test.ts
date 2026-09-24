import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { nextTick } from 'vue';

import StudySetForm from '@/Components/AdminForms/StudySetForm.vue';
import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', async () => {
  const actual = await vi.importActual('@inertiajs/vue3');
  return {
    ...actual,
    usePage: () => ({
      props: {
        app: { locale: 'lt' },
      },
    }),
  };
});

interface StudySetFormVm {
  form: {
    name: { lt: string; en: string };
    description: { lt: string; en: string };
    tenant_id: number | null;
    courses: Array<{
      semester: string;
      credits: number;
      is_visible: boolean;
      [key: string]: unknown;
    }>;
    reviews: Array<{
      study_set_course_id: string;
      is_visible: boolean;
      [key: string]: unknown;
    }>;
  };
  tenantIdString: string;
  activeLocale: 'lt' | 'en';
  addCourse: () => void;
  removeCourse: (index: number) => void;
  addReview: () => void;
  removeReview: (index: number) => void;
}

describe('StudySetForm.vue', () => {
  let wrapper: ReturnType<typeof mount>;

  const defaultStudySet = {
    name: { lt: 'Testinis komplektas', en: 'Test Set' },
    description: { lt: 'Aprašymas', en: 'Description' },
    order: 1,
    is_visible: true,
    tenant_id: 1,
    courses: [],
    reviews: [],
  };

  const tenants = [
    { id: 1, shortname: 'VU SA' },
    { id: 2, shortname: 'VU MIF' },
  ];

  const createWrapper = (props = {}) => {
    return mount(StudySetForm, {
      props: {
        studySet: defaultStudySet,
        tenants,
        ...props,
      },
      global: {
        stubs: {
          ...commonStubs,
          FormPage: {
            template: '<form @submit.prevent><slot /><slot name="danger-zone" /></form>',
            props: ['title', 'locale', 'barTitle', 'activitySubject', 'timestamps'],
          },
          ConfirmDialog: {
            props: ['open'],
            template: '<div v-if="open"><button data-testid="confirm-delete" @click="$emit(\'confirm\')">Confirm</button></div>',
          },
          FormSection: {
            template: '<section><slot /></section>',
            props: ['title'],
          },
          FormFieldWrapper: {
            template: '<div><label>{{ label }}</label><slot /></div>',
            props: ['id', 'label', 'required', 'error'],
          },
          Textarea: {
            template: '<textarea data-testid="textarea" :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)" />',
            props: ['modelValue'],
          },
          Select: {
            template: '<select data-testid="select" :value="modelValue" @change="$emit(\'update:modelValue\', $event.target.value)"><slot /></select>',
            props: ['modelValue'],
          },
          SelectTrigger: { template: '<div><slot /></div>' },
          SelectValue: { template: '<span>{{ placeholder }}</span>', props: ['placeholder'] },
          SelectContent: { template: '<div><slot /></div>' },
          SelectItem: {
            template: '<option :value="value"><slot /></option>',
            props: ['value'],
          },
          Input: {
            template: '<input data-testid="input" :id="id" :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)" />',
            props: ['modelValue', 'type', 'min', 'step', 'id'],
          },
          Switch: {
            template: '<button type="button" :aria-checked="modelValue" @click="$emit(\'update:modelValue\', !modelValue)"><slot /></button>',
            props: ['modelValue'],
          },
          Button: {
            template: '<button type="button" :disabled="disabled" @click="$emit(\'click\', $event)"><slot /></button>',
            props: ['variant', 'size', 'disabled'],
          },
          PlusIcon: { template: '<span class="icon-plus" />' },
          Trash2Icon: { template: '<span class="icon-trash" />' },
        },
      },
    });
  };

  beforeEach(() => {
    vi.clearAllMocks();
  });

  afterEach(() => {
    wrapper?.unmount();
  });

  describe('rendering', () => {
    it('renders form with sections', () => {
      wrapper = createWrapper();
      expect(wrapper.find('form').exists()).toBe(true);
      expect(wrapper.findAll('section').length).toBeGreaterThanOrEqual(3);
    });

    it('renders tenant select with options', () => {
      wrapper = createWrapper();
      const options = wrapper.findAll('option');
      expect(options.length).toBeGreaterThanOrEqual(2);
    });

    it('renders inputs for basic info', () => {
      wrapper = createWrapper();
      expect(wrapper.findAll('[data-testid="input"]').length).toBeGreaterThan(0);
    });
  });

  describe('courses', () => {
    it('has empty courses initially', () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as unknown as StudySetFormVm;
      expect(vm.form.courses).toHaveLength(0);
    });

    it('adds a course via addCourse method', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as unknown as StudySetFormVm;

      vm.addCourse();
      await nextTick();

      expect(vm.form.courses).toHaveLength(1);
      expect(vm.form.courses[0].semester).toBe('autumn');
      expect(vm.form.courses[0].credits).toBe(5);
      expect(vm.form.courses[0].is_visible).toBe(true);
    });

    it('removes a course via removeCourse method', async () => {
      const studySetWithCourse = {
        ...defaultStudySet,
        courses: [
          { id: 'course-1', _key: 'key-1', name: { lt: 'Kursas', en: 'Course' }, semester: 'autumn', credits: 5, order: 0, is_visible: true },
        ],
      };
      wrapper = createWrapper({ studySet: studySetWithCourse });
      const vm = wrapper.vm as unknown as StudySetFormVm;

      expect(vm.form.courses).toHaveLength(1);

      vm.removeCourse(0);
      await nextTick();

      expect(vm.form.courses).toHaveLength(0);
    });

    it('disables add review button when no saved courses exist', () => {
      wrapper = createWrapper();
      const buttons = wrapper.findAll('button').filter(b => b.text().includes('Pridėti atsiliepimą'));
      expect(buttons.length).toBe(1);
      expect(buttons[0]!.attributes('disabled')).toBeDefined();
    });
  });

  describe('reviews', () => {
    it('enables add review button when saved courses exist', () => {
      const studySetWithCourse = {
        ...defaultStudySet,
        courses: [
          { id: 'course-1', _key: 'key-1', name: { lt: 'Kursas', en: 'Course' }, semester: 'autumn', credits: 5, order: 0, is_visible: true },
        ],
      };
      wrapper = createWrapper({ studySet: studySetWithCourse });
      const buttons = wrapper.findAll('button').filter(b => b.text().includes('Pridėti atsiliepimą'));
      expect(buttons.length).toBe(1);
      expect(buttons[0]!.attributes('disabled')).toBeUndefined();
    });

    it('adds a review via addReview method', async () => {
      const studySetWithCourse = {
        ...defaultStudySet,
        courses: [
          { id: 'course-1', _key: 'key-1', name: { lt: 'Kursas', en: 'Course' }, semester: 'autumn', credits: 5, order: 0, is_visible: true },
        ],
      };
      wrapper = createWrapper({ studySet: studySetWithCourse });
      const vm = wrapper.vm as unknown as StudySetFormVm;

      expect(vm.form.reviews).toHaveLength(0);

      vm.addReview();
      await nextTick();

      expect(vm.form.reviews).toHaveLength(1);
      expect(vm.form.reviews[0].study_set_course_id).toBe('');
      expect(vm.form.reviews[0].is_visible).toBe(true);
    });

    it('removes a review via removeReview method', async () => {
      const studySetWithReview = {
        ...defaultStudySet,
        courses: [
          { id: 'course-1', _key: 'key-1', name: { lt: 'Kursas', en: 'Course' }, semester: 'autumn', credits: 5, order: 0, is_visible: true },
        ],
        reviews: [
          { id: 'review-1', _key: 'key-2', study_set_course_id: 'course-1', lecturer: { lt: 'Dėstytojas', en: 'Lecturer' }, comment: { lt: 'Komentaras', en: 'Comment' }, is_visible: true },
        ],
      };
      wrapper = createWrapper({ studySet: studySetWithReview });
      const vm = wrapper.vm as unknown as StudySetFormVm;

      expect(vm.form.reviews).toHaveLength(1);

      vm.removeReview(0);
      await nextTick();

      expect(vm.form.reviews).toHaveLength(0);
    });
  });

  describe('locale', () => {
    it('binds translatable fields to the form-level locale', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as unknown as StudySetFormVm;

      vm.activeLocale = 'en';
      await nextTick();
      await wrapper.find('#name').setValue('Renamed set');

      expect(vm.form.name).toEqual({ lt: 'Testinis komplektas', en: 'Renamed set' });
    });

    it('fills null translatable columns so both locales can be edited', () => {
      wrapper = createWrapper({ studySet: { ...defaultStudySet, description: null } });
      const vm = wrapper.vm as unknown as StudySetFormVm;

      expect(vm.form.description).toEqual({ lt: '', en: '' });
    });
  });

  describe('tenant select', () => {
    it('converts tenant_id to string for select and back to number', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as unknown as StudySetFormVm;

      expect(vm.tenantIdString).toBe('1');

      vm.tenantIdString = '2';
      await nextTick();

      expect(vm.form.tenant_id).toBe(2);
    });

    it('handles null tenant_id', async () => {
      wrapper = createWrapper({ studySet: { ...defaultStudySet, tenant_id: null } });
      const vm = wrapper.vm as unknown as StudySetFormVm;

      expect(vm.tenantIdString).toBe('');

      vm.tenantIdString = '';
      await nextTick();

      expect(vm.form.tenant_id).toBeNull();
    });
  });

  describe('danger zone', () => {
    it('shows danger zone when editing and enableDelete is true', () => {
      wrapper = createWrapper({
        studySet: { ...defaultStudySet, id: '1' },
        enableDelete: true,
      });

      const dangerButton = wrapper.findAll('button').find(b => b.text().includes('Ištrinti'));
      expect(dangerButton).toBeDefined();
    });

    it('emits delete event when confirmed in dialog', async () => {
      wrapper = createWrapper({
        studySet: { ...defaultStudySet, id: '1' },
        enableDelete: true,
      });

      const deleteBtn = wrapper.find('button.border-destructive\\/40');
      expect(deleteBtn.exists()).toBe(true);
      await deleteBtn.trigger('click');

      const confirmBtn = wrapper.find('[data-testid="confirm-delete"]');
      expect(confirmBtn.exists()).toBe(true);
      await confirmBtn.trigger('click');

      expect(wrapper.emitted('delete')).toHaveLength(1);
    });
  });
});
