declare namespace App.Props {
  export interface BreadcrumbOption {
    label: string;
    icon: Component;
    dropdownOptions?: DropdownOption[];
    routeOptions: {
      name: string;
      params?: RouteParamsWithQueryOverload | RouteParam;
    };
  }
}
