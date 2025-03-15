import { NTag } from "naive-ui";
import { mount } from "@vue/test-utils";
import { describe, it, expect } from "vitest";
import ModelChip from "./ModelChip.vue";

describe("ModelChip", () => {
  it("renders the slot content", () => {
    const wrapper = mount(ModelChip, {
      slots: {
        default: "Model Name",
      },
    });
    expect(wrapper.text()).toContain("Model Name");
  });

  it("renders the slot icon", () => {
    const wrapper = mount(ModelChip, {
      slots: {
        icon: '<i class="fa fa-user"></i>',
      },
    });
    expect(wrapper.find(".fa-user").exists()).toBe(true);
  });

  it("renders the Naive UI tag component", () => {
    const wrapper = mount(ModelChip);
    expect(wrapper.findComponent(NTag).exists()).toBe(true);
  });

  it("passes the correct props to the Naive UI tag component", () => {
    const wrapper = mount(ModelChip, {
      slots: {
        default: "Model Name",
      },
    });
    const tagComponent = wrapper.findComponent(NTag);
    expect(tagComponent.props("round")).toBe(true);
    expect(tagComponent.props("size")).toBe("tiny");
  });
});
