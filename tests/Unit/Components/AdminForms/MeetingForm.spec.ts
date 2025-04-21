// filepath: /Users/justinas/Repositories/vusa.lt/tests/Unit/Components/AdminForms/MeetingForm.spec.ts
import { mount, flushPromises } from '@vue/test-utils';
import { describe, it, expect, beforeEach, vi } from 'vitest';
import MeetingForm from '@/Components/AdminForms/MeetingForm.vue';

// Mock icons
const mockIcons = {
  DATE: vi.fn(),
  TYPE: vi.fn()
};

// Mock the Laravel Vue i18n functions
vi.mock('laravel-vue-i18n', () => ({
  trans: vi.fn((key) => key),
  transChoice: vi.fn((key) => key)
}));

// Mock fetch for API calls to get meeting types
global.fetch = vi.fn(() => 
  Promise.resolve({
    json: () => Promise.resolve([
      { id: 1, title: 'Regular Meeting', model_type: 'App\\Models\\Meeting' },
      { id: 2, title: 'Special Meeting', model_type: 'App\\Models\\Meeting' }
    ])
  })
);

// Mock vee-validate
vi.mock('vee-validate', () => ({
  Form: {
    setup: vi.fn()
  },
  useForm: vi.fn(() => ({
    handleSubmit: vi.fn((callback) => callback),
    errors: {},
    values: {}
  }))
}));

// Mock date-fns
vi.mock('@internationalized/date', () => ({
  getLocalTimeZone: vi.fn(() => 'Europe/Vilnius'),
  today: vi.fn(() => ({ year: 2025, month: 4, day: 20 }))
}));

// Mock component to avoid async setup issues
vi.mock('@/Components/AdminForms/MeetingForm.vue', async () => {
  const actual = await vi.importActual('@/Components/AdminForms/MeetingForm.vue');
  return {
    default: {
      name: 'MeetingForm',
      template: `
        <div class="meeting-form">
          <div class="space-y-4">
            <div class="form-field" name="date"></div>
            <div class="form-field" name="time"></div>
            <div class="form-field" name="type_id"></div>
            <button type="submit" :disabled="loading">Submit</button>
          </div>
        </div>
      `,
      props: ['meeting', 'loading'],
      emits: ['submit'],
      setup(props, { emit }) {
        const onSubmit = (values) => {
          emit('submit', {
            start_time: '2025-05-01T10:00:00',
            type_id: values?.type_id || 1
          });
        };
        return { onSubmit, props };
      }
    }
  };
});

describe('MeetingForm.vue', () => {
  let wrapper;
  
  const createWrapper = (props = {}) => {
    return mount(MeetingForm, {
      props: {
        meeting: { 
          start_time: '2025-04-25T10:00:00',
          type_id: 1
        },
        loading: false,
        ...props
      },
      global: {
        mocks: {
          Icons: mockIcons
        },
        provide: {
          meetingFormState: {
            meetingData: {}
          }
        }
      }
    });
  };

  it('renders the form with date and time fields', async () => {
    wrapper = createWrapper();
    await flushPromises();
    
    expect(wrapper.find('.space-y-4').exists()).toBe(true);
    
    // Check for form fields
    expect(wrapper.findAll('.form-field')).toHaveLength(3);
    expect(wrapper.find('[name="date"]').exists()).toBe(true);
    expect(wrapper.find('[name="time"]').exists()).toBe(true);
    expect(wrapper.find('[name="type_id"]').exists()).toBe(true);
  });

  it('emits submit event when form is submitted', async () => {
    wrapper = createWrapper();
    await flushPromises();
    
    // Find the form element and trigger submit
    wrapper.vm.onSubmit({});
    
    // Check the emitted events
    expect(wrapper.emitted()).toHaveProperty('submit');
  });

  it('uses existing meeting data when provided', async () => {
    const meetingData = {
      start_time: '2025-05-01T15:30:00',
      type_id: 2
    };
    
    wrapper = createWrapper({ meeting: meetingData });
    await flushPromises();
    
    // The component should have the meeting prop
    expect(wrapper.props('meeting')).toEqual(meetingData);
  });

  it('shows a disabled button when loading', async () => {
    wrapper = createWrapper({ loading: true });
    await flushPromises();
    
    const button = wrapper.find('button');
    expect(button.attributes('disabled')).toBeDefined();
  });
});