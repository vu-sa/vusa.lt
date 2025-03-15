import eslint from '@eslint/js';
import tseslint from 'typescript-eslint';
import pluginVue from 'eslint-plugin-vue'
// import vueParser from 'vue-eslint-parser';
import pluginVueA11y from "eslint-plugin-vuejs-accessibility";
//import tailwind from "eslint-plugin-tailwindcss";
import noSecrets from "eslint-plugin-no-secrets";
import globals from "globals";

export default tseslint.config(
    eslint.configs.recommended,
    tseslint.configs.recommendedTypeChecked,
    pluginVue.configs['flat/recommended'],
    pluginVueA11y.configs["flat/recommended"],
    //...tailwind.configs["flat/recommended"],
    {
        // languageOptions: {
        //     parserOptions: {
        //         projectService: true,
        //         tsconfigRootDir: import.meta.dirname,
        //         extraFileExtensions: ['.vue', '.mjs'],
        //     },
        // },
    },
    {
        plugins: {
            "no-secrets": noSecrets
        },

        // See https://typescript-eslint.io/troubleshooting/faqs/frameworks
    }, {
    files: ["**/*.{ts,vue}"],
    languageOptions: {
        globals: {
            ...globals.browser,
        },
        // parser: vueParser,
        parserOptions: {
            ecmaFeatures: {
                jsx: true,
            },
            parser: tseslint.parser,
            sourceType: 'module',
        },
    },

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
);
