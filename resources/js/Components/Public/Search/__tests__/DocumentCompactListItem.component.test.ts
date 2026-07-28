import { describe, test, expect } from 'vitest';
import { mount } from '@vue/test-utils';

import DocumentCompactListItem from '../DocumentCompactListItem.vue';

import { stubIcon } from '@/tests/stubs';
import type { DocumentDisplayItem } from '@/Composables/useDocumentDisplay';

// Icons are stubbed per the project's "real components by default" policy —
// @iconify/vue's <Icon> fetches icon data at runtime, which is unnecessary
// noise here since we assert on href/warning presence, not icon markup.
const stubs = { Icon: stubIcon('icon') };

const baseDocument: DocumentDisplayItem = {
  id: 1,
  title: 'VU SA Veiklos ataskaita 2022-2023 m. m',
  anonymous_url: 'https://sharepoint.example.com/document/123',
  share_url: 'https://vusa.lt/d/abc123',
};

describe('DocumentCompactListItem', () => {
  test('links to link_url for a resolved shortcut document', () => {
    const wrapper = mount(DocumentCompactListItem, {
      props: {
        document: {
          ...baseDocument,
          name: 'ataskaita2023.vusa.lt.url',
          link_url: 'https://ataskaita2023.vusa.lt',
        },
      },
      global: { stubs },
    });

    expect(wrapper.find('a').attributes('href')).toBe('https://ataskaita2023.vusa.lt');
  });

  test('links to share_url for a normal document', () => {
    const wrapper = mount(DocumentCompactListItem, {
      props: { document: { ...baseDocument, name: 'protokolas.pdf' } },
      global: { stubs },
    });

    expect(wrapper.find('a').attributes('href')).toBe('https://vusa.lt/d/abc123');
  });

  test('hints that a shortcut leads to an external site', () => {
    const shortcut = mount(DocumentCompactListItem, {
      props: {
        document: {
          ...baseDocument,
          name: 'ataskaita2023.vusa.lt.url',
          link_url: 'https://ataskaita2023.vusa.lt',
        },
      },
      global: { stubs },
    });
    expect(shortcut.find('a').attributes('title')).toBe('search.document_link_hint');

    const normal = mount(DocumentCompactListItem, {
      props: { document: { ...baseDocument, name: 'protokolas.pdf' } },
      global: { stubs },
    });
    expect(normal.find('a').attributes('title')).toBeUndefined();
  });

  test('shows the unresolved-shortcut warning icon only when link_url is missing', () => {
    const unresolved = mount(DocumentCompactListItem, {
      props: {
        document: {
          ...baseDocument,
          name: 'ataskaita2023.vusa.lt.url',
          link_url: null,
        },
      },
      global: { stubs },
    });
    expect(unresolved.findAll('[title="search.document_link_unresolved"]').length).toBeGreaterThan(0);

    const resolved = mount(DocumentCompactListItem, {
      props: {
        document: {
          ...baseDocument,
          name: 'ataskaita2023.vusa.lt.url',
          link_url: 'https://ataskaita2023.vusa.lt',
        },
      },
      global: { stubs },
    });
    expect(resolved.findAll('[title="search.document_link_unresolved"]').length).toBe(0);

    const normal = mount(DocumentCompactListItem, {
      props: { document: { ...baseDocument, name: 'protokolas.pdf' } },
      global: { stubs },
    });
    expect(normal.findAll('[title="search.document_link_unresolved"]').length).toBe(0);
  });
});
