export type ColumnType = 'text' | 'social' | 'contact'

export type IconStyle = 'rounded' | 'square' | 'icon_only'

/** Social platforms (aligned with legacy PHP admin mock). */
export const SOCIAL_PLATFORMS = [
  'facebook',
  'instagram',
  'x',
  'linkedin',
  'youtube',
  'tiktok',
  'pinterest',
  'snapchat',
  'reddit',
  'tumblr',
  'whatsapp',
  'telegram',
  'discord',
  'threads',
  'mastodon',
  'medium',
  'github',
  'dribbble',
  'behance',
  'flickr',
] as const

export type SocialPlatform = (typeof SOCIAL_PLATFORMS)[number]

export interface SocialLink {
  platform: SocialPlatform | ''
  url: string
}

/** Contact row kinds (aligned with legacy PHP admin mock). */
export const CONTACT_KINDS = [
  'email',
  'phone',
  'mobile',
  'address',
  'location',
  'website',
  'fax',
  'support',
  'calendar',
] as const

export type ContactKind = (typeof CONTACT_KINDS)[number]

export interface ContactEntry {
  kind: ContactKind | ''
  value: string
}

export interface TextBarColumn {
  id: string
  type: 'text'
  effect: 'none' | 'slider' | 'fadein' | 'blink'
  messages: string[]
  size_percent: number
  messages_mobile_visible: boolean
}

export interface SocialBarColumn {
  id: string
  type: 'social'
  icon_style: IconStyle
  background_color: string
  icon_color: string
  links: SocialLink[]
  size_percent: number
  messages_mobile_visible: boolean
}

export interface ContactBarColumn {
  id: string
  type: 'contact'
  icon_style: IconStyle
  background_color: string
  icon_color: string
  contacts: ContactEntry[]
  size_percent: number
  messages_mobile_visible: boolean
}

export type BarColumn = TextBarColumn | SocialBarColumn | ContactBarColumn

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
  max_columns: number
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
