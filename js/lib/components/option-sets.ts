import {SelectOption} from "./select-list";

export type OptionSet = SelectOption & {
  options: SelectOption[]
}

/**
 * Group a flat list of hierarchy options into parents and their children.
 *
 * Children are identified by a leading "-" in their label, which is how Drupal
 * flattens taxonomy hierarchies in select options.
 *
 * @param options
 *   Flat list of options.
 *
 * @return
 *   List of parent options, each with its child options.
 */
export const buildOptionSets = (options: SelectOption[]): OptionSet[] => {
  const optionSets: OptionSet[] = []

  options.forEach(option => {
    if (option.value === "All") return

    if (!option.label.startsWith("-")) {
      optionSets.push({...option, options: []})
      return
    }

    optionSets[optionSets.length - 1]?.options.push({
      ...option,
      label: option.label.substring(1),
    })
  })

  return optionSets
}
