import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import CollectionPrimaryCell from '../CollectionPrimaryCell.vue';

import { documentWithMatch, type SearchMatchDocument } from '@/Shared/Search/matches';

describe('CollectionPrimaryCell', () => {
  it('highlights the existing title and shows a distinct excerpt in place of the sub-line', () => {
    const title = 'Student guide';
    const doc = documentWithMatch({ document: { title }, highlights: [
      { field: 'title', snippet: '⟦Student⟧ guide' },
      { field: 'body', snippet: 'Information for ⟦students⟧' },
    ] }) as SearchMatchDocument;
    const wrapper = mount(CollectionPrimaryCell, { props: { title, clickable: true, sub: 'guides/students', titleMatches: doc._searchTitleMatches, match: doc._searchMatch } });
    expect(wrapper.get('button').text()).toBe(title);
    expect(wrapper.get('button mark').text()).toBe('Student');
    expect(wrapper.get('p').text()).toBe('Information for students');
    expect(wrapper.text()).not.toContain('guides/students');
  });

  it('shows an unmatched title once and suppresses an excerpt repeating it', () => {
    const wrapper = mount(CollectionPrimaryCell, { props: { title: 'Guide', match: { field: 'body', segments: [{ text: 'Guide', matched: true }] } } });
    expect(wrapper.text()).toBe('Guide');
    expect(wrapper.find('mark').exists()).toBe(false);
    expect(wrapper.find('[data-field="body"]').exists()).toBe(false);
  });

  it('never adds an excerpt line to a row without a sub-line', () => {
    const wrapper = mount(CollectionPrimaryCell, { props: { title: 'Guide', match: { field: 'body', segments: [{ text: 'for students', matched: true }] } } });
    expect(wrapper.findAll('p')).toHaveLength(1);
    expect(wrapper.text()).toBe('Guide');
  });

  it('keeps the sub-line to one line unless it is a description, which wraps to three', () => {
    const line = (props: Record<string, unknown>) => mount(CollectionPrimaryCell, { props: { title: 'Komunikacija', sub: 'Tvarko naujienas.', ...props } }).findAll('p')[1];

    expect(line({}).classes()).toContain('truncate');
    expect(line({ subLines: 3 }).classes()).toContain('line-clamp-3');
    expect(line({ subLines: 3 }).classes()).not.toContain('truncate');
  });

  it('clamps title to two lines and exposes the full title attribute when requested', () => {
    const title = 'A long title that needs two lines to display properly';
    const wrapper = mount(CollectionPrimaryCell, { props: { title, titleLines: 2, href: '/mano/meetings/1' } });
    const link = wrapper.get('a');

    expect(link.classes()).toContain('line-clamp-2');
    expect(link.classes()).not.toContain('truncate');
    expect(link.attributes('title')).toBe(title);
  });

  it('exposes the full title attribute on button and paragraph variants', () => {
    const title = 'A long title on a button or plain text';
    const buttonWrapper = mount(CollectionPrimaryCell, { props: { title, clickable: true } });
    expect(buttonWrapper.get('button').attributes('title')).toBe(title);

    const textWrapper = mount(CollectionPrimaryCell, { props: { title } });
    expect(textWrapper.get('p').attributes('title')).toBe(title);
  });
});
