import { trans as $t, transChoice as $tChoice } from "laravel-vue-i18n";
import { capitalize } from "@/Utils/String";

export const padalinysColumn = (filters, padaliniai) => {
  return {
    key: "padalinys.id",
    title() {
      return capitalize($tChoice("entities.padalinys.model", 1));
    },
    width: 120,
    filter: true,
    filterOptionValues: filters.value["padalinys.id"],
    filterOptions: padaliniai.map((padalinys) => {
      return {
        label: $t(padalinys.shortname),
        value: padalinys.id,
      };
    }),
  };
};

export const langColumn = (filters) => {
  return {
    key: "lang",
    title() {
      return $t("Kalba");
    },
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
