import {createIslandWebComponent} from 'preact-island'
import SelectList from "../components/select-list";
import {useEffect, useRef, useState} from "preact/compat";

const FilterIsland = ({focus = false}) => {
  const ref = useRef<HTMLDivElement>(null);
  const [originalSelect, setOriginalSelect] = useState(null);

  useEffect(() => {
    setOriginalSelect(ref.current.parentNode.querySelector('select'));

    // Use visibility because when display none, the field isn't updated
    // sometimes after ajax.
    const origSelect = ref.current.parentNode.querySelector('select');
    origSelect.setAttribute('aria-hidden', 'true');
    origSelect.style.visibility = 'hidden'
    origSelect.style.height = '0'
    origSelect.style.position = 'absolute'

    const origLabel = ref.current.parentNode.querySelector('label');
    origLabel.style.visibility = 'hidden'
    origLabel.style.height = '0'
    origLabel.style.position = 'absolute'
  }, [])

  const getSelectOptions = (selectElement) => {
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
        options.find(item => item.label === parent).options.push({
          value,
          label,
          disabled: option.getAttribute('disabled') === 'disabled'
        })

        option.setAttribute('data-preact-parent', parent)
      }
    }

    return options;
  }

  const onSelectChange = (parentLabel, e, value) => {
    console.log(parentLabel, value)
    for (let option of originalSelect.children) {
      if (option.getAttribute('data-preact-parent') === parentLabel) {
        if (value.includes(option.getAttribute('value'))) {
          option.setAttribute('selected', 'selected')
        } else {
          option.removeAttribute('selected');
        }
      }
    }
  }

  const getDefaultValue = () => {
    let defaultValue = [];
    for (let option of originalSelect?.children) {
      if (option.getAttribute('selected')) {
        if (!originalSelect.getAttribute('multiple')) return option.getAttribute('value');

        defaultValue.push(option.getAttribute('value'))
      }
    }
    return defaultValue;
  }

  const selectOptions: Array<{
    label: string,
    options: Array<{
      value: string,
      label: string,
      disabled: boolean
    }>
  }> = (originalSelect && getSelectOptions(originalSelect)) || [];

  return (
    <div ref={ref}>
      {selectOptions.map((set, i) =>
        <SelectList
          key={i}
          name={originalSelect.getAttribute('id') + `-preact-${i}`}
          options={set.options.filter(item => item.value !== 'All')}
          label={set.label}
          multiple={originalSelect.getAttribute('multiple') === 'multiple'}
          onChange={onSelectChange.bind(null, set.label)}
          defaultValue={getDefaultValue()}
          emptyLabel={set.options.find(item => item.value === 'All')?.label}
        />
      )
      }
    </div>
  )
}

if (process.env.NODE_ENV === 'development') {
  const island = createIslandWebComponent('combobox-select-list', FilterIsland)
  island.render({
    selector: `.select-preact`,
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
          selector: `${contextClass} .hierarchy-select-preact`.trim(),
          initialProps: {focus: contextClass.indexOf('js-view-dom-id') >= 0}
        })
      }
    };
  })();
}
