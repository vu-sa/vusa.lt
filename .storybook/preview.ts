import type { Preview, Decorator } from '@storybook/vue3-vite';
import { setup } from '@storybook/vue3-vite';

import '../resources/css/app.css';
import './storybook.css'; // Storybook-specific overrides for dialogs, etc.

// Unified mocks with real (Lithuanian) translations
import { trans, transChoice } from '../resources/js/mocks/i18n';
import { route } from '../resources/js/mocks/route';
// inertia.storybook.ts, not inertia.mock.ts: it uses `fn()` from storybook/test rather than
// vitest's `vi`, so it works in `storybook dev` as well as the browser-mode test project.
// .storybook/main.ts and vitest.config.ts both alias `@inertiajs/vue3` to the same file.
import { usePage } from '../resources/js/mocks/inertia.storybook';

setup((app) => {
  app.config.globalProperties.$t = trans;
  app.config.globalProperties.$tChoice = transChoice;
  app.config.globalProperties.route = route;
  // A getter, not a snapshot. Assigning `usePage()` once froze the *default* mock into every
  // template, so a story that overrode `usePage.mockImplementation` still saw the defaults in
  // `$page.props` (its `<script setup>` code saw the override, its template did not) — which is
  // how the header story lost its tenant quick links. Re-reading per access keeps both in step.
  Object.defineProperty(app.config.globalProperties, '$page', {
    get: () => usePage(),
    configurable: true,
  });

  if (typeof window !== 'undefined') {
    (window as unknown as { route: typeof route }).route = route;
  }
});

/**
 * Surface + theme switching.
 *
 * The design tokens are scoped exactly as they are in production: `data-surface="public"` and
 * `.dark`, both on <html> (see resources/css/app.css and resources/views/app.blade.php). The
 * decorator sets them on the story iframe's own documentElement rather than on a wrapper div,
 * so a story renders against the same cascade the real page does — including the rules that
 * are written against `html` itself, such as the a11y font scale.
 *
 * This is the only place dark mode can be checked at all: jsdom does not implement the CSS
 * needed for Tailwind's `dark:` variant to resolve, so component tests can assert that a class
 * is bound but never that it renders (see AGENTS.md).
 */
const withSurfaceAndTheme: Decorator = (story, context) => {
  const { surface, theme } = context.globals;

  if (typeof document !== 'undefined') {
    const root = document.documentElement;

    if (surface === 'public') {
      root.setAttribute('data-surface', 'public');
    } else {
      root.removeAttribute('data-surface');
    }

    // Any story containing DarkModeButton (the header, for one) runs VueUse's `useDark()`, which
    // syncs <html>.dark from `vueuse-color-scheme` and will happily undo the line below. So drive
    // that key too — the same one app.blade.php's FOUC script reads — and fire a storage event,
    // because VueUse's shared ref only re-reads localStorage when it sees one.
    try {
      const key = 'vueuse-color-scheme';
      localStorage.setItem(key, theme as string);
      window.dispatchEvent(new StorageEvent('storage', {
        key,
        newValue: theme as string,
        storageArea: localStorage,
      }));
    } catch {
      // Storage unavailable: the class toggle below still gets the story mostly right.
    }

    root.classList.toggle('dark', theme === 'dark');
  }

  // The story gets its own painted wrapper rather than relying on <body>. In docs mode each
  // story is embedded in Storybook's own light-themed docs page, so a dark story rendered onto
  // the body would still sit on a white card; painting the wrapper works in both.
  //
  // The height differs by view mode on purpose. On the canvas the ground should fill the frame,
  // or a short story leaves a pale band under it. In docs there are many previews down one
  // scrolling page, so `min-h-screen` would turn each into a viewport-tall slab — there the
  // block hugs its content and just takes padding.
  const frameClass = context.viewMode === 'docs'
    ? 'p-6'
    : 'min-h-screen';

  return {
    components: { story },
    template: `<div data-sb-surface class="bg-background text-foreground ${frameClass}"><story /></div>`,
  };
};

const preview: Preview = {
  globalTypes: {
    surface: {
      description: 'Design token scope — public takes the editorial palette, admin the current one',
      defaultValue: 'public',
      toolbar: {
        title: 'Surface',
        icon: 'globe',
        items: [
          { value: 'public', title: 'Public' },
          { value: 'admin', title: 'Admin' },
        ],
        dynamicTitle: true,
      },
    },
    theme: {
      description: 'Colour scheme',
      defaultValue: 'light',
      toolbar: {
        title: 'Theme',
        icon: 'circlehollow',
        items: [
          { value: 'light', title: 'Light' },
          { value: 'dark', title: 'Dark' },
        ],
        dynamicTitle: true,
      },
    },
  },

  decorators: [withSurfaceAndTheme],

  parameters: {
    controls: {
      matchers: {
        color: /(background|color)$/i,
        date: /Date$/i,
      },
    },

    docs: {
      description: {
        component: 'Component documentation',
      },
    },

    a11y: {
      // 'todo' — report violations in the test UI without failing. Public/Base primitives opt
      // up to 'error' in their own story files: they are new code with no legacy debt, so
      // there is no reason to let an axe violation through there.
      test: 'todo',
    },
  },
};

export default preview;
