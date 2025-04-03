import {createIslandWebComponent} from 'preact-island'
import {useEffect, useRef, useState} from "preact/compat";
import styled from "styled-components";

const Fieldset = styled.fieldset`
  max-height: 300px;
  overflow-y: auto;
`

const Label = styled.label`
  display: flex;
  align-items: center;
  gap: 10px;
  margin: 0;
  cursor: pointer;
`

const Checkbox = styled.input`
  outline: 2px;
  clip: unset;
  position: relative;
  width: 25px;
  height: 25px;
  display: inline-block;
  clip-path: unset;
`

const FilterIsland = ({originalSelect, selectOptions}) => {
  const initRef = useRef(0)
  const [selectedValues, setSelectedValues] = useState<Array<string>>([])

  useEffect(() => {
    let defaultValues = [];
    for (let option of originalSelect.children) {
      if (option.getAttribute('selected')) defaultValues.push(option.getAttribute('value'))
    }
    setSelectedValues(defaultValues)
  }, [])

  useEffect(() => {
    // Initial render has a value of 0, and after the default selected values is set, the value is 1.
    // We only want to update the original select element after that point, then submit the form.
    if (initRef.current <= 1) {
      initRef.current++
      return
    }

    for (let i = 0; i < originalSelect.options.length; i++) {
      originalSelect.options[i].selected = selectedValues.indexOf(originalSelect.options[i].value) >= 0;
    }

    originalSelect?.closest('form').querySelector('[data-bef-auto-submit-click]')?.click();
  }, [selectedValues]);

  const onChange = (event) => {
    const value = event.target.value
    const isChecked = event.target.checked;

    setSelectedValues(prevSelectedValues => {
      if (isChecked) {
        // Add the number to the array if checked
        return [...prevSelectedValues, value];
      } else {
        // Remove the number from the array if unchecked
        return prevSelectedValues.filter(val => val !== value);
      }
    });
  };

  const optionSets: Array<{ label: string, options: { label: string, value: string | number }[] }> = []
  let parentLabel = ''

  selectOptions.map(option => {
    if (!option.label.startsWith('-')) {
      parentLabel = option.label
      optionSets.push({label: option.label, options: []})
    } else {
      optionSets.find(item => item.label === parentLabel)?.options.push({
        value: option.value,
        label: option.label.substring(1),
      })
    }
  })

  return (
    <div className="hierarchy-preact-checkbox">
      {optionSets.map((set, i) =>
        <Fieldset key={i} className="preact-checkbox-item">
          <legend>
            {set.label}
          </legend>

          {set.options.map(option =>
            <Label key={option.value}>
              <Checkbox
                type="checkbox"
                value={option.value}
                checked={selectedValues.includes(option.value)}
                onChange={onChange}
                data-bef-auto-submit-exclude
              />
              {option.label}
            </Label>
          )}
        </Fieldset>
      )
      }
    </div>
  )
}

if (process.env.NODE_ENV === 'development') {
  const island = createIslandWebComponent('hierarchy-checkbox', FilterIsland)
  island.render({
    selector: `.hierarchy-checkbox-preact`,
  })
} else {
  (function () {
    Drupal.behaviors.stanfordFieldsHierarchyCheckboxesPreact = {
      attach: function (context, settings) {
        const island = createIslandWebComponent('hierarchy-checkbox', FilterIsland)

        settings.preactFilters.taxonomy_label_hierarchy_checkbox.map(field => {
          island.render({
            selector: '#' + field.id,
            initialProps: {
              selectOptions: field.options,
              originalSelect: context.querySelector('#' + field.id + ' select')
            }
          })

        })
      }
    };
  })();
}
