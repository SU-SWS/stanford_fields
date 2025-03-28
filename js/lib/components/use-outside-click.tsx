import {RefObject} from "react"
import {useOnClickOutside} from "usehooks-ts"

/**
 * Perform some function when the user clicks, touches, or focuses outside a given element.
 *
 * @param ref
 *   Html Element container.
 * @param onClickOutside
 *   Function to trigger on outside click.
 */
const useOutsideClick = (ref: RefObject<HTMLElement | null>, onClickOutside: () => void) => {
  useOnClickOutside(ref as RefObject<HTMLElement>, onClickOutside, "mousedown")
  useOnClickOutside(ref as RefObject<HTMLElement>, onClickOutside, "touchstart")
  useOnClickOutside(ref as RefObject<HTMLElement>, onClickOutside, "focusin")
}

export default useOutsideClick
