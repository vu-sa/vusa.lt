# Component Demos for VitePress Documentation

This folder contains Vue components that can be used to demonstrate application components within the VitePress documentation.

## How to Use Components in Documentation

### 1. Create a Demo Component

Create a new `.vue` file in `docs/components/demos/`:

```vue
<script setup lang="ts">
import { ref } from 'vue'
import SuggestionAlert from '@/Components/Alerts/SuggestionAlert.vue'

const showAlert = ref(true)
</script>

<template>
  <SuggestionAlert v-model="showAlert">
    <p>This is an example alert from the application.</p>
  </SuggestionAlert>
</template>
```

### 2. Use in Markdown

In your `.md` file, import and use the demo component:

```md
<script setup>
import MyComponentDemo from './components/demos/MyComponentDemo.vue'
</script>

## Component Example

<ComponentDemo 
  title="My Component" 
  description="Shows how the component works"
>
  <MyComponentDemo />
</ComponentDemo>
```

### 3. ComponentDemo Wrapper

The `<ComponentDemo>` wrapper provides:

- **Style isolation** - App components render exactly as in production, isolated from VitePress styles
- **Suspense boundary** - Handles async component loading
- **Error boundary** - Catches and displays component errors gracefully
- **Dark mode support** - Syncs with VitePress theme

#### Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `title` | `string` | - | Header title for the demo |
| `description` | `string` | - | Description text below title |
| `variant` | `'default' \| 'dark' \| 'transparent'` | `'default'` | Background variant |
| `noPadding` | `boolean` | `false` | Remove padding from demo area |

## Available Mocks

Component demos have access to these mocked functions:

- `$t(key)` - Returns the translation key (for i18n)
- `$tChoice(key, count)` - Returns the translation key with count
- `route(name, params)` - Returns a mock URL path

## Example: Alert Component

```vue
<script setup lang="ts">
import { ref } from 'vue'
import SuggestionAlert from '@/Components/Alerts/SuggestionAlert.vue'

const show = ref(true)
</script>

<template>
  <div class="space-y-4">
    <SuggestionAlert v-model="show">
      <p>Rezervuojant resursus, nepamirškite:</p>
      <ul>
        <li>Patikrinti laikotarpį</li>
        <li>Užpildyti aprašymą</li>
      </ul>
    </SuggestionAlert>
    
    <button 
      v-if="!show" 
      @click="show = true"
      class="px-3 py-1.5 text-sm bg-primary text-primary-foreground rounded"
    >
      Show Alert
    </button>
  </div>
</template>
```

## Notes

- Import paths use `@/` alias pointing to `resources/js/`
- Tailwind CSS utilities are available inside demos
- All shadcn/ui CSS variables are defined for component styling

## When to Use Screenshots vs Live Demos

| Use `<ComponentScreenshot>` | Use `<ComponentDemo>` |
|---|---|
| Static UI (buttons, badges, cards) | Interactive flows (forms, modals) |
| Layout examples | Components with state changes |
| Design reference | Animations or transitions |

### ComponentScreenshot Usage

Place screenshots in `docs/public/images/components/` and use the global wrapper:

```md
<ComponentScreenshot
  src="/docs/images/components/my-component.png"
  title="My Component"
  description="Shows the default appearance"
  alt="Screenshot of My Component"
  caption="Light mode variant"
/>
```

#### Props

| Prop | Type | Required | Description |
|------|------|----------|-------------|
| `src` | `string` | ✅ | Image path (relative to `/docs/public/`) |
| `title` | `string` | - | Header title |
| `description` | `string` | - | Description below title |
| `alt` | `string` | - | Alt text (default: "Component screenshot") |
| `caption` | `string` | - | Caption below the image |

Using screenshots is a common and recommended approach — it avoids maintenance overhead of keeping live component demos in sync with the app.
