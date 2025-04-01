import {createIslandWebComponent} from 'preact-island'
import SelectList from "../components/select-list";
import {useEffect, useRef, useState} from "preact/compat";

type SelectOptionSet = {
  label: string
  options: Array<{value: string, label: string, disabled: boolean}>
}

const FilterIsland = () => {
  const [selectOptions, setSelectOptions] = useState<SelectOptionSet[]>([])
  const ref = useRef<HTMLDivElement>(null);

  useEffect(() => {
    const selectElement = getOriginalSelect()
    if (!selectElement) return []

    const options: Array<{ label: string, options: [] }> = [];

    const optionElements = selectElement.children
    let parent = ''

    for (let i = 0; i < optionElements.length; i++) {
      const option = optionElements[i]

      const value = option.getAttribute('value')
      const label = option.textContent

      if (!label.startsWith('-')) {
        options.push({label, options: []})
        parent = label
      } else {
        options.find(item => item.label === parent)?.options.push({
          value,
          label: label.substring(1),
          disabled: option.getAttribute('disabled') === 'disabled'
        })

        option.setAttribute('data-preact-parent', parent)
      }
    }
    setSelectOptions(options)
  }, []);

  const getOriginalSelect = () => {
    return ref.current?.parentNode.querySelector('select')
  }

  const onSelectChange = (parentLabel, event, value) => {
    for (let option of getOriginalSelect().children) {
      if (option.getAttribute('data-preact-parent') === parentLabel) {
        option.selected = value?.includes(option.getAttribute('value'))
      }
    }
  }

  const getDefaultValue = () => {
    const originalSelect = getOriginalSelect()

    let defaultValue = [];
    for (let option of originalSelect?.children) {
      if (option.getAttribute('selected')) {
        if (!originalSelect.getAttribute('multiple')) return option.getAttribute('value');

        defaultValue.push(option.getAttribute('value'))
      }
    }
    return defaultValue;
  }

  const originalSelect = getOriginalSelect()

  return (
    <div ref={ref} className="hierarchy-preact-select">
      {selectOptions.map((set, i) =>
        <div key={i} className="preact-select-item">
          <SelectList
            name={originalSelect.getAttribute('id') + `-preact-${i}`}
            options={set.options.filter(item => item.value !== 'All')}
            label={set.label}
            multiple={originalSelect.getAttribute('multiple') === 'multiple'}
            onChange={onSelectChange.bind(null, set.label)}
            defaultValue={getDefaultValue()}
            emptyLabel={set.options.find(item => item.value === 'All')?.label}
          />
        </div>
      )
      }
    </div>
  )
}

if (process.env.NODE_ENV === 'development') {
  const island = createIslandWebComponent('combobox-select-list', FilterIsland)
  island.render({
    selector: `.taxonomy-label-hierarchy`,
  })
} else {
  (function () {
    Drupal.behaviors.stanfordFieldsSelectPreact = {
      attach: function (context) {
        let contextClass = ''
        try {
          contextClass = '.' + context.getAttribute('class').replace(/ /g, '.');
        } catch (e) {
        }

        const island = createIslandWebComponent('combobox-select-list', FilterIsland)

        island.render({
          selector: `${contextClass} .taxonomy-label-hierarchy`.trim(),
          initialProps: {focus: contextClass.indexOf('js-view-dom-id') >= 0}
        })
      }
    };
  })();
}
