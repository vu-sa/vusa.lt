export const pluralizeModels = (word: string, forPermissions = true) => {
  if (word.endsWith("y")) {
    return word.slice(0, -1) + "ies";
  }

  // When the permissions where created, the Str::class was used to pluralize
  // As to stay consistent, by default these words will be pluralized but their
  // semantic meaning is not the same in this application as those words may
  // imply.

  if (["navigation", "calendar"].includes(word)) {
    return forPermissions ? word + "s" : word;
  }

  if (word.endsWith("s")) {
    return word;
  }

  return word + "s";
};
