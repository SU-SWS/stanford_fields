import {useMemo} from "preact/hooks";
import SelectList from "../components/select-list";
import {buildOptionSets, OptionSet} from "../components/option-sets";
import {FilterProps, getSelectedValues, registerPreactFilter, submitFilter} from "../components/drupal-filter";

const FilterIsland = ({originalSelect, selectOptions}: FilterProps) => {
  const optionSets = useMemo(() => buildOptionSets(selectOptions), [selectOptions])
  const selectedValues = getSelectedValues(originalSelect)
  const multiple = originalSelect.multiple

  const onCommit = (set: OptionSet, values: string[]) => {
    const childValues = set.options.map(option => option.value)
    Array.from(originalSelect.options).forEach(option => {
      if (childValues.includes(option.value)) option.selected = values.includes(option.value)
    })
    submitFilter(originalSelect, set.value)
  }

  return (
    <div className="hierarchy-preact-select">
      {optionSets.map(set =>
        <div key={set.value} className="preact-select-item">
          <SelectList
            id={`${originalSelect.id}-preact-${set.value}`}
            items={set.options}
            label={set.label}
            multiple={multiple}
            defaultValue={selectedValues.filter(val => set.options.find(opt => opt.value === val))}
            emptyLabel={multiple ? undefined : Drupal.t("- Any -")}
            focusKey={set.value}
            onCommit={onCommit.bind(null, set)}
          />
        </div>
      )}
    </div>
  )
}

registerPreactFilter("stanfordFieldsHierarchySelectPreact", "taxonomy_label_hierarchy", FilterIsland, {removeViewPath: true})
