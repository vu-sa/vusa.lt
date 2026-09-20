import { mount } from '@vue/test-utils';
import { useForm } from '@inertiajs/vue3';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import AssignDutyUserSheet from '@/Features/Admin/Occupancy/AssignDutyUserSheet.vue';
import { commonStubs } from '@/tests/stubs';

type FormMock = ReturnType<typeof useForm> & Record<string, unknown>;

const ConfirmStub = {
  props: ['open', 'title', 'confirmLabel'],
  emits: ['confirm', 'update:open'],
  template: '<div v-if="open" data-testid="confirm"><button type="button" data-testid="confirm-yes" @click="$emit(\'confirm\')">{{ confirmLabel }}</button></div>',
};

const stubs = {
  ...commonStubs,
  Sheet: { template: '<div><slot /></div>' },
  SheetContent: { template: '<div><slot /></div>' },
  SheetHeader: { template: '<div><slot /></div>' },
  SheetTitle: { template: '<h2><slot /></h2>' },
  SheetDescription: { template: '<p><slot /></p>' },
  SheetForm: {
    props: ['title', 'saveLabel', 'processing', 'dirty'],
    emits: ['submit'],
    template: '<form @submit.prevent="$emit(\'submit\')"><h2>{{ title }}</h2><slot /><slot name="danger-zone" /><button type="submit">{{ saveLabel }}</button></form>',
  },
  ConfirmDialog: ConfirmStub,
  DatePicker: { props: ['modelValue', 'disabled'], template: '<input class="date" :value="modelValue" :disabled="disabled" />' },
  TiptapEditor: { template: '<div class="tiptap" />' },
  ImageUpload: { template: '<div class="image-upload" />' },
  MemberSearchField: { props: ['modelValue', 'takenIds'], template: '<div data-testid="member-search" />' },
  DutySearchField: {
    props: ['modelValue'],
    emits: ['update:modelValue'],
    template: '<button type="button" data-testid="duty-search" @click="$emit(\'update:modelValue\', { id: \'duty-7\', name: \'Narys\', homeTenantId: 5 })" />',
  },
  AccessChangeWarningDialog: true,
  UserAvatar: { template: '<span class="avatar" />' },
  InflectedDutyName: { props: ['name'], template: '<span>{{ typeof name === "string" ? name : name?.lt }}</span>' },
};

const duty = { id: 'duty-1', name: { lt: 'Pirmininkas', en: 'Chair' }, places_to_occupy: 1, institution: { name: 'VU SA MIF' } };

const mountSheet = (props: Record<string, unknown> = {}) =>
  mount(AssignDutyUserSheet, { props: { open: true, duty, ...props }, global: { stubs } });

const lastForm = () => vi.mocked(useForm).mock.results.at(-1)!.value as FormMock;

