import {createIsland} from 'preact-island'
import SelectList, {SelectOption} from "../components/select-list";
import {useEffect} from "preact/compat";
import {useRef} from "preact/hooks";

const FilterIsland = ({originalSelect, selectOptions}: { originalSelect: HTMLSelectElement, selectOptions: SelectOption[] }) => {
  const ref = useRef<HTMLDivElement>(null)

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

  const onSelectChange = (parentLabel: string, value: string | string[]) => {
    const selectElem: HTMLSelectElement | null | undefined = ref.current?.closest('.preact-filter')?.querySelector('.form-select')
    for (let option of selectElem.children) {

      if (option.getAttribute('data-preact-parent') === parentLabel) {
        option.selected = value?.includes(option.getAttribute('value'))
      }
    }
    selectElem?.closest('form').querySelector('[data-bef-auto-submit-click]')?.click();
  }

  const optionSets: Array<{ label: string, options: SelectOption[] }> = []
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

  let defaultValue: string[] = [];
  for (let option of originalSelect?.children) {
    const val = option.getAttribute('value')
    if (val && option.getAttribute('selected')) {
      defaultValue.push(val)
    }
  }

  const multiple = originalSelect.getAttribute('multiple') === 'multiple' || undefined

  return (
    <div className="hierarchy-preact-select" ref={ref}>
      {optionSets.map((set, i) =>
        <div key={i} className="preact-select-item">
          <SelectList
            name={originalSelect.getAttribute('id') + `-preact-${i}`}
            items={set.options.filter(item => item.value !== 'All')}
            label={set.label}
            multiple={multiple}
            onValueChange={onSelectChange.bind(null, set.label)}
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

          delete settings.views.ajaxViews[`views_dom_id:${field.viewId}`].view_path

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
