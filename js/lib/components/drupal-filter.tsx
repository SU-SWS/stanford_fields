import {ComponentType, render} from "preact"
import {SelectOption} from "./select-list";

export type FilterSettings = {
  id: string
  options: SelectOption[]
  viewId: string
}

export type FilterProps = {
  originalSelect: HTMLSelectElement
  selectOptions: SelectOption[]
}

type Mounted = {
  container: HTMLElement
  onceId: string
}

// Wrapper element => rendered preact container.
const mounted = new WeakMap<Element, Mounted>()

// The filter that triggered the last submit, so focus can return to it after
// the views AJAX response replaces the form.
let lastChanged: { wrapperId: string, focusKey: string } | null = null

/**
 * Attribute used to mark the element that should receive focus after an AJAX
 * refresh. The value should be unique within a single filter.
 */
export const FOCUS_KEY_ATTRIBUTE = "data-filter-focus-key"

/**
 * Notify Drupal & BEF that the original select has changed.
 *
 * Dispatching a change event lets BEF's auto submit decide whether to submit
 * the form, which respects its exclusions, delays and media queries.
 *
 * @param select
 *   The original select element that has already been updated.
 * @param focusKey
 *   Value of the FOCUS_KEY_ATTRIBUTE that should receive focus after AJAX.
 */
export const submitFilter = (select: HTMLSelectElement, focusKey: string) => {
  const wrapperId = select.closest(".preact-filter")?.id
  lastChanged = wrapperId ? {wrapperId, focusKey} : null
  select.dispatchEvent(new Event("change", {bubbles: true}))
}

/**
 * Get the values of the selected options in a select element.
 */
export const getSelectedValues = (select: HTMLSelectElement): string[] =>
  Array.from(select.options).filter(option => option.selected && option.value).map(option => option.value)

/**
 * Register a Drupal behavior that renders a preact component for a BEF filter.
 *
 * @param behaviorName
 *   Unique Drupal behavior name.
 * @param pluginId
 *   The BEF filter plugin id, used as the key in drupalSettings.preactFilters.
 * @param Component
 *   The component to render.
 * @param options
 *   - removeViewPath: Remove the view_path from the views ajax settings.
 */
export const registerPreactFilter = (
  behaviorName: string,
  pluginId: string,
  Component: ComponentType<FilterProps>,
  {removeViewPath = false}: { removeViewPath?: boolean } = {}
) => {
  const onceId = `stanford-fields-${pluginId}`

  Drupal.behaviors[behaviorName] = {
    attach(context, settings) {
      const fields = Object.values(settings.preactFilters?.[pluginId] ?? {})

      fields.forEach(field => {
        const wrapperSelector = `#${CSS.escape(field.id)}`
        const originalSelect = once<HTMLSelectElement>(onceId, `${wrapperSelector} select`, context)[0]
        const wrapper = originalSelect?.closest(wrapperSelector)
        if (!originalSelect || !wrapper) return

        if (removeViewPath) {
          // Views would otherwise build the AJAX path from the page path which
          // fails on some Drupal versions. Fall back to the views ajax path.
          const ajaxView = settings.views?.ajaxViews?.[`views_dom_id:${field.viewId}`]
          if (ajaxView) delete ajaxView.view_path
        }

        // Pull the current state from the select so disabled options are
        // accurate even if the settings were cached.
        const selectOptions = field.options.map(option => {
          const optionElement = Array.from(originalSelect.options).find(opt => opt.value === option.value)
          return {...option, disabled: !!optionElement?.disabled}
        })

        const container = document.createElement("div")
        container.className = "preact-filter-mount"
        wrapper.appendChild(container)
        mounted.set(wrapper, {container, onceId})

        render(<Component originalSelect={originalSelect} selectOptions={selectOptions}/>, container)

        if (lastChanged?.wrapperId === wrapper.id) {
          const focusKey = CSS.escape(lastChanged.focusKey)
          container.querySelector<HTMLElement>(`[${FOCUS_KEY_ATTRIBUTE}="${focusKey}"]`)?.focus()
          lastChanged = null
        }
      })
    },

    detach(context, settings, trigger) {
      if (trigger !== "unload") return

      const wrappers = Array.from(context.querySelectorAll(".preact-filter"))
      if (context instanceof Element && context.matches(".preact-filter")) wrappers.push(context)

      wrappers.forEach(wrapper => {
        const mount = mounted.get(wrapper)
        if (!mount || mount.onceId !== onceId) return

        // Unmount so effects and Base UI listeners are cleaned up.
        render(null, mount.container)
        mount.container.remove()
        mounted.delete(wrapper)
        once.remove(onceId, wrapper.querySelectorAll("select"))
      })
    },
  }
}
