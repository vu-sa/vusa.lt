import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

import HeroButtonHotspot from '../HeroButtonHotspot.vue';
import { ACTIVE_HOTSPOT_KEY, useActiveHotspot } from '../../Editor/Fullscreen/useActiveHotspot';
import { commonStubs, stubPopover, stubPopoverAnchor, stubPopoverContent } from '@/tests/stubs';

const stubs = {
  ...commonStubs,
  Popover: stubPopover,
  PopoverAnchor: stubPopoverAnchor,
  PopoverContent: stubPopoverContent,
  RCIcon: { props: ['name'], template: '<span class="rc-icon" />' },
  RCIconSelect: { props: ['modelValue', 'allowNone'], template: '<div class="rc-icon-select" />' },
  Select: {
    props: ['modelValue'],
    emits: ['update:modelValue'],
    template: '<select :value="modelValue" @change="$emit(\'update:modelValue\', $event.target.value)"><slot /></select>',
  },
  SelectTrigger: { template: '<div><slot /></div>' },
  SelectValue: { template: '<div />' },
  SelectContent: { template: '<slot />' },
  SelectItem: { props: ['value'], template: '<option :value="value"><slot /></option>' },
};

function mountHotspot(overrides: Record<string, unknown> = {}) {
  const hotspots = useActiveHotspot();
  const wrapper = mount(HeroButtonHotspot, {
    props: {
      button: { text: 'Registruotis', link: '#reg', variant: 'default' as const },
      index: 0,
      blockKey: 'hero-1',
      ...overrides,
    },
    global: { stubs, provide: { [ACTIVE_HOTSPOT_KEY]: hotspots } },
  });
  return { wrapper, hotspots };
}

describe('HeroButtonHotspot', () => {
  it('renders the button text and the outline variant', () => {
    const { wrapper } = mountHotspot({ button: { text: 'Skaityti', link: '#', variant: 'outline' as const } });
    expect(wrapper.text()).toContain('Skaityti');
  });

  it('renders the icon when the button has one', () => {
    const { wrapper } = mountHotspot({ button: { text: 'A', link: '#', icon: 'star' } });
    expect(wrapper.find('.rc-icon').exists()).toBe(true);
  });

  it('opens the popover with the id `${blockKey}:buttons:${index}` when clicked', async () => {
    const { wrapper, hotspots } = mountHotspot({ index: 2, blockKey: 'hero-9' });
    await wrapper.find('[data-rc-interactive]').trigger('click');
    expect(hotspots.isPopoverOpen('hero-9:buttons:2')).toBe(true);
  });

  it('prevents the click from navigating (capture-phase stop + preventDefault)', async () => {
    const { wrapper } = mountHotspot();
    const event = new MouseEvent('click', { bubbles: true, cancelable: true });
    wrapper.find('[data-rc-interactive]').element.dispatchEvent(event);
    expect(event.defaultPrevented).toBe(true);
  });

  it('keeps the popover anchor inside the button hotspot, outside the buttons-row flex flow', () => {
    const { wrapper } = mountHotspot();
    const hotspot = wrapper.get('[data-rc-interactive]');
    const anchor = wrapper.getComponent({ name: 'PopoverAnchorStub' });

    expect(anchor.element.parentElement).toBe(hotspot.element);
    expect(hotspot.classes()).toContain('relative');
  });

  it('emits update:button with patched text', async () => {
    const { wrapper, hotspots } = mountHotspot();
    hotspots.openPopover('hero-1:buttons:0');
    await wrapper.vm.$nextTick();
    const textInput = wrapper.find('input[placeholder="rich-content.enter_button_text"]');
    await textInput.setValue('Nauja tekstas');
    const emitted = wrapper.emitted('update:button');
    expect(emitted).toBeTruthy();
    expect((emitted!.at(-1)![0] as { text: string }).text).toBe('Nauja tekstas');
  });

  it('emits update:button with patched link', async () => {
    const { wrapper, hotspots } = mountHotspot();
    hotspots.openPopover('hero-1:buttons:0');
    await wrapper.vm.$nextTick();
    const linkInput = wrapper.find('input[placeholder="https://..."]');
    await linkInput.setValue('https://vusa.lt');
    const emitted = wrapper.emitted('update:button');
    expect((emitted!.at(-1)![0] as { link: string }).link).toBe('https://vusa.lt');
  });

  it('emits remove when the author removes the button from its popover', async () => {
    const { wrapper, hotspots } = mountHotspot();
    hotspots.openPopover('hero-1:buttons:0');
    await wrapper.vm.$nextTick();

    await wrapper.findAll('button').find(button => button.text() === 'rich-content.remove_button')!.trigger('click');

    expect(wrapper.emitted('remove')).toHaveLength(1);
  });

  it('does not render the popover body until its hotspot is the active one', () => {
    const { wrapper } = mountHotspot();
    expect(wrapper.find('.popover-content').exists()).toBe(false);
  });
});
