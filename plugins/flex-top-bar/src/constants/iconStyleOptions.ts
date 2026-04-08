import type { IconStyle } from '@/types'
import type { __ } from '@wordpress/i18n'

export function getIconStyleOptions(translate: typeof __): { value: IconStyle; label: string }[] {
  return [
    { value: 'rounded', label: translate('Rounded', 'flex-top-bar') },
    { value: 'square', label: translate('Square', 'flex-top-bar') },
    { value: 'black', label: translate('Black', 'flex-top-bar') },
    { value: 'white', label: translate('White', 'flex-top-bar') },
    { value: 'color', label: translate('Color', 'flex-top-bar') },
  ]
}

