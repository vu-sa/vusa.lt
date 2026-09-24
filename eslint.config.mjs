import eslint from '@eslint/js';
import tseslint from 'typescript-eslint';
import pluginVue from 'eslint-plugin-vue';
import pluginVueA11y from 'eslint-plugin-vuejs-accessibility';
import importPlugin from 'eslint-plugin-import-x';
import stylistic from '@stylistic/eslint-plugin';
import globals from 'globals';

// Paths join this fence only after their redesign migration lands. Keeping the initial list empty
// lets foundation work land without turning legacy debt into an unreviewable lint failure.
export const MIGRATED_ADMIN_PATHS = [
  'resources/js/Components/Layouts/Shell/**',
  'resources/js/Components/CommandPalette/**',
  'resources/js/Pages/Admin/AccessDenied.vue',
  'resources/js/Pages/Admin/LoginForm.vue',
  'resources/js/Components/Layouts/RecordPage*.vue',
  'resources/js/Components/Meetings/MeetingDatePlate.vue',
  'resources/js/Components/Meetings/MeetingCompletionChecklist.vue',
  'resources/js/Features/Admin/ActivityLogViewer/RecordActivity.vue',
  'resources/js/Pages/Admin/ShowAdminHome.vue',
  'resources/js/Pages/Admin/Representation/IndexMeeting.vue',
  'resources/js/Pages/Admin/Calendar/IndexCalendarEvents.vue',
  'resources/js/Pages/Admin/Calendar/IndexEventType.vue',
  'resources/js/Pages/Admin/Content/IndexBanner.vue',
  'resources/js/Pages/Admin/Content/IndexNews.vue',
  'resources/js/Pages/Admin/Content/IndexPages.vue',
  'resources/js/Pages/Admin/Content/CreateNews.vue',
  'resources/js/Pages/Admin/Content/EditNews.vue',
  'resources/js/Pages/Admin/Content/CreatePage.vue',
  'resources/js/Pages/Admin/Content/EditPage.vue',
  'resources/js/Pages/Admin/Files/IndexDocument.vue',
  'resources/js/Pages/Admin/Forms/IndexForm.vue',
  'resources/js/Pages/Admin/ModelMeta/IndexRelationships.vue',
  'resources/js/Pages/Admin/ModelMeta/IndexTypes.vue',
  'resources/js/Pages/Admin/People/IndexDuty.vue',
  'resources/js/Pages/Admin/People/IndexInstitution.vue',
  'resources/js/Pages/Admin/People/IndexStudyProgram.vue',
  'resources/js/Pages/Admin/People/IndexTenant.vue',
  'resources/js/Pages/Admin/People/IndexUser.vue',
  'resources/js/Pages/Admin/Permissions/IndexPermission.vue',
  'resources/js/Pages/Admin/Permissions/IndexRole.vue',
  'resources/js/Pages/Admin/Problems/IndexProblem.vue',
  'resources/js/Pages/Admin/Reservations/IndexResource.vue',
  'resources/js/Pages/Admin/Reservations/IndexResourceCategory.vue',
  'resources/js/Pages/Admin/StudySets/IndexStudySet.vue',
  'resources/js/Components/Brand/**',
  'resources/js/Components/ui/control/**',
  'resources/js/Pages/Admin/Representation/ShowMeeting.vue',
  'resources/js/Pages/Admin/Representation/EditAgendaItem.vue',
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
  'resources/js/Pages/Admin/People/ShowDuty.vue',
  'resources/js/Pages/Admin/People/CreateDuty.vue',
  'resources/js/Pages/Admin/People/EditDuty.vue',
  'resources/js/Components/AdminForms/DutyForm.vue',
  'resources/js/Pages/Admin/Settings/**',
  'resources/js/Pages/Admin/ModelMeta/**',
  'resources/js/Pages/Admin/StudySets/**',
  'resources/js/Pages/Admin/Content/IndexQuickLink.vue',
  'resources/js/Components/AdminForms/{RelationshipForm,TypeForm,StudySetForm}.vue',
  'resources/js/Pages/Admin/Reservations/IndexReservation.vue',
  'resources/js/Pages/Admin/Content/IndexTag.vue',
  'resources/js/Features/Admin/Tags/**',
  'resources/js/Components/Layouts/OverviewPage.vue',
  'resources/js/Pages/Admin/ShowMyRoles.vue',
  'resources/js/Components/Duties/MyDutyTermRow.vue',
  'resources/js/Pages/Admin/Search/SearchIndex.vue',
  'resources/js/Features/Admin/AdminSearch/Components/SearchResultGroup.vue',
  'resources/js/Features/Admin/AdminSearch/Components/Detail/MeetingDetailPreview.vue',
  'resources/js/Components/Overview/**',
  'resources/js/Components/Patterns/OverviewSection.vue',
  'resources/js/Pages/Admin/Dashboard/ShowAtstovavimas.vue',
  'resources/js/Pages/Admin/Dashboard/Components/InstitutionStatusTrendChart.vue',
  'resources/js/Pages/Admin/Dashboard/ShowReservations.vue',
  'resources/js/Pages/Admin/Dashboard/ShowSvetaine.vue',
  'resources/js/Pages/Admin/Dashboard/ShowOrganizacija.vue',
  'resources/js/Pages/Admin/Dashboard/ShowSistema.vue',
  'resources/js/Pages/Admin/ShowAdministration.vue',
  'resources/js/Pages/Admin/ShowRepMetrics.vue',
  'resources/js/Components/Reservations/**',
  'resources/js/Components/Duties/**',
  'resources/js/Components/Institutions/{AddCheckInDialog,InstitutionDutiesSection,InstitutionMeetingsList,InstitutionOverviewSection,RelatedInstitutionTile,SecretariesSection}.vue',
  'resources/js/Components/AdminForms/{DutyCard,InstitutionForm,ReservationForm,ResourceForm,UserForm}.vue',
  'resources/js/Features/Admin/DutiableTimeline/**',
  'resources/js/Features/Admin/ResourceCategories/**',
  'resources/js/Pages/Admin/People/{CreateInstitution,EditInstitution,ShowInstitution,IndexInstitution,CreateUser,EditUser,ShowUser,IndexUser,IndexDuty,DutyUserUpdateWizard,DutiableTimeline}.vue',
  'resources/js/Pages/Admin/Reservations/{CreateReservation,ShowReservation,CreateResource,EditResource,IndexResource,IndexResourceCategory}.vue',
  'resources/js/Pages/Admin/Problems/**',
  'resources/js/Components/AdminForms/ProblemForm.vue',
  'resources/js/Pages/Admin/Forms/**',
  'resources/js/Components/AdminForms/{FormForm,FormFieldForm}.vue',
  'resources/js/Pages/Admin/Content/{CreatePage,EditPage,CreateNews,EditNews,EditHomePage,IndexBanner,CreateBanner,EditBanner,IndexQuickLink,CreateQuickLink,EditQuickLink,CreateTag,EditTag}.vue',
  'resources/js/Pages/Admin/Calendar/{CreateCalendarEvent,EditCalendarEvent,IndexEventType}.vue',
  'resources/js/Features/Admin/EventTypes/**',
  'resources/js/Pages/Admin/Navigation/**',
  'resources/js/Features/Admin/NavigationBuilder/**',
  'resources/js/Components/AdminForms/{PageForm,NewsForm,CalendarForm,FormStatusHeader,FormFieldWrapper,PermalinkField,PermalinkPreviewHint,SEOPreview,FormLinkButton,BannerForm,QuickLinkForm,NavigationForm,NavigationParentForm,TagForm}.vue',
  'resources/js/Components/Analytics/ContentAnalyticsCard.vue',
  'resources/js/Pages/Admin/Files/**',
  'resources/js/Features/Admin/FileManager/**',
  'resources/js/Features/Admin/SharepointFileManager/**',
  'resources/js/Pages/Admin/Permissions/ShowRole.vue',
  'resources/js/Pages/Admin/ModelMeta/{ShowType,ShowRelationship}.vue',
  'resources/js/Pages/Admin/{SystemStatus,MailQueue}.vue',
  'resources/js/Pages/Admin/SupportRequests/ShowSupportRequest.vue',
  'resources/js/Pages/Admin/{ShowProfile,ShowNotificationSettings}.vue',
  'resources/js/Features/Admin/Notifications/{PushDeviceManagement,NotificationPreferences,DigestEmailSelector}.vue',
  'resources/js/Pages/Admin/{ShowTasks,ShowTasksSummary}.vue',
  'resources/js/Features/Admin/TaskManager/**',
  'resources/js/Components/Tasks/{TaskFilter,TaskItem}.vue',
  'resources/js/Pages/Admin/ShowNotifications.vue',
  'resources/js/Features/Admin/Notifications/NotificationCard.vue',
];

