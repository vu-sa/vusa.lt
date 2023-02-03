import type { Ref } from "vue";

export function isDarkMode() {
  // get html color-scheme
  const colorScheme = document
    .querySelector("html")
    ?.getAttribute("color-scheme");

  return colorScheme === "dark";
}

export function updateDarkMode(ref: Ref<boolean>) {
  const target = document.querySelector("html");
  const config = { attributes: true, childList: false, subtree: false };
  const callback = () => {
    ref.value = isDarkMode();
  };
  const observer = new MutationObserver(callback);
  observer.observe(target, config);
}
