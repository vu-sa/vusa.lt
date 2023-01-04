import InstitutionCard from "@/Components/Cards/InstitutionCard.vue";

export default {
  title: "Admin/InstitutionCard",
  component: InstitutionCard,
};

export const Default = {
  render: (args) => ({
    components: { InstitutionCard },
    setup() {
      return { args };
    },
    template: "<InstitutionCard v-bind='args' />",
  }),
  args: {
    institution: {
      name: "Pareigybių institucija",
      types: [
        {
          id: 1,
          title: "Tipas 1",
        },
        {
          id: 2,
          title: "Tipas 2",
        },
      ],
      users: [
        {
          name: "Vardenis Pavardenis",
        },
        {
          name: "Pavardenis Vardenis",
        },
      ],
    },
  },
};
