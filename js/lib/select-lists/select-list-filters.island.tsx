import {createIslandWebComponent} from 'preact-island'
import SelectList from "../components/select-list";
import {useEffect, useRef, useState} from "preact/compat";

type SelectOption = {
  value: string,
  label: string,
  disabled: boolean
}

const FilterIsland = ({focus = false}) => {
  const initRef = useRef(0)
  const ref = useRef();
  const [selectOptions, setSelectOptions] = useState<SelectOption[]>([])
  const [selectedValues, setSelectedValues] = useState([])

  useEffect(() => {
    const selectElement = getOriginalSelect()

    const options = [];
    const defaultValue = [];

    for (let option of selectElement?.children) {
      if (option.getAttribute('selected')) {
        defaultValue.push(option.getAttribute('value'))
      }

      const value = option.getAttribute('value')
      const label = option.textContent;
      options.push({value, label, disabled: option.getAttribute('disabled') === 'disabled'});
    }

    setSelectedValues(defaultValue);
    setSelectOptions(options);
  }, []);

  useEffect(() => {
    // Initial render has a value of 0, and after the default selected values is set, the value is 1.
    // We only want to update the original select element after that point, then submit the form.
    if (initRef.current <= 1) {
      initRef.current++
      return
    }

    for (let option of originalSelect.children) {
      option.selected = selectedValues.includes(option.getAttribute('value'))
    }
    getOriginalSelect()?.closest('form').querySelector('[data-bef-auto-submit-click]')?.click();
  }, [selectedValues]);

  const getOriginalSelect = () => {
    return ref.current?.parentNode.querySelector('select')
  }

  const getOriginalLabel = () => {
    return ref.current?.parentNode.querySelector('label').textContent
  }

  const onSelectChange = (event, value) => {
    setSelectedValues(Array.isArray(value) ? value : [value])
  }

  const originalSelect = getOriginalSelect()

  return (
    <div ref={ref} className="preact-select">
      {originalSelect &&
        <SelectList
          name={originalSelect.getAttribute('id') + '-preact'}
          options={selectOptions.filter(item => item.value !== 'All')}
          label={getOriginalLabel()}
          multiple={originalSelect.getAttribute('multiple') === 'multiple'}
          onChange={onSelectChange}
          value={selectedValues}
          emptyLabel={selectOptions.find(item => item.value === 'All')?.label}
        />
      }
    </div>
  )
}

if (process.env.NODE_ENV === 'development') {
  const island = createIslandWebComponent('combobox-select-list', FilterIsland)
  island.render({
    selector: `.preact-combo-box`,
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
          selector: `${contextClass} .preact-combo-box`.trim(),
          initialProps: {focus: contextClass.indexOf('js-view-dom-id') >= 0}
        })
      }
    };
  })();
}
