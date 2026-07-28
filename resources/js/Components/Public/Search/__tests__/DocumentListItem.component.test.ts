import { describe, test, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';

vi.mock('vue-sonner', () => ({
  toast: {
    success: vi.fn(),
    error: vi.fn(),
    info: vi.fn(),
  },
}));

import DocumentListItem from '../DocumentListItem.vue';

import { commonStubs, stubIcon } from '@/tests/stubs';
import type { DocumentDisplayItem } from '@/Composables/useDocumentDisplay';

// Icons are stubbed per the project's "real components by default" policy —
// @iconify/vue's <Icon> fetches icon data at runtime, which is unnecessary
// noise here since we assert on href/badge/warning presence, not icon markup.
const stubs = { ...commonStubs, Icon: stubIcon('icon') };

const baseDocument: DocumentDisplayItem = {
  id: 1,
  title: 'VU SA Veiklos ataskaita 2022-2023 m. m',
  anonymous_url: 'https://sharepoint.example.com/document/123',
  share_url: 'https://vusa.lt/d/abc123',
};

describe('DocumentListItem', () => {
  test('links to link_url for a resolved shortcut document', () => {
    const wrapper = mount(DocumentListItem, {
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
    const wrapper = mount(DocumentListItem, {
      props: {
        document: {
          ...baseDocument,
          name: 'protokolas.pdf',
        },
      },
      global: { stubs },
    });

    expect(wrapper.find('a').attributes('href')).toBe('https://vusa.lt/d/abc123');
  });

  test('hides the download button for a shortcut document', () => {
    const wrapper = mount(DocumentListItem, {
      props: {
        document: {
          ...baseDocument,
          name: 'ataskaita2023.vusa.lt.url',
          link_url: 'https://ataskaita2023.vusa.lt',
        },
      },
      global: { stubs },
    });

    expect(wrapper.findAll('button')).toHaveLength(2); // Open + Copy-link only
  });

  test('shows the download button for a normal document', () => {
    const wrapper = mount(DocumentListItem, {
      props: {
        document: {
          ...baseDocument,
          name: 'protokolas.pdf',
        },
      },
      global: { stubs },
    });

    expect(wrapper.findAll('button')).toHaveLength(3); // Open + Download + Copy-link
  });

  test('renders the link badge only for shortcut documents', () => {
    const shortcut = mount(DocumentListItem, {
      props: {
        document: {
          ...baseDocument,
          name: 'ataskaita2023.vusa.lt.url',
          link_url: 'https://ataskaita2023.vusa.lt',
        },
      },
      global: { stubs },
    });
    expect(shortcut.text()).toContain('search.document_link_badge');

    const normal = mount(DocumentListItem, {
      props: { document: { ...baseDocument, name: 'protokolas.pdf' } },
      global: { stubs },
    });
    expect(normal.text()).not.toContain('search.document_link_badge');
  });

  test('renders the unresolved-shortcut warning only when link_url is missing', () => {
    const unresolved = mount(DocumentListItem, {
      props: {
        document: {
          ...baseDocument,
          name: 'ataskaita2023.vusa.lt.url',
          link_url: null,
        },
      },
      global: { stubs },
    });
    expect(unresolved.text()).toContain('search.document_link_unresolved');

    const resolved = mount(DocumentListItem, {
      props: {
        document: {
          ...baseDocument,
          name: 'ataskaita2023.vusa.lt.url',
          link_url: 'https://ataskaita2023.vusa.lt',
        },
      },
      global: { stubs },
    });
    expect(resolved.text()).not.toContain('search.document_link_unresolved');

    const normal = mount(DocumentListItem, {
      props: { document: { ...baseDocument, name: 'protokolas.pdf' } },
      global: { stubs },
    });
    expect(normal.text()).not.toContain('search.document_link_unresolved');
  });

  test('copying the link writes link_url to the clipboard when resolved', async () => {
    const writeText = vi.fn().mockResolvedValue(undefined);
    Object.assign(navigator, { clipboard: { writeText } });

    const wrapper = mount(DocumentListItem, {
      props: {
        document: {
          ...baseDocument,
          name: 'ataskaita2023.vusa.lt.url',
          link_url: 'https://ataskaita2023.vusa.lt',
        },
      },
      global: { stubs },
    });

    // Open + Copy-link (download hidden for shortcuts) — copy-link is the last one.
    const buttons = wrapper.findAll('button');
    await buttons[buttons.length - 1].trigger('click');

    expect(writeText).toHaveBeenCalledWith('https://ataskaita2023.vusa.lt');
  });
});
