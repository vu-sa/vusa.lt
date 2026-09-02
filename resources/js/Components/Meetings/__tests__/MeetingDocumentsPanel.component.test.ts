import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

import MeetingDocumentsPanel from '../MeetingDocumentsPanel.vue';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

vi.stubGlobal('route', (name?: string) => (name === undefined ? { current: () => false } : `/mocked/${name}`));

const dialogStub = {
  name: 'CollectionSelectDialog',
  props: ['open', 'collection', 'multiple', 'baseFilterBy', 'disabledIds', 'title', 'description', 'confirmLabel', 'searchPlaceholder', 'emptyMessage'],
  template: '<div />',
};

const stubs = {
  CollectionSelectDialog: dialogStub,
  SectionCard: { template: '<section><slot name="action" /><slot name="empty" /><slot /></section>' },
  FilePicker: { template: '<div />', props: ['loading'], emits: ['pick'] },
};

const factory = (props: Record<string, unknown> = {}) =>
  mount(MeetingDocumentsPanel, {
    props: {
      meetingId: 'm1',
      documents: [],
      canUpdate: true,
      ...props,
    },
    global: { stubs },
  });

const dialog = (wrapper: ReturnType<typeof mount>) => wrapper.findComponent({ name: 'CollectionSelectDialog' });

describe('MeetingDocumentsPanel', () => {
  it('scopes the picker to the meeting institutions only', () => {
    const wrapper = factory({ institutionIds: ['inst-1', 'inst-2'] });

    expect(dialog(wrapper).props('baseFilterBy')).toBe('institution_id:=[`inst-1`,`inst-2`]');
  });

  it('also accepts documents of the same tenants, not just the meeting institutions', () => {
    // Parlamentas meetings: paperwork is filed under the central VU SA institution of the
    // same tenant, so the institution-only filter matched nothing.
    const wrapper = factory({ institutionIds: ['parlamentas'], tenantShortnames: ['VU SA'] });

    expect(dialog(wrapper).props('baseFilterBy'))
      .toBe('institution_id:=[`parlamentas`] || tenant_shortname:=[`VU SA`]');
  });

  it('filters by tenants alone when the meeting has no institutions', () => {
    const wrapper = factory({ tenantShortnames: ['VU SA'] });

    expect(dialog(wrapper).props('baseFilterBy')).toBe('tenant_shortname:=[`VU SA`]');
  });

  it('leaves the picker unscoped when neither is given', () => {
    expect(dialog(factory()).props('baseFilterBy')).toBeUndefined();
  });

  describe('language labelling', () => {
    const doc = (overrides: Record<string, unknown> = {}) => ({
      id: 1,
      title: 'VU SA Tarybos protokolas',
      name: null,
      content_type: 'Protokolas',
      document_date: '2026-07-23',
      anonymous_url: null,
      language_code: 'lt',
      ...overrides,
    });

    it('labels a document with its language', () => {
      const wrapper = factory({ documents: [doc()] });

      expect(wrapper.text()).toContain('LT');
    });

    it('labels an English document as EN', () => {
      const wrapper = factory({ documents: [doc({ language_code: 'en' })] });

      expect(wrapper.text()).toContain('EN');
    });

    it('claims no language for a document SharePoint never labelled', () => {
      const wrapper = factory({ documents: [doc({ language_code: 'unknown' })] });

      expect(wrapper.text()).not.toContain('UNKNOWN');
      // The chip is absent entirely rather than rendered empty.
      expect(wrapper.findAll('span').some(s => s.text() === 'LT' || s.text() === 'EN')).toBe(false);
    });

    it('renders the document date as a plain day', () => {
      const wrapper = factory({ documents: [doc()] });

      expect(wrapper.text()).toContain('2026-07-23');
      expect(wrapper.text()).not.toContain('00:00:00');
      expect(wrapper.text()).not.toContain('T00:00');
    });
  });
});
