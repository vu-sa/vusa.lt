# Storybook - AI Guidance

Quick reference for AI assistants working with Storybook in vusa.lt.

**For comprehensive documentation**: See [README.md](README.md) in this directory.

## Quick Reference

### When to Use Storybook

- **Visual components**: UI elements like buttons, modals, cards
- **Interactive testing**: User flows that need browser environment
- **Documentation**: Component showcase for team
- **Accessibility testing**: Using a11y addon

**Don't use for**: Services, composables, utilities (use unit tests instead)

## Mock System

### Available Mocks

**Location**: `resources/js/mocks/` (NOT `.storybook/mocks/`)

- **`inertia.mock.ts`**: usePage, router, useForm
- **`i18n.mock.ts`**: trans, transChoice, $t, $tChoice
- **`route.mock.ts`**: route() function

### Using Mocks in Stories

```typescript
import { usePage, router } from "@/mocks/inertia.mock";
import { trans } from "@/mocks/i18n.mock";
import { route } from "@/mocks/route.mock";

// Override for specific test
usePage.mockImplementation(() => ({
  props: {
    auth: { user: { id: 1, name: 'Test User' }, can: { create: { meeting: true } } },
    flash: { success: 'Operation successful' }
  }
}));
```

## Story Patterns

### Basic Story

```typescript
import type { Meta, StoryObj } from '@storybook/vue3-vite';
import ComponentName from './ComponentName.vue';

const meta: Meta<typeof ComponentName> = {
  title: 'Components/ComponentName',
  component: ComponentName,
  tags: ['autodocs']
};

export default meta;
type Story = StoryObj<typeof meta>;

export const Default: Story = {
  args: {
    title: 'Example Title'
  }
};
```

### Interactive Story with Tests

```typescript
import { userEvent, within } from 'storybook/test';

export const Interactive: Story = {
  play: async ({ canvasElement }) => {
    const canvas = within(canvasElement);

    const button = canvas.getByRole('button');
    await userEvent.click(button);

    await canvas.findByText('Expected result');
  }
};
```

## Troubleshooting

### Issue: "Failed to resolve import '@/mocks/...'"

**Cause**: Alias configuration mismatch
**Solution**: Ensure `.storybook/main.ts` aliases match `vite.config.mts`

```typescript
// .storybook/main.ts must have:
resolve: {
  alias: {
    "@": "/resources/js",
    "ziggy-js": "/vendor/tightenco/ziggy/dist"
  }
}
```

### Issue: "Component stuck in loading state"

**Cause**: Component uses top-level await or async data fetching
**Solution**: Make component accept data via props

```vue
<!-- ❌ Avoid -->
<script setup>
const data = await fetch('/api/data');
</script>

<!-- ✅ Prefer -->
<script setup>
const props = defineProps<{ data?: ApiData[] }>();
const data = ref(props.data || []);

onMounted(async () => {
  if (!props.data) {
    data.value = await fetchApiData();
  }
});
</script>
```

### Issue: "Tests failing with undefined globals"

**Cause**: Missing mock setup
**Solution**: Ensure `vitest.setup.ts` properly configures mocks

Use `globalThis` instead of `global`:

```typescript
// ✅ Correct
globalThis.route = route;

// ❌ Wrong
global.route = route;
```

### Issue: "Browser tests not running"

**Cause**: Playwright browsers not installed
**Solution**: Install Playwright browsers

```bash
npx playwright install
```

### Issue: "Checkbox v-model not working"

**Pattern**: Use `modelValue` prop, not `checked`

```vue
<!-- ✅ Correct -->
<Checkbox v-model="isChecked" />

<!-- ❌ Wrong -->
<Checkbox v-model:checked="isChecked" />
```

## Configuration Gotchas

### Alias Configuration

**Critical**: Aliases in `.storybook/main.ts` **must** exactly match `vite.config.mts`

### Mock Import Paths

**Always use full mock file names**:
- ✅ `@/mocks/inertia.mock`
- ❌ `@/mocks/inertia` (missing `.mock`)

### Test Environment

- **Browser tests**: Chromium via Playwright
- **Setup file**: `.storybook/vitest.setup.ts`
- **Mocks**: Automatically applied globally

## Best Practices for AI

### When Creating Stories

1. **Start with basic visual story** (no interactions)
2. **Add interactive tests** if component has user interactions
3. **Mock all Laravel dependencies** (Inertia, i18n, routes)
4. **Use dependency injection** (props) over async fetching

### Component Design

1. **Accept data via props** when possible
2. **Avoid top-level await** in components
3. **Handle loading/error states** gracefully
4. **Use semantic HTML** for accessibility

### Testable Components

```vue
<!-- ✅ Good: Testable with dependency injection -->
<script setup>
const props = defineProps<{
  users?: User[];
  onSave?: (data: FormData) => void;
}>();

const users = ref(props.users || []);
const handleSave = props.onSave || defaultSaveHandler;
</script>

<!-- ❌ Bad: Hard to test -->
<script setup>
const users = await fetchUsers(); // Top-level await
const handleSave = () => {
  axios.post('/api/save'); // Hard-coded dependency
};
</script>
```

## Running Tests

```bash
# Daily development (skips browser tests)
npm run test

# Storybook tests only
npm run test:storybook

# All tests including browser
npm run test:all

# Interactive Storybook UI
npm run storybook
```

## Quick Decision Tree

```
Need to test visual appearance? → Write Storybook story
Need to test user interactions? → Write Storybook story with `play` function
Need to test business logic? → Write unit test (*.test.ts)
Need to test component API? → Write component test (*.component.test.ts)
```

## Debug Tips

1. **Use Storybook UI**: Run tests directly in browser
2. **Check console**: Look for mock-related errors
3. **Verify mocks**: Log mock return values
4. **Test isolation**: Clear mocks between stories

```typescript
import { beforeEach, vi } from 'vitest';

beforeEach(() => {
  vi.clearAllMocks();
});
```

---

**See [README.md](README.md) for**:
- Complete configuration details
- Story writing guide
- Testing integration setup
- Best practices guide
- Migration notes
