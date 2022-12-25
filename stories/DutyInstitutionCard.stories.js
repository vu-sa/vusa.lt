import DutyInstitutionCard from "@/Components/Cards/DutyInstitutionCard.vue";

export default {
  title: "Admin/DutyInstitutionCard",
  component: DutyInstitutionCard,
};

export const Default = {
  render: (args) => ({
    components: { DutyInstitutionCard },
    setup() {
      return { args };
    },
    template: "<DutyInstitutionCard v-bind='args' />",
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
