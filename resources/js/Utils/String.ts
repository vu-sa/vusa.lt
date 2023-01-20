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

export const genitivize = (name: string | null) => {
  if (name === null) {
    return "";
  }

  return name
    .replace(/a$/, "os")
    .replace(/as$/, "o")
    .replace(/ė$/, "ės")
    .replace(/is$/, "io")
    .replace(/iai$/, "ių")
    .replace(/ė$/, "ės");
};

export const genitivizeEveryWord = (name: string | null) => {
  if (name === null) {
    return "";
  }

  // delimit by spaces
  const words = name.split(" ");

  // genitivize each word
  const genitivizedWords = words.map((word) => genitivize(word));

  // join back together
  return genitivizedWords.join(" ");
};

export const splitFileNameAndExtension = (fileName: string) => {
  const parts = fileName.split(".");
  const extension = "." + parts.pop();
  const name = parts.join(".");

  return { name, extension };
};
