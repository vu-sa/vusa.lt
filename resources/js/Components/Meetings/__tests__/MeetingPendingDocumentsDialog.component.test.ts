import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

import MeetingPendingDocumentsDialog from '../MeetingPendingDocumentsDialog.vue';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

vi.stubGlobal('route', (name?: string) => (name === undefined ? { current: () => false } : `/mocked/${name}`));

const searchSelectDialogStub = {
  name: 'SearchSelectDialog',
  props: ['open', 'multiple', 'title', 'description', 'confirmLabel'],
  emits: ['update:open', 'confirm'],
  template: '<div><slot name="trigger" /><slot :selected-ids="new Set()" :toggle="() => {}" /></div>',
};

const pending = {
  id: 42, title: 'Tarybos protokolas', name: 'protokolas.pdf', status: 'pending', content_type: null, language: null,
  document_date: '2026-09-14', institution: null, sharepoint_institution_label: null, sharepoint_path: 'VU SA/Protokolai',
  sharepoint_web_url: null, sharepoint_modified_at: null, removed_from_sharepoint_at: null, problems: [],
  sync_status: 'success', sync_error_message: null, public_url: null, can: { update: true },
};

const factory = () => mount(MeetingPendingDocumentsDialog, {
  props: { meetingId: 'm1', suggestions: [pending] },
  global: { stubs: { SearchSelectDialog: searchSelectDialogStub } },
});

describe('MeetingPendingDocumentsDialog', () => {
  it('lists the suggested files with their SharePoint folder and date', () => {
    const text = factory().get('[data-slot="meeting-pending-documents"]').text();

    expect(text).toContain('Tarybos protokolas');
    expect(text).toContain('VU SA/Protokolai · 2026-09-14');
  });

  it('confirms with the chosen document ids', () => {
    const wrapper = factory();

    wrapper.findComponent({ name: 'SearchSelectDialog' }).vm.$emit('confirm', [{ id: 'documents-42', recordId: '42' }]);

    expect(wrapper.emitted('confirm')).toEqual([[[42]]]);
  });
});
