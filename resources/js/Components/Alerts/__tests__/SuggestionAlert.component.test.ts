import { mount } from '@vue/test-utils';
import { describe, it, expect } from 'vitest';
import SuggestionAlert from '@/Components/Alerts/SuggestionAlert.vue';

describe('SuggestionAlert.vue', () => {
  it('renders properly with the $t function', () => {
    const wrapper = mount(SuggestionAlert, {
      props: {
        modelValue: true
      },
      slots: {
        default: 'Test alert content'
      }
    });
    
    // Should not throw any errors about $t
    expect(wrapper.html()).toContain('Įsidėmėk'); // This will actually contain the key name due to our mock
    expect(wrapper.html()).toContain('Test alert content');
    expect(wrapper.props().modelValue).toBe(true);
  });

  it('emits update:modelValue when close button is clicked', async () => {
    const wrapper = mount(SuggestionAlert, {
      props: {
        modelValue: true
      }
    });
    
    await wrapper.find('.cursor-pointer').trigger('click');
    expect(wrapper.emitted()).toHaveProperty('update:modelValue');
    expect(wrapper.emitted()['update:modelValue'][0]).toEqual([false]);
  });
});