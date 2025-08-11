# Frontend Testing Guide

This document provides comprehensive guidance for frontend testing in the vusa.lt project using Vitest, Vue Test Utils, and browser-based testing.

## 🎯 Overview

Our frontend testing architecture provides:
- **Unit Tests**: Services, composables, utilities (`*.test.ts`)
- **Component Tests**: Vue components with DOM testing (`*.component.test.ts`) 
- **Storybook Tests**: Interactive browser testing (`*.stories.ts`)
- **TypeScript Support**: Full type safety for all test scenarios
- **Mock System**: Comprehensive Laravel dependency mocking

## 📁 Test Architecture

### 3-Tier Testing System

```
Frontend Tests/
├── Unit Tests (*.test.ts)           # Pure functions, business logic
├── Component Tests (*.component.test.ts) # Vue components, user interactions  
└── Storybook Tests (*.stories.ts)   # Visual components, browser testing
```

### File Organization

```
resources/js/
├── Services/__tests__/
│   └── ServiceName.test.ts         # Unit tests for services
├── Composables/__tests__/
│   └── useComposable.test.ts       # Unit tests for composables
├── Utils/__tests__/
│   └── UtilityName.test.ts         # Unit tests for utilities
└── Components/
    └── ComponentName/
        ├── __tests__/
        │   └── ComponentName.component.test.ts  # Component tests
        ├── ComponentName.vue               # Component implementation
        └── ComponentName.stories.ts       # Storybook stories + tests
```

## ⚡ Running Tests

### Daily Development Commands

```bash
# Fast daily development (unit + component tests)
./vendor/bin/sail npm run test

# Watch mode for active development
./vendor/bin/sail npm run test:watch

# Unit tests only
./vendor/bin/sail npm run test:unit

# Component tests only  
./vendor/bin/sail npm run test:component

# All tests including browser-based Storybook tests
./vendor/bin/sail npm run test:all

# Coverage reports
./vendor/bin/sail npm run coverage
```

### Test Projects

The Vitest configuration defines 3 separate projects:

1. **Unit Project**: Services, composables, utilities
2. **Component Project**: Vue components with jsdom
3. **Storybook Project**: Browser-based testing with Playwright

## 🧪 Unit Testing

### When to Write Unit Tests
- Pure functions and business logic
- Services and API integrations
- Composables and utilities
- Data transformations
- Complex calculations

### Unit Test Template

```typescript
import { describe, it, expect, beforeEach, vi } from 'vitest'
import { ServiceName } from '../ServiceName'

describe('ServiceName', () => {
  let service: ServiceName

  beforeEach(() => {
    service = new ServiceName()
    vi.clearAllMocks()
  })

  describe('methodName', () => {
    it('should handle normal case', () => {
      const result = service.methodName('input')
      expect(result).toBe('expected')
    })

    it('should handle edge cases', () => {
      expect(() => service.methodName(null)).toThrow()
    })
  })
})
```

### Testing Composables

```typescript
import { describe, it, expect, beforeEach } from 'vitest'
import { nextTick } from 'vue'
import { useComposableName } from '../useComposableName'

describe('useComposableName', () => {
  it('should initialize with default values', () => {
    const { state, action } = useComposableName()
    
    expect(state.value).toBe(defaultValue)
    expect(typeof action).toBe('function')
  })

  it('should react to state changes', async () => {
    const { state, updateState } = useComposableName()
    
    updateState('newValue')
    await nextTick()
    
    expect(state.value).toBe('newValue')
  })
})
```

## 🎨 Component Testing

### When to Write Component Tests
- User interactions and events
- Props and slot rendering  
- Component lifecycle behavior
- Form validation and submission
- Accessibility compliance

### Component Test Template

