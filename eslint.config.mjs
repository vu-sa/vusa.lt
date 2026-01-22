import eslint from '@eslint/js';
import tseslint from 'typescript-eslint';
import pluginVue from 'eslint-plugin-vue';
import pluginVueA11y from 'eslint-plugin-vuejs-accessibility';
import importPlugin from 'eslint-plugin-import';
import stylistic from '@stylistic/eslint-plugin';
import globals from 'globals';

// ESLint server doesn't support 'configs' yet...
export default tseslint.config(
  eslint.configs.recommended,
  tseslint.configs.recommended,
  pluginVue.configs['flat/recommended'],
  pluginVueA11y.configs['flat/recommended'],
  // Tailwind ESLint plugin disabled - incompatible with Tailwind v4
  // See: https://github.com/francoismassart/eslint-plugin-tailwindcss/issues/325
  // ESLint Stylistic for formatting (semi: true, single quotes, 2 space indent)
  stylistic.configs.customize({
    indent: 2,
    quotes: 'single',
    semi: true,
    jsx: true,
  }),
  {
    languageOptions: {
      parserOptions: {
        // Project Service disabled - performance bottleneck confirmed
        // Even with all optimizations the Project Service causes 2+ minute timeouts
        ecmaVersion: 'latest',
        sourceType: 'module',
        extraFileExtensions: ['.vue', '.mjs'],
      },
    },
  },
  {
    plugins: {
      import: importPlugin,
    },
  },
  {
    files: ['**/*.{ts,vue,js,mjs}'],
    ignores: [
      '**/node_modules/**',
      '**/vendor/**',
      '**/storage/**',
      '**/public/build/**',
      '**/*.min.js',
      '**/coverage/**',
    ],
    languageOptions: {
      globals: {
        ...globals.browser,
      },
      parserOptions: {
        ecmaFeatures: {
          jsx: true,
        },
        parser: tseslint.parser,
        sourceType: 'module',
      },
    },

    rules: {
      // Code quality and conciseness
      'max-len': ['warn', {
        code: 180,
        ignoreUrls: true,
        ignoreStrings: true,
        ignoreTemplateLiterals: true,
        ignoreComments: true,
      }],
      'object-shorthand': ['warn', 'always'],
      'prefer-arrow-callback': ['warn', { allowNamedFunctions: false }],
      'prefer-const': 'warn',
      'prefer-template': 'warn',
      'prefer-destructuring': ['warn', {
        array: false,
        object: true,
      }],
      'prefer-spread': 'warn',
      'prefer-rest-params': 'warn',
      'prefer-regex-literals': 'warn',
      'no-var': 'error',
      'no-lonely-if': 'warn',
      'no-unneeded-ternary': 'warn',
      'no-useless-concat': 'warn',
      'no-useless-return': 'warn',

      // Import organization
      'import/order': ['warn', {
        'groups': ['builtin', 'external', 'internal', 'parent', 'sibling', 'index'],
        'newlines-between': 'always',
      }],
      'import/newline-after-import': 'warn',
      'import/no-duplicates': 'warn',

      // Prevent full lodash imports (use lodash-es or @vueuse/core utilities)
      'no-restricted-imports': ['error', {
        paths: [{
          name: 'lodash',
          message: 'Import from "lodash-es" for tree-shaking, or use "@vueuse/core" utilities like useDebounceFn.',
        }],
        patterns: [{
          group: ['lodash/*'],
          message: 'Import from "lodash-es/*" for tree-shaking.',
        }],
      }],

      // TypeScript modern patterns
      '@typescript-eslint/prefer-as-const': 'warn',
      '@typescript-eslint/prefer-function-type': 'warn',
      '@typescript-eslint/no-array-constructor': 'error',
      '@typescript-eslint/no-explicit-any': 'error',
      '@typescript-eslint/no-unused-vars': 'off', // Handled by TypeScript LSP
      '@typescript-eslint/consistent-type-definitions': ['warn', 'interface'],
      '@typescript-eslint/consistent-type-imports': ['warn', {
        prefer: 'type-imports',
        fixStyle: 'separate-type-imports',
      }],
      '@typescript-eslint/no-import-type-side-effects': 'warn',
      '@typescript-eslint/ban-ts-comment': ['warn', {
        'ts-expect-error': 'allow-with-description',
        'ts-ignore': true,
        'ts-nocheck': true,
        'ts-check': false,
      }],

      // Vue.js best practices
      'vue/max-attributes-per-line': 'off', // Handled by Stylistic
      'vue/component-name-in-template-casing': ['warn', 'PascalCase'],
      'vue/define-props-declaration': ['warn', 'type-based'],
      'vue/define-emits-declaration': ['warn', 'type-based'],
      'vue/v-bind-style': ['warn', 'shorthand', { sameNameShorthand: 'always' }],
      'vue/v-on-style': ['warn', 'shorthand'],
      'vue/prefer-separate-static-class': 'off', // To follow guidelines in CLAUDE.md
      'vue/prefer-true-attribute-shorthand': 'warn',
      'vue/no-useless-v-bind': 'warn',
      'vue/no-useless-mustaches': 'warn',
      'vue/no-useless-concat': 'warn',

      // Modern Vue 3 patterns
      'vue/no-deprecated-scope-attribute': 'error',
      'vue/no-deprecated-slot-attribute': 'error',
      'vue/no-deprecated-slot-scope-attribute': 'error',
      'vue/prefer-import-from-vue': 'warn',
      'vue/prefer-prop-type-boolean-first': 'warn',
      'vue/require-explicit-emits': 'warn',
      'vue/no-empty-component-block': 'warn',
      'vue/block-order': ['warn', {
        order: ['template', 'script', 'style'],
      }],
      'vue/component-api-style': ['warn', ['script-setup']],
      'vue/html-button-has-type': 'warn',
      'vue/no-boolean-default': 'warn',
      'vue/no-duplicate-attr-inheritance': 'warn',
      'vue/no-empty-pattern': 'warn',
      'vue/no-multiple-objects-in-class': 'warn',
      'vue/no-static-inline-styles': 'warn',
      'vue/no-template-target-blank': 'warn',
      'vue/no-this-in-before-route-enter': 'warn',
      'vue/no-undef-components': 'off', // Too aggressive with component auto-imports
      'vue/no-undef-properties': 'off', // Too aggressive with computed/props
      'vue/no-unused-properties': 'off', // Too aggressive with template usage
      'vue/padding-line-between-blocks': 'warn',

      // Disable Vue formatting rules (handled by Stylistic)
      'vue/html-indent': 'off',
      'vue/html-closing-bracket-newline': 'off',
      'vue/first-attribute-linebreak': 'off',

      // Project-specific overrides
      'no-undef': 'off', // Namespaces shown as undefined

      // Accessibility - disabled for ShadcnVue compatibility
      'vuejs-accessibility/label-has-for': 'off',
      'vuejs-accessibility/form-control-has-label': 'off',
    },
  },
);
