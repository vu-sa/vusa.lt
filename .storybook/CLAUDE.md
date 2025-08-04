# Storybook Setup & Configuration Guide

This document provides comprehensive information about the Storybook setup for the vusa.lt project, including configuration, testing integration, and best practices.

## 🎯 Overview

Our Storybook setup provides:
- **Component Documentation**: Interactive documentation for Vue components
- **Visual Testing**: Browser-based component testing with Playwright
- **Vitest Integration**: Run tests directly from Storybook UI
- **Mock System**: Centralized mocking for Laravel dependencies
- **TypeScript Support**: Full type safety for stories and tests

## 📁 Architecture

```
.storybook/
├── main.ts              # Core Storybook configuration
├── preview.ts           # Global decorators and parameters
├── vitest.setup.ts      # Test environment setup
└── CLAUDE.md           # This documentation

resources/js/mocks/
├── inertia.mock.ts     # Inertia.js mocks (usePage, router, useForm)
├── i18n.mock.ts        # Laravel Vue i18n mocks (trans, transChoice)
└── route.mock.ts       # Ziggy route mocks

resources/js/Components/
└── **/*.stories.ts     # Component stories
```

## ⚙️ Configuration Files

### `.storybook/main.ts`

The main configuration follows official Vue3+Vite guidelines with essential customizations:

```typescript
export default {
  stories: ["../resources/js/**/*.stories.@(js|jsx|mjs|ts|tsx)"],
  addons: [
    "@storybook/addon-docs",
    "@storybook/addon-vitest", 
    "@storybook/addon-a11y"
  ],
  framework: {
    name: "@storybook/vue3-vite",
    options: {}
  },
  // Critical: Alias configuration must match main vite.config.mts
  async viteFinal(config) {
    return mergeConfig(config, {
      resolve: {
        alias: {
          "@": "/resources/js",
          "ziggy-js": "/vendor/tightenco/ziggy/dist",
        }
      }
    });
  }
}
```

**Key Points:**
- ✅ Aliases must exactly match main Vite config (`"@": "/resources/js"`)
- ✅ Uses minimal addon set for stability
- ✅ TypeScript checking disabled for performance (`check: false`)

### `vitest.config.ts` Integration

The Vitest configuration includes a dedicated Storybook project:

```typescript
{
  test: {
    name: 'storybook',
    browser: {
      enabled: true,
      provider: 'playwright',
      instances: [{ browser: 'chromium' }],
      headless: true
    },
    setupFiles: ['./.storybook/vitest.setup.ts']
  }
}
```

## 🔧 Mock System

### Centralized Mocks

All Laravel-specific dependencies are mocked in dedicated files:

#### `@/mocks/inertia.mock.ts`
```typescript
export const usePage = vi.fn(() => ({
  props: {
    app: { locale: 'lt', subdomain: 'www', name: 'VU SA' },
    auth: { user: null, can: {} },
    flash: { success: null, error: null }
  }
}));

export const router = {
  visit: vi.fn(),
  get: vi.fn(),
  post: vi.fn(),
  // ... other router methods
};
```

#### `@/mocks/i18n.mock.ts`
```typescript
export const trans = vi.fn((key: string) => {
  const translations = {
    'Save': 'Išsaugoti',
    'Cancel': 'Atšaukti',
    // ... more translations
  };
  return translations[key] || key;
});
```

#### `@/mocks/route.mock.ts`
```typescript
export const route = vi.fn((name: string, params?: any) => {
  const routes = {
    'users.index': '/mano/users',
    'users.create': '/mano/users/create',
    // ... more routes
  };
  return routes[name] || `/mocked-route/${name}`;
});
```

### Using Mocks in Stories

```typescript
import { usePage, router } from "@/mocks/inertia.mock";

// Override specific mock behavior
usePage.mockImplementation(() => ({
  props: {
    auth: {
      user: { id: 1, name: 'Test User' },
      can: { create: { meeting: true } }
    }
  }
}));
```

## 📝 Writing Stories

### Basic Story Template

```typescript
import type { Meta, StoryObj } from '@storybook/vue3-vite';
import { fn } from 'storybook/test';
import ComponentName from './ComponentName.vue';

const meta: Meta<typeof ComponentName> = {
  title: 'Components/ComponentName',
  component: ComponentName,
  tags: ['autodocs'],
  argTypes: {
    variant: { control: 'select', options: ['default', 'destructive'] }
  },
  args: {
    onClick: fn()
  }
};

export default meta;
type Story = StoryObj<typeof meta>;

export const Default: Story = {
  args: {
    children: 'Button text'
  }
};
```

### Interactive Story with Testing

