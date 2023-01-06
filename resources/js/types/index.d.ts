import "./app";
import "./forms";
import "./inertia";
import "./laravel";
import "./models";
import "./props";
import "./routes";
import "./ziggy";
import { AxiosStatic } from "axios";

export {};

// bootstrap.js
declare global {
  interface Window {
    axios: AxiosStatic;
  }
}
