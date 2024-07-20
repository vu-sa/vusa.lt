import { trans as $t, transChoice as $tChoice } from "laravel-vue-i18n";
import { capitalize } from "@/Utils/String";

export const tenantColumn = (filters, tenants) => {
  return {
    key: "tenant.id",
    title() {
      return capitalize($tChoice("entities.tenant.model", 1));
    },
    width: 120,
    filter: true,
    filterOptionValues: filters.value["tenant.id"],
    filterOptions: tenants.map((tenant) => {
      return {
        label: $t(tenant.shortname),
        value: tenant.id,
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
