import { afterEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';

import WorkspacePicker from '../WorkspacePicker.vue';

import { atstovavimas, pradzia, rezervacijos } from './fixtures';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

// Same reasoning as PadalinysSelector's test: reka's Popover portals and traps focus in ways jsdom
// cannot reproduce, so the pieces are stubbed and what is asserted is the wiring.
const stubs = {
  Popover: { props: ['open'], emits: ['update:open'], template: '<div :data-open="open"><slot /></div>' },
  PopoverTrigger: { template: '<div><slot /></div>' },
  PopoverContent: { emits: ['open-auto-focus', 'close-auto-focus'], template: '<div data-testid="panel"><slot /></div>' },
};

const mountPicker = (props: Record<string, unknown> = {}) => mount(WorkspacePicker, {
  props: {
    workspaces: [pradzia, atstovavimas, rezervacijos],
    activeWorkspace: atstovavimas,
    activeSection: atstovavimas.sections[1],
    ...props,
  },
  global: { stubs },
});

const isOpen = (wrapper: ReturnType<typeof mount>) => wrapper.find('[data-open]').attributes('data-open') === 'true';

describe('WorkspacePicker', () => {
  afterEach(() => {
    vi.useRealTimers();
  });

  it('names the current workspace on the trigger', () => {
    expect(mountPicker().find('button').text()).toContain('shell.workspaces.atstovavimas.title');
  });

  it('lists every workspace with its description', () => {
    const text = mountPicker().find('[data-testid="panel"]').text();

    for (const key of ['pradzia', 'atstovavimas', 'rezervacijos']) {
      expect(text).toContain(`shell.workspaces.${key}.title`);
      expect(text).toContain(`shell.workspaces.${key}.description`);
    }
  });

  it('marks the current workspace with a brand rule and lists its sections beneath it', () => {
    const wrapper = mountPicker();
    const links = wrapper.findAll('a');

    const current = links.find(link => link.text().includes('shell.workspaces.atstovavimas.title'));
    const other = links.find(link => link.text().includes('shell.workspaces.pradzia.title'));

    expect(current?.attributes('aria-current')).toBe('true');
    expect(current?.classes()).toContain('border-brand-fill');
    expect(other?.attributes('aria-current')).toBeUndefined();
    expect(links.filter(link => link.text().includes('shell.sections.')).map(link => link.text()))
      .toEqual(['shell.sections.apzvalga', 'shell.sections.posedziai']);
    expect(links.find(link => link.text() === 'shell.sections.posedziai')?.attributes('aria-current')).toBe('page');
  });

  it('links each workspace to its first section', () => {
    const link = mountPicker().findAll('a').find(candidate => candidate.text().includes('shell.workspaces.pradzia.title'));

    expect(link?.attributes('href')).toBe('/mocked-route/dashboard');
  });

  it('offers Visi skyriai only when the user may open it', () => {
    expect(mountPicker().text()).not.toContain('shell.chrome.all_sections');
    expect(mountPicker({ showAllSections: true }).text()).toContain('shell.chrome.all_sections');
  });

  it('opens on hover, and closes only after the grace period', async () => {
    vi.useFakeTimers();
    const wrapper = mountPicker();
    const trigger = wrapper.findComponent(stubs.PopoverTrigger);

    await trigger.trigger('mouseenter');
    expect(isOpen(wrapper)).toBe(true);

    await trigger.trigger('mouseleave');
    await vi.advanceTimersByTimeAsync(100);
    expect(isOpen(wrapper)).toBe(true);

    await vi.advanceTimersByTimeAsync(100);
    expect(isOpen(wrapper)).toBe(false);
  });

  it('stays open when the pointer moves from the trigger into the panel', async () => {
    vi.useFakeTimers();
    const wrapper = mountPicker();

    await wrapper.findComponent(stubs.PopoverTrigger).trigger('mouseenter');
    await wrapper.findComponent(stubs.PopoverTrigger).trigger('mouseleave');
    await wrapper.findComponent(stubs.PopoverContent).trigger('mouseenter');
    await vi.advanceTimersByTimeAsync(400);

    expect(isOpen(wrapper)).toBe(true);
  });

  it('ignores the click that follows a hover-open instead of closing the panel again', async () => {
    const wrapper = mountPicker();

    await wrapper.findComponent(stubs.PopoverTrigger).trigger('mouseenter');
    wrapper.findComponent(stubs.Popover).vm.$emit('update:open', false);
    await wrapper.vm.$nextTick();

    expect(isOpen(wrapper)).toBe(true);
  });
});
