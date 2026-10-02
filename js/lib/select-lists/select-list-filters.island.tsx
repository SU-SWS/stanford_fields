import {createIsland} from 'preact-island'
import SelectList, {SelectOption} from "../components/select-list";
import {useRef} from "preact/hooks";
import {useLayoutEffect} from "preact/compat";

const FilterIsland = ({originalSelect, selectOptions}: { originalSelect: HTMLSelectElement, selectOptions: SelectOption[] }) => {
  const ref = useRef<HTMLDivElement>(null)
  const multipleRef = useRef(originalSelect.getAttribute('multiple') === 'multiple')

  useLayoutEffect(() => {
    ref.current?.querySelector('button')?.focus()
  }, [])

  const onSelectChange = (value: string[]) => {
    const selectElem: HTMLSelectElement | null | undefined = ref.current?.closest('.preact-filter')?.querySelector('.form-select')

    if (!selectElem) {
      console.error('Unable to find select element')
      return
    }
    const options = Array.from(selectElem.children) as HTMLOptionElement[]
    for (let option of options) {
      option.selected = value && (typeof value === 'string' ? value == option.getAttribute('value') : value.includes(option.getAttribute('value')))
    }
    const befSubmit = selectElem?.closest('form')?.querySelector('[data-bef-auto-submit-click]') as HTMLInputElement
    befSubmit?.click();
  }

  const defaultValue: string[] = [];
  for (let option of originalSelect?.children) {
    const val = option.getAttribute('value')
    if (option.getAttribute('selected') && val) {
      defaultValue.push(val)
    }
  }

  const label = originalSelect.parentNode?.querySelector('label')?.textContent || "Filter"
  return (
    <div ref={ref} className="preact-select">
      <SelectList
        id={originalSelect.getAttribute('id')}
        name={originalSelect.getAttribute('id') + '-preact'}
        items={selectOptions.filter(item => item.value !== 'All')}
        label={label}
        multiple={multipleRef.current || undefined}
        onValueChange={onSelectChange}
        defaultValue={defaultValue}
        emptyValue={selectOptions.find(item => item.value === 'All')?.value}
        emptyLabel={selectOptions.find(item => item.value === 'All')?.label}
        required={originalSelect.getAttribute('required') == 'required'}
      />
    </div>
  )
}

if (process.env.NODE_ENV === 'development') {
  const island = createIsland(FilterIsland)
  island.render({
    selector: `.preact-combo-box`,
  })
} else {
  (function (once) {
    Drupal.behaviors.stanfordFieldsSelectPreact = {
      attach: function (context, settings) {
        const island = createIsland(FilterIsland)

        settings.preactFilters.preact_combo_box.map(field => {
          const originalSelect = once('preact-select', `#${field.id} select`, context)[0]
          if (!originalSelect) return

          field.options.map(option => {
            option.disabled = originalSelect.querySelector(`[value="${option.value}"]`).getAttribute('disabled') === "true"
          })
          island.render({
            selector: `#${field.id}`,
            initialProps: {
              selectOptions: field.options,
              originalSelect
            }
          })
          context.querySelector(`#${field.id}`)
        })
      }
    };
  })(once);
}
