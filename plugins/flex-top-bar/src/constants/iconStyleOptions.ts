import type { IconStyle } from '@/types'
import type { __ } from '@wordpress/i18n'

export function getIconStyleOptions(translate: typeof __): { value: IconStyle; label: string }[] {
  return [
    { value: 'rounded', label: translate('Rounded', 'top-bar') },
    { value: 'square', label: translate('Square', 'top-bar') },
    { value: 'black', label: translate('Black', 'top-bar') },
    { value: 'white', label: translate('White', 'top-bar') },
    { value: 'color', label: translate('Color', 'top-bar') },
  ]
}

