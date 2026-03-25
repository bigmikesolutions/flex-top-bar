export interface BarColumn {
  id: string
  type: 'text'
  effect: 'none' | 'slider' | 'fadein' | 'blink'
  messages: string[]
  /** Column width as a percentage of the bar (25–100). */
  size_percent: number
  messages_mobile_visible: boolean
}

export interface Bar {
  id: string
  name: string
  visible: boolean
  admin_visibile: boolean
  scheduled_enabled: boolean
  scheduled_from_datetime: string
  scheduled_to_datetime: string
  position: 'top' | 'bottom'
  /** Mirrored from the first column for backward compatibility. */
  effect: 'none' | 'slider' | 'fadein' | 'blink'
  /** Mirrored from the first column for backward compatibility. */
  messages: string[]
  /** Mirrored from the first column for backward compatibility. */
  messages_mobile_visible: boolean
  columns: BarColumn[]
  bg_color: string
  frame_color: string
  frame_width: number
  hide_on_scroll: boolean
}

export interface FeatureFlags {
  max_bars: number
  max_messages: number
  schedule_enabled: boolean
}

export interface ApiError {
  error: string
  code?: string
}

export interface ApiResponse<T> {
  data?: T
  error?: ApiError
}

export interface TopBarConfig {
  apiRoot: string
  nonce: string
  i18n: Record<string, string>
}

// Global window interface
declare global {
  interface Window {
    topBarConfig: TopBarConfig
  }
}
