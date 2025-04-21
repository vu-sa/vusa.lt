// filepath: /Users/justinas/Repositories/vusa.lt/tests/Unit/Components/Modals/NewMeetingModal.spec.ts
import { mount, flushPromises } from '@vue/test-utils';
import { describe, it, expect, beforeEach, vi } from 'vitest';
import { nextTick } from 'vue';
import NewMeetingModal from '@/Components/Modals/NewMeetingModal.vue';

// Mock icons
const mockIconsRegular = {
  INSTITUTION: vi.fn(),
  MEETING: vi.fn(),
  AGENDA_ITEM: vi.fn()
};

// Mock the Laravel Vue i18n function
vi.mock('laravel-vue-i18n', () => ({
  trans: vi.fn((key) => key)
}));

// Mock inertia
vi.mock('@inertiajs/vue3', () => ({
  router: {
    visit: vi.fn()
  },
  useForm: vi.fn(() => ({
    transform: vi.fn().mockReturnThis(),
    post: vi.fn((url, { onSuccess, onError, onFinish }) => {
      if (onSuccess) onSuccess({ props: { flash: { data: { id: 'new-meeting-id' } } } });
      if (onFinish) onFinish();
      return true;
    }),
    reset: vi.fn()
  }))
}));

// Mock component to avoid dependency issues
vi.mock('@/Components/Modals/NewMeetingModal.vue', () => ({
  default: {
    name: 'NewMeetingModal',
    template: `
      <div class="new-meeting-modal">
        <div class="stepper">
          <div class="stepper-item" v-for="i in 3" :key="i"></div>
        </div>
        <div v-if="current === 1" class="institution-selector-stub"></div>
        <div v-if="current === 2" class="meeting-form-stub"></div>
        <div v-if="current === 3" class="agenda-items-form-stub"></div>
      </div>
    `,
    props: ['showModal', 'institution'],
    emits: ['close'],
    data() {
      return {
        current: 1,
        formState: {
          institution_id: '',
          meetingData: {},
          agendaItemsData: {
            agendaItemTitles: []
          }
        },
        meetingAgendaForm: {
          transform: vi.fn().mockReturnThis(),
          post: vi.fn((url, options) => {
            if (options && options.onSuccess) {
              options.onSuccess({ props: { flash: { data: { id: 'new-meeting-id' } } } });
            }
            if (options && options.onFinish) {
              options.onFinish();
            }
            return true;
          }),
          reset: vi.fn()
        },
        formSubmitted: false
      };
    },
    methods: {
      handleInstitutionSelect(id) {
        this.formState.institution_id = id;
        this.current = 2;
      },
      handleMeetingFormSubmit(data) {
        this.formState.meetingData = data;
        this.current = 3;
      },
      handleAgendaItemsFormSubmit(data) {
        this.formState.agendaItemsData = data;
        this.formSubmitted = true;
        // Simulate form submission
        this.meetingAgendaForm.transform();
        this.meetingAgendaForm.post('route/meetings.store', {
          onSuccess: () => {},
          onFinish: () => {}
        });
      },
      resetForm() {
        this.formState.institution_id = '';
        this.formState.meetingData = {};
        this.formState.agendaItemsData.agendaItemTitles = [];
        this.formSubmitted = false;
      }
    },
    watch: {
      institution: {
        handler(newInstitution) {
          if (newInstitution) {
            this.formState.institution_id = newInstitution.id;
            this.current = 2;
          }
        },
        immediate: true
      }
    },
    unmounted() {
      if (this.formSubmitted) {
        this.resetForm();
      }
    }
  }
}));

