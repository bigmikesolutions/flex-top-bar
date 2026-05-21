import { describe, it, expect } from 'vitest'
import { validateIconMediaAttachment } from '@/composables/useWpMedia'

const limits = {
  maxWidth: 64,
  maxHeight: 64,
  maxFileBytes: 1024,
  allowedMimeTypes: ['image/png', 'image/jpeg', 'image/svg+xml'],
  displaySizePx: 24,
}

describe('validateIconMediaAttachment', () => {
  it('returns null for valid raster attachment', () => {
    const result = validateIconMediaAttachment(
      {
        id: 1,
        url: 'https://example.com/icon.png',
        width: 32,
        height: 32,
        filesizeInBytes: 512,
        mime: 'image/png',
      },
      limits,
    )
    expect(result).toBeNull()
  })

  it('rejects oversized dimensions', () => {
    const result = validateIconMediaAttachment(
      {
        id: 1,
        url: 'https://example.com/icon.png',
        width: 128,
        height: 32,
        filesizeInBytes: 512,
        mime: 'image/png',
      },
      limits,
    )
    expect(result).toContain('64')
  })

  it('rejects oversized file', () => {
    const result = validateIconMediaAttachment(
      {
        id: 1,
        url: 'https://example.com/icon.png',
        width: 32,
        height: 32,
        filesizeInBytes: 2048,
        mime: 'image/png',
      },
      limits,
    )
    expect(result).toContain('KB')
  })

  it('skips dimension check for svg', () => {
    const result = validateIconMediaAttachment(
      {
        id: 1,
        url: 'https://example.com/icon.svg',
        width: 200,
        height: 200,
        filesizeInBytes: 512,
        mime: 'image/svg+xml',
      },
      limits,
    )
    expect(result).toBeNull()
  })
})
