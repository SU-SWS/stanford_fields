import {createIslandWebComponent} from 'preact-island'
import SelectList from "../components/select-list";

const FilterIsland = ({originalSelect, selectOptions}) => {

  const onSelectChange = (event, value) => {
    for (let option of originalSelect.children) {
      option.selected = value.includes(option.getAttribute('value'))
    }
    originalSelect?.closest('form').querySelector('[data-bef-auto-submit-click]')?.click();
  }

  const defaultValue = [];
  for (let option of originalSelect?.children) {
    if (option.getAttribute('selected')) {
      defaultValue.push(option.getAttribute('value'))
    }
  }

  return (
    <div className="preact-select">
      <SelectList
        name={originalSelect.getAttribute('id') + '-preact'}
        options={selectOptions.filter(item => item.value !== 'All')}
        label={originalSelect.parentNode.querySelector('label').textContent}
        multiple={originalSelect.getAttribute('multiple') === 'multiple'}
        onChange={onSelectChange}
        defaultValue={defaultValue}
        emptyLabel={selectOptions.find(item => item.value === 'All')?.label}
      />
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
      attach: function (context, settings) {
        const island = createIslandWebComponent('combobox-select-list', FilterIsland)

        settings.preactFilters.preact_combo_box.map(field => {
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
