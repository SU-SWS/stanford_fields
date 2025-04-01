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

const FilterIsland = () => {
  const initRef = useRef(0)

  const ref = useRef<HTMLDivElement>(null);
  const [selectedValues, setSelectedValues] = useState<Array<number>>([])

  const getOriginalSelect = () => {
    return ref.current?.parentNode.querySelector('select')
  }

  useEffect(() => {
    const selectElement = getOriginalSelect()

    let defaultValues = [];
    for (let option of selectElement.children) {
      if (option.getAttribute('selected')) defaultValues.push(parseInt(option.getAttribute('value')))
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

    const selectElement = getOriginalSelect()
    for (let i = 0; i < selectElement.options.length; i++) {
      selectElement.options[i].selected = selectedValues.indexOf(parseInt(selectElement.options[i].value)) >= 0;
    }

    getOriginalSelect()?.closest('form').querySelector('[data-bef-auto-submit-click]')?.click();
  }, [selectedValues]);

  const getSelectOptions = () => {
    const selectElement = getOriginalSelect()
    if (!selectElement) return []

    const options: Array<{ label: string, options: [] }> = [];

    const optionElements = selectElement.children
    let parent = ''
    for (let i = 0; i < optionElements.length; i++) {
      const option = optionElements[i]

      const value = parseInt(option.getAttribute('value'))
      const label = option.textContent

      if (!label.startsWith('-')) {
        options.push({label, options: []})
        parent = label
      } else {
        options.find(item => item.label === parent)?.options.push({
          value,
          label,
          disabled: option.getAttribute('disabled') === 'disabled'
        })

        option.setAttribute('data-preact-parent', parent)
      }
    }

    return options;
  }

  const onChange = (event) => {
    const value = parseInt(event.target.value); // Parse value to a number
    const isChecked = event.target.checked;

    setSelectedValues(prevSelectedValues => {
      if (isChecked) {
        // Add the number to the array if checked
        return [...prevSelectedValues, value];
      } else {
        // Remove the number from the array if unchecked
        return prevSelectedValues.filter(number => number !== value);
      }
    });
  };

  const selectOptions: Array<{
    label: string,
    options: Array<{
      value: string,
      label: string,
      disabled: boolean
    }>
  }> = getSelectOptions();

  return (
    <div ref={ref} className="hierarchy-preact-checkbox">
      {selectOptions.map((set, i) =>
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
              {option.label.substring(1)}
            </Label>
          )}
        </Fieldset>
      )
      }
    </div>
  )
}

if (process.env.NODE_ENV === 'development') {
  const island = createIslandWebComponent('combobox-hierarchy-checkbox', FilterIsland)
  island.render({
    selector: `.hierarchy-checkbox-preact`,
  })
} else {
  (function () {
    Drupal.behaviors.stanfordFieldsHierarchyCheckboxesPreact = {
      attach: function (context) {
        let contextClass = ''

        try {
          contextClass = '.' + context.getAttribute('class').replace(/ /g, '.');
        } catch (e) {
        }

        const island = createIslandWebComponent('combobox-hierarchy-checkbox', FilterIsland)

        island.render({
          selector: `${contextClass} .taxonomy-label-hierarchy-checkbox`.trim(),
          initialProps: {focus: contextClass.indexOf('js-view-dom-id') >= 0}
        })
      }
    };
  })();
}
