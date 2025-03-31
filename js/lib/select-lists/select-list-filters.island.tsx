import {createIslandWebComponent} from 'preact-island'
import SelectList from "../components/select-list";
import {useEffect, useRef, useState} from "preact/compat";

const FilterIsland = ({focus = false}) => {
  const ref = useRef();
  const [originalSelect, setOriginalSelect] = useState(null);
  const [label, setLabel] = useState('');

  useEffect(() => {
    setOriginalSelect(ref.current.parentNode.querySelector('select'));
    setLabel(ref.current.parentNode.querySelector('label').textContent);
  }, [])

  const getSelectOptions = (selectElement) => {
    const options = [];

    const optionElements = selectElement.children;

    for (let i = 0; i < optionElements.length; i++) {
      const option = optionElements[i];
      const value = option.getAttribute('value')
      const label = option.textContent;
      options.push({value, label, disabled: option.getAttribute('disabled') === 'disabled'});
    }
    return options;
  }

  const onSelectChange = (event, value) => {
    event.stopPropagation()

    if (!originalSelect.getAttribute('multiple')) return originalSelect.value = value;

    for (let option of originalSelect.children) {
      if (value.includes(option.getAttribute('value'))) {
        option.setAttribute('selected', 'selected')
      } else {
        option.removeAttribute('selected');
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
    value: string,
    label: string,
    disabled: boolean
  }> = originalSelect && getSelectOptions(originalSelect);

  return (
    <div ref={ref} className="preact-select">
      {originalSelect &&
        <SelectList
          name={originalSelect.getAttribute('id') + '-preact'}
          options={selectOptions.filter(item => item.value !== 'All')}
          label={label}
          multiple={originalSelect.getAttribute('multiple') === 'multiple'}
          onChange={onSelectChange}
          defaultValue={getDefaultValue()}
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