describe('AssignDutyUserSheet.vue', () => {
  beforeEach(() => {
    vi.useFakeTimers({ toFake: ['Date'] });
    // 01:30 in Vilnius is still the previous day in UTC.
    vi.setSystemTime(new Date('2026-09-19T22:30:00Z'));
    vi.mocked(useForm).mockClear();
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  describe('create from a member (duty not fixed)', () => {
    const person = { id: 'user-9', name: 'Ona', email: 'ona@vusa.lt' };

    it('asks for the duty and treats the member as fixed', () => {
      const wrapper = mountSheet({ duty: null, user: person });

      expect(wrapper.text()).toContain('Pridėti pareigybę');
      expect(wrapper.find('[data-testid="duty-search"]').exists()).toBe(true);
      expect(wrapper.find('[data-testid="member-search"]').exists()).toBe(false);
      expect(wrapper.text()).toContain('Ona');
    });

    it('posts the chosen duty with the fixed member', async () => {
      const wrapper = mountSheet({ duty: null, user: person });
      const form = lastForm();

      await wrapper.find('[data-testid="duty-search"]').trigger('click');
      await wrapper.find('form').trigger('submit');

      expect(form.duty_id).toBe('duty-7');
      expect(form.user_id).toBe('user-9');
      expect(form.post).toHaveBeenCalledWith('/mocked-route/dutiables.store', expect.any(Object));
    });

    it('only offers study programmes of the chosen duty\'s tenant', async () => {
      const wrapper = mountSheet({
        duty: null,
        user: person,
        studyPrograms: [
          { id: 1, name: 'Ekonomika', tenant_id: 5 },
          { id: 2, name: 'Fizika', tenant_id: 6 },
        ],
      });

      await wrapper.find('[data-testid="duty-search"]').trigger('click');

      const offered = (wrapper.vm as unknown as { availableStudyPrograms: { name: string }[] }).availableStudyPrograms;

      expect(offered.map(program => program.name)).toEqual(['Ekonomika']);
    });
  });

  describe('create', () => {
    it('shows the duty context and a member search, not a member banner', () => {
      const wrapper = mountSheet();

      expect(wrapper.text()).toContain('Priskirti narį');
      expect(wrapper.text()).toContain('Pirmininkas');
      expect(wrapper.text()).toContain('VU SA MIF');
      expect(wrapper.find('[data-testid="member-search"]').exists()).toBe(true);
    });

    it('starts on today\'s Vilnius date, not the UTC one', () => {
      mountSheet();

      expect(lastForm().start_date).toBe('2026-09-20');
    });

    it('posts to the store route with the chosen member', async () => {
      const wrapper = mountSheet({ user: { id: 'user-9', name: 'Ona' } });
      const form = lastForm();

      await wrapper.find('form').trigger('submit');

      expect(form.transform).toHaveBeenCalled();
      expect(form.post).toHaveBeenCalledWith('/mocked-route/dutiables.store', expect.objectContaining({ preserveScroll: true }));
      expect(form.user_id).toBe('user-9');
    });

    it('sends an empty description as null, and a written one as translations', async () => {
      const wrapper = mountSheet();
      const form = lastForm();
      await wrapper.find('form').trigger('submit');

      const transform = vi.mocked(form.transform).mock.calls[0][0] as (data: Record<string, unknown>) => Record<string, unknown>;
      const note = { lt: '', en: '' };

      expect(transform({ description: { lt: '  ', en: '' }, study_program_note: note }).description).toBeNull();
      expect(transform({ description: { lt: '<p>Sveiki</p>', en: '' }, study_program_note: note }).description).toEqual({ lt: '<p>Sveiki</p>', en: '' });
    });

    it('sends an empty study-programme note as null, and a written one as translations', async () => {
      const wrapper = mountSheet();
      const form = lastForm();
      await wrapper.find('form').trigger('submit');

      const transform = vi.mocked(form.transform).mock.calls[0][0] as (data: Record<string, unknown>) => Record<string, unknown>;
      const description = { lt: '', en: '' };

      expect(transform({ description, study_program_note: { lt: ' ', en: '' } }).study_program_note).toBeNull();
      expect(transform({ description, study_program_note: { lt: '1 kursas', en: '' } }).study_program_note).toEqual({ lt: '1 kursas', en: '' });
    });

    it('warns, without blocking, when every place is taken', () => {
      const full = mountSheet({ occupiedPlaces: 1 });
      const free = mountSheet({ occupiedPlaces: 0 });

      expect(full.find('[data-testid="assign-sheet-full-notice"]').exists()).toBe(true);
      expect(free.find('[data-testid="assign-sheet-full-notice"]').exists()).toBe(false);
    });

    it('hides the danger zone', () => {
      const wrapper = mountSheet();

      expect(wrapper.text()).not.toContain('Ištrinti priskyrimą');
    });
  });

  describe('edit', () => {
    const dutiable = {
      id: 'dutiable-1',
      duty_id: 'duty-1',
      dutiable_id: 'user-1',
      start_date: '2026-01-01',
      end_date: null,
      description: { lt: '<p>Vadovas</p>', en: '' },
      duty: { id: 'duty-1', name: { lt: 'Sekretorius' } },
      user: { id: 'user-1', name: 'Jonas Jonaitis', email: 'jonas@stud.vu.lt' },
    };

    it('shows the member and no search, and reads the description as translations', () => {
      const wrapper = mountSheet({ dutiable, duty: null });

      expect(wrapper.text()).toContain('Redaguoti kadenciją');
      expect(wrapper.text()).toContain('Jonas Jonaitis');
      expect(wrapper.text()).toContain('Sekretorius');
      expect(wrapper.find('[data-testid="member-search"]').exists()).toBe(false);
      expect(wrapper.text()).not.toContain('[object Object]');
      expect(lastForm().description).toEqual({ lt: '<p>Vadovas</p>', en: '' });
    });

    it('patches the dutiable on submit', async () => {
      const wrapper = mountSheet({ dutiable, duty: null });
      const form = lastForm();

      await wrapper.find('form').trigger('submit');

      expect(form.patch).toHaveBeenCalledTimes(1);
      expect(vi.mocked(form.patch).mock.calls[0][0]).toContain('/mocked-route/dutiables.update');
    });

    it('asks before ending the term, then ends it today (Vilnius date)', async () => {
      const wrapper = mountSheet({ dutiable, duty: null });
      const form = lastForm();

      expect(wrapper.findAll('[data-testid="confirm"]')).toHaveLength(0);

      const endButton = wrapper.findAll('button').find(b => b.text().includes('Baigti kadenciją'));
      await endButton!.trigger('click');
      await wrapper.find('[data-testid="confirm-yes"]').trigger('click');

      expect(form.end_date).toBe('2026-09-20');
      expect(form.patch).toHaveBeenCalled();
    });

    it('asks before deleting the assignment', async () => {
      const wrapper = mountSheet({ dutiable, duty: null });

      const deleteButton = wrapper.findAll('button').find(b => b.text() === 'Ištrinti');
      await deleteButton!.trigger('click');

      expect(wrapper.find('[data-testid="confirm"]').exists()).toBe(true);
    });

    it('cannot end a term that has not started', () => {
      const wrapper = mountSheet({ dutiable: { ...dutiable, start_date: '2027-01-01' }, duty: null });

      expect(wrapper.findAll('button').some(b => b.text().includes('Baigti kadenciją'))).toBe(false);
      expect(wrapper.findAll('button').some(b => b.text() === 'Ištrinti')).toBe(true);
    });

    it('locks dates and hides ending or deleting for an ex-officio term', () => {
      const wrapper = mountSheet({ dutiable: { ...dutiable, via_dutiable_id: 'source-1' }, duty: null });

      expect(wrapper.text()).toContain('Pareigos pagal pareigas (ex-officio)');
      expect(wrapper.findAll('input.date').every(input => input.attributes('disabled') !== undefined)).toBe(true);
      expect(wrapper.text()).not.toContain('Ištrinti priskyrimą');
    });
  });
});
