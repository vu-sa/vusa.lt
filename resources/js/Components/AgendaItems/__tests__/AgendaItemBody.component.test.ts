import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import AgendaItemBody from '@/Components/AgendaItems/AgendaItemBody.vue';
import { createMockForm } from '@/tests/helpers/createMockForm';
import { commonStubs } from '@/tests/stubs';

function makeForm(overrides: Record<string, unknown> = {}) {
  return createMockForm({
    title: { lt: 'Test item', en: '' },
    type: 'voting',
    brought_by_students: false,
    student_position: { lt: '', en: '' },
    description: { lt: '', en: '' },
    votes: [
      { id: '1', is_main: true, is_consensus: false, title: { lt: '', en: '' }, note: { lt: '', en: '' }, student_vote: 'positive', decision: 'positive', student_benefit: 'positive', order: 0 },
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

const typeButton = (wrapper: ReturnType<typeof mount>, label: string) =>
  wrapper.findAll('button').find(b => b.text() === label);

describe('AgendaItemBody', () => {
  /**
   * Swapping the controls for a read-only rendering shifted the whole page as the
   * edit toggle flipped, so both modes render the same markup.
   */
  describe('stable layout across modes', () => {
    it('renders the same settings card in both modes', () => {
      for (const editing of [true, false]) {
        const { wrapper } = factory({ editing });
        expect(wrapper.text()).toContain('Klausimo tipas');
        expect(wrapper.text()).toContain('Laikas');
        expect(wrapper.text()).toContain('Atstovų iškeltas klausimas');
      }
    });

    it('locks the type control instead of hiding it', () => {
      expect(typeButton(factory({ editing: true }).wrapper, 'Informacinis')!.attributes('disabled')).toBeUndefined();
      expect(typeButton(factory({ editing: false }).wrapper, 'Informacinis')!.attributes('disabled')).toBeDefined();
    });

    it('keeps the description editable only while editing', () => {
      expect(factory({ editing: true }).wrapper.find('textarea').attributes('readonly')).toBeUndefined();
      expect(factory({ editing: false }).wrapper.find('textarea').attributes('readonly')).toBeDefined();
    });
  });

  describe('type', () => {
    it('sets the item type from the segmented control', async () => {
      const { wrapper, form } = factory({ editing: true, formOverrides: { type: null, votes: [] } });

      await typeButton(wrapper, 'Informacinis')!.trigger('click');

      expect(form.type).toBe('informational');
    });

    it('does not render the voting section for informational items', () => {
      const { wrapper } = factory({ editing: false, formOverrides: { type: 'informational', votes: [] } });
      expect(wrapper.text()).not.toContain('Balsavimo klausimai');
    });

    it('does not render its own save button (lives in the page action bar)', () => {
      const { wrapper } = factory({ editing: true });
      expect(wrapper.find('button[type="submit"]').exists()).toBe(false);
    });
  });

  describe('governance scope', () => {
    it('hides the student position tab for a VU SA body', () => {
      expect(factory({ editing: true, requiresStudentPerspective: true }).wrapper.text())
        .toContain('Išsakyta studentų pozicija');
      expect(factory({ editing: true, requiresStudentPerspective: false }).wrapper.text())
        .not.toContain('Išsakyta studentų pozicija');
    });
  });

  describe('agenda item types', () => {
    it('offers a break alongside the other types', () => {
      const { wrapper } = factory({ editing: true });

      // A pause is part of the agenda; without the option editors mistyped it as something else.
      expect(wrapper.text()).toContain('Pertrauka');
      expect(wrapper.text()).toContain('Balsavimas');
      expect(wrapper.text()).toContain('Informacinis');
      expect(wrapper.text()).toContain('Atidėtas');
    });

    it('records the break type on the form', async () => {
      const { wrapper, form } = factory({ editing: true });

      const breakButton = wrapper.findAll('button').find(b => b.text().includes('Pertrauka'));
      await breakButton?.trigger('click');

      expect(form.type).toBe('break');
    });
  });
});