```typescript
import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import ComponentName from '../ComponentName.vue'

describe('ComponentName.vue', () => {
  let wrapper: any

  beforeEach(() => {
    wrapper = mount(ComponentName, {
      props: {
        title: 'Test Title'
      },
      global: {
        mocks: {
          $t: (key: string) => key // Mock translation function
        }
      }
    })
  })

  afterEach(() => {
    wrapper.unmount()
  })

  describe('rendering', () => {
    it('renders title prop correctly', () => {
      expect(wrapper.text()).toContain('Test Title')
    })

    it('applies correct CSS classes', () => {
      expect(wrapper.classes()).toContain('expected-class')
    })
  })

  describe('user interactions', () => {
    it('emits event on button click', async () => {
      await wrapper.find('button').trigger('click')
      
      expect(wrapper.emitted('click')).toHaveLength(1)
    })

    it('updates internal state on input', async () => {
      const input = wrapper.find('input')
      await input.setValue('new value')
      
      expect(wrapper.vm.internalValue).toBe('new value')
    })
  })
})
```

### Testing Forms

```typescript
describe('Form Component', () => {
  it('validates required fields', async () => {
    const wrapper = mount(FormComponent)
    
    // Submit form without filling required fields
    await wrapper.find('form').trigger('submit')
    
    expect(wrapper.find('.error-message').exists()).toBe(true)
    expect(wrapper.emitted('submit')).toBeFalsy()
  })

  it('submits valid form data', async () => {
    const wrapper = mount(FormComponent)
    
    // Fill form with valid data
    await wrapper.find('input[name="name"]').setValue('Test Name')
    await wrapper.find('input[name="email"]').setValue('test@example.com')
    
    // Submit form
    await wrapper.find('form').trigger('submit')
    
    expect(wrapper.emitted('submit')).toHaveLength(1)
    expect(wrapper.emitted('submit')[0][0]).toEqual({
      name: 'Test Name',
      email: 'test@example.com'
    })
  })
})
```

## 🌐 Browser Testing (Storybook)

For comprehensive browser testing documentation, see `.storybook/CLAUDE.md`.

### Quick Reference

```typescript
// Interactive story with testing
export const Interactive: Story = {
  play: async ({ canvasElement }) => {
    const canvas = within(canvasElement)
    
    const button = canvas.getByRole('button')
    await userEvent.click(button)
    
    await canvas.findByText('Expected result')
  }
}
```

## 🎭 Mock System

### Global Mocks

The `tests/setup.ts` file provides global mocks for all frontend tests:

- **Inertia.js**: `usePage`, `router`, `useForm`  
- **Laravel i18n**: `trans`, `transChoice`, `$t`, `$tChoice`
- **Ziggy routes**: `route()` function
- **Typesense**: Search client and adapter
- **Fetch API**: Global fetch mock

### Test-Specific Mocking

```typescript
import { vi } from 'vitest'

describe('Component with API calls', () => {
  beforeEach(() => {
    // Mock specific API endpoint
    global.fetch = vi.fn().mockResolvedValue({
      ok: true,
      json: () => Promise.resolve({ data: 'test' })
    })
  })

  afterEach(() => {
    vi.restoreAllMocks()
  })
})
```

### Mocking Composables

```typescript
// Mock a composable
vi.mock('@/Composables/useDocumentSearch', () => ({
  useDocumentSearch: vi.fn(() => ({
    search: vi.fn(),
    results: ref([]),
    loading: ref(false)
  }))
}))
```

## 🔧 Testing Utilities

### Custom Test Helpers

```typescript
// Helper for mounting components with common setup
export const mountComponent = (Component: any, options = {}) => {
  return mount(Component, {
    global: {
      mocks: {
        $t: (key: string) => key,
        $tChoice: (key: string) => key,
        route: (name: string) => `/mocked-route/${name}`
      },
      provide: {
        // Common injections
      }
    },
    ...options
  })
}

// Helper for waiting for async operations
export const waitForAsync = () => {
  return new Promise(resolve => {
    setTimeout(resolve, 0)
  })
}
```

### Accessibility Testing

