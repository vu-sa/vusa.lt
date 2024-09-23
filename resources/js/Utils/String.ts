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

export const addressivize = (name: string | null) => {
  if (name === null) {
    return "";
  }

  return name
    .replace(/as$/, "ai")
    .replace(/ė$/, "e")
    .replace(/is$/, "i")
    .replace(/us$/, "au")
    .replace(/ys$/, "y")
    .replace(/iai$/, "iai")
}

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

/**
 * Get faculty name from padalinys.fullname
 * @param padalinys
 * @returns facultyName
 * @example getFacultyName({fullname: "Vilniaus universiteto Studentų atstovybė Matematikos ir informatikos fakultete"}) => "Matematikos ir informatikos fakultetas"
 */

export const getFacultyName = ({ fullname }: { fullname: string }) => {
  // split string into two parts, separated by string "Vilniaus universiteto Studentų atstovybė"
  let facultyName = fullname.split(
    "Vilniaus universiteto Studentų atstovybė"
  )[1];

  // change faculty name only at the string ending from "ete" to "etas"
  if (facultyName.endsWith("ete")) {
    facultyName = facultyName.replace("ete", "etas");
  }
  // also apply this to "tre" to "tas"
  if (facultyName.endsWith("tre")) {
    facultyName = facultyName.replace("tre", "tras");
  }

  // also if ends with "ykloje", change to "ykla"
  if (facultyName.endsWith("ykloje")) {
    facultyName = facultyName.replace("ykloje", "ykla");
  }

  // change "ute" to "utas"
  if (facultyName.endsWith("ute")) {
    facultyName = facultyName.replace("ute", "utas");
  }

  return facultyName;
};

export const capitalize = (word: string) => {
  return word.charAt(0).toUpperCase() + word.slice(1);
};

export const changeDutyNameEndings = (
  contact: App.Entities.User | null | undefined,
  dutyName: App.Entities.Duty["name"],
  locale: string,
  pronouns: string,
  useOriginalDutyName: boolean
) => {
  if (locale === "en") {
    return dutyName;
  }

  // check if duty name should not be explicitly changed
  if (useOriginalDutyName) return dutyName;

  const splitPronouns = pronouns?.split("/");

  // replace duty.name ending 'ius' with 'ė', but only on end of string
  const womanizedTitle = dutyName
    .replace(/ius$/, "ė")
    .replace(/as$/, "ė")
    .replace(/ys$/, "ė");

  const pluralizedTitle = dutyName
    .replace(/ius$/, "iai")
    .replace(/as$/, "ai")
    .replace(/ys$/, "iai");

  const masculinedTitle = dutyName
    .replace(/vė$/, "vas")
    .replace(/rė$/, "rius")
    .replace(/kė$/, "kas");


  if (splitPronouns[0] === "ji" || splitPronouns[0] === "she") {
    return womanizedTitle;
  } else if (splitPronouns[0] === "jie" || splitPronouns[0] === "they") {
    return pluralizedTitle;
  } else if (splitPronouns[0] === "jis" || splitPronouns[0] === "he") {
    return masculinedTitle;
  }

  // If no pronouns are set, try to guess based on the name
  if (!contact) {
    return dutyName;
  }

  const firstName = contact.name.split(" ")[0];

  const namesToWomanize = ["Katrin"];
  if (namesToWomanize.includes(firstName)) {
    return womanizedTitle;
  }

  const namesNotToWomanize = ["German"];
  if (namesNotToWomanize.includes(firstName)) {
    return dutyName;
  }

  if (contact.name.endsWith("ė") || firstName.endsWith("ė")) {
    return womanizedTitle;
  }

  // check for first name ending with 's'
  if (contact.name.endsWith("a") && !firstName.endsWith("s")) {
    return womanizedTitle;
  }

  return dutyName ?? "";
};
