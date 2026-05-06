import {createIsland} from 'preact-island'
import SelectList from "../components/select-list";
import {useEffect} from "preact/compat";

const FilterIsland = ({originalSelect, selectOptions}) => {

  useEffect(() => {
    const optionElements = originalSelect.children
    let parent = ''

    for (let i = 0; i < optionElements.length; i++) {
      const label = optionElements[i].textContent
      if (!label.startsWith('-')) {
        parent = label
      } else {
        optionElements[i].setAttribute('data-preact-parent', parent)
      }
    }

  }, []);

  const onSelectChange = (parentLabel, event, value) => {
    for (let option of originalSelect.children) {
      if (option.getAttribute('data-preact-parent') === parentLabel) {
        option.selected = value?.includes(option.getAttribute('value'))
      }
    }
    originalSelect?.closest('form').querySelector('[data-bef-auto-submit-click]')?.click();
  }

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

  let defaultValue = [];
  for (let option of originalSelect?.children) {
    if (option.getAttribute('selected')) {
      defaultValue.push(option.getAttribute('value'))
    }
  }

  return (
    <div className="hierarchy-preact-select">
      {optionSets.map((set, i) =>
        <div key={i} className="preact-select-item">
          <SelectList
            name={originalSelect.getAttribute('id') + `-preact-${i}`}
            options={set.options.filter(item => item.value !== 'All')}
            label={set.label}
            multiple={originalSelect.getAttribute('multiple') === 'multiple'}
            onChange={onSelectChange.bind(null, set.label)}
            defaultValue={defaultValue.filter(val => set.options.find(opt => opt.value === val))}
          />
        </div>
      )}
    </div>
  )
}

if (process.env.NODE_ENV === 'development') {
  const island = createIsland(FilterIsland)
  island.render({
    selector: `.taxonomy-label-hierarchy`,
  })
} else {
  (function (once) {
    Drupal.behaviors.stanfordFieldsSelectPreact = {
      attach: function (context, settings) {

        const island = createIsland(FilterIsland)
        settings.preactFilters.taxonomy_label_hierarchy.map(field => {
          const originalSelect = once('preact-select', `#${field.id} select`, context)[0]
          if (!originalSelect) return;

          island.render({
            selector: '#' + field.id,
            initialProps: {
              selectOptions: field.options,
              originalSelect: originalSelect
            }
          })
        })
      }
    };
  })(once);
}