```typescript
import { axe, toHaveNoViolations } from 'vitest-axe'

expect.extend(toHaveNoViolations)

describe('Component Accessibility', () => {
  it('should not have accessibility violations', async () => {
    const wrapper = mount(Component)
    const results = await axe(wrapper.element)
    
    expect(results).toHaveNoViolations()
  })
})
```

## 📊 Coverage & Quality

### Coverage Targets
- **Branches**: 75% minimum
- **Functions**: 75% minimum  
- **Lines**: 75% minimum
- **Statements**: 75% minimum

### Coverage Commands

```bash
# Generate coverage report
./vendor/bin/sail npm run coverage

# Interactive coverage UI
./vendor/bin/sail npm run coverage:ui

# CI coverage (unit + component only)
./vendor/bin/sail npm run coverage:ci
```

### Quality Gates
- All tests must pass before merging
- Coverage thresholds must be met
- No accessibility violations in component tests
- TypeScript compilation must succeed

## 🚀 Best Practices

### Test Writing Guidelines

1. **Write Tests First**: Consider TDD for complex logic
2. **Test Behavior**: Focus on user-facing behavior, not implementation
3. **Use Descriptive Names**: Test names should explain expected behavior
4. **Keep Tests Isolated**: Each test should be independent
5. **Mock External Dependencies**: Don't test third-party libraries

### Component Testing Best Practices

1. **Test User Interactions**: Click, type, submit, etc.
2. **Test Props and Events**: Component contract testing
3. **Test Accessibility**: Use screen reader accessible queries
4. **Test Error States**: How component handles failures
5. **Test Loading States**: Component behavior during async operations

### Performance Testing

```typescript
describe('Performance', () => {
  it('should render large lists efficiently', () => {
    const startTime = performance.now()
    
    const wrapper = mount(ListComponent, {
      props: { items: generateLargeDataset(1000) }
    })
    
    const endTime = performance.now()
    expect(endTime - startTime).toBeLessThan(100) // ms
  })
})
```

## 🔍 Debugging Tests

### Common Debugging Techniques

```typescript
// Debug component state
console.log('Component data:', wrapper.vm.$data)
console.log('Component props:', wrapper.props())

// Debug DOM structure  
console.log('HTML:', wrapper.html())

// Debug events
console.log('Emitted events:', wrapper.emitted())

// Wait for DOM updates
await wrapper.vm.$nextTick()
await nextTick()
```

### Test Debugging Commands

```bash  
# Run single test file
./vendor/bin/sail npm run test -- ServiceName.test.ts

# Run tests in verbose mode
./vendor/bin/sail npm run test -- --verbose

# Run tests with debug info
./vendor/bin/sail npm run test -- --run --reporter=verbose
```

## 🛠️ Troubleshooting

### Common Issues

**"Cannot resolve '@/...' imports"**
- ✅ Ensure `vitest.config.ts` has proper path aliases
- ✅ Check TypeScript `paths` configuration in `tsconfig.json`

**"Component tests failing with missing globals"**
- ✅ Check `tests/setup.ts` has required global mocks
- ✅ Verify Vue Test Utils global configuration

**"Async tests timing out"**
- ✅ Use proper async/await patterns
- ✅ Increase timeout for slow operations
- ✅ Mock async dependencies

**"Mock not working in tests"**
- ✅ Ensure mocks are defined before imports
- ✅ Use `vi.clearAllMocks()` in `beforeEach`
- ✅ Check mock implementation matches actual API

### Debug Environment

Set environment variables for debugging:
```bash
DEBUG=true npm run test
VITEST_UI=true npm run test
```

## 📚 Resources

- [Vitest Documentation](https://vitest.dev/)
- [Vue Test Utils](https://test-utils.vuejs.org/)
- [Testing Library Vue](https://testing-library.com/docs/vue-testing-library/intro/)
- [Vitest Browser Mode](https://vitest.dev/guide/browser.html)

---

**Last Updated**: January 2025  
**Vitest Version**: 3.2.4  
**Vue Test Utils Version**: 2.3.2