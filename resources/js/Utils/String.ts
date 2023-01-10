export const pluralizeModels = (word: string) => {
  if (word.endsWith("y")) {
    return word.slice(0, -1) + "ies";
  }

  if (word === "navigation") {
    return word;
  }

  if (word === "calendar") {
    return word;
  }

  return word + "s";
};
