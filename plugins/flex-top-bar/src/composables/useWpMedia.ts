import { computed } from 'vue'
import type { IconColumnMediaLimits } from '@/types'
import {
  ICON_COLUMN_ALLOWED_MIME_TYPES,
  ICON_COLUMN_MAX_FILE_BYTES,
  ICON_COLUMN_MAX_HEIGHT,
  ICON_COLUMN_MAX_WIDTH,
} from '@/constants/iconColumn'

type WpMediaAttachment = {
  id: number
  url: string
  width?: number
  height?: number
  filesizeInBytes?: number
  mime?: string
}

type WpMediaSelection = {
  first: () => { toJSON: () => WpMediaAttachment }
}

type WpMediaFrame = {
  open: () => void
  on: (event: string, callback: () => void) => WpMediaFrame
  state: () => { get: (key: string) => { get: (key: string) => WpMediaSelection } }
}

declare global {
  interface Window {
    wp?: {
      media: (options: Record<string, unknown>) => WpMediaFrame
    }
    flexTopBarConfig?: {
      iconColumnMedia?: IconColumnMediaLimits
    }
  }
}

const defaultLimits: IconColumnMediaLimits = {
  maxWidth: ICON_COLUMN_MAX_WIDTH,
  maxHeight: ICON_COLUMN_MAX_HEIGHT,
  maxFileBytes: ICON_COLUMN_MAX_FILE_BYTES,
  allowedMimeTypes: [...ICON_COLUMN_ALLOWED_MIME_TYPES],
  displaySizePx: 24,
}

export function getIconColumnMediaLimits(): IconColumnMediaLimits {
  return window.flexTopBarConfig?.iconColumnMedia ?? defaultLimits
}

export function validateIconMediaAttachment(
  attachment: WpMediaAttachment,
  limits: IconColumnMediaLimits = getIconColumnMediaLimits(),
): string | null {
  const mime = attachment.mime ?? ''
  if (mime && !limits.allowedMimeTypes.includes(mime)) {
    return `Allowed formats: PNG, JPG, GIF, WebP, SVG.`
  }

  const bytes = attachment.filesizeInBytes ?? 0
  if (bytes > limits.maxFileBytes) {
    const maxKb = Math.round(limits.maxFileBytes / 1024)
    return `File must be ${maxKb} KB or smaller.`
  }

  if (mime !== 'image/svg+xml') {
    const width = attachment.width ?? 0
    const height = attachment.height ?? 0
    if (width > limits.maxWidth || height > limits.maxHeight) {
      return `Image must be at most ${limits.maxWidth}×${limits.maxHeight} px.`
    }
  }

  return null
}

export function useWpMedia() {
  const limits = computed(() => getIconColumnMediaLimits())

  function openIconPicker(onSelect: (payload: { id: number; url: string }) => void): void {
    if (!window.wp?.media) {
      return
    }

    const frame = window.wp.media({
      title: 'Select icon',
      button: { text: 'Use this icon' },
      library: { type: 'image' },
      multiple: false,
    })

    frame.on('select', () => {
      const selection = frame.state().get('selection')
      const json = selection.first().toJSON() as WpMediaAttachment
      const error = validateIconMediaAttachment(json, limits.value)
      if (error) {
        window.alert(error)
        return
      }
      onSelect({
        id: json.id,
        url: json.url,
      })
    })

    frame.open()
  }

  return {
    limits,
    openIconPicker,
    validateIconMediaAttachment,
  }
}
