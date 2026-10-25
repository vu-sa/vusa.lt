import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';

import { PreviewChipList, PreviewFact, PreviewPane, PreviewSection } from '..';

describe('PreviewPane', () => {
  it('links the title to the canonical page when given an href', () => {
    const wrapper = mount(PreviewPane, {
      props: { eyebrow: 'Posėdis', title: 'Senato posėdis', href: '/mano/meetings/1', subtitle: 'Senatas' },
    });

    expect(wrapper.text()).toContain('Posėdis');
    expect(wrapper.text()).toContain('Senatas');
    expect(wrapper.find('h2 a').attributes('href')).toBe('/mano/meetings/1');
  });

  it('renders the slots it is given and omits the empty regions', () => {
    const bare = mount(PreviewPane, { props: { eyebrow: 'Žyma', title: 'T' } });
    expect(bare.find('[data-slot="preview-actions"]').exists()).toBe(false);

    const wrapper = mount(PreviewPane, {
      props: { eyebrow: 'Žyma', title: 'T' },
      slots: {
        badges: '<span class="badge">Aktyvi</span>',
        status: '<span class="status">Juodraštis</span>',
        actions: '<button class="open">Atidaryti</button>',
        default: '<p class="body">Turinys</p>',
      },
    });

    expect(wrapper.find('.badge').exists()).toBe(true);
    expect(wrapper.find('.status').exists()).toBe(true);
    expect(wrapper.find('[data-slot="preview-actions"] .open').exists()).toBe(true);
    expect(wrapper.find('.body').exists()).toBe(true);
  });
});

describe('PreviewFact', () => {
  it('shows a dash for a missing value', () => {
    const wrapper = mount(PreviewFact, { props: { label: 'El. paštas', value: '' } });

    expect(wrapper.find('dt').text()).toBe('El. paštas');
    expect(wrapper.find('dd').text()).toBe('—');
  });

  it('keeps zero as a value', () => {
    const wrapper = mount(PreviewFact, { props: { label: 'Nariai', value: 0, tone: 'attention' } });

    expect(wrapper.find('dd').text()).toBe('0');
    expect(wrapper.find('dd').classes()).toContain('text-status-attention');
  });
});

describe('PreviewSection', () => {
  it('shows a skeleton while loading, then the empty line or the content', async () => {
    const wrapper = mount(PreviewSection, {
      props: { title: 'Darbotvarkė', loading: true, emptyMessage: 'Nėra punktų' },
      slots: { default: '<ol class="content" />' },
    });
    expect(wrapper.find('[data-slot="preview-section-skeleton"]').exists()).toBe(true);
    expect(wrapper.find('.content').exists()).toBe(false);

    await wrapper.setProps({ loading: false, empty: true });
    expect(wrapper.text()).toContain('Nėra punktų');

    await wrapper.setProps({ empty: false });
    expect(wrapper.find('.content').exists()).toBe(true);
  });
});

describe('PreviewChipList', () => {
  const items = Array.from({ length: 8 }, (_, i) => ({ id: i, name: `Institucija ${i}`, href: `/i/${i}` }));

  it('caps the list and counts the rest', () => {
    const wrapper = mount(PreviewChipList, { props: { items, max: 6, moreHref: '/i' } });

    expect(wrapper.findAll('li a[href^="/i/"]')).toHaveLength(6);
    expect(wrapper.find('[data-slot="preview-chip-more"]').text()).toBe('+2');
    expect(wrapper.find('[data-slot="preview-chip-more"] a').attributes('href')).toBe('/i');
  });

  it('shows the empty message when there is nothing to list', () => {
    const wrapper = mount(PreviewChipList, { props: { items: [], emptyMessage: 'Nėra' } });

    expect(wrapper.text()).toBe('Nėra');
  });
});