// Shared import restriction fragments — reused in per-surface blocks so the
// global lodash ban is not silently dropped when a later config overrides this rule.
const lodashImportPaths = [
  { name: 'lodash', message: 'Import from "lodash-es" for tree-shaking, or use "@vueuse/core" utilities like useDebounceFn.' },
];
const lodashImportPatterns = [
  { group: ['lodash/*'], message: 'Import from "lodash-es/*" for tree-shaking.' },
];
// Icon sets that have been fully removed from the codebase.
const removedIconPatterns = [
  { group: ['~icons/mdi/*'], message: 'MDI was removed. Use lucide-vue-next for admin icons or ~icons/simple-icons/* for brand glyphs.' },
  { group: ['@/Types/Icons/*'], message: 'Legacy default-export barrel was deleted. Use direct imports from lucide-vue-next or @/Components/icons.' },
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
        paths: [...lodashImportPaths],
        patterns: [...lodashImportPatterns, ...removedIconPatterns],
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

  ...(MIGRATED_ADMIN_PATHS.length > 0
    ? [{
        files: MIGRATED_ADMIN_PATHS,
        plugins: { 'admin-redesign': adminRedesignPlugin },
        rules: { 'admin-redesign/no-legacy-utility': 'error' },
      }]
    : []),

  // Icon surface conventions (warn = migrate as you touch, not a hard block)
  // Admin surfaces must use Lucide; Fluent is for Public only.
  {
    files: [
      'resources/js/Pages/Admin/**/*.{vue,ts}',
      'resources/js/Features/Admin/**/*.{vue,ts}',
    ],
    rules: {
      'no-restricted-imports': ['warn', {
        paths: [...lodashImportPaths],
        patterns: [
          ...lodashImportPatterns,
          ...removedIconPatterns,
          { group: ['~icons/fluent/*'], message: 'Admin uses Lucide (lucide-vue-next). Fluent is reserved for Public surfaces.' },
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
      'no-restricted-imports': ['warn', {
        paths: [...lodashImportPaths],
        patterns: [
          ...lodashImportPatterns,
          ...removedIconPatterns,
          { group: ['~icons/fluent/*'], message: 'Admin uses Lucide (lucide-vue-next). Fluent is reserved for Public surfaces.' },
          {
            group: ['@/Components/ui/card', '@/Components/ui/card/*'],
            message: 'Use SectionCard from @/Components/Patterns for titled panels, or an entity component. Raw ui/card belongs in Components/**, not in a page.',
          },
        ],
      }],
    },
  },

  // Public surfaces must use Fluent; Lucide is for Admin only.
  {
    files: [
      'resources/js/Pages/Public/**/*.{vue,ts}',
      'resources/js/Components/Public/**/*.{vue,ts}',
    ],
    rules: {
      'no-restricted-imports': ['warn', {
        paths: [
          ...lodashImportPaths,
          { name: 'lucide-vue-next', message: 'Public uses Fluent (~icons/fluent/*) for a more stylized look. Lucide is reserved for Admin surfaces.' },
        ],
        patterns: [...lodashImportPatterns, ...removedIconPatterns],
      }],
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
