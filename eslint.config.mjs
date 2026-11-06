import path from 'node:path';
import { fileURLToPath } from 'node:url';

import eslint from '@eslint/js';
import tseslint from 'typescript-eslint';
import pluginVue from 'eslint-plugin-vue';
import pluginVueA11y from 'eslint-plugin-vuejs-accessibility';
import importPlugin, { createNodeResolver } from 'eslint-plugin-import-x';
import stylistic from '@stylistic/eslint-plugin';
import globals from 'globals';

const projectRoot = path.dirname(fileURLToPath(import.meta.url));
const sourceResolver = createNodeResolver({
  alias: { '@': [path.resolve(projectRoot, 'resources/js')] },
  extensions: ['.ts', '.tsx', '.vue', '.js', '.jsx', '.mjs', '.json'],
});

export const LEGACY_ADMIN_EXCEPTIONS = [
  'resources/js/Pages/Admin/Dashboard/Components/CoordinatorSkeleton.vue',
  'resources/js/Pages/Admin/Dashboard/Components/RepresentativeActivitySkeleton.vue',
  'resources/js/Pages/Admin/Dashboard/Components/RepresentativeDataTable.vue',
];

const MIGRATED_ADMIN_COMPONENT_PATHS = [
  'resources/js/Components/Layouts/Shell/**',
  'resources/js/Components/CommandPalette/**',
  'resources/js/Components/Layouts/RecordPage*.vue',
  'resources/js/Components/Meetings/MeetingDatePlate.vue',
  'resources/js/Components/Meetings/MeetingCompletionChecklist.vue',
  'resources/js/Features/Admin/ActivityLogViewer/RecordActivity.vue',
  'resources/js/Components/Graphs/GanttZoomControl.vue',
  'resources/js/Components/Graphs/MeetingsGantt.vue',
  'resources/js/Components/Graphs/MeetingsGanttToolbar.vue',
  'resources/js/Features/Admin/DutiableTimeline/*.vue',
  'resources/js/Components/Patterns/FocusModeFrame.vue',
  'resources/js/Features/Admin/TaskManager/TaskCompletionControl.vue',
  'resources/js/Features/Admin/TaskManager/TaskDetails.vue',
  'resources/js/Features/Admin/TaskManager/TaskRow.vue',
  'resources/js/Components/Brand/**',
  'resources/js/Components/ui/control/**',
  'resources/js/Components/AgendaItems/**',
  'resources/js/Components/Meetings/**',
  'resources/js/Components/Discussions/**',
  'resources/js/Components/Avatars/{UserAvatar,UsersAvatarGroup,UserPopover,UsersFactList}.vue',
  'resources/js/Components/Home/**',
  'resources/js/Components/Collection/**',
  'resources/js/Components/Layouts/CollectionPage.vue',
  'resources/js/Components/Meetings/MeetingCollectionRow.vue',
  'resources/js/Components/ActionWindow/{ActionWindow,ActionWindowBody,ActionWindowScreen,ActionWindowPrimaryButton,ActionChoiceButton,ActionChoiceList,ReviewRow,ScreenLoading,AgendaItemsEditor}.vue',
  'resources/js/Components/ActionWindow/screens/**',
  'resources/js/Components/ActionWindow/screenRegistry.ts',
  'resources/js/Components/Layouts/FormPage.vue',
  'resources/js/Components/Patterns/SheetForm.vue',
  'resources/js/Components/Patterns/ConfirmDialog.vue',
  'resources/js/Components/Patterns/FormSection.vue',
  'resources/js/Features/Admin/Occupancy/**',
  'resources/js/Components/AdminForms/DutyForm.vue',
  'resources/js/Components/AdminForms/{RelationshipForm,TypeForm,StudySetForm}.vue',
  'resources/js/Features/Admin/Tags/**',
  'resources/js/Components/Layouts/OverviewPage.vue',
  'resources/js/Components/Duties/MyDutyTermRow.vue',
  'resources/js/Features/Admin/AdminSearch/Components/SearchResultGroup.vue',
  'resources/js/Features/Admin/AdminSearch/Components/Detail/MeetingDetailPreview.vue',
  'resources/js/Components/Overview/**',
  'resources/js/Components/Patterns/OverviewSection.vue',
  'resources/js/Components/Reservations/**',
  'resources/js/Components/Duties/**',
  'resources/js/Components/Institutions/{AddCheckInDialog,InstitutionDutiesSection,InstitutionMeetingsList,InstitutionOverviewSection,RelatedInstitutionTile,SecretariesSection}.vue',
  'resources/js/Components/AdminForms/{DutyCard,InstitutionForm,ReservationForm,ResourceForm,UserForm,TenantForm,StudyProgramForm}.vue',
  'resources/js/Features/Admin/DutiableTimeline/**',
  'resources/js/Features/Admin/ResourceCategories/**',
  'resources/js/Features/Admin/Tenants/**',
  'resources/js/Components/AdminForms/ProblemForm.vue',
  'resources/js/Components/AdminForms/{FormForm,FormFieldForm}.vue',
  'resources/js/Features/Admin/EventTypes/**',
  'resources/js/Features/Admin/NavigationBuilder/**',
  'resources/js/Components/AdminForms/{PageForm,NewsForm,CalendarForm,FormFieldWrapper,PermalinkField,PermalinkPreviewHint,SEOPreview,BannerForm,QuickLinkForm,NavigationForm,NavigationParentForm,TagForm}.vue',
  'resources/js/Components/Analytics/ContentAnalyticsCard.vue',
  'resources/js/Features/Admin/FileManager/**',
  'resources/js/Features/Admin/SharepointFileManager/**',
  'resources/js/Features/Admin/PermissionMatrix/**',
  'resources/js/Features/Admin/Responsibilities/**',
  'resources/js/Features/Admin/Notifications/{PushDeviceManagement,NotificationPreferences,DigestEmailSelector}.vue',
  'resources/js/Features/Admin/TaskManager/**',
  'resources/js/Features/Admin/Notifications/NotificationCard.vue',
  'resources/js/Components/AdminForms/ResponsibilityForm.vue',
  'resources/js/Components/SupportRequests/SupportRequestForm.vue',
  'resources/js/Components/DutyUserWizard/**',
  'resources/js/Features/Admin/MailingLists/**',

];

