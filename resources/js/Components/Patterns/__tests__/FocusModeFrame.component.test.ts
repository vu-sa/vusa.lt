import { afterEach, describe, expect, it } from 'vitest';
import { defineComponent, h, nextTick, ref } from 'vue';
import { mount } from '@vue/test-utils';

import FocusModeFrame from '../FocusModeFrame.vue';

/** A page around the frame: the shell's scroll area and a toggle that lives inside it. */
function mountFrame() {
  const active = ref(false);

  const Host = defineComponent({
    setup() {
      return () => h('div', { 'data-slot': 'admin-scroll-area' }, [
        h(FocusModeFrame, {
          'label': 'Grafikas',
          'active': active.value,
          'onUpdate:active': (value: boolean) => { active.value = value; },
        }, {
          default: ({ active: isActive, toggle }: { active: boolean; toggle: () => void }) => h('button', {
            'type': 'button',
            'data-testid': 'toggle',
            'aria-pressed': String(isActive),
            'onClick': toggle,
          }, 'toggle'),
        }),
      ]);
    },
  });

  const wrapper = mount(Host, { attachTo: document.body });

  return { wrapper, active };
}

function pressEscape(): void {
  document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }));
}

describe('FocusModeFrame', () => {
  let wrapper: ReturnType<typeof mount> | undefined;

  afterEach(() => {
    wrapper?.unmount();
    document.body.innerHTML = '';
  });

  it('pins itself over the page and stops the page scrolling underneath', async () => {
    const mounted = mountFrame();
    wrapper = mounted.wrapper;

    const frame = wrapper.get('[data-slot="focus-mode-frame"]');
    expect(frame.attributes('role')).toBe('region');
    expect(frame.attributes('aria-label')).toBe('Grafikas');
    expect(frame.classes()).not.toContain('fixed');

    await wrapper.get('[data-testid="toggle"]').trigger('click');

    expect(mounted.active.value).toBe(true);
    expect(frame.classes()).toContain('fixed');
    expect(wrapper.get('[data-testid="toggle"]').attributes('aria-pressed')).toBe('true');
    expect(wrapper.get<HTMLElement>('[data-slot="admin-scroll-area"]').element.style.overflow).toBe('hidden');

    await wrapper.get('[data-testid="toggle"]').trigger('click');

    expect(frame.classes()).not.toContain('fixed');
    expect(wrapper.get<HTMLElement>('[data-slot="admin-scroll-area"]').element.style.overflow).toBe('');
  });

  it('leaves full screen on Escape and hands focus back to the toggle', async () => {
    const mounted = mountFrame();
    wrapper = mounted.wrapper;

    const toggle = wrapper.get<HTMLButtonElement>('[data-testid="toggle"]');
    toggle.element.focus();
    await toggle.trigger('click');

    pressEscape();
    await nextTick();

    expect(mounted.active.value).toBe(false);
    expect(document.activeElement).toBe(toggle.element);
  });

  /** Escape belongs to the dialog first; one press must not also drop out of full screen. */
  it('ignores Escape while a dialog opened from inside is still open', async () => {
    const mounted = mountFrame();
    wrapper = mounted.wrapper;

    await wrapper.get('[data-testid="toggle"]').trigger('click');

    const dialog = document.createElement('div');
    dialog.setAttribute('role', 'dialog');
    dialog.setAttribute('data-state', 'open');
    document.body.appendChild(dialog);

    pressEscape();
    await nextTick();
    expect(mounted.active.value).toBe(true);

    dialog.remove();
    pressEscape();
    await nextTick();
    expect(mounted.active.value).toBe(false);
  });
});
