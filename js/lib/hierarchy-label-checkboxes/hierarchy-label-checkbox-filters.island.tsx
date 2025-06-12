import {createIslandWebComponent} from 'preact-island'
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

const Radio = styled.input`
  outline: 2px;
  clip: unset;
  position: relative;
  width: 25px;
  height: 25px;
  display: inline-block;
  clip-path: unset;
`

const FilterIsland = ({originalSelect, selectOptions}) => {

  const onChange = (parentValue: string, event) => {
    let parent = null
    for (let i = 0; i < originalSelect.options.length; i++) {
      if (!selectOptions.find(opt => opt.value === originalSelect.options[i].value).label.startsWith('-')) {
        parent = originalSelect.options[i].value
        continue
      }

      if (parentValue !== parent) continue
      originalSelect.options[i].selected = event.target.value === originalSelect.options[i].value
    }
    originalSelect?.closest('form').querySelector('[data-bef-auto-submit-click]')?.click();
  };


  const optionSets: Array<{
    label: string,
    value: string,
    options: { label: string, value: string }[]
  }> = []
  let parentLabel = ''

  selectOptions.map(option => {
    if (!option.label.startsWith('-')) {
      parentLabel = option.label
      optionSets.push({...option, options: []})
    } else {
      optionSets.find(item => item.label === parentLabel)?.options.push({
        value: option.value,
        label: option.label.substring(1),
      })
    }
  })


  let defaultValues: Array<string> = [];
  for (let option of originalSelect.children) {
    if (option.getAttribute('selected')) defaultValues.push(option.getAttribute('value'))
  }

  return (
    <div className="hierarchy-preact-checkbox">
      {optionSets.map(set =>
        <Fieldset key={set.value} className="preact-checkbox-item">
          <legend>
            {set.label}
          </legend>

          <Label>
            <Radio
              type="radio"
              name={set.value}
              defaultChecked={!set.options.find(option => defaultValues.includes(option.value))}
              onChange={onChange.bind(null, set.value)}
              data-bef-auto-submit-exclude
            />
            - All -
          </Label>

          {set.options.map(option =>
            <Label key={option.value}>
              <Radio
                type="radio"
                name={set.value}
                value={option.value}
                defaultChecked={defaultValues.includes(option.value)}
                onChange={onChange.bind(null, set.value)}
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

const island = createIslandWebComponent('hierarchy-checkbox', FilterIsland)

if (process.env.NODE_ENV === 'development') {
  island.render({selector: `.hierarchy-checkbox-preact`})
} else {
  (function () {
    Drupal.behaviors.stanfordFieldsHierarchyCheckboxesPreact = {
      attach: function (context, settings) {
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
