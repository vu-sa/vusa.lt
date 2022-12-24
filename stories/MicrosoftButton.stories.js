import MicrosoftButton from "@/Components/Buttons/MicrosoftLoginButton.vue";

export default {
  title: "Components/MicrosoftButton",
  component: MicrosoftButton,
  argTypes: {
    disabled: { control: "boolean" },
    loading: { control: "boolean" },
  },
};

export const Default = {
  render: (args) => ({
    components: { MicrosoftButton },
    setup() {
      return { args };
    },
    template: "<MicrosoftButton v-bind='args' />",
  }),
  args: {
    loading: true,
    disabled: false,
  },
};
