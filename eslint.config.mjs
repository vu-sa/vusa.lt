import noSecrets from "eslint-plugin-no-secrets";
import tailwind from "eslint-plugin-tailwindcss";
import pluginVue from 'eslint-plugin-vue'
import eslint from '@eslint/js';
import tseslint from 'typescript-eslint';
import vueParser from 'vue-eslint-parser';
import stylistic from '@stylistic/eslint-plugin'
import pluginVueA11y from "eslint-plugin-vuejs-accessibility";

export default [
    eslint.configs.recommended,
    ...tseslint.configs.recommended,
    ...pluginVue.configs['flat/recommended'],
    ...pluginVueA11y.configs["flat/recommended"],
    ...tailwind.configs["flat/recommended"],
    {
        plugins: {
            '@stylistic': stylistic,
            "no-secrets": noSecrets
        },

        // See https://typescript-eslint.io/troubleshooting/faqs/frameworks
        languageOptions: {
            parser: vueParser,
            parserOptions: {
                ecmaFeatures: {
                    jsx: true,
                },
                parser: tseslint.parser,
                sourceType: 'module',
            },
        },
    }, {
        files: ["**/*.ts", "**/*.vue"],

        rules: {
            "no-secrets/no-secrets": "error",
            "vue/max-attributes-per-line": "off",
            "tailwindcss/no-custom-classname": "off",
        },
    }
];
