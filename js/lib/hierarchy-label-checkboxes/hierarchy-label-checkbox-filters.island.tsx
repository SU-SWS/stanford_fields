import {createIslandWebComponent} from 'preact-island'
import {useEffect, useRef, useState} from "preact/compat";
import styled from "styled-components";

const Fieldset = styled.fieldset`
  max-height: 300px;
  overflow-y: scroll;
`

const Label = styled.label`
  display: flex;
  align-items: center;
  gap: 10px;
`

const Checkbox = styled.input`
  border: 1px solid black;
  clip: unset;
  position: relative;
  width: 25px;
  height: 25px;
  display: inline-block;
  clip-path: unset;
`

const FilterIsland = ({focus = false}) => {
  const ref = useRef<HTMLDivElement>(null);
  const [originalSelect, setOriginalSelect] = useState<HTMLSelectElement>(null);
  const [selectedValues, setSelectedValues] = useState<Array<number>>([])

  useEffect(() => {
    const selectElement = ref.current.parentNode.querySelector('select')
    setOriginalSelect(selectElement);

    let defaultValues = [];
    for (let option of selectElement.children) {
      if (option.getAttribute('selected')) defaultValues.push(parseInt(option.getAttribute('value')))
    }
    setSelectedValues(defaultValues)
  }, [])

  useEffect(() => {
    if (!originalSelect) return
    for (let option of originalSelect.children) {
      if (selectedValues?.includes(parseInt(option.getAttribute('value')))) {
        option.setAttribute('selected', 'selected')
      } else {
        option.removeAttribute('selected');
      }
    }
  }, [selectedValues]);

  const getSelectOptions = (selectElement) => {
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
    event.stopPropagation()

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
  }> = (originalSelect && getSelectOptions(originalSelect)) || [];

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
