import { flushPromises, mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

import { adminSearchCollections, normalizeHit } from '../adminSearchCollections';
import SearchDetailPane from '../SearchDetailPane.vue';

vi.mock('@/Components/AgendaItems/AgendaItemPreview.vue', () => ({
  __esModule: true,
  default: { props: ['item'], template: '<p data-testid="agenda-preview">{{ item.title }}</p>' },
}));

describe('SearchDetailPane', () => {
  it('loads the preview for an agenda item and passes its document', async () => {
    const hit = normalizeHit('agenda_items', { id: 'a1', title: 'Posėdžio klausimas' });
    const wrapper = mount(SearchDetailPane, { props: { hit } });
    await vi.dynamicImportSettled();
    await flushPromises();

    expect(wrapper.get('[data-testid="agenda-preview"]').text()).toBe(hit.title);
    wrapper.unmount();
  });

  it('shows the selected hit when its collection has no preview', () => {
    const { preview } = adminSearchCollections.agenda_items;
    adminSearchCollections.agenda_items.preview = undefined;
    try {
      const hit = normalizeHit('agenda_items', { id: 'a1', title: 'Posėdžio klausimas' });
      const wrapper = mount(SearchDetailPane, { props: { hit } });
      try {
        expect(wrapper.get('[data-slot="search-hit-row"]').text()).toContain(hit.title);
      }
      finally {
        wrapper.unmount();
      }
    }
    finally {
      adminSearchCollections.agenda_items.preview = preview;
    }
  });
});
