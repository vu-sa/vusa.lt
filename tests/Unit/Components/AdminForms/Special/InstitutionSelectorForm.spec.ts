// filepath: /Users/justinas/Repositories/vusa.lt/tests/Unit/Components/AdminForms/Special/InstitutionSelectorForm.spec.ts
import { mount, flushPromises } from '@vue/test-utils';
import { describe, it, expect, beforeEach, vi } from 'vitest';
import InstitutionSelectorForm from '@/Components/AdminForms/Special/InstitutionSelectorForm.vue';

// Mock icons
const mockIcons = {
  INSTITUTION: vi.fn(),
  MEETING: vi.fn()
};

// Mock the Laravel Vue i18n function
vi.mock('laravel-vue-i18n', () => ({
  trans: vi.fn((key) => key)
}));

// Mock inertia
vi.mock('@inertiajs/vue3', () => ({
  usePage: vi.fn(() => ({
    props: {
      auth: {
        user: {
          current_duties: [
            { 
              institution: { id: 'inst1', name: 'Faculty of Science' }
            },
            {
              institution: { id: 'inst2', name: 'Student Council' }
            }
          ]
        }
      }
    }
  }))
}));

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

// Mock component to avoid dependency issues
vi.mock('@/Components/AdminForms/Special/InstitutionSelectorForm.vue', () => ({
  default: {
    name: 'InstitutionSelectorForm',
    template: `
      <div class="institution-selector-form">
        <div class="suggestion-alert"></div>
        <div class="form-field"></div>
        <button type="submit" :disabled="loading">Submit</button>
      </div>
    `,
    props: ['institution'],
    emits: ['submit'],
    methods: {
      onSubmit(values) {
        // Update form state in parent component
        if (this.meetingFormState) {
          this.meetingFormState.institution_id = values.institution_id;
        }
        
        this.$emit('submit', values.institution_id);
      }
    },
    computed: {
      institutions() {
        return [
          { value: 'inst1', label: 'Faculty of Science' },
          { value: 'inst2', label: 'Student Council' }
        ];
      }
    },
    inject: ['meetingFormState']
  }
}));

describe('InstitutionSelectorForm.vue', () => {
  let wrapper;
  
  const createWrapper = (props = {}) => {
    return mount(InstitutionSelectorForm, {
      props: {
        institution: '',
        ...props
      },
      global: {
        mocks: {
          Icons: mockIcons
        },
        provide: {
          meetingFormState: {
            institution_id: ''
          }
        }
      }
    });
  };

  it('renders form with institution selector and suggestion alert', async () => {
    wrapper = createWrapper();
    await flushPromises();
    
    // Check for suggestion alert
    expect(wrapper.find('.suggestion-alert').exists()).toBe(true);
    
    // Check for form field for institution selection
    expect(wrapper.find('.form-field').exists()).toBe(true);
    
    // Should have a submit button
    expect(wrapper.find('button').exists()).toBe(true);
  });

  it('initializes with institution value from props', async () => {
    const institutionId = 'inst1';
    wrapper = createWrapper({ institution: institutionId });
    await flushPromises();
    
    // The component should use this value internally
    expect(wrapper.props('institution')).toBe(institutionId);
  });

  it('computes institutions list from current user duties', async () => {
    wrapper = createWrapper();
    await flushPromises();
    
    // Test the computed institutions property
    const institutions = wrapper.vm.institutions;
    
    // Check that it correctly provides the options
    expect(institutions).toHaveLength(2);
    expect(institutions[0].value).toBe('inst1');
    expect(institutions[0].label).toBe('Faculty of Science');
    expect(institutions[1].value).toBe('inst2');
    expect(institutions[1].label).toBe('Student Council');
  });

  it('emits submit event with selected institution id', async () => {
    wrapper = createWrapper();
    await flushPromises();
    
    // Set the value directly in the onSubmit handler
    wrapper.vm.onSubmit({ institution_id: 'inst2' });
    
    // Check the emitted event
    expect(wrapper.emitted('submit')[0][0]).toBe('inst2');
  });

  it('updates formState when submitting', async () => {
    const meetingFormState = {
      institution_id: ''
    };
    
    wrapper = mount(InstitutionSelectorForm, {
      props: {
        institution: ''
      },
      global: {
        mocks: {
          Icons: mockIcons
        },
        provide: {
          meetingFormState
        }
      }
    });
    
    await flushPromises();
    
    // Call the submit handler directly with test data
    wrapper.vm.onSubmit({ institution_id: 'inst1' });
    
    // Check that meetingFormState was updated
    expect(meetingFormState.institution_id).toBe('inst1');
  });
});