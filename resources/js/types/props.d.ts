import type { Component } from "vue";
import type { DropdownOption } from "naive-ui";
import type { RouteParam, RouteParamsWithQueryOverload } from "ziggy-js";

declare namespace App.Props {
  export interface BreadcrumbOption {
    label: string;
    icon: Component;
    dropdownOptions?: DropdownOption[];
    routeOptions?: {
      name: string;
      params?: RouteParamsWithQueryOverload | RouteParam;
    };
  }
}
