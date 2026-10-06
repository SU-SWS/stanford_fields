import SelectList from "../components/select-list";
import {FilterProps, getSelectedValues, registerPreactFilter, submitFilter} from "../components/drupal-filter";

const FilterIsland = ({originalSelect, selectOptions}: FilterProps) => {
  const emptyOption = selectOptions.find(item => item.value === "All")

  const onCommit = (values: string[]) => {
    Array.from(originalSelect.options).forEach(option => {
      option.selected = values.includes(option.value)
    })
    submitFilter(originalSelect, "trigger")
  }

  return (
    <div className="preact-select">
      <SelectList
        id={`${originalSelect.id}-preact`}
        items={selectOptions.filter(item => item.value !== "All")}
        label={originalSelect.labels?.[0]?.textContent?.trim() || Drupal.t("Filter")}
        multiple={originalSelect.multiple}
        required={originalSelect.required}
        defaultValue={getSelectedValues(originalSelect)}
        emptyValue={emptyOption?.value}
        emptyLabel={emptyOption?.label}
        focusKey="trigger"
        onCommit={onCommit}
      />
    </div>
  )
}

registerPreactFilter("stanfordFieldsComboBoxPreact", "preact_combo_box", FilterIsland)
