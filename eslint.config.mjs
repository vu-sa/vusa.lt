import eslint from '@eslint/js';
import tseslint from 'typescript-eslint';
import pluginVue from 'eslint-plugin-vue'
import pluginVueA11y from "eslint-plugin-vuejs-accessibility";
import importPlugin from "eslint-plugin-import";
import noSecrets from "eslint-plugin-no-secrets";
import globals from "globals";
// import { dirname } from 'path';
// import { fileURLToPath } from 'url';

export default tseslint.config(
    eslint.configs.recommended,
    tseslint.configs.recommended,
    pluginVue.configs['flat/recommended'],
    // Performance-tested plugins
    pluginVueA11y.configs["flat/recommended"],  // Accessibility - good performance (~18s)
    // ...tailwind.configs["flat/recommended"],  // Tailwind - causes major slowdown, disable for now
    {
        languageOptions: {
            parserOptions: {
                // Project Service disabled - performance bottleneck confirmed
                // Even with all optimizations (allowDefaultProject, maximumDefaultProjectFileMatchCount, etc.)
                // the Project Service causes 2+ minute timeouts on this codebase size
                
                // Alternative: Use traditional project configuration for type-aware rules only in CI
                // projectService: { 
                //     allowDefaultProject: ['eslint.config.mjs', '*.js', '*.mjs'],
                //     defaultProject: './tsconfig.json',
                //     maximumDefaultProjectFileMatchCount: 30,
                // },
                ecmaVersion: 'latest',
                sourceType: 'module',
                extraFileExtensions: ['.vue', '.mjs'],
            },
        },
    },
    {
        plugins: {
            "no-secrets": noSecrets,
            "import": importPlugin
        },
        settings: {
            // tailwindcss: {
            //     // Path to TailwindCSS entry file for v4 config resolution
            //     config: `${dirname(fileURLToPath(import.meta.url))}/resources/css/app.css`,
            //     cssFiles: [
            //         'resources/css/app.css',
            //         'resources/**/*.css',
            //     ],
            //     whitelist: ['typography', 'not-typography'],
            // }
        },
    },
    {
        files: ["**/*.{ts,vue,js,mjs}"],
        ignores: [
            "**/node_modules/**",
            "**/vendor/**", 
            "**/storage/**",
            "**/public/build/**",
            "**/*.min.js",
            "**/coverage/**"
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
            // Security
            "no-secrets/no-secrets": "error",

            // Code quality and conciseness
            "max-len": ["warn", {
                "code": 180,
                "ignoreUrls": true,
                "ignoreStrings": true,
                "ignoreTemplateLiterals": true,
                "ignoreComments": true
            }],
            "object-shorthand": ["warn", "always"],
            "prefer-arrow-callback": ["warn", { "allowNamedFunctions": false }],
            "prefer-const": "warn",
            "prefer-template": "warn",
            "prefer-destructuring": ["warn", {
                "array": false, // Don't force array destructuring
                "object": true  // Encourage object destructuring  
            }],
            "prefer-spread": "warn",
            "prefer-rest-params": "warn",
            "prefer-regex-literals": "warn",
            "no-var": "error",
            "no-implicit-coercion": ["warn", { "allow": ["!!", "+"] }],
            "no-lonely-if": "warn",
            "no-unneeded-ternary": "warn",
            "no-useless-concat": "warn",
            "no-useless-return": "warn",
            "yoda": "warn",

            // Import organization (simplified for performance)
            "import/order": ["warn", {
                "groups": ["builtin", "external", "internal", "parent", "sibling", "index"],
                "newlines-between": "always"
                // Alphabetize disabled for performance - causes significant slowdown
            }],
            "import/newline-after-import": "warn",
            "import/no-duplicates": "warn",

            // TypeScript modern patterns (carefully selected non-type-aware rules)
            "@typescript-eslint/prefer-as-const": "warn",
            "@typescript-eslint/prefer-function-type": "warn",
            // "@typescript-eslint/prefer-includes": "warn",  // Requires type info
            // "@typescript-eslint/prefer-string-starts-ends-with": "warn",  // Requires type info
            // "@typescript-eslint/prefer-for-of": "warn",  // Requires type info
            "@typescript-eslint/no-array-constructor": "error",
            "@typescript-eslint/no-explicit-any": "error",
            "@typescript-eslint/no-unused-vars": "off", // Handled by TypeScript LSP
            "@typescript-eslint/consistent-type-definitions": ["warn", "interface"],
            "@typescript-eslint/consistent-type-imports": ["warn", {
                "prefer": "type-imports",
                "fixStyle": "separate-type-imports"
            }],
            "@typescript-eslint/no-import-type-side-effects": "warn",
            "@typescript-eslint/ban-ts-comment": ["warn", {
                "ts-expect-error": "allow-with-description",
                "ts-ignore": true,
                "ts-nocheck": true,
                "ts-check": false
            }],
            
            // Type-aware rules (disabled - require project service which causes major performance issues)
            // These rules provide excellent type safety but cause 2+ minute linting times
            // Consider enabling only in CI/pre-commit hooks for comprehensive checking
            // "@typescript-eslint/prefer-optional-chain": "warn",
            // "@typescript-eslint/prefer-nullish-coalescing": "warn",  
            // "@typescript-eslint/no-unnecessary-type-assertion": "warn",
            // "@typescript-eslint/prefer-includes": "warn",
            // "@typescript-eslint/prefer-string-starts-ends-with": "warn",

            // Vue.js conciseness and best practices
            "vue/max-attributes-per-line": "off", // Handled by Volar
            // "vue/max-len": ["warn", {
            //     "code": 120,
            //     "template": 120,
            //     "ignoreHTMLAttributeValues": false,
            //     "ignoreHTMLTextContents": true
            // }], // Handled by max-len
            "vue/component-name-in-template-casing": ["warn", "PascalCase"],
            "vue/define-props-declaration": ["warn", "type-based"],
            "vue/define-emits-declaration": ["warn", "type-based"],
            "vue/v-bind-style": ["warn", "shorthand", { "sameNameShorthand": "always" }],
            "vue/v-on-style": ["warn", "shorthand"],
            "vue/prefer-separate-static-class": "off", // To follow guidelines in CLAUDE.md
            "vue/prefer-true-attribute-shorthand": "warn",
            "vue/no-useless-v-bind": "warn",
            "vue/no-useless-mustaches": "warn",
            "vue/no-useless-concat": "warn",
            
            // Modern Vue 3 patterns
            "vue/no-deprecated-scope-attribute": "error",
            "vue/no-deprecated-slot-attribute": "error",
            "vue/no-deprecated-slot-scope-attribute": "error",
            "vue/prefer-import-from-vue": "warn",
            "vue/prefer-prop-type-boolean-first": "warn",
            "vue/require-explicit-emits": "warn",
            "vue/no-empty-component-block": "warn",
            "vue/block-order": ["warn", {
                "order": ["template", "script", "style"]
            }],
            "vue/component-api-style": ["warn", ["script-setup"]],
            "vue/html-button-has-type": "warn",
            "vue/no-boolean-default": "warn",
            "vue/no-duplicate-attr-inheritance": "warn",
            "vue/no-empty-pattern": "warn",
            "vue/no-multiple-objects-in-class": "warn",
            "vue/no-static-inline-styles": "warn",
            "vue/no-template-target-blank": "warn",
            "vue/no-this-in-before-route-enter": "warn",
            "vue/no-undef-components": "off", // Too aggressive with component auto-imports
            "vue/no-undef-properties": "off", // Too aggressive with computed/props
            "vue/no-unused-properties": "off", // Too aggressive with template usage
            "vue/padding-line-between-blocks": "warn",

            // Disable formatting rules (handled by Volar)
            'vue/html-indent': "off",
            'vue/html-closing-bracket-newline': "off",
            'vue/first-attribute-linebreak': "off",

            // Tailwind CSS (disabled for performance testing)
            // "tailwindcss/classnames-order": "warn",
            // "tailwindcss/enforces-negative-arbitrary-values": "warn",
            // "tailwindcss/enforces-shorthand": "warn",
            // "tailwindcss/migration-from-tailwind-2": "off",
            // "tailwindcss/no-arbitrary-value": "off",
            // "tailwindcss/no-custom-classname": "off",
            // "tailwindcss/no-contradicting-classname": "off",
            // "tailwindcss/no-unnecessary-arbitrary-value": "warn",

            // Project-specific overrides
            "no-undef": "off", // Namespaces shown as undefined, disabled for now
            
            // Accessibility - disabled for ShadcnVue compatibility
            "vuejs-accessibility/label-has-for": "off",
            "vuejs-accessibility/form-control-has-label": "off",
        },
    }
);