// Shared import restriction fragments — reused in per-surface blocks so the
// global lodash ban is not silently dropped when a later config overrides this rule.
const lodashImportPaths = [
  { name: 'lodash', message: 'Import from "lodash-es" for tree-shaking, or use "@vueuse/core" utilities like useDebounceFn.' },
];
const lodashImportPatterns = [
  { group: ['lodash/*'], message: 'Import from "lodash-es/*" for tree-shaking.' },
];
// Dates render through @/Utils/dateTime (Europe/Vilnius); date-fns stays for arithmetic.
const dateImportPaths = [
  {
    name: 'date-fns',
    importNames: ['format', 'formatDistance', 'formatDistanceToNow', 'formatDistanceStrict', 'formatRelative'],
    message: 'Display dates with @/Utils/dateTime or useDateFormatter (Europe/Vilnius). date-fns is for date arithmetic.',
  },
];
const dateImportPatterns = [
  { group: ['@/Utils/IntlTime'], message: 'IntlTime was removed. Use @/Utils/dateTime or useDateFormatter.' },
];
// Icon sets that have been fully removed from the codebase.
const removedIconPatterns = [
  { group: ['~icons/mdi/*'], message: 'MDI was removed. Use @lucide/vue for admin icons or ~icons/simple-icons/* for brand glyphs.' },
  { group: ['@/Types/Icons/*'], message: 'Legacy default-export barrel was deleted. Use direct imports from @lucide/vue or @/Components/icons.' },
];

const rawHuePattern = /^(?:bg|text|border|ring|fill|stroke|from|via|to|divide|outline|decoration|caret|accent)-(?:slate|gray|zinc|neutral|stone|red|orange|amber|yellow|lime|green|emerald|teal|cyan|sky|blue|indigo|violet|purple|fuchsia|pink|rose|white|black)(?:-[\w.]+)?(?:\/[\d.]+)?$/; // eslint-disable-line max-len

function legacyUtility(value) {
  return value.split(/\s+/).map(token => token.replace(/^!/, '').split(':').at(-1)).find(token =>
    rawHuePattern.test(token)
    || /^rounded(?:-(?!none$)[\w-]+)?$/.test(token)
    || /^(?:shadow|drop-shadow)(?:-(?!none$)[\w-]+)?$/.test(token),
  );
}

