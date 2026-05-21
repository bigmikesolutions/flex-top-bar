/** Max upload dimensions (pixels) for custom column icons. */
export const ICON_COLUMN_MAX_WIDTH = 64
export const ICON_COLUMN_MAX_HEIGHT = 64

/** Max file size in bytes (512 KB). */
export const ICON_COLUMN_MAX_FILE_BYTES = 512 * 1024

export const ICON_COLUMN_ALLOWED_MIME_TYPES = [
  'image/jpeg',
  'image/png',
  'image/gif',
  'image/webp',
  'image/svg+xml',
] as const

/** Display size on the top bar (CSS pixels). */
export const ICON_COLUMN_DISPLAY_SIZE_PX = 24
