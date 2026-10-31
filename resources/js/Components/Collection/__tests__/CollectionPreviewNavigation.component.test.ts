import { mount } from '@vue/test-utils';
import { ref } from 'vue';
import { describe, expect, it, vi } from 'vitest';

import DocumentPreview from '@/Components/Files/DocumentPreview.vue';
import AgendaItemPreview from '@/Components/AgendaItems/AgendaItemPreview.vue';
import MeetingPreview from '@/Components/Meetings/MeetingPreview.vue';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));
vi.mock('@/Composables/usePreviewApi', () => ({ usePreviewApi: () => ({ data: ref(null), isLoading: ref(false) }) }));

const slots = { actions: '<button data-testid="supplied-action">Veiksmas</button>' };

describe('collection preview navigation', () => {
  it('keeps the document record link alongside supplied file actions', () => {
    const wrapper = mount(DocumentPreview, { props: { document: { id: 'd1', title: 'Dokumentas', is_active: true } } as never, slots });
    expect(wrapper.get('[data-slot="preview-actions"] a').attributes('href')).toBe(route('documents.show', 'd1'));
    expect(wrapper.find('[data-testid="supplied-action"]').exists()).toBe(true);
  });

  it('keeps the parent meeting link alongside supplied agenda actions', () => {
    const wrapper = mount(AgendaItemPreview, { props: { item: { id: 'a1', title: 'Klausimas', meeting_id: 'm1' } } as never, slots });
    expect(wrapper.get('[data-slot="preview-actions"] a').attributes('href')).toBe('/mocked-route/meetings.show');
  });

  it('keeps the institution link alongside supplied meeting actions', () => {
    const wrapper = mount(MeetingPreview, { props: { meeting: { id: 'm1', title: 'Posėdis', institution_ids: ['i1'], start_time: 1793000000 } } as never, slots });
    expect(wrapper.get('[data-slot="preview-actions"] a').attributes('href')).toBe('/mocked-route/institutions.show');
  });
});
