export interface Bar {
  id: string
  name: string
  visible: boolean
  admin_visibile: boolean
  scheduled_enabled: boolean
  scheduled_from_datetime: string
  scheduled_to_datetime: string
  position: 'top' | 'bottom'
  effect: 'none' | 'slider' | 'fadein' | 'blink'
  messages: string[]
  messages_mobile_visible: boolean
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
