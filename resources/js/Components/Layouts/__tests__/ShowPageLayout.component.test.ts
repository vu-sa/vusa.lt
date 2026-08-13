import { describe, it, expect, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';

import ShowPageLayout from '../ShowPageLayout.vue';

import { commonStubs } from '@/tests/stubs';

const TABS = [
  { value: 'overview', label: 'Apžvalga' },
  { value: 'files', label: 'Failai' },
];

/**
 * ActivityLogSheet fetches on mount and Tabs content is driven by reka-ui, which
 * only mounts the active panel. Everything else renders for real.
 */
const mountLayout = (props: Record<string, unknown> = {}, slots: Record<string, string> = {}) =>
  mount(ShowPageLayout, {
    props: { title: 'Pirmininkas', tabs: TABS, tabStorageKey: 'test-tab', ...props },
    slots,
    global: {
      stubs: {
        ...commonStubs,
        ActivityLogSheet: { template: '<div class="activity-log" />' },
      },
    },
  });

/**
 * reka-ui's TabsTrigger activates on `mousedown.left`, not `click` — a plain
 * click() does nothing, so drive it the way the component actually listens.
 */
const selectTab = (wrapper: ReturnType<typeof mount>, index: number) =>
  wrapper.findAll('[role="tab"]')[index].trigger('mousedown', { button: 0, ctrlKey: false });

describe('ShowPageLayout', () => {
  beforeEach(() => {
    localStorage.clear();
  });

  it('renders the title in the hero', () => {
    expect(mountLayout().text()).toContain('Pirmininkas');
  });

  it('renders a trigger per tab, with counts when present', () => {
    const wrapper = mountLayout({
      tabs: [
        { value: 'overview', label: 'Apžvalga' },
        { value: 'duties', label: 'Pareigos', count: 4 },
      ],
    });

    expect(wrapper.text()).toContain('Apžvalga');
    expect(wrapper.text()).toContain('Pareigos');
    expect(wrapper.text()).toContain('4');
  });

  it('hides a zero count rather than rendering "0"', () => {
    const wrapper = mountLayout({
      tabs: [{ value: 'overview', label: 'Apžvalga', count: 0 }],
    });

    expect(wrapper.text()).not.toContain('0');
  });

  it('fills the first tab from the slot named after its value', () => {
    const wrapper = mountLayout({}, { overview: '<p class="overview-body">body</p>' });

    expect(wrapper.find('.overview-body').exists()).toBe(true);
  });

  it('renders the default slot instead of tabs when no tabs are given', () => {
    const wrapper = mountLayout(
      { tabs: undefined },
      { default: '<p class="plain-body">body</p>' },
    );

    expect(wrapper.find('.plain-body').exists()).toBe(true);
    expect(wrapper.find('[role="tablist"]').exists()).toBe(false);
  });

  it('restores the remembered tab from storage', () => {
    localStorage.setItem('test-tab', 'files');

    const wrapper = mountLayout({}, { files: '<p class="files-body">files</p>' });

    expect(wrapper.find('.files-body').exists()).toBe(true);
  });

  it('falls back to the first tab when the stored value is no longer offered', () => {
    localStorage.setItem('test-tab', 'a-tab-that-was-removed');

    const wrapper = mountLayout({}, { overview: '<p class="overview-body">body</p>' });

    expect(wrapper.find('.overview-body').exists()).toBe(true);
  });

  it('mounts the activity log only when an audit subject type is given', () => {
    expect(mountLayout({ model: { id: '1' } }).find('.activity-log').exists()).toBe(false);

    const audited = mountLayout({ model: { id: '1' }, auditSubjectType: 'duty' });
    expect(audited.find('.activity-log').exists()).toBe(true);
  });

  it('renders the default slot alongside tabs, so page dialogs still mount', () => {
    const wrapper = mountLayout({}, {
      overview: '<p class="overview-body">body</p>',
      default: '<p class="page-dialog">dialog</p>',
    });

    expect(wrapper.find('.overview-body').exists()).toBe(true);
    expect(wrapper.find('.page-dialog').exists()).toBe(true);
  });

  it('renders the alert slot outside the tab panels', () => {
    const wrapper = mountLayout({}, { alert: '<p class="vacancy">vacant</p>' });

    expect(wrapper.find('.vacancy').exists()).toBe(true);
  });

  it('renders the subtitle slot in the hero', () => {
    const wrapper = mountLayout({}, { subtitle: '<p class="joint">Kartu su MIF</p>' });

    expect(wrapper.find('.joint').exists()).toBe(true);
  });

  it('renders a tab icon when one is given', () => {
    const wrapper = mountLayout({
      tabs: [{ value: 'overview', label: 'Apžvalga', icon: { template: '<i class="tab-icon" />' } }],
    });

    expect(wrapper.find('.tab-icon').exists()).toBe(true);
  });

  describe('controlled tabs (v-model:tab)', () => {
    it('renders the panel named by the bound value, ignoring storage', () => {
      localStorage.setItem('test-tab', 'overview');

      const wrapper = mountLayout({ tab: 'files' }, { files: '<p class="files-body">files</p>' });

      expect(wrapper.find('.files-body').exists()).toBe(true);
    });

    it('emits update:tab instead of writing storage when a trigger is clicked', async () => {
      const wrapper = mountLayout({ tab: 'overview' });

      await selectTab(wrapper, 1);

      expect(wrapper.emitted('update:tab')?.[0]).toEqual(['files']);
      // The page owns persistence in controlled mode; the layout must stay out of it.
      expect(localStorage.getItem('test-tab')).toBeNull();
    });

    it('leaves storage untouched on mount', () => {
      mountLayout({ tab: 'overview' });

      expect(localStorage.getItem('test-tab')).toBeNull();
    });

    it('still writes storage when uncontrolled', async () => {
      const wrapper = mountLayout();

      await selectTab(wrapper, 1);

      expect(localStorage.getItem('test-tab')).toBe('files');
    });
  });
});
