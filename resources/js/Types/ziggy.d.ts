import { route as ziggyRouteFunction } from "ziggy-js";

// Defines the function in all TS files and the script tags in Vue SFC.
declare global {
  const route: typeof ziggyRouteFunction;
}

// Defines the function in your vue templates.
// You can simply remove this if you are not using vue.
declare module "@vue/runtime-core" {
  interface ComponentCustomProperties {
    route: typeof ziggyRouteFunction;
  }
}
