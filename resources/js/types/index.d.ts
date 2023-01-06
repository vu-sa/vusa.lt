import "./forms";
import "./inertia";
import "./laravel";
import "./models";
import "./routes";
import { AxiosStatic } from "axios";

export {};

// bootstrap.js
declare global {
  interface Window {
    axios: AxiosStatic;
  }
}
