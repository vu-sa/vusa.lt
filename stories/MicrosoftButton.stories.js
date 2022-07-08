import MicrosoftButton from "/resources/js/Components/MicrosoftButton.vue";

export default {
  title: "Components/MicrosoftButton",
  component: MicrosoftButton,
  argTypes: {
    disabled: { control: "boolean" },
    loading: { control: "boolean" },
    strong: { control: "boolean" },
  },
};

const Template = (args) => ({
  components: { MicrosoftButton },
  setup() {
    return { args };
  },
  template: "<MicrosoftButton v-bind='args' />",
});

export const Primary = Template.bind({});
