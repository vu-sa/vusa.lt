import { enableAutoUnmount, mount } from '@vue/test-utils';
import { afterEach, describe, expect, it } from 'vitest';

import SearchMatch from '@/Components/ui/SearchMatch.vue';

import { descriptionMatch, documentWithMatch, highlightText, matchTitle, type SearchMatchDocument } from '../matches';

enableAutoUnmount(afterEach);

describe('search match rendering', () => {
  it('highlights inflected title words from stemmed fields without adding an excerpt', () => {
    const title = 'VU SA Įstatai (nuo 2025 m.)';
    const doc = documentWithMatch({ document: { title }, highlights: [{ field: 'search_text_lt', snippet: 'VU SA ⟦Įstatai⟧ (nuo 2025 m.)' }] }) as SearchMatchDocument;
    const wrapper = mount(SearchMatch, { props: { match: matchTitle(title, doc._searchTitleMatches) } });
    expect(wrapper.text()).toBe(title);
    expect(wrapper.get('mark').text()).toBe('Įstatai');
    expect(doc._searchMatch).toBeUndefined();
  });
  it('prefers body excerpts and renders untrusted highlight text without HTML', () => {
    const document = documentWithMatch({ document: { title: 'Guide' }, highlights: [
      { field: 'title', snippet: '⟦Guide⟧' },
      { field: 'body', snippet: '<img src=x onerror=alert(1)> ⟦students⟧ & friends' },
    ] });
    const wrapper = mount(SearchMatch, { props: { match: (document as SearchMatchDocument)._searchMatch } });
    expect(wrapper.find('img').exists()).toBe(false);
    expect(wrapper.find('mark').text()).toBe('students');
    expect(wrapper.text()).toBe('students & friends');
  });

  it('strips stored and encoded HTML while keeping highlighted text and block spacing', () => {
    for (const snippet of [
      '<p>For <strong>⟦students⟧</strong></p><p>&amp; representatives</p><script>alert(1)</script>',
      '&lt;p&gt;For &lt;strong&gt;⟦students⟧&lt;/strong&gt;&lt;/p&gt;&lt;p&gt;&amp;amp; representatives&lt;/p&gt;',
    ]) {
      const doc = documentWithMatch({ document: { title: 'Guide' }, highlights: [{ field: 'description', snippet }] });
      const wrapper = mount(SearchMatch, { props: { match: (doc as SearchMatchDocument)._searchMatch } });
      expect(wrapper.text()).toBe('For students & representatives');
      expect(wrapper.find('mark').text()).toBe('students');
    }
  });

  it('keeps metadata matches out of the excerpt and highlights them where the row shows them', () => {
    const doc = documentWithMatch({ document: { title: 'Guide', content_type: 'VU SA Įstatai', summary: 'Rules for students' }, highlights: [
      { field: 'content_type', snippet: 'VU SA ⟦Įstatai⟧' },
      { field: 'summary', snippet: 'Rules for ⟦stud⟧ents' },
    ] }) as SearchMatchDocument;
    expect(doc._searchMatch?.field).toBe('summary');
    expect(highlightText('Įstatai', doc._searchFieldMatches?.content_type)?.segments).toEqual([{ text: 'Įstatai', matched: true }]);
    const summary = mount(SearchMatch, { props: { match: descriptionMatch(doc, 'summary') } });
    expect(summary.text()).toBe('Rules for students');
    expect(summary.get('mark').text()).toBe('stud');
  });

  it('preserves the complete displayed title when the highlighted snippet is truncated', () => {
    const title = 'A long guide for students and their representatives';
    const doc = documentWithMatch({ document: { title }, highlights: [{ field: 'title', snippet: 'guide for ⟦students⟧ and their' }] }) as SearchMatchDocument;
    const match = matchTitle(title, doc._searchTitleMatches);
    const wrapper = mount(SearchMatch, { props: { match } });
    expect(wrapper.text()).toBe(title);
    expect(wrapper.get('mark').text()).toBe('students');
    expect(matchTitle('A different translation', doc._searchTitleMatches)).toBeUndefined();
  });
});
