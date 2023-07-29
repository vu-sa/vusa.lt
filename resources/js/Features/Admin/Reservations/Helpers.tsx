import { trans as $t } from "laravel-vue-i18n";
import { NIcon, NTag, type SelectOption } from "naive-ui";
import Icons from "@/Types/Icons/regular";

export const renderResourceLabel = (
  option: SelectOption,
  selected: boolean,
  leftCapacity: number,
) => {
  return (
    <div class="my-2 flex items-center gap-2">
      <NIcon
        class="text-gray-400"
        component={Icons.RESOURCE}
        size="small"
      ></NIcon>
      <span>{option.name}</span>
      <span class="text-gray-400">
        {leftCapacity} {$t("iš")} {option.capacity}
      </span>
      <NTag size="tiny" round>
        <span class="text-xs text-gray-400">
          {option?.padalinys?.shortname}
        </span>
      </NTag>
    </div>
  );
};

export const renderResourceTag = (option, resources) => {
  if (!resources) return null;

  const resource = resources.find((resource) => resource.id === option.id);

  return (
    <div class="flex items-center gap-2">
      <NIcon
        class="text-gray-400"
        component={Icons.RESOURCE}
        size="small"
      ></NIcon>
      <span>{resource.name}</span>
      <span class="text-gray-400">
        {resource?.lowestCapacityAtDateTimeRange} {$t("iš")} {resource.capacity}
      </span>
      <NTag size="tiny" round>
        <span class="text-xs text-gray-400">
          {option?.padalinys?.shortname}
        </span>
      </NTag>
    </div>
  );
};
