import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';

import { commonStubs } from '@/tests/stubs';
import {
  CellTooltip,
  DateCell,
  TagList,
  TruncatedBadge,
  TruncatedLink,
  TruncatedText,
} from '@/Components/ui/data-table/cells';

describe('TruncatedText', () => {
  it('renders the provided text', () => {
    const wrapper = mount(TruncatedText, {
      props: { text: 'Hello World' },
      global: { stubs: commonStubs },
    });

    expect(wrapper.text()).toContain('Hello World');
  });

  it('applies truncate class for single line', () => {
    const wrapper = mount(TruncatedText, {
      props: { text: 'Hello' },
      global: { stubs: commonStubs },
    });

    const span = wrapper.find('span');
    expect(span.classes()).toContain('truncate');
  });

  it('applies line-clamp class for multi-line', () => {
    const wrapper = mount(TruncatedText, {
      props: { text: 'Hello', lines: 2 },
      global: { stubs: commonStubs },
    });

    const span = wrapper.find('span');
    expect(span.classes()).toContain('line-clamp-2');
  });

  it('renders em-dash for null text', () => {
    const wrapper = mount(TruncatedText, {
      props: { text: null },
      global: { stubs: commonStubs },
    });

    expect(wrapper.text()).toBe('—');
  });

  // jsdom has no layout engine and no ResizeObserver, so nothing ever measures
  // as clipped here — which is exactly the "text fits" case we want covered.
  // Whether a real browser measures overflow correctly is not testable in jsdom.
  it('renders no tooltip when the text is not clipped', () => {
    const wrapper = mount(TruncatedText, {
      props: { text: 'Short' },
      global: { stubs: commonStubs },
    });

    expect(wrapper.find('.tooltip-content').exists()).toBe(false);
  });
});

describe('CellTooltip', () => {
  const mountTooltip = (props: Record<string, unknown>) => mount(CellTooltip, {
    props,
    slots: { default: '<span class="cell-value">Value</span>' },
    global: { stubs: commonStubs },
  });

  it('wraps the cell in a tooltip when the value is hidden', () => {
    const wrapper = mountTooltip({ text: 'Full value', enabled: true });

    expect(wrapper.find('.tooltip-content').text()).toBe('Full value');
    expect(wrapper.find('.cell-value').exists()).toBe(true);
  });

  it('renders the cell bare when nothing is hidden', () => {
    const wrapper = mountTooltip({ text: 'Full value', enabled: false });

    expect(wrapper.find('.tooltip-content').exists()).toBe(false);
    expect(wrapper.find('.cell-value').exists()).toBe(true);
  });

  it('renders no tooltip when there is no text to reveal', () => {
    const wrapper = mountTooltip({ text: null, enabled: true });

    expect(wrapper.find('.tooltip-content').exists()).toBe(false);
    expect(wrapper.find('.cell-value').exists()).toBe(true);
  });
});

describe('TruncatedLink', () => {
  it('renders an Inertia Link for internal routes', () => {
    const wrapper = mount(TruncatedLink, {
      props: { href: '/users/1', text: 'User Name' },
      global: { stubs: { ...commonStubs, Link: { template: '<a :href="href"><slot /></a>', props: ['href'] } } },
    });

    expect(wrapper.text()).toContain('User Name');
    expect(wrapper.find('a').exists()).toBe(true);
  });

  it('renders an anchor for external links', () => {
    const wrapper = mount(TruncatedLink, {
      props: { href: 'mailto:test@example.com', text: 'test@example.com', external: true },
      global: { stubs: commonStubs },
    });

    const anchor = wrapper.find('a');
    expect(anchor.exists()).toBe(true);
    expect(anchor.attributes('href')).toBe('mailto:test@example.com');
    expect(anchor.attributes('target')).toBe('_blank');
  });

  it('applies truncate class', () => {
    const wrapper = mount(TruncatedLink, {
      props: { href: '/test', text: 'Test' },
      global: { stubs: commonStubs },
    });

    expect(wrapper.find('a').classes()).toContain('truncate');
  });
});

describe('TruncatedBadge', () => {
  it('renders badge with text', () => {
    const wrapper = mount(TruncatedBadge, {
      props: { text: 'Draft' },
      global: { stubs: commonStubs },
    });

    expect(wrapper.text()).toContain('Draft');
  });

  it('renders em-dash for null text', () => {
    const wrapper = mount(TruncatedBadge, {
      props: { text: null },
      global: { stubs: commonStubs },
    });

    expect(wrapper.text()).toBe('—');
  });
});

describe('TagList', () => {
  it('renders all items when under maxVisible', () => {
    const wrapper = mount(TagList, {
      props: {
        items: [
          { id: 1, title: 'Tag A' },
          { id: 2, title: 'Tag B' },
        ],
        maxVisible: 3,
      },
      global: { stubs: commonStubs },
    });

    expect(wrapper.text()).toContain('Tag A');
    expect(wrapper.text()).toContain('Tag B');
    expect(wrapper.text()).not.toContain('+');
  });

  it('shows overflow count when items exceed maxVisible', () => {
    const wrapper = mount(TagList, {
      props: {
        items: [
          { id: 1, title: 'Tag A' },
          { id: 2, title: 'Tag B' },
          { id: 3, title: 'Tag C' },
        ],
        maxVisible: 2,
      },
      global: { stubs: commonStubs },
    });

    expect(wrapper.text()).toContain('Tag A');
    expect(wrapper.text()).toContain('Tag B');
    expect(wrapper.text()).toContain('+1');
    expect(wrapper.text()).not.toContain('Tag C');
  });

  it('resolves translatable label values', () => {
    const wrapper = mount(TagList, {
      props: {
        items: [{ id: 1, name: { lt: 'Pavadinimas', en: 'Title' } }],
        labelKey: 'name',
      },
      global: { stubs: commonStubs },
    });

    expect(wrapper.text()).toContain('Pavadinimas');
  });
});

describe('DateCell', () => {
  it('renders absolute date by default', () => {
    const wrapper = mount(DateCell, {
      props: { date: '2024-01-15T10:30:00' },
      global: { stubs: commonStubs },
    });

    expect(wrapper.text().length).toBeGreaterThan(0);
  });

  it('renders relative time when mode is relative', () => {
    const wrapper = mount(DateCell, {
      props: { date: new Date().toISOString(), mode: 'relative' },
      global: { stubs: commonStubs },
    });

    expect(wrapper.text().length).toBeGreaterThan(0);
  });

  it('renders em-dash for null date', () => {
    const wrapper = mount(DateCell, {
      props: { date: null },
      global: { stubs: commonStubs },
    });

    expect(wrapper.text()).toBe('—');
  });

  it('adds no tooltip to an absolute date, which already reads in full', () => {
    const wrapper = mount(DateCell, {
      props: { date: '2024-01-15T10:30:00' },
      global: { stubs: commonStubs },
    });

    expect(wrapper.find('.tooltip-content').exists()).toBe(false);
  });

  it('reveals the exact timestamp behind a relative date', () => {
    const wrapper = mount(DateCell, {
      props: { date: '2024-01-15T10:30:00', mode: 'relative' },
      global: { stubs: commonStubs },
    });

    expect(wrapper.find('.tooltip-content').text()).toContain('2024');
  });
});
