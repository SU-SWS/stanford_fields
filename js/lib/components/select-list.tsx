import {Select, SelectRootChangeEventDetails} from "@base-ui/react/select"
import {ReactNode} from "preact/compat";
import {ChevronDownIcon} from "@heroicons/react/20/solid"
import {SelectRootProps} from "@base-ui/react/select"
import styled from "styled-components";
import {useRef} from "preact/hooks";

export type SelectOption = {
  value: string
  label: string
}

interface Props<Value, Multiple extends boolean | undefined = false> extends Omit<
  SelectRootProps<Value, Multiple>,
  "items"
> {
  items?: SelectOption[]
  label?: string
  emptyValue?: string
  emptyLabel?: string
  className?: string
}

const SelectList = ({items = [], label, emptyValue, className, emptyLabel, required, multiple, onValueChange, ...props}: Props<string, true>) => {
  const ref = useRef<HTMLDivElement>(null)
  const valChanged = (val: string [], e: SelectRootChangeEventDetails) => {
    if (onValueChange) onValueChange(val, e)
  }

  const options = !multiple && !required && emptyLabel ? [{
    value: emptyValue || "",
    label: emptyLabel
  }, ...items] : [...items]
  emptyLabel = emptyLabel || multiple ? "Choose one or more from dropdown" : "Choose from dropdown"
  return (
    <SelectListWrapper ref={ref}>
      <Select.Root items={options} required={required} multiple={multiple} onValueChange={valChanged} modal={false} {...props}>
        <Select.Label>{label}</Select.Label>
        <Select.Trigger className="select-trigger">
          <Select.Value>
            {(value: Array<SelectOption["value"]>) => (
              <span className="selected-values">
                {!value.length && <span className="empty-label">{emptyLabel}</span>}
                {value.map(val =>
                  <span className="selected-value" key={val}>{options.find(o => o.value === val)?.label}</span>
                )}
              </span>
            )}
          </Select.Value>
          <Select.Icon>
            <ChevronDownIcon width={20}/>
          </Select.Icon>
        </Select.Trigger>

        <Select.Portal className="select-portal" container={ref}>
          <Portal>
            <Select.Positioner align="start" alignItemWithTrigger={false}>
              <Select.Popup className="select-popup" finalFocus={() => console.log('here')}>
                <Select.List className="select-list">
                  {options.map(item => (
                    <Option key={item.value} value={item.value} label={item.label}/>
                  ))}
                </Select.List>
              </Select.Popup>
            </Select.Positioner>
          </Portal>
        </Select.Portal>
      </Select.Root>
    </SelectListWrapper>
  )
}

const Option = ({value, label}: {
  value: string | number;
  label: string | ReactNode
}) => {
  return (
    <Select.Item value={value} className="select-item">
      <Select.ItemText>{label}</Select.ItemText>
    </Select.Item>
  )
}

const SelectListWrapper = styled.div`
  button {
    background: transparent;
    color: black;
    width: 100%;
    border: 1px solid black;
    border-radius: 5px;
    text-align: left;
    min-height: 40px;
    display: flex;
    justify-content: space-between;

    &:hover, &:focus {
      background: transparent;
      color: black;
      text-decoration: underline;
    }
  }
  .empty-label {
    color: #4c4740;
    font-size: 18px;
  }

  .selected-values {
    display: block;
    margin: 5px 0;
  }
  .selected-value {
    padding: 5px;
    border: 1px solid #b6b1a9;
    border-radius: 5px;
    margin: 5px 5px 5px 0;
  }
`

const Portal = styled.div`
  .select-popup {
    background: white;
    width: var(--anchor-width);
    border: 1px solid rgb(213, 213, 212);
    box-shadow: rgba(0, 0, 0, 0) 0px 0px 0px 0px, rgba(0, 0, 0, 0) 0px 0px 0px 0px, rgba(0, 0, 0, 0.1) 0px 10px 15px -3px, rgba(0, 0, 0, 0.1) 0px 4px 6px -4px;
    max-height: 300px;
    overflow-y: auto;
  }

  .select-item {
    cursor: pointer;
    padding: 5px;

    &:hover, &:focus {
      background: #d9d7d2;
      text-decoration: underline;
    }

    &[aria-selected="true"] {
      background: #b6b1a9;
    }
  }
`

export default SelectList