const adminRedesignPlugin = {
  rules: {
    'no-legacy-utility': {
      meta: {
        type: 'problem',
        schema: [],
        messages: {
          legacy: '"{{utility}}" is legacy admin styling. Use semantic tokens with square, hairline surfaces instead.',
        },
      },
      create(context) {
        function inspect(value, node) {
          const utility = legacyUtility(value);
          if (utility) {
            context.report({ node, messageId: 'legacy', data: { utility } });
          }
        }

        const scriptVisitor = {
          Literal(node) {
            if (typeof node.value === 'string') {
              inspect(node.value, node);
            }
          },
          TemplateLiteral(node) {
            if (node.expressions.length === 0) {
              inspect(node.quasis[0]?.value.cooked ?? '', node);
            }
          },
        };

        const templateVisitor = {
          VAttribute(node) {
            if (node.key && (node.key.name === 'class' || node.key.rawName === 'class') && node.value) {
              inspect(node.value.value ?? '', node);
            }
          },
        };

        return context.sourceCode?.parserServices?.defineTemplateBodyVisitor
          ? context.sourceCode.parserServices.defineTemplateBodyVisitor(templateVisitor, scriptVisitor)
          : scriptVisitor;
      },
    },
  },
};

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
    ignores: [
      'resources/js/Types/enums.ts',
      'resources/js/Types/models.d.ts',
    ],
  },
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
      'import-x': importPlugin,
    },
    settings: {
      'import-x/resolver-next': [sourceResolver],
    },
  },
  {
    files: ['**/*.{ts,tsx,vue,js,jsx,mjs}'],
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
      'import-x/order': ['warn', {
        'groups': ['builtin', 'external', 'internal', 'parent', 'sibling', 'index'],
        'newlines-between': 'always',
      }],
      'import-x/newline-after-import': 'warn',
      'import-x/no-duplicates': 'warn',

      // Prevent full lodash imports (use lodash-es or @vueuse/core utilities)
      // Also ban icon sets that have been fully removed from the codebase.
      'no-restricted-imports': ['error', {
        paths: [...lodashImportPaths, ...dateImportPaths],
        patterns: [...lodashImportPatterns, ...removedIconPatterns, ...dateImportPatterns],
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

      // Vue template formatting
      // NOTE: vue/html-indent is needed because @stylistic/indent only handles <script>,
      // not <template> blocks. Without this, content broken to new lines lands at column 0.
      'vue/html-indent': ['warn', 2, {
        alignAttributesVertically: false,
      }],
      'vue/html-closing-bracket-newline': 'off',
      'vue/first-attribute-linebreak': 'off',

      // Project-specific overrides
      'no-undef': 'off', // Namespaces shown as undefined

      // Accessibility - disabled for ShadcnVue compatibility
      'vuejs-accessibility/label-has-for': 'off',
      'vuejs-accessibility/form-control-has-label': 'off',
    },
  },

  {
    files: ['resources/js/Pages/Admin/**/*.{vue,ts}'],
    ignores: LEGACY_ADMIN_EXCEPTIONS,
    plugins: { 'admin-redesign': adminRedesignPlugin },
    rules: { 'admin-redesign/no-legacy-utility': 'error' },
  },
  {
    files: MIGRATED_ADMIN_COMPONENT_PATHS,
    plugins: { 'admin-redesign': adminRedesignPlugin },
    rules: { 'admin-redesign/no-legacy-utility': 'error' },
  },
  {
    files: ['resources/js/**/*.{vue,ts,tsx,js,jsx}'],
    ignores: ['**/__tests__/**', '**/*.{test,spec,stories}.{ts,tsx,js,jsx}', 'resources/js/tests/**', 'resources/js/mocks/**'],
    rules: {
      'import-x/no-restricted-paths': ['error', {
        basePath: projectRoot,
        zones: [
          {
            target: ['Components', 'Composables', 'Constants', 'Utils', 'Types', 'Services', 'Shared', 'Features']
              .map(folder => `resources/js/${folder}`),
            from: 'resources/js/Pages',
            message: 'Shared source cannot depend on page modules. Move the shared contract or implementation below Pages.',
          },
          {
            target: 'resources/js/Components/ui',
            from: 'resources/js/Components',
            except: ['ui'],
            message: 'UI primitives cannot depend on higher component tiers, features or pages.',
          },
          {
            target: 'resources/js/Components/ui',
            from: 'resources/js/Features',
            message: 'UI primitives cannot depend on features.',
          },
          {
            target: ['resources/js/Components/Public', 'resources/js/Pages/Public'],
            from: ['resources/js/Components/Patterns', 'resources/js/Components/Layouts', 'resources/js/Features/Admin'],
            message: 'Public surfaces use Public/Base and Public/Layouts instead of admin patterns, shells or features.',
          },
        ],
      }],
    },
  },

  // Icon surface conventions (warn = migrate as you touch, not a hard block)
  // Admin surfaces must use Lucide; Fluent is for Public only.
  {
    files: [
      'resources/js/Pages/Admin/**/*.{vue,ts}',
      'resources/js/Features/Admin/**/*.{vue,ts}',
    ],
    rules: {
      'no-restricted-imports': ['warn', {
        paths: [...lodashImportPaths, ...dateImportPaths],
        patterns: [
          ...lodashImportPatterns,
          ...removedIconPatterns,
          ...dateImportPatterns,
          { group: ['~icons/fluent/*'], message: 'Admin uses Lucide (@lucide/vue). Fluent is reserved for Public surfaces.' },
        ],
      }],
    },
  },

  // Pages compose; they don't hand-roll card chrome. See resources/js/Components/CLAUDE.md.
  // Scoped to Pages only — Components/** and Features/** legitimately build on ui/card.
  {
    files: ['resources/js/Pages/Admin/**/*.{vue,ts}'],
    rules: {
      // NOTE: no-restricted-imports fully overrides rather than merges, so the
      // lodash and Fluent-icon entries from the block above must be repeated here.
      // An error since no admin page imports ui/card any more.
      'no-restricted-imports': ['error', {
        paths: [...lodashImportPaths, ...dateImportPaths],
        patterns: [
          ...lodashImportPatterns,
          ...removedIconPatterns,
          ...dateImportPatterns,
          { group: ['~icons/fluent/*'], message: 'Admin uses Lucide (@lucide/vue). Fluent is reserved for Public surfaces.' },
          {
            group: ['@/Components/ui/card', '@/Components/ui/card/*'],
            message: 'Use SectionCard from @/Components/Patterns for titled panels, or an entity component. Raw ui/card belongs in Components/**, not in a page.',
          },
        ],
      }],
    },
  },

  // Public surfaces must use Fluent; Lucide is for Admin only. Head metadata is server-owned:
  // a client <Head> overrides it after hydration, and that is what Google indexes.
  {
    files: [
      'resources/js/Pages/Public/**/*.{vue,ts}',
      'resources/js/Components/Public/**/*.{vue,ts}',
    ],
    rules: {
      'no-restricted-imports': ['error', {
        paths: [
          ...lodashImportPaths,
          ...dateImportPaths,
          { name: '@lucide/vue', message: 'Public uses Fluent (~icons/fluent/*) for a more stylized look. Lucide is reserved for Admin surfaces.' },
          { name: '@inertiajs/vue3', importNames: ['Head'], message: 'Public <head> comes from PublicController::applyPageHead() (.ai/rules/seo.md).' },
        ],
        patterns: [...lodashImportPatterns, ...removedIconPatterns, ...dateImportPatterns],
      }],
    },
  },

  // Size signals, the frontend counterpart of phpmd: warnings to split on touch, never a block.
  {
    files: ['resources/js/**/*.{vue,ts}'],
    ignores: ['resources/js/Components/ui/**'],
    rules: {
      'complexity': ['warn', 15],
      'max-depth': ['warn', 4],
      'max-lines': ['warn', { max: 500, skipBlankLines: true, skipComments: true }],
    },
  },

  // Shadcn UI primitives and Inertia page routes use single-word filenames by design (Button, Index, etc.).
  {
    files: [
      'resources/js/Components/ui/**/*.{vue,ts}',
      'resources/js/Pages/**/*.{vue,ts}',
    ],
    rules: {
      'vue/multi-word-component-names': 'off',
    },
  },
);
