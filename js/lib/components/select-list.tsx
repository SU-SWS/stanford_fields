import {Select} from "@base-ui/react/select"
import {ChevronDownIcon} from "@heroicons/react/20/solid"
import {useRef} from "preact/hooks";
import {FOCUS_KEY_ATTRIBUTE} from "./drupal-filter";

export type SelectOption = {
  value: string
  label: string
  disabled?: boolean
}

type Props = {
  id?: string
  items?: SelectOption[]
  label?: string
  emptyValue?: string
  emptyLabel?: string
  className?: string
  multiple?: boolean
  required?: boolean
  defaultValue?: string[]
  focusKey?: string
  /**
   * Called when the selection should be applied. Single selects commit on
   * every change, multiple selects commit once the popup is closed so users
   * can choose several values before the form is submitted.
   */
  onCommit?: (values: string[]) => void
}

const toArray = (value: unknown): string[] => {
  if (Array.isArray(value)) return value
  return value === null || value === undefined || value === "" ? [] : [value as string]
}

const SelectList = ({
  id,
  items = [],
  label,
  emptyValue = "",
  emptyLabel,
  className,
  multiple = false,
  required = false,
  defaultValue = [],
  focusKey,
  onCommit,
}: Props) => {
  const ref = useRef<HTMLDivElement>(null)
  const pendingValues = useRef<string[] | null>(null)

  const options = !multiple && !required && emptyLabel ? [{value: emptyValue, label: emptyLabel}, ...items] : items
  const placeholder = emptyLabel || (multiple ? Drupal.t("Choose one or more from dropdown") : Drupal.t("Choose from dropdown"))

  const onValueChange = (value: unknown) => {
    const values = toArray(value)
    if (multiple) {
      pendingValues.current = values
      return
    }
    onCommit?.(values)
  }

  const onOpenChange = (open: boolean) => {
    if (open || !pendingValues.current) return
    const values = pendingValues.current
    pendingValues.current = null
    onCommit?.(values)
  }

  // Base UI renders a hidden input for form integration. Keep it from
  // triggering BEF auto submit; the original select is the source of truth.
  const inputRef = (input: HTMLInputElement | null) => {
    input?.setAttribute("data-bef-auto-submit-exclude", "")
  }

  return (
    <div className={["preact-select-list", className].filter(Boolean).join(" ")} ref={ref}>
      <Select.Root
        id={id}
        items={options}
        required={required}
        multiple={multiple}
        defaultValue={multiple ? defaultValue : (defaultValue[0] ?? null)}
        onValueChange={onValueChange}
        onOpenChange={onOpenChange}
        inputRef={inputRef}
        modal={false}
      >
        <Select.Label className="select-label">{label}</Select.Label>
        <Select.Trigger className="select-trigger" {...{[FOCUS_KEY_ATTRIBUTE]: focusKey}}>
          <Select.Value>
            {(value: unknown) => {
              const values = toArray(value).filter(val => val !== emptyValue)
              return (
                <span className="selected-values">
                  {!values.length && <span className="empty-label">{placeholder}</span>}
                  {values.map(val =>
                    <span className="selected-value" key={val}>{options.find(o => o.value === val)?.label}</span>
                  )}
                </span>
              )
            }}
          </Select.Value>
          <Select.Icon className="select-icon">
            <ChevronDownIcon width={20}/>
          </Select.Icon>
        </Select.Trigger>

        <Select.Portal container={ref}>
          <Select.Positioner align="start" alignItemWithTrigger={false}>
            <Select.Popup className="select-popup">
              <Select.List className="select-list">
                {options.map(item => (
                  <Select.Item key={item.value} value={item.value} disabled={item.disabled} className="select-item">
                    <Select.ItemText>{item.label}</Select.ItemText>
                  </Select.Item>
                ))}
              </Select.List>
            </Select.Popup>
          </Select.Positioner>
        </Select.Portal>
      </Select.Root>
    </div>
  )
}

export default SelectList
