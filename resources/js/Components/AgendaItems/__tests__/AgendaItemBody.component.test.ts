import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import AgendaItemBody from '@/Components/AgendaItems/AgendaItemBody.vue';
import { createMockForm } from '@/tests/helpers/createMockForm';
import { commonStubs } from '@/tests/stubs';

function makeForm(overrides: Record<string, unknown> = {}) {
  return createMockForm({
    title: 'Test item',
    type: 'voting',
    brought_by_students: false,
    student_position: '',
    description: '',
    votes: [
      { id: '1', is_main: true, is_consensus: false, title: null, student_vote: 'positive', decision: 'positive', student_benefit: 'positive', order: 0 },
    ],
    ...overrides,
  });
}

function factory(props: Record<string, unknown> = {}) {
  const form = makeForm((props.formOverrides as Record<string, unknown>) ?? {});
  const wrapper = mount(AgendaItemBody, {
    props: { form, editing: false, ...props },
    global: {
      stubs: {
        ...commonStubs,
        AdminVotingHelpButton: { template: '<div class="help-stub" />' },
      },
    },
  });
  return { wrapper, form };
}

describe('AgendaItemBody', () => {
  describe('view mode', () => {
    it('shows read-only vote values and the main marker', () => {
      const { wrapper } = factory({ editing: false });
      expect(wrapper.text()).toContain('Priimtas');
      expect(wrapper.text()).toContain('Pritarė');
      expect(wrapper.text()).toContain('Palanku');
      expect(wrapper.text()).toContain('Pagrindinis');
    });

    it('does not render vote buttons or an add-vote button in view mode', () => {
      const { wrapper } = factory({ editing: false });
      expect(wrapper.findAll('button').some(b => b.text().includes('Pridėti balsavimo klausimą'))).toBe(false);
    });

    it('shows a "not discussed" state for a voting item with no votes', () => {
      const { wrapper } = factory({ editing: false, formOverrides: { votes: [] } });
      expect(wrapper.text()).toContain('Neaptarta');
    });

    it('does not render the voting section for informational items', () => {
      const { wrapper } = factory({ editing: false, formOverrides: { type: 'informational', votes: [] } });
      expect(wrapper.text()).toContain('Informacinis');
      expect(wrapper.text()).not.toContain('Balsavimo klausimai');
    });

    it('shows the student-question marker when brought by students', () => {
      const { wrapper } = factory({ editing: false, formOverrides: { brought_by_students: true } });
      expect(wrapper.text()).toContain('Atstovų iškeltas klausimas');
    });
  });

  describe('governance scope', () => {
    it('records the student position for an external body', () => {
      const { wrapper } = factory({ editing: false, requiresStudentPerspective: true });
      expect(wrapper.text()).toContain('Rezultatas');
      expect(wrapper.text()).toContain('Studentai');
      expect(wrapper.text()).toContain('Nauda');
    });

    it('asks a VU SA body only for the outcome', () => {
      const { wrapper } = factory({ editing: false, requiresStudentPerspective: false });
      expect(wrapper.text()).toContain('Rezultatas');
      expect(wrapper.text()).not.toContain('Studentai');
      expect(wrapper.text()).not.toContain('Nauda');
    });

    it('hides the student position tab for a VU SA body', () => {
      expect(factory({ editing: true, requiresStudentPerspective: true }).wrapper.text())
        .toContain('Išsakyta studentų pozicija');
      expect(factory({ editing: true, requiresStudentPerspective: false }).wrapper.text())
        .not.toContain('Išsakyta studentų pozicija');
    });

    it('consensus only sets the outcome for a VU SA body', async () => {
      const { wrapper, form } = factory({
        editing: true,
        requiresStudentPerspective: false,
        formOverrides: {
          votes: [{ id: '1', is_main: true, is_consensus: false, title: null, student_vote: null, decision: null, student_benefit: null, order: 0 }],
        },
      });

      // Switches in order: "brought by students", then the per-vote "bendru sutarimu".
      const switches = wrapper.findAllComponents({ name: 'Switch' });
      await switches[switches.length - 1].vm.$emit('update:modelValue', true);

      expect(form.votes[0].decision).toBe('positive');
      expect(form.votes[0].student_vote).toBeNull();
      expect(form.votes[0].student_benefit).toBeNull();
    });
  });

  describe('edit mode', () => {
    it('adds a voting question', async () => {
      const { wrapper, form } = factory({ editing: true });
      const addButton = wrapper.findAll('button').find(b => b.text().includes('Pridėti balsavimo klausimą'));
      await addButton!.trigger('click');
      expect(form.votes).toHaveLength(2);
    });

    it('reflects meeting visibility on the public indicators', () => {
      expect(factory({ editing: true, meetingIsPublic: true }).wrapper.text()).toContain('matomas viešai');
      expect(factory({ editing: true, meetingIsPublic: false }).wrapper.text()).toContain('Nematomas viešai');
    });

    it('lets the only vote be removed, main or not', () => {
      const { wrapper, form } = factory({ editing: true });
      const removeButton = wrapper.findAll('button').find(b => b.attributes('title') === 'Šalinti balsavimą');

      expect(form.votes).toHaveLength(1);
      expect(form.votes[0].is_main).toBe(true);
      expect(removeButton?.attributes('disabled')).toBeUndefined();
    });

    it('removing the main vote promotes the next one', async () => {
      const { wrapper, form } = factory({
        editing: true,
        formOverrides: {
          votes: [
            { id: '1', is_main: true, is_consensus: false, title: null, student_vote: null, decision: null, student_benefit: null, order: 0 },
            { id: '2', is_main: false, is_consensus: false, title: null, student_vote: null, decision: null, student_benefit: null, order: 1 },
          ],
        },
      });

      const removeButtons = wrapper.findAll('button').filter(b => b.attributes('title') === 'Šalinti balsavimą');
      await removeButtons[0].trigger('click');

      expect(form.votes).toHaveLength(1);
      expect(form.votes[0].id).toBe('2');
      expect(form.votes[0].is_main).toBe(true);
    });

    it('leaves a voting item with no votes at all when the last one is removed', async () => {
      const { wrapper, form } = factory({ editing: true });
      const removeButton = wrapper.findAll('button').find(b => b.attributes('title') === 'Šalinti balsavimą');

      await removeButton!.trigger('click');

      expect(form.votes).toHaveLength(0);
    });

    it('does not render its own save button (lives in the page action bar)', () => {
      const { wrapper } = factory({ editing: true });
      expect(wrapper.find('button[type="submit"]').exists()).toBe(false);
    });
  });
});
