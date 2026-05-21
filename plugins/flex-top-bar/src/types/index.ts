export type ColumnType = 'text' | 'social' | 'contact' | 'icon' | 'countdown'

export const COUNTDOWN_STYLES = ['plain', 'boxed'] as const
export type CountdownStyle = (typeof COUNTDOWN_STYLES)[number]

export const COUNTDOWN_DIRECTIONS = ['up', 'down'] as const
export type CountdownDirection = (typeof COUNTDOWN_DIRECTIONS)[number]

export const COUNTDOWN_TEXT_POSITIONS = ['before', 'after'] as const
export type CountdownTextPosition = (typeof COUNTDOWN_TEXT_POSITIONS)[number]

export const ICON_POSITIONS = ['before', 'after'] as const
export type IconPosition = (typeof ICON_POSITIONS)[number]

export type IconStyle = 'rounded' | 'square' | 'black' | 'white' | 'color'

/** Social platforms (aligned with legacy PHP admin mock). */
export const SOCIAL_PLATFORMS = [
  'facebook',
  'twitterX',
  'instagram',
  'linkedin',
  'google',
  'youtube',
  'apple',
  'snapchat',
  'pinterest',
  'medium',
  'github',
  'threads',
  'whatsapp',
  'figma',
  'dribbble',
  'reddit',
  'discord',
  'tiktok',
  'tumblr',
  'telegram',
  'bluesky',
  'signal',
  'vk',
  'spotify',
  'twitch',
  'messenger'
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
  'location',
  'chat',
  'website',
  'support',
] as const

export type ContactKind = (typeof CONTACT_KINDS)[number]

export interface ContactEntry {
  kind: ContactKind | ''
  value: string
}

export const CONTENT_POSITIONS = ['left', 'center', 'right'] as const
export type ContentPosition = (typeof CONTENT_POSITIONS)[number]

export interface TextBarColumn {
  id: string
  type: 'text'
  effect: 'none' | 'slider' | 'fadein' | 'blink'
  messages: string[]
  size_percent: number
  content_position: ContentPosition
  messages_mobile_visible: boolean
}

export interface SocialBarColumn {
  id: string
  type: 'social'
  icon_style: IconStyle
  background_color: string
  icon_color: string
  icon_border_width: number
  icon_border_color: string
  links: SocialLink[]
  size_percent: number
  content_position: ContentPosition
  messages_mobile_visible: boolean
}

export interface ContactBarColumn {
  id: string
  type: 'contact'
  icon_style: IconStyle
  background_color: string
  icon_color: string
  icon_border_width: number
  icon_border_color: string
  contacts: ContactEntry[]
  size_percent: number
  content_position: ContentPosition
  messages_mobile_visible: boolean
}

export interface IconBarColumn {
  id: string
  type: 'icon'
  /** WordPress attachment ID; 0 when unset or invalid. */
  icon_attachment_id: number
  /** Resolved attachment URL for frontend render. */
  icon_url: string
  text: string
  icon_position: IconPosition
  size_percent: number
  content_position: ContentPosition
  messages_mobile_visible: boolean
}

export interface CountdownBarColumn {
  id: string
  type: 'countdown'
  counter_style: CountdownStyle
  count_direction: CountdownDirection
  /** Wall-clock end time for count-down mode (`YYYY-MM-DDTHH:MM`). */
  countdown_to_datetime: string
  /** Wall-clock start time for count-up mode (`YYYY-MM-DDTHH:MM`). */
  countup_from_datetime: string
  /** IANA timezone for countdown wall-clock values. */
  countdown_timezone: string
  text: string
  text_position: CountdownTextPosition
  background_color: string
  counter_color: string
  text_color: string
  size_percent: number
  content_position: ContentPosition
  messages_mobile_visible: boolean
}

export type BarColumn =
  | TextBarColumn
  | SocialBarColumn
  | ContactBarColumn
  | IconBarColumn
  | CountdownBarColumn

export interface Bar {
  id: string
  name: string
  visible: boolean
  admin_visibile: boolean
  scheduled_enabled: boolean
  scheduled_from_datetime: string
  scheduled_to_datetime: string
  /** IANA timezone or UTC offset used for scheduled_from/to wall-clock values. */
  scheduled_timezone: string
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
  plan_name: string
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

export interface IconColumnMediaLimits {
  maxWidth: number
  maxHeight: number
  maxFileBytes: number
  allowedMimeTypes: string[]
  displaySizePx: number
}

export interface TopBarConfig {
  apiRoot: string
  nonce: string
  i18n: Record<string, string>
  version?: string
  /** Absolute URL to BMS favicon (WordPress `plugins_url`); used for admin footer branding. */
  bmsFaviconUrl?: string
  iconColumnMedia?: IconColumnMediaLimits
}

// Global window interface
declare global {
  interface Window {
    flexTopBarConfig: TopBarConfig
  }
}
