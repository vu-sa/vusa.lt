import { mount } from '@vue/test-utils';
import { router, usePage } from '@inertiajs/vue3';
import { describe, expect, it, vi } from 'vitest';

import MeetingDocumentsPanel from '../MeetingDocumentsPanel.vue';

import { createMockPage } from '@/tests/helpers/createMockPage';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

vi.stubGlobal('route', (name?: string) => (name === undefined ? { current: () => false } : `/mocked/${name}`));

const dialogStub = {
  name: 'CollectionSelectDialog',
  props: ['open', 'collection', 'multiple', 'baseFilterBy', 'disabledIds', 'title', 'description', 'confirmLabel', 'searchPlaceholder', 'emptyMessage'],
  template: '<div />',
};

const stubs = {
  CollectionSelectDialog: dialogStub,
  FilePicker: { name: 'FilePicker', template: '<div />', emits: ['pick'] },
  SectionCard: { template: '<section><slot name="action" /><slot name="empty" /><slot /></section>' },
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

  describe('pending SharePoint files', () => {
    const pending = {
      id: 42, title: 'Tarybos protokolas', name: 'protokolas.pdf', status: 'pending', content_type: null, language: null,
      document_date: '2026-09-14', institution: null, sharepoint_institution_label: null, sharepoint_path: 'VU SA/Protokolai',
      sharepoint_web_url: null, sharepoint_modified_at: null, removed_from_sharepoint_at: null, problems: [],
      sync_status: 'success', sync_error_message: null, public_url: null, can: { update: true },
    };

    it('offers a search for the rest only when the suggestions may not be all', () => {
      const few = factory({ pendingDocuments: [pending] });
      const full = factory({ pendingDocuments: Array.from({ length: 8 }, (_, index) => ({ ...pending, id: index + 1 })) });

      expect(few.find('[data-slot="meeting-pending-search"]').exists()).toBe(false);
      expect(full.find('[data-slot="meeting-pending-search"]').exists()).toBe(true);
    });

    it('lists them and links one by publishing it', async () => {
      vi.mocked(router.post).mockClear();
      const wrapper = factory({ pendingDocuments: [pending] });

      const section = wrapper.get('[data-slot="meeting-pending-documents"]');
      expect(section.text()).toContain('Tarybos protokolas');

      await section.get('button').trigger('click');

      expect(router.post).toHaveBeenCalledWith('/mocked/meetings.documents.store', { document_id: 42 }, expect.objectContaining({ preserveScroll: true }));
    });

    it('hides the list from someone who cannot link documents', () => {
      expect(factory({ pendingDocuments: [pending], canUpdate: false }).find('[data-slot="meeting-pending-documents"]').exists()).toBe(false);
    });
  });

  describe('SharePoint file picker', () => {
    it('links the picked files by their SharePoint list item', () => {
      const wasSecure = window.isSecureContext;
      vi.stubGlobal('isSecureContext', true);
      vi.mocked(usePage).mockReturnValue(createMockPage({ app: { url: 'https://www.vusa.test' } }));
      vi.mocked(router.post).mockClear();
      const wrapper = factory();

      wrapper.findComponent({ name: 'FilePicker' }).vm.$emit('pick', [
        { name: 'Protokolas.pdf', sharepointIds: { siteId: 'site', listId: 'list', listItemUniqueId: 'unique' } },
      ]);

      expect(router.post).toHaveBeenCalledWith(
        '/mocked/meetings.documents.storeFromSharepoint',
        { documents: [{ site_id: 'site', list_id: 'list', list_item_unique_id: 'unique' }] },
        expect.any(Object),
      );
      vi.stubGlobal('isSecureContext', wasSecure);
    });

    it('is not offered over plain http, where SharePoint sign-in cannot run', () => {
      vi.mocked(usePage).mockReturnValue(createMockPage({ app: { url: 'http://www.vusa.test' } }));

      expect(factory().findComponent({ name: 'FilePicker' }).exists()).toBe(false);
    });
  });
});
