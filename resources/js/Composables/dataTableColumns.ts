export const padalinysColumn = (filters, padaliniai) => {
  return {
    key: "padalinys.id",
    title: "Padalinys",
    filter: true,
    filterOptionValues: filters.value["padalinys.id"],
    filterOptions: padaliniai.map((padalinys) => {
      return {
        label: padalinys.shortname,
        value: padalinys.id,
      };
    }),
  };
};

export const langColumn = (filters) => {
  return {
    key: "lang",
    title: "Kalba",
    width: 100,
    filter: true,
    filterOptionValues: filters.value["lang"],
    filterOptions: [
      {
        label: "Lietuvių",
        value: "lt",
      },
      {
        label: "Anglų",
        value: "en",
      },
    ],
  };
};
