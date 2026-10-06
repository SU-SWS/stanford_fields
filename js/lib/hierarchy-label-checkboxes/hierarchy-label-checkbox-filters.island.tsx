import {useMemo} from "preact/hooks";
import {buildOptionSets, OptionSet} from "../components/option-sets";
import {FilterProps, FOCUS_KEY_ATTRIBUTE, getSelectedValues, registerPreactFilter, submitFilter} from "../components/drupal-filter";

const FilterIsland = ({originalSelect, selectOptions}: FilterProps) => {
  const optionSets = useMemo(() => buildOptionSets(selectOptions), [selectOptions])
  const selectedValues = getSelectedValues(originalSelect)

  // Radios need a name to group them, but they must not be submitted with the
  // exposed form. Pointing them to a form id that doesn't exist detaches them.
  const detachedFormId = `${originalSelect.id}-preact-detached`

  const onChange = (set: OptionSet, value: string) => {
    const childValues = set.options.map(option => option.value)
    Array.from(originalSelect.options).forEach(option => {
      if (childValues.includes(option.value)) option.selected = option.value === value
    })
    submitFilter(originalSelect, set.value)
  }

  return (
    <div className="hierarchy-preact-checkbox">
      {optionSets.map(set => {
        const checkedValue = set.options.find(option => selectedValues.includes(option.value))?.value ?? ""
        const name = `${originalSelect.id}-preact-${set.value}`
        const radios = [{value: "", label: Drupal.t("- All -"), disabled: false}, ...set.options]

        return (
          <fieldset key={set.value} className="preact-checkbox-item">
            <legend>{set.label}</legend>

            <div className="options">
              {radios.map(option =>
                <label key={option.value} className="preact-radio-label">
                  <input
                    className="preact-radio"
                    type="radio"
                    name={name}
                    form={detachedFormId}
                    value={option.value}
                    disabled={option.disabled}
                    defaultChecked={option.value === checkedValue}
                    onChange={() => onChange(set, option.value)}
                    data-bef-auto-submit-exclude
                    {...(option.value === checkedValue ? {[FOCUS_KEY_ATTRIBUTE]: set.value} : {})}
                  />
                  {option.label}
                </label>
              )}
            </div>
          </fieldset>
        )
      })}
    </div>
  )
}

registerPreactFilter("stanfordFieldsHierarchyCheckboxesPreact", "taxonomy_label_hierarchy_checkbox", FilterIsland, {removeViewPath: true})
