/**
 * Minimal typings for the Drupal globals the filter islands rely on.
 */
type DrupalSettings = {
  preactFilters?: Record<string, Record<string, import("./components/drupal-filter").FilterSettings>>
  views?: {
    ajaxViews?: Record<string, { view_path?: string, [key: string]: unknown }>
  }
  [key: string]: unknown
}

type DrupalBehavior = {
  attach?: (context: Document | Element, settings: DrupalSettings) => void
  detach?: (context: Document | Element, settings: DrupalSettings, trigger: "unload" | "serialize" | "move" | string) => void
}

declare const Drupal: {
  behaviors: Record<string, DrupalBehavior>
  t: (str: string, args?: Record<string, string>, options?: { context?: string }) => string
}

declare const once: {
  <T extends Element = Element>(id: string, selector: string | Element | Element[] | NodeListOf<Element>, context?: Document | Element): T[]
  remove: <T extends Element = Element>(id: string, selector: string | Element | Element[] | NodeListOf<Element>, context?: Document | Element) => T[]
}
