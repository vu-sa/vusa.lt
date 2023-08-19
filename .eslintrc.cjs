module.exports = {
  env: {
    node: true,
  },
  extends: [
    "plugin:@typescript-eslint/recommended",
    "plugin:tailwindcss/recommended",
    "eslint:recommended",
    "plugin:vue/vue3-recommended",
    "plugin:prettier/recommended",
  ],
  parserOptions: {
    parser: "@typescript-eslint/parser",
    ecmaVersion: 2020,
    sourceType: "module",
  },
  plugins: [
    "@typescript-eslint",
    "tailwindcss",
    "no-secrets",
    "sort-imports-es6-autofix",
  ],
  overrides: [
    {
      files: ["*.ts", "*.vue"],
      rules: {
        "no-secrets/no-secrets": "error",
        "no-undef": "off",
        "vue/max-attributes-per-line": "off",
      },
    },
    {
      files: ["*.d.ts"],
      rules: {
        // TODO: For some reason, top level declares in .d.ts files are not being recognized
      },
    },
  ],
  rules: {
    "sort-imports-es6-autofix/sort-imports-es6": [
      2,
      {
        ignoreCase: false,
        ignoreMemberSort: false,
        memberSyntaxSortOrder: ["none", "all", "multiple", "single"],
      },
    ],
    "tailwindcss/no-custom-classname": "off",
    "arrow-parens": "off",
    "eol-last": "error",
    "no-unused-vars": "off",
  },
  ignorePatterns: ["dist"],
};
