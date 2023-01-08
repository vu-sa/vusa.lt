export {};

import type { Component } from "vue";
import type { DropdownOption } from "naive-ui";
import type { RouteParam, RouteParamsWithQueryOverload } from "ziggy-js";

declare global {
  export namespace App {
    export type Locale = "lt" | "en";

    namespace Models {
      export interface DutyExtended extends Duty {
        roles?: Array<App.Models.Role> | null; // manually added
        roles_count?: number | null; // manually added
      }

      export interface InstitutionMeetingExtended
        extends Omit<InstitutionMeeting, "start_time"> {
        start_time: number; // casted to number
      }

      export interface UserExtended extends Omit<User, "padaliniai"> {
        padaliniai?: Array<App.Models.Padalinys> | null; // manually added
        roles?: Array<App.Models.Role> | null; // manually added
        roles_count?: number | null; // manually added
      }
    }

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
