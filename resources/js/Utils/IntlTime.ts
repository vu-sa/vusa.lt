import { LocaleEnum } from "@/Types/enums";

const MINUTE_MILISECONDS = 60 * 1000;
const HOUR_MILISECONDS = MINUTE_MILISECONDS * 60;
const DAY_MILISECONDS = HOUR_MILISECONDS * 24;

export const formatRelativeTime = (
  time: number | Date,
  dateTimeOptions: Intl.RelativeTimeFormatOptions = {
    numeric: "auto",
  },
  // check locale against LocaleEnum
  locale: LocaleEnum = LocaleEnum.LT
) => {
  const date = new Date(time);

  const rtf = new Intl.RelativeTimeFormat(locale, dateTimeOptions);

  const daysDifference = Math.round(
    (date.getTime() - new Date().getTime()) / DAY_MILISECONDS
  );

  const hoursDifference = Math.round(
    (date.getTime() - new Date().getTime()) / HOUR_MILISECONDS
  );

  const minutesDifference = Math.round(
    (date.getTime() - new Date().getTime()) / MINUTE_MILISECONDS
  );

  const secondsDifference = Math.round(
    (date.getTime() - new Date().getTime()) / 1000
  );

  if (daysDifference != 0) {
    return rtf.format(daysDifference, "day");
  } else if (hoursDifference != 0) {
    return rtf.format(hoursDifference, "hour");
  } else if (minutesDifference != 0) {
    return rtf.format(minutesDifference, "minute");
  } else if (secondsDifference != 0) {
    return rtf.format(secondsDifference, "second");
  } else {
    return locale === LocaleEnum.LT ? "dabar" : "now";
  }
};

export const formatStaticTime = (
  time: number | Date | undefined,
  dateTimeOptions: Intl.DateTimeFormatOptions = {
    year: "numeric",
    month: "numeric",
    day: "numeric",
  },
  locale: LocaleEnum = LocaleEnum.LT
) => {
  if (!time) return "";
  // make date of time
  const date = new Date(time);

  const staticTime = new Intl.DateTimeFormat(locale, dateTimeOptions).format(
    date
  );

  return staticTime;
};

export const getDaysDifference = (time: number | Date) => {
  const now = new Date();
  const difference = new Date(time);

  const daysDifference = Math.round(
    (now.getTime() - difference.getTime()) / DAY_MILISECONDS
  );

  return daysDifference;
};
