const MINUTE_MILISECONDS = 60 * 1000;
const HOUR_MILISECONDS = MINUTE_MILISECONDS * 60;
const DAY_MILISECONDS = HOUR_MILISECONDS * 24;

const getRelativeTime = (timestamp: string) => {
  const notificationDateTime = new Date(timestamp);

  const rtf = new Intl.RelativeTimeFormat("lt", {
    numeric: "auto",
  });
  const daysDifference = Math.round(
    (notificationDateTime.getTime() - new Date().getTime()) / DAY_MILISECONDS
  );

  const hoursDifference = Math.round(
    (notificationDateTime.getTime() - new Date().getTime()) / HOUR_MILISECONDS
  );

  const minutesDifference = Math.round(
    (notificationDateTime.getTime() - new Date().getTime()) / MINUTE_MILISECONDS
  );

  const secondsDifference = Math.round(
    (notificationDateTime.getTime() - new Date().getTime()) / 1000
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
    return "dabar";
  }
};

export default getRelativeTime;