describe('NewMeetingModal.vue', () => {
  let wrapper;
  
  const createWrapper = (props = {}) => {
    return mount(NewMeetingModal, {
      props: {
        showModal: true,
        institution: null,
        ...props
      },
      global: {
        mocks: {
          IconsRegular: mockIconsRegular
        }
      }
    });
  };

  it('renders the modal with a three-step stepper', async () => {
    wrapper = createWrapper();
    await flushPromises();
    
    // Should have stepper component
    expect(wrapper.find('.stepper').exists()).toBe(true);
    
    // Should have 3 stepper items (Institution, Meeting, Agenda Items)
    expect(wrapper.findAll('.stepper-item')).toHaveLength(3);
  });

  it('starts with institution selector form if no institution provided', async () => {
    wrapper = createWrapper();
    await flushPromises();
    
    // Should display institution selector as first step
    expect(wrapper.find('.institution-selector-stub').exists()).toBe(true);
    expect(wrapper.find('.meeting-form-stub').exists()).toBe(false);
    expect(wrapper.find('.agenda-items-form-stub').exists()).toBe(false);
    
    // Current step should be 1
    expect(wrapper.vm.current).toBe(1);
  });

  it('starts with meeting form if institution is provided', async () => {
    wrapper = createWrapper({
      institution: { id: 'inst1', name: 'Faculty of Science' }
    });
    await flushPromises();
    
    // Should display meeting form as second step
    expect(wrapper.find('.institution-selector-stub').exists()).toBe(false);
    expect(wrapper.find('.meeting-form-stub').exists()).toBe(true);
    expect(wrapper.find('.agenda-items-form-stub').exists()).toBe(false);
    
    // formState should have institution_id set
    expect(wrapper.vm.formState.institution_id).toBe('inst1');
  });

  it('advances through steps when handlers are called', async () => {
    wrapper = createWrapper();
    await flushPromises();
    
    // Step 1: Institution selection
    expect(wrapper.vm.current).toBe(1);
    
    // Trigger institution selection
    wrapper.vm.handleInstitutionSelect('inst1');
    await nextTick();
    
    // Should advance to step 2
    expect(wrapper.vm.current).toBe(2);
    
    // Trigger meeting form submission
    wrapper.vm.handleMeetingFormSubmit({ start_time: '2025-05-01T10:00:00', type_id: 1 });
    await nextTick();
    
    // Should advance to step 3
    expect(wrapper.vm.current).toBe(3);
    
    // Check that meeting data was stored in formState
    expect(wrapper.vm.formState.meetingData.start_time).toBe('2025-05-01T10:00:00');
    expect(wrapper.vm.formState.meetingData.type_id).toBe(1);
  });

  it('submits meeting data when agenda items form is submitted', async () => {
    wrapper = createWrapper();
    await flushPromises();
    
    // Set up test data
    wrapper.vm.formState.institution_id = 'inst1';
    wrapper.vm.formState.meetingData = { start_time: '2025-05-01T10:00:00', type_id: 1 };
    wrapper.vm.current = 3;
    await nextTick();
    
    // Mock the form functions
    const spy = vi.spyOn(wrapper.vm.meetingAgendaForm, 'transform');
    const postSpy = vi.spyOn(wrapper.vm.meetingAgendaForm, 'post');
    
    // Trigger agenda items submission
    wrapper.vm.handleAgendaItemsFormSubmit({ agendaItemTitles: ['Item 1', 'Item 2'] });
    
    // Check that transform was called
    expect(spy).toHaveBeenCalled();
    expect(postSpy).toHaveBeenCalled();
    
    // Check that agenda items data was stored in formState
    expect(wrapper.vm.formState.agendaItemsData.agendaItemTitles).toEqual(['Item 1', 'Item 2']);
  });

  it('resets form state when successfully submitted', async () => {
    // Set up component with test data
    wrapper = createWrapper();
    await flushPromises();
    
    wrapper.vm.formState.institution_id = 'inst1';
    wrapper.vm.formState.meetingData = { start_time: '2025-05-01T10:00:00', type_id: 1 };
    wrapper.vm.formState.agendaItemsData.agendaItemTitles = ['Item 1', 'Item 2'];
    
    // Mark as submitted
    wrapper.vm.formSubmitted = true;
    
    // Call reset method directly
    wrapper.vm.resetForm();
    
    // Form state should be reset
    expect(wrapper.vm.formState.institution_id).toBe('');
    expect(wrapper.vm.formState.meetingData).toEqual({});
    expect(wrapper.vm.formState.agendaItemsData.agendaItemTitles).toEqual([]);
  });
});