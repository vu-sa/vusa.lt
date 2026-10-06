import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';

import FilePicker from '../FilePicker.vue';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const passthrough = { template: '<div><slot /></div>' };
const stubs = { Dialog: passthrough, DialogTrigger: passthrough, DialogContent: passthrough, DialogTitle: passthrough };

describe('FilePicker', () => {
  it('renders the caller\'s own trigger button', () => {
    const wrapper = mount(FilePicker, {
      slots: { trigger: '<button data-testid="own-trigger">Įkelti iš SharePoint</button>' },
      global: { stubs },
    });

    expect(wrapper.get('[data-testid="own-trigger"]').text()).toBe('Įkelti iš SharePoint');
    expect(wrapper.findAll('button')).toHaveLength(1);
  });

  it('falls back to an outline button around the default slot, disabled while loading', () => {
    const wrapper = mount(FilePicker, {
      props: { loading: true },
      slots: { default: 'Įkelti' },
      global: { stubs },
    });

    const button = wrapper.get('button');
    expect(button.text()).toBe('Įkelti');
    expect(button.classes()).toContain('border-border');
    expect(button.attributes('disabled')).toBeDefined();
  });
});
