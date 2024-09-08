import noSecrets from "eslint-plugin-no-secrets";
import tailwind from "eslint-plugin-tailwindcss";
import pluginVue from 'eslint-plugin-vue'
import eslint from '@eslint/js';
import tseslint from 'typescript-eslint';
import vueParser from 'vue-eslint-parser';
import pluginVueA11y from "eslint-plugin-vuejs-accessibility";

export default [
    eslint.configs.recommended,
    ...tseslint.configs.recommended,
    ...pluginVue.configs['flat/recommended'],
    ...pluginVueA11y.configs["flat/recommended"],
    ...tailwind.configs["flat/recommended"],
    {
        plugins: {
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
            // Some bugs for this rule
            "tailwindcss/no-custom-classname": "off",
            // Namespaces shown as undefined, so disable for now
            "no-undef": "off",
            // Haven't found a way to override volar with eslint
            'vue/html-indent': "off",
            'vue/html-closing-bracket-newline': "off",
            'vue/first-attribute-linebreak': "off",
        },
    }
];
