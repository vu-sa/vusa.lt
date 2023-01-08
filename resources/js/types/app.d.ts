export {};

import type { Component } from "vue";
import type { DropdownOption } from "naive-ui";
import type { RouteParam, RouteParamsWithQueryOverload } from "ziggy-js";

declare global {
  export namespace App {
    export type Locale = "lt" | "en";

    namespace Props {
      export interface BreadcrumbOption {
        label: string | null;
        icon?: Component;
        dropdownOptions?: DropdownOption[];
        routeOptions?: {
          name: string;
          params?: RouteParamsWithQueryOverload | RouteParam;
        };
      }
    }
  }
}