```typescript
import { userEvent, within } from 'storybook/test';

export const Interactive: Story = {
  play: async ({ canvasElement }) => {
    const canvas = within(canvasElement);
    
    // Wait for component to render
    await new Promise(resolve => setTimeout(resolve, 100));
    
    // Interact with component
    const button = canvas.getByRole('button');
    await userEvent.click(button);
    
    // Verify behavior
    await canvas.findByText('Expected result');
  }
};
```

## 🧪 Testing Integration

### Running Tests

```bash
# Daily development (unit + component tests)
npm run test

# Storybook tests only
npm run test:storybook

# All tests including browser tests
npm run test:all

# Interactive Storybook with testing UI
npm run storybook
```

### Test Environment

- **Browser**: Chromium via Playwright
- **Environment**: jsdom for component tests, real browser for stories
- **Mocks**: Automatic setup via `vitest.setup.ts`
- **Coverage**: Istanbul provider with 75% thresholds

### Writing Testable Components

**❌ Avoid:**
```typescript
// Top-level await makes component async and untestable
const apiData = await fetch('/api/data');
```

**✅ Prefer:**
```typescript
// Props allow for dependency injection in tests
const props = defineProps<{
  data?: ApiData[];
}>();

const data = ref(props.data || []);

onMounted(async () => {
  if (!props.data) {
    data.value = await fetchApiData();
  }
});
```

## 🎨 Story Categories

### Component Organization

```
Components/
├── ui/                 # Base UI components (Button, Input, etc.)
├── Alerts/            # Alert and notification components  
├── AdminForms/        # Admin form components
├── Public/            # Public-facing components
└── Modals/           # Modal and dialog components
```

### Story Naming Conventions

- **Title**: Use hierarchical structure (`Components/ui/Button`)
- **Stories**: Descriptive names (`Default`, `WithIcon`, `Loading`)
- **Interactive**: Suffix with interaction purpose (`WithValidation`, `UserFlow`)

## 🔍 Troubleshooting

### Common Issues

**1. "Failed to resolve import '@/mocks/...'"**
- ✅ **Solution**: Ensure aliases in `.storybook/main.ts` match `vite.config.mts`
- ✅ **Check**: Use `@/mocks/inertia.mock` (not `@/mocks/inertia`)

**2. "Component stuck in loading state"**
- ✅ **Solution**: Provide data via props instead of async fetching
- ✅ **Pattern**: Make components testable with dependency injection

**3. "Tests failing with undefined globals"**
- ✅ **Solution**: Use `globalThis` instead of `global` in setup files
- ✅ **Check**: Ensure proper mock setup in `vitest.setup.ts`

**4. "Browser tests not running"**
- ✅ **Solution**: Install Playwright browsers: `npx playwright install`
- ✅ **Check**: Use modern `browser.instances` config (not deprecated `browser.name`)

### Debug Tips

1. **Use Storybook UI**: Click "Run tests" button to see detailed test results
2. **Check Network**: Monitor failed requests in browser DevTools
3. **Console Logs**: Add `console.log` in stories for debugging
4. **Accessibility**: Use a11y addon to catch accessibility issues

## 🚀 Best Practices

### Story Writing

1. **Start Simple**: Create basic visual stories first
2. **Add Interactions**: Use `play` functions for user interactions
3. **Mock Dependencies**: Always mock external dependencies
4. **Document Variants**: Cover all important component states

### Component Design

1. **Dependency Injection**: Accept data via props when possible
2. **Error Boundaries**: Handle loading and error states gracefully
3. **Accessibility**: Use semantic HTML and ARIA attributes
4. **TypeScript**: Provide proper prop types and interfaces

### Performance

1. **Lazy Loading**: Use `defineAsyncComponent` for heavy components
2. **Mock Optimization**: Keep mocks simple and focused
3. **Test Isolation**: Ensure tests don't interfere with each other
4. **Bundle Size**: Monitor story compilation times

## 📚 Resources

- [Storybook Vue3+Vite Documentation](https://storybook.js.org/docs/get-started/frameworks/vue3-vite)
- [Vitest Browser Mode](https://vitest.dev/guide/browser.html)
- [Testing Library Vue](https://testing-library.com/docs/vue-testing-library/intro/)
- [Playwright Documentation](https://playwright.dev/)

## 🎯 Migration Notes

### From Legacy Naive UI Tables
- **Don't migrate** existing working tables unnecessarily
- **Use TanStack** for new table features
- **Stories benefit** from simplified component architecture

### From Complex Components
- **Refactor async components** to accept props
- **Extract business logic** from presentation components  
- **Use composition API** for better testability

---

**Last Updated**: January 2025  
**Storybook Version**: 9.1.0  
**Vitest Version**: 3.2.4