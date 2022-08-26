// import { toRaw } from "vue";

export function checkForEmptyArray(attributes) {
  console.log(Object.prototype.toString.call(attributes));
  // check if attributes prototype is Object
  if (Object.prototype.toString.call(attributes) === "[object Object]")
    return attributes;

  if (!Array.isArray(attributes) || !attributes.length) {
    // array does not exist, is not an array, or is empty
    return {};
  }

  return attributes;
}
