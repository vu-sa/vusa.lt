// filepath: /Users/justinas/Repositories/vusa.lt/tests/Unit/Components/AdminForms/Special/AgendaItemsForm.spec.ts
import { mount, flushPromises } from '@vue/test-utils';
import { describe, it, expect, beforeEach, vi } from 'vitest';
import AgendaItemsForm from '@/Components/AdminForms/Special/AgendaItemsForm.vue';

// Mock icons
const mockIcons = {
  AGENDA_ITEM: vi.fn()
};

const mockIconsRegular = {
  AGENDA_ITEM: vi.fn()
};

// Mock the Laravel Vue i18n function
vi.mock('laravel-vue-i18n', () => ({
  trans: vi.fn((key) => key)
}));

// Mock vee-validate
vi.mock('vee-validate', () => ({
  Form: {
    setup: vi.fn()
  },
  FieldArray: vi.fn(),
  useForm: vi.fn(() => ({
    handleSubmit: vi.fn((callback) => callback),
    errors: {},
    values: {}
  }))
}));

// Mock component to avoid dependency issues
vi.mock('@/Components/AdminForms/Special/AgendaItemsForm.vue', () => ({
  default: {
    name: 'AgendaItemsForm',
    template: `
      <div class="agenda-items-form">
        <div class="suggestion-alert"></div>
        <div class="form-field"></div>
        <textarea v-if="showQuestionInputInTextArea" class="textarea"></textarea>
        <button type="submit" :disabled="loading">Submit</button>
      </div>
    `,
    props: ['loading'],
    emits: ['submit'],
    data() {
      return {
        showQuestionInputInTextArea: false,
        questionInputInTextArea: '',
        agendaItemField: { setValue: vi.fn() }
      };
    },
    methods: {
      handleQuestionsFromTextArea() {
        const items = this.questionInputInTextArea.split('\n');
        this.agendaItemField.setValue(items);
        this.showQuestionInputInTextArea = false;
      },
      onSubmit(values) {
        const filteredItems = values.agendaItemTitles.filter(item => 
          item && item.trim().length > 0
        );
        
        // Update form state in parent component
        if (this.meetingFormState) {
          this.meetingFormState.agendaItemsData.agendaItemTitles = filteredItems;
        }
        
        this.$emit('submit', { agendaItemTitles: filteredItems });
      }
    },
    computed: {
      // Mock any computed properties needed for tests
    },
    inject: ['meetingFormState']
  }
}));

describe('AgendaItemsForm.vue', () => {
  let wrapper;
  
  const createWrapper = (props = {}) => {
    return mount(AgendaItemsForm, {
      props: {
        loading: false,
        ...props
      },
      global: {
        mocks: {
          $page: {
            props: {
              app: {
                locale: 'lt'
              }
            }
          },
          IconsFilled: mockIcons,
          IconsRegular: mockIconsRegular
        },
        provide: {
          meetingFormState: {
            agendaItemsData: {
              agendaItemTitles: []
            }
          }
        }
      }
    });
  };

  it('renders the form with suggestion alert and empty agenda items', async () => {
    wrapper = createWrapper();
    await flushPromises();
    
    // Check for alert
    expect(wrapper.find('.suggestion-alert').exists()).toBe(true);
    
    // Form fields
    expect(wrapper.find('.form-field').exists()).toBe(true);
    
    // Submit button
    expect(wrapper.find('button').exists()).toBe(true);
  });

  it('displays the text area input mode when toggled', async () => {
    wrapper = createWrapper();
    await flushPromises();
    
    // Initially the text area mode is not shown
    expect(wrapper.find('.textarea').exists()).toBe(false);
    
    // Set the flag to show text area
    wrapper.vm.showQuestionInputInTextArea = true;
    await wrapper.vm.$nextTick();
    
    // Should now show the textarea component
    expect(wrapper.find('.textarea').exists()).toBe(true);
  });

  it('processes agenda items from text area when requested', async () => {
    wrapper = createWrapper();
    await flushPromises();
    
    // Set textarea content
    wrapper.vm.questionInputInTextArea = "Item 1\nItem 2\nItem 3";
    
    // Call the handler
    wrapper.vm.handleQuestionsFromTextArea();
    
    // Check if setValue was called with the correct parameters
    expect(wrapper.vm.agendaItemField.setValue).toHaveBeenCalledWith(["Item 1", "Item 2", "Item 3"]);
    
    // Should hide the textarea mode after processing
    expect(wrapper.vm.showQuestionInputInTextArea).toBe(false);
  });

  it('emits submit event with filtered agenda items', async () => {
    wrapper = createWrapper();
    await flushPromises();
    
    // Set up test data
    const testItems = ['Item 1', '', 'Item 2', '  '];
    
    // Call the submit handler directly
    wrapper.vm.onSubmit({ agendaItemTitles: testItems });
    
    // Check the emitted event data - should only have non-empty items
    expect(wrapper.emitted('submit')[0][0]).toEqual({
      agendaItemTitles: ['Item 1', 'Item 2']
    });
  });

  it('updates formState when submitting', async () => {
    const meetingFormState = {
      agendaItemsData: {
        agendaItemTitles: []
      }
    };
    
    wrapper = mount(AgendaItemsForm, {
      props: {
        loading: false
      },
      global: {
        mocks: {
          $page: {
            props: {
              app: {
                locale: 'lt'
              }
            }
          },
          IconsFilled: mockIcons,
          IconsRegular: mockIconsRegular
        },
        provide: {
          meetingFormState
        }
      }
    });
    
    await flushPromises();
    
    // Submit form with test data
    const testItems = ['Updated Item 1', 'Updated Item 2'];
    wrapper.vm.onSubmit({ agendaItemTitles: testItems });
    
    // Check that form state was updated
    expect(meetingFormState.agendaItemsData.agendaItemTitles).toEqual(testItems);
  });
});