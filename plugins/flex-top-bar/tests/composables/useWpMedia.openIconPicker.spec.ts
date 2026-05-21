import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { useWpMedia, validateIconMediaAttachment } from '@/composables/useWpMedia'

describe('useWpMedia openIconPicker', () => {
  const open = vi.fn()
  const state = vi.fn(() => frameState)
  const on = vi.fn((event: string, cb: () => void) => {
    if (event === 'select') {
      frameSelectCallback = cb
    }
    return { open, on, state }
  })
  const frameState = {
    get: (key: string) => {
      if (key !== 'selection') {
        return {}
      }
      return {
        first: () => ({
          toJSON: () => frameAttachment,
        }),
      }
    },
  }
  let frameSelectCallback: (() => void) | null = null
  let frameAttachment: Record<string, unknown> = {}

  beforeEach(() => {
    frameSelectCallback = null
    frameAttachment = {
      id: 5,
      url: 'http://example.test/uploads/icon.png',
      width: 80,
      height: 80,
      filesizeInBytes: 400,
      mime: 'image/png',
    }
    open.mockClear()
    on.mockClear()
    window.wp = {
      media: vi.fn(() => ({ open, on, state })),
    }
    window.flexTopBarConfig = {
      apiRoot: '/wp-json/flex-top-bar/v1',
      nonce: 'nonce',
      i18n: {},
      iconColumnMedia: {
        maxWidth: 64,
        maxHeight: 64,
        maxFileBytes: 512 * 1024,
        allowedMimeTypes: ['image/png', 'image/jpeg', 'image/svg+xml'],
        displaySizePx: 24,
      },
    }
    window.alert = vi.fn()
  })

  afterEach(() => {
    delete window.wp
    delete window.flexTopBarConfig
    delete (window as Window & { alert?: unknown }).alert
  })

  it('calls onSelect with attachment id and url when valid', () => {
    const onSelect = vi.fn()
    const { openIconPicker } = useWpMedia()

    openIconPicker(onSelect)
    expect(open).toHaveBeenCalled()
    frameSelectCallback?.()

    expect(onSelect).toHaveBeenCalledWith({
      id: 5,
      url: 'http://example.test/uploads/icon.png',
    })
    expect(window.alert).toHaveBeenCalledTimes(0)
  })

  it('alerts and skips onSelect when mime is not allowed', () => {
    frameAttachment = { ...frameAttachment, mime: 'application/pdf' }
    const onSelect = vi.fn()
    const { openIconPicker } = useWpMedia()

    openIconPicker(onSelect)
    frameSelectCallback?.()

    expect(onSelect).not.toHaveBeenCalled()
    expect(window.alert).toHaveBeenCalledTimes(1)
  })

  it('does nothing when wp.media is unavailable', () => {
    delete window.wp
    const onSelect = vi.fn()
    const { openIconPicker } = useWpMedia()

    openIconPicker(onSelect)

    expect(onSelect).not.toHaveBeenCalled()
  })
})

describe('validateIconMediaAttachment mime guard', () => {
  it('rejects disallowed mime types', () => {
    const result = validateIconMediaAttachment(
      {
        id: 1,
        url: 'http://example.com/file.pdf',
        mime: 'application/pdf',
      },
      {
        maxWidth: 64,
        maxHeight: 64,
        maxFileBytes: 1024,
        allowedMimeTypes: ['image/png'],
        displaySizePx: 24,
      },
    )
    expect(result).toContain('Allowed formats')
  })
})
