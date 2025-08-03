# Storybook Story Templates

This document provides standardized templates for creating consistent and maintainable stories across the project.

## 🏗️ Component Story Template

Use this template for all new component stories:

```typescript
import type { Meta, StoryObj } from '@storybook/vue3-vite'
import { fn } from 'storybook/test'
import ComponentName from './ComponentName.vue'

// Import mocks if needed
import { usePage } from '@/mocks/inertia'
import { trans } from '@/mocks/i18n' 
import { route } from '@/mocks/route'

// Define mock data
const mockData = {
  // Your mock data here
}

const meta: Meta<typeof ComponentName> = {
  title: 'Category/ComponentName', // Use hierarchical categories
  component: ComponentName,
  tags: ['autodocs'],
  argTypes: {
    // Define controls for props
    prop1: { control: 'text' },
    prop2: { control: 'boolean' },
    prop3: { control: 'select', options: ['option1', 'option2'] },
    // Event handlers
    onEvent: fn(),
  },
  args: {
    // Default prop values
    prop1: 'default value',
    prop2: false,
    prop3: 'option1',
    // Default event handlers
    onEvent: fn(),
  },
  decorators: [
    (story) => ({
      components: { story },
      template: \`
        <div class="p-6">
          <story />
        </div>
      \`
    })
  ],
  parameters: {
    layout: 'centered', // or 'fullscreen' for full-width components
    docs: {
      description: {
        component: 'Brief description of what this component does.'
      }
    }
  }
}

export default meta
type Story = StoryObj<typeof meta>

// Standard story variants
export const Default: Story = {
  render: (args) => ({
    components: { ComponentName },
    setup() {
      return { args }
    },
    template: '<ComponentName v-bind="args" @event="args.onEvent" />'
  })
}

export const Loading: Story = {
  args: {
    loading: true
  },
  render: (args) => ({
    components: { ComponentName },
    setup() {
      return { args }
    },
    template: '<ComponentName v-bind="args" @event="args.onEvent" />'
  })
}

export const WithData: Story = {
  args: {
    data: mockData
  },
  render: (args) => ({
    components: { ComponentName },
    setup() {
      return { args }
    },
    template: '<ComponentName v-bind="args" @event="args.onEvent" />'
  })
}

// Interactive story with play function (optional)
export const Interactive: Story = {
  render: (args) => ({
    components: { ComponentName },
    setup() {
      return { args }
    },
    template: '<ComponentName v-bind="args" @event="args.onEvent" />'
  }),
  play: async ({ canvasElement, args }) => {
    const canvas = within(canvasElement)
    
    // Wait for component to render
    await new Promise(resolve => setTimeout(resolve, 500))
    
    // Perform interactions
    const button = canvas.getByRole('button')
    await userEvent.click(button)
    
    // Add assertions if needed
    await new Promise(resolve => setTimeout(resolve, 200))
  }
}
```

## 📋 Category Guidelines

### Component Categories
- **`UI/ComponentName`** - Basic UI components (Button, Input, Alert, etc.)
- **`Components/ComponentName`** - Application-specific components
- **`Forms/ComponentName`** - Form-related components
- **`Search/ComponentName`** - Search and filter components
- **`Modals/ComponentName`** - Modal and dialog components
- **`Admin/ComponentName`** - Admin-specific components

### Story Naming Conventions
- **`Default`** - Standard component state
- **`Loading`** - Loading/pending state
- **`Empty`** - Empty/no data state
- **`Error`** - Error state
- **`WithData`** - Component with mock data
- **`Interactive`** - Story with user interactions
- **`Mobile`** - Mobile viewport variant
- **`[SpecificState]`** - Any other specific state

## 🎭 Mock Usage Patterns

### Import Centralized Mocks
```typescript
// Always use centralized mocks
import { usePage, router } from '@/mocks/inertia'
import { trans, $t } from '@/mocks/i18n'
import { route } from '@/mocks/route'

// ❌ Avoid - Don't create inline mocks
const mockUsePage = vi.fn(() => ({ ... }))
```

### Override Mock Behavior
```typescript
// Override specific mock behavior for component needs
usePage.mockImplementation(() => ({
  props: {
    auth: {
      user: { id: 1, name: 'Test User' },
      can: { create: { meeting: true } }
    }
  }
}))
```

## 🎨 Styling and Layout

### Decorators for Layout
```typescript
decorators: [
  (story) => ({
    components: { story },
    template: \`
      <!-- Centered layout for small components -->
      <div class="p-6 max-w-md mx-auto">
        <story />
      </div>
      
      <!-- Full-width layout for complex components -->
      <div class="p-6 bg-gray-50 min-h-screen">
        <div class="max-w-6xl mx-auto">
          <story />
        </div>
      </div>
    \`
  })
]
```

### Parameters Configuration
```typescript
parameters: {
  layout: 'centered', // 'centered', 'fullscreen', 'padded'
  backgrounds: {
    default: 'light', // 'light', 'dark'
    values: [
      { name: 'light', value: '#ffffff' },
      { name: 'dark', value: '#1a1a1a' }
    ]
  },
  viewport: {
    defaultViewport: 'responsive' // 'mobile1', 'tablet', 'desktop'
  }
}
```

## 🧪 Testing with Play Functions

### Basic Interaction Testing
```typescript
play: async ({ canvasElement, args }) => {
  const canvas = within(canvasElement)
  
  // Wait for component to render
  await new Promise(resolve => setTimeout(resolve, 500))
  
  try {
    // Find elements and interact
    const input = canvas.getByPlaceholderText('Search...')
    await userEvent.type(input, 'test query')
    
    const button = canvas.getByRole('button', { name: /search/i })
    await userEvent.click(button)
    
    // Verify events were called
    await new Promise(resolve => setTimeout(resolve, 200))
    
  } catch (error) {
    console.warn('Some elements may not be available:', error)
  }
}
```

## 📁 File Organization

### Recommended Structure
```
resources/js/Components/
├── ComponentName/
│   ├── ComponentName.vue
│   ├── ComponentName.stories.ts
│   └── index.ts
```

### Story File Naming
- Use `ComponentName.stories.ts` (not `.stories.js`)
- Keep stories co-located with components
- Avoid `__tests__` directories for stories

## ✅ Quality Checklist

Before creating a story, ensure:

- [ ] Uses centralized mocks from `@/mocks/`
- [ ] Follows hierarchical title naming (`Category/Component`)  
- [ ] Includes `tags: ['autodocs']` for automatic documentation
- [ ] Has meaningful default args
- [ ] Includes standard story variants (Default, Loading, etc.)
- [ ] Uses `fn()` for event handlers
- [ ] Has appropriate decorators for layout
- [ ] Play functions handle errors gracefully
- [ ] Mock data is realistic and comprehensive

## 🚀 Quick Start Commands

```bash
# Test stories
npm run storybook

# Run story tests  
npm run test:storybook

# Build storybook
npm run storybook:build
```