# Storybook Setup & Configuration

Comprehensive documentation for Storybook setup, configuration, and best practices in the vusa.lt project.

> **For AI Assistants**: See [CLAUDE.md](CLAUDE.md) for quick patterns and troubleshooting.

## Table of Contents

- [Overview](#overview)
- [Architecture](#architecture)
- [Configuration](#configuration)
- [Mock System](#mock-system)
- [Writing Stories](#writing-stories)
- [Testing Integration](#testing-integration)
- [Story Categories](#story-categories)
- [Best Practices](#best-practices)
- [Troubleshooting](#troubleshooting)
- [Migration Notes](#migration-notes)

## Overview

Our Storybook setup provides:
- **Component Documentation**: Interactive documentation for Vue components
- **Visual Testing**: Browser-based component testing with Playwright
- **Vitest Integration**: Run tests directly from Storybook UI
- **Mock System**: Centralized mocking for Laravel dependencies
- **TypeScript Support**: Full type safety for stories and tests
- **Accessibility Testing**: Using a11y addon for WCAG compliance

## Architecture

```
.storybook/
├── main.ts              # Core Storybook configuration
├── preview.ts           # Global decorators and parameters
├── vitest.setup.ts      # Test environment setup
├── CLAUDE.md           # AI assistant guidance
└── README.md           # This file

resources/js/mocks/
├── inertia.mock.ts     # Inertia.js mocks (usePage, router, useForm)
├── i18n.mock.ts        # Laravel Vue i18n mocks (trans, transChoice)
└── route.mock.ts       # Ziggy route mocks

resources/js/Components/
└── **/*.stories.ts     # Component stories
```

## Configuration

### `.storybook/main.ts`

The main configuration file follows official Vue3+Vite guidelines:

```typescript
import { mergeConfig } from 'vite';
import type { StorybookConfig } from '@storybook/vue3-vite';

const config: StorybookConfig = {
  // Story file locations
  stories: ["../resources/js/**/*.stories.@(js|jsx|mjs|ts|tsx)"],

  // Addons for enhanced functionality
  addons: [
    "@storybook/addon-docs",      // Automatic documentation
    "@storybook/addon-vitest",    // Vitest integration
    "@storybook/addon-a11y"       // Accessibility testing
  ],

  // Framework configuration
  framework: {
    name: "@storybook/vue3-vite",
    options: {
      docgen: "vue-component-meta"
    }
  },

  // Documentation configuration
  docs: {
    autodocs: 'tag'
  },

  // TypeScript configuration
  typescript: {
    check: false  // Disabled for performance
  },

  // Vite configuration override
  async viteFinal(config) {
    return mergeConfig(config, {
      resolve: {
        alias: {
          "@": "/resources/js",
          "ziggy-js": "/vendor/tightenco/ziggy/dist"
        }
      }
    });
  }
};

export default config;
```

**Critical Points**:
- ✅ Aliases must exactly match `vite.config.mts`
- ✅ TypeScript checking disabled for faster development
- ✅ Minimal addon set for stability

### `.storybook/preview.ts`

Global parameters and decorators:

```typescript
import type { Preview } from '@storybook/vue3';

const preview: Preview = {
  parameters: {
    controls: {
      matchers: {
        color: /(background|color)$/i,
        date: /Date$/i
      }
    },
    backgrounds: {
      default: 'light',
      values: [
        { name: 'light', value: '#ffffff' },
        { name: 'dark', value: '#1a1a1a' }
      ]
    }
  }
};

export default preview;
```

### `.storybook/vitest.setup.ts`

Test environment configuration:

```typescript
import { beforeAll } from 'vitest';

beforeAll(() => {
  // Setup global mocks
  globalThis.route = route;
  globalThis.trans = trans;
  globalThis.$t = $t;
});
```

### Vitest Integration

The main `vitest.config.ts` includes a Storybook project:

```typescript
export default defineConfig({
  test: {
    name: 'storybook',
    browser: {
      enabled: true,
      provider: 'playwright',
      instances: [{ browser: 'chromium' }],
      headless: true
    },
    include: ['**/*.stories.ts'],
    setupFiles: ['./.storybook/vitest.setup.ts']
  }
});
```

## Mock System

### Centralized Mocks

All Laravel-specific dependencies are mocked in `resources/js/mocks/`:

#### `inertia.mock.ts`

```typescript
import { vi } from 'vitest';

export const usePage = vi.fn(() => ({
  props: {
    app: {
      locale: 'lt',
      subdomain: 'www',
      name: 'VU SA'
    },
    auth: {
      user: null,
      can: {}
    },
    flash: {
      success: null,
      error: null,
      warning: null,
      info: null
    }
  }
}));

export const router = {
  visit: vi.fn(),
  get: vi.fn(),
  post: vi.fn(),
  put: vi.fn(),
  patch: vi.fn(),
  delete: vi.fn(),
  reload: vi.fn(),
  replace: vi.fn(),
  remember: vi.fn(),
  restore: vi.fn()
};

export const useForm = vi.fn((data) => ({
  ...data,
  processing: false,
  errors: {},
  hasErrors: false,
  submit: vi.fn(),
  reset: vi.fn(),
  clearErrors: vi.fn()
}));
```

#### `i18n.mock.ts`

```typescript
import { vi } from 'vitest';

const translations: Record<string, string> = {
  'Save': 'Išsaugoti',
  'Cancel': 'Atšaukti',
  'Delete': 'Ištrinti',
  'Edit': 'Redaguoti',
  // ... more translations
};

export const trans = vi.fn((key: string) => {
  return translations[key] || key;
});

export const transChoice = vi.fn((key: string, count: number) => {
  return translations[key] || key;
});

export const $t = trans;
export const $tChoice = transChoice;
```

#### `route.mock.ts`

```typescript
import { vi } from 'vitest';

const routes: Record<string, string> = {
  'users.index': '/mano/users',
  'users.create': '/mano/users/create',
  'users.edit': '/mano/users/:id/edit',
  'users.destroy': '/mano/users/:id',
  // ... more routes
};

export const route = vi.fn((name: string, params?: Record<string, any>) => {
  let path = routes[name] || `/mocked-route/${name}`;

  if (params) {
    Object.entries(params).forEach(([key, value]) => {
      path = path.replace(`:${key}`, String(value));
    });
  }

  return path;
});

export const Ziggy = {
  routes: routes
};
```

### Using Mocks in Stories

Override mock behavior for specific stories:

```typescript
import { usePage, router } from "@/mocks/inertia.mock";
import type { Meta, StoryObj } from '@storybook/vue3-vite';

// Override usePage for authenticated user
usePage.mockImplementation(() => ({
  props: {
    auth: {
      user: {
        id: 1,
        name: 'Test User',
        email: 'test@example.com'
      },
      can: {
        create: { meeting: true },
        delete: { meeting: false }
      }
    },
    flash: {
      success: 'Operation successful'
    }
  }
}));

// Test router interactions
const onVisit = vi.fn();
router.visit = onVisit;

export const WithRouter: Story = {
  play: async () => {
    // Trigger navigation
    await someAction();

    // Verify router was called
    expect(onVisit).toHaveBeenCalledWith('/expected/route');
  }
};
```

## Writing Stories

### Basic Story Template

```typescript
import type { Meta, StoryObj } from '@storybook/vue3-vite';
import Button from './Button.vue';

const meta: Meta<typeof Button> = {
  title: 'Components/ui/Button',
  component: Button,
  tags: ['autodocs'],
  argTypes: {
    variant: {
      control: 'select',
      options: ['default', 'destructive', 'outline', 'ghost', 'link']
    },
    size: {
      control: 'select',
      options: ['default', 'sm', 'lg', 'icon']
    }
  },
  args: {
    onClick: fn() // Track click events
  }
};

export default meta;
type Story = StoryObj<typeof meta>;

export const Default: Story = {
  args: {
    children: 'Click me'
  }
};

export const Destructive: Story = {
  args: {
    children: 'Delete',
    variant: 'destructive'
  }
};
```

### Interactive Story with Testing

```typescript
import { userEvent, within, expect } from 'storybook/test';

export const WithValidation: Story = {
  args: {
    initialValue: ''
  },
  play: async ({ canvasElement, args }) => {
    const canvas = within(canvasElement);

    // Find form elements
    const input = canvas.getByRole('textbox');
    const submitButton = canvas.getByRole('button', { name: /submit/i });

    // Try submitting empty form
    await userEvent.click(submitButton);

    // Verify validation error appears
    await canvas.findByText('This field is required');

    // Fill in valid data
    await userEvent.type(input, 'Valid input');
    await userEvent.click(submitButton);

    // Verify success
    expect(args.onSubmit).toHaveBeenCalledWith({ value: 'Valid input' });
  }
};
```

### Story with Slots

```typescript
export const WithSlots: Story = {
  render: (args) => ({
    components: { Card },
    setup() {
      return { args };
    },
    template: `
      <Card v-bind="args">
        <template #header>
          <h2 class="text-xl font-bold">Custom Header</h2>
        </template>
        <template #default>
          <p>Card content goes here</p>
        </template>
        <template #footer>
          <button>Action Button</button>
        </template>
      </Card>
    `
  })
};
```

## Testing Integration

### Running Tests

```bash
# Daily development (unit + component tests, skips browser)
npm run test

# Storybook tests only
npm run test:storybook

# All tests including browser-based Storybook tests
npm run test:all

# Interactive Storybook with testing UI
npm run storybook
```

### Test Environment

- **Browser**: Chromium via Playwright
- **Provider**: `@vitest/browser` with Playwright
- **Setup**: Automatic mock configuration via `vitest.setup.ts`
- **Coverage**: Istanbul provider with 75% thresholds

### Writing Testable Components

**❌ Avoid (Hard to Test)**:

```vue
<script setup>
// Top-level await makes component async
const apiData = await fetch('/api/data').then(r => r.json());

// Hard-coded dependencies
const handleSave = async () => {
  await axios.post('/api/save', formData);
};
</script>
```

**✅ Prefer (Easy to Test)**:

```vue
<script setup>
// Dependency injection via props
const props = defineProps<{
  data?: ApiData[];
  onSave?: (data: FormData) => Promise<void>;
}>();

const data = ref(props.data || []);

// Fetch only if data not provided
onMounted(async () => {
  if (!props.data) {
    data.value = await fetchApiData();
  }
});

// Use provided callback or default
const handleSave = props.onSave || defaultSaveHandler;
</script>
```

## Story Categories

### Component Organization

```
Components/
├── ui/                 # Base UI components (Button, Input, Card, etc.)
│   ├── Button.stories.ts
│   ├── Input.stories.ts
│   └── Card.stories.ts
├── Alerts/            # Alert and notification components
│   ├── Alert.stories.ts
│   └── Toast.stories.ts
├── AdminForms/        # Admin-specific form components
│   ├── FormSection.stories.ts
│   └── FormField.stories.ts
├── Public/            # Public-facing components
│   └── Hero.stories.ts
└── Modals/           # Modal and dialog components
    └── Dialog.stories.ts
```

### Story Naming Conventions

- **Title**: Use hierarchical structure (`Components/ui/Button`)
- **Stories**: Descriptive state names (`Default`, `WithIcon`, `Loading`, `Disabled`)
- **Interactive**: Describe the interaction (`WithValidation`, `UserLoginFlow`)

### Example Structure

```typescript
const meta: Meta<typeof Component> = {
  title: 'Components/ui/Button',  // Category/Subcategory/ComponentName
  component: Component
};

// States and variants
export const Default: Story = { ... };
export const WithIcon: Story = { ... };
export const Loading: Story = { ... };
export const Disabled: Story = { ... };

// Interactive flows
export const ClickInteraction: Story = { ... };
export const ValidationFlow: Story = { ... };
```

## Best Practices

### Story Writing

1. **Start Simple**: Create basic visual stories first
2. **Document Variants**: Cover all important component states
3. **Add Interactions**: Use `play` functions for user flows
4. **Mock Dependencies**: Always mock external APIs and services
5. **Test Edge Cases**: Include error states, loading states, empty states

### Component Design

1. **Dependency Injection**: Accept data and callbacks via props
2. **Loading States**: Show appropriate feedback during async operations
3. **Error Handling**: Gracefully handle and display errors
4. **Accessibility**: Use semantic HTML and ARIA attributes
5. **TypeScript**: Provide proper prop types and interfaces

### Performance

1. **Lazy Loading**: Use `defineAsyncComponent` for heavy components
2. **Mock Optimization**: Keep mocks simple and focused
3. **Test Isolation**: Ensure tests don't interfere with each other
4. **Bundle Management**: Monitor story compilation times

### Accessibility

1. **Semantic HTML**: Use appropriate HTML elements
2. **ARIA Attributes**: Add when semantic HTML isn't enough
3. **Keyboard Navigation**: Ensure all interactive elements are keyboard accessible
4. **Color Contrast**: Meet WCAG guidelines
5. **Screen Reader Testing**: Use a11y addon to catch issues

## Troubleshooting

### "Failed to resolve import '@/mocks/...'"

**Cause**: Alias configuration mismatch between Storybook and Vite

**Solution**:
```typescript
// .storybook/main.ts must match vite.config.mts
resolve: {
  alias: {
    "@": "/resources/js",
    "ziggy-js": "/vendor/tightenco/ziggy/dist"
  }
}
```

### "Component stuck in loading state"

**Cause**: Component uses top-level `await` or async data fetching

**Solution**: Refactor to accept data via props (dependency injection)

### "Tests failing with undefined globals"

**Cause**: Mocks not properly configured in test environment

**Solution**:
- Use `globalThis` instead of `global` in setup files
- Ensure `.storybook/vitest.setup.ts` is properly configured
- Import mocks from correct path (`@/mocks/inertia.mock`)

### "Browser tests not running"

**Cause**: Playwright browsers not installed

**Solution**:
```bash
npx playwright install
```

### "Module resolution errors"

**Cause**: TypeScript/Vite path issues

**Solution**:
- Check `tsconfig.json` paths configuration
- Verify Vite alias configuration
- Ensure consistent paths across all config files

## Migration Notes

### From Legacy Naive UI Tables

- **Don't migrate** existing working tables unnecessarily
- **Use TanStack** tables for new features
- **Stories** work better with simplified component architecture

### From Complex Components

- **Refactor async components** to accept props
- **Extract business logic** from presentation
- **Use Composition API** for better testability
- **Add TypeScript** for type safety

### Upgrading Storybook

When upgrading Storybook:
1. Check [Storybook migration guide](https://storybook.js.org/docs/migration-guide)
2. Update `.storybook/main.ts` configuration
3. Test all stories after upgrade
4. Update addons if needed

## Resources

- [Storybook Vue3+Vite Documentation](https://storybook.js.org/docs/get-started/frameworks/vue3-vite)
- [Vitest Browser Mode](https://vitest.dev/guide/browser.html)
- [Testing Library Vue](https://testing-library.com/docs/vue-testing-library/intro/)
- [Playwright Documentation](https://playwright.dev/)
- [Vue 3 Documentation](https://vuejs.org/)
- [WCAG Guidelines](https://www.w3.org/WAI/WCAG21/quickref/)

---

**Last Updated**: January 2025
**Storybook Version**: 9.1.0
**Vitest Version**: 3.2.4
**Vue Version**: 3.3.4
