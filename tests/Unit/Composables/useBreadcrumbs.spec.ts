import { describe, expect, it, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import { nextTick } from 'vue';
import { useBreadcrumbs, createBreadcrumbState } from '@/Composables/useBreadcrumbs';

// Mock the icon import for the home icon
vi.mock('~icons/fluent/home24-filled', () => ({
  default: {}
}));

// Mock the i18n function
vi.mock('laravel-vue-i18n', () => ({
  trans: vi.fn(key => key)
}));

// Mock route function
global.route = vi.fn((name, params) => `/mocked-route/${name}`);

// Mock Inertia
vi.mock('@inertiajs/vue3', () => {
  const handlers = {};
  return {
    router: {
      on: vi.fn((event, handler) => {
        handlers[event] = handler;
      })
    },
    usePage: vi.fn(() => ({
      component: 'TestComponent'
    }))
  };
});

// Simple wrapper component for testing
const BreadcrumbProvider = {
  template: '<div><slot></slot></div>',
  setup() {
    return createBreadcrumbState();
  }
};

describe('Breadcrumbs', () => {
  // Clear state between tests
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('should create breadcrumb items', () => {
    const TestComponent = {
      template: '<div>Test</div>',
      setup() {
        const { createBreadcrumbItem } = useBreadcrumbs();
        const item = createBreadcrumbItem('Test Item', '/test');
        
        return {
          item
        };
      }
    };
    
    const wrapper = mount(BreadcrumbProvider, {
      slots: {
        default: TestComponent
      }
    });
    
    const vm = wrapper.findComponent(TestComponent).vm;
    expect(vm.item).toEqual({
      label: 'Test Item',
      href: '/test',
      icon: undefined
    });
  });
  
  it('should manage breadcrumbs state', async () => {
    const TestComponent = {
      template: `
        <div>
          <span id="breadcrumb-count">{{ breadcrumbs.length }}</span>
          <button id="set-breadcrumbs" @click="setTwoBreadcrumbs">Set</button>
          <button id="add-breadcrumb" @click="addOneBreadcrumb">Add</button>
          <button id="clear-breadcrumbs" @click="clearAllBreadcrumbs">Clear</button>
        </div>
      `,
      setup() {
        const { breadcrumbs, setBreadcrumbs, addBreadcrumb, clearBreadcrumbs, createBreadcrumbItem } = useBreadcrumbs();
        
        function setTwoBreadcrumbs() {
          setBreadcrumbs([
            createBreadcrumbItem('Item 1', '/url1'),
            createBreadcrumbItem('Item 2', '/url2')
          ]);
        }
        
        function addOneBreadcrumb() {
          addBreadcrumb(createBreadcrumbItem('Item 3', '/url3'));
        }
        
        function clearAllBreadcrumbs() {
          clearBreadcrumbs();
        }
        
        return {
          breadcrumbs,
          setTwoBreadcrumbs,
          addOneBreadcrumb,
          clearAllBreadcrumbs
        };
      }
    };
    
    const wrapper = mount(BreadcrumbProvider, {
      slots: {
        default: TestComponent
      }
    });
    
    // Initial state should be empty
    expect(wrapper.find('#breadcrumb-count').text()).toBe('0');
    
    // Set breadcrumbs
    await wrapper.find('#set-breadcrumbs').trigger('click');
    await nextTick();
    expect(wrapper.find('#breadcrumb-count').text()).toBe('2');
    
    // Add a breadcrumb
    await wrapper.find('#add-breadcrumb').trigger('click');
    await nextTick();
    expect(wrapper.find('#breadcrumb-count').text()).toBe('3');
    
    // Clear breadcrumbs
    await wrapper.find('#clear-breadcrumbs').trigger('click');
    await nextTick();
    expect(wrapper.find('#breadcrumb-count').text()).toBe('0');
  });
});