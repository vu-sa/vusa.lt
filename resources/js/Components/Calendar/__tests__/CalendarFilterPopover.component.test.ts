import { describe, it, expect, afterEach } from 'vitest';
import { mount } from '@vue/test-utils';

import CalendarFilterPopover, { type FilterOption } from '@/Components/Calendar/CalendarFilterPopover.vue';

describe('Calendar/CalendarFilterPopover.vue', () => {
  const sampleOptions: FilterOption[] = [
    { value: 'MIF', label: 'Matematikos ir informatikos fakultetas', count: 12 },
    { value: 'FF', label: 'Fizikos fakultetas', count: 5 },
    { value: 'EVAF', label: 'Ekonomikos ir verslo administravimo fakultetas', count: 0 },
  ];

  function mountComponent(props: Record<string, unknown> = {}) {
    return mount(CalendarFilterPopover, {
      props: {
        label: 'Padalinys',
        options: sampleOptions,
        selected: [],
        ...props,
      },
      attachTo: document.body,
    });
  }

  afterEach(() => {
    document.body.innerHTML = '';
  });

  it('renders trigger button with label', () => {
    const wrapper = mountComponent();
    expect(wrapper.text()).toContain('Padalinys');
  });

  it('displays selected count badge when options are selected', () => {
    const wrapper = mountComponent({ selected: ['MIF', 'FF'] });
    expect(wrapper.text()).toContain('2');
  });

  it('opens popover when trigger button is clicked', async () => {
    const wrapper = mountComponent();
    const trigger = wrapper.find('button');
    expect(document.body.querySelector('[role="dialog"]')).toBeNull();

    await trigger.trigger('click');
    expect(document.body.querySelector('[role="dialog"]')).not.toBeNull();
  });

  it('renders option list with labels and counts when open', async () => {
    const wrapper = mountComponent();
    await wrapper.find('button').trigger('click');

    const dialog = document.body.querySelector('[role="dialog"]');
    expect(dialog).not.toBeNull();
    expect(dialog?.textContent).toContain('Matematikos ir informatikos fakultetas');
    expect(dialog?.textContent).toContain('12');
    expect(dialog?.textContent).toContain('Fizikos fakultetas');
  });

  it('emits toggle event when an option is clicked', async () => {
    const wrapper = mountComponent();
    await wrapper.find('button').trigger('click');

    const optionButtons = document.body.querySelectorAll('[role="dialog"] button[role="checkbox"]');
    expect(optionButtons.length).toBe(3);

    (optionButtons[0] as HTMLElement).click();
    expect(wrapper.emitted('toggle')).toBeTruthy();
    expect(wrapper.emitted('toggle')![0]).toEqual(['MIF']);
  });

  it('filters options when search query is entered', async () => {
    const wrapper = mountComponent({ searchable: true });
    await wrapper.find('button').trigger('click');

    const searchInput = document.body.querySelector('[role="dialog"] input[type="text"]') as HTMLInputElement;
    expect(searchInput).not.toBeNull();

    searchInput.value = 'Fizikos';
    searchInput.dispatchEvent(new Event('input'));
    await new Promise(r => setTimeout(r, 50));

    const optionButtons = document.body.querySelectorAll('[role="dialog"] button[role="checkbox"]');
    expect(optionButtons.length).toBe(1);
    expect(optionButtons[0].textContent).toContain('Fizikos fakultetas');
  });

  it('emits clear event when clear button is clicked', async () => {
    const wrapper = mountComponent({ selected: ['MIF'] });
    await wrapper.find('button').trigger('click');

    const buttons = Array.from(document.body.querySelectorAll('[role="dialog"] button'));
    const clearButton = buttons.find(b => b.textContent?.includes('Išvalyti')) as HTMLElement;
    expect(clearButton).toBeDefined();

    clearButton.click();
    expect(wrapper.emitted('clear')).toBeTruthy();
  });

  it('applies custom triggerClass to the trigger button', () => {
    const wrapper = mountComponent({ triggerClass: 'h-10 min-w-36 px-4' });
    const trigger = wrapper.find('button');
    expect(trigger.classes()).toContain('h-10');
    expect(trigger.classes()).toContain('min-w-36');
  });
});
