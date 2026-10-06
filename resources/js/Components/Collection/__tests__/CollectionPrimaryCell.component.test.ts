import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import CollectionPrimaryCell from '../CollectionPrimaryCell.vue';

import { documentWithMatch, type SearchMatchDocument } from '@/Shared/Search/matches';

describe('CollectionPrimaryCell', () => {
  it('highlights the existing title and retains a distinct content excerpt', () => {
    const title = 'Student guide';
    const doc = documentWithMatch({ document: { title }, highlights: [
      { field: 'title', snippet: '⟦Student⟧ guide' },
      { field: 'body', snippet: 'Information for ⟦students⟧' },
    ] }) as SearchMatchDocument;
    const wrapper = mount(CollectionPrimaryCell, { props: { title, clickable: true, titleMatches: doc._searchTitleMatches, match: doc._searchMatch } });
    expect(wrapper.get('button').text()).toBe(title);
    expect(wrapper.get('button mark').text()).toBe('Student');
    expect(wrapper.get('[data-slot="search-match"]').text()).toBe('Information for students');
  });

  it('shows an unmatched title once and suppresses an excerpt repeating it', () => {
    const wrapper = mount(CollectionPrimaryCell, { props: { title: 'Guide', match: { field: 'body', segments: [{ text: 'Guide', matched: true }] } } });
    expect(wrapper.text()).toBe('Guide');
    expect(wrapper.find('mark').exists()).toBe(false);
    expect(wrapper.find('[data-slot="search-match"]').exists()).toBe(false);
  });
});
