<template>
  <div class="top-bar-container top-bar-container--preview">
    <div
      :id="`top-bar-${bar.id}-preview`"
      class="top-bar top-bar--preview"
      :style="getBarStyles(bar)"
      role="banner"
      :data-top-bar-id="bar.id"
      :data-top-bar-position="bar.position"
      :data-top-bar-effect="getBarEffect(bar)"
      :data-top-bar-hide-on-scroll="bar.hide_on_scroll ? '1' : '0'"
    >
      <div class="top-bar__inner">
        <div class="top-bar__columns">
          <div
            v-for="column in getColumns(bar)"
            :key="column.id"
            class="top-bar__column"
            :class="{ 'top-bar__column--mobile-hidden': !column.messages_mobile_visible }"
            :style="getColumnStyle(column)"
          >
            <template v-if="column.type === 'text'">
              <div class="top-bar-text-column" :style="getTextAlignStyle(column)">
                <template v-if="column.effect === 'none' || column.messages.length <= 1">
                  {{ getConcatenatedMessage(column) }}
                </template>
                <template v-else>
                  <transition :name="getTransitionName(column.effect)" mode="out-in">
                    <div :key="currentMessageIndex[columnKey(bar.id, column.id)] ?? 0">
                      {{ getCurrentMessage(bar, column) }}
                    </div>
                  </transition>
                </template>
              </div>
            </template>
            <template v-else-if="column.type === 'social'">
              <div
                class="top-bar-social-column"
                :class="socialColumnClass(column)"
                :style="{ ...socialColumnStyle(column), ...getFlexAlignStyle(column) }"
              >
                <a
                  v-for="(link, i) in column.links.filter(l => l.url.trim() !== '')"
                  :key="`${column.id}-link-${i}`"
                  class="top-bar-social-column__link"
                  :href="safeHref(link.url)"
                  target="_blank"
                  :title="socialPlatformLabel(link.platform)"
                  rel="noopener noreferrer"
                >
                  <span
                    class="top-bar-icon top-bar-icon--social"
                    :style="iconStyleFromClass(socialIconClass(link.platform), column.icon_style, usesIconColors(column.icon_style) ? column.icon_color : '')"
                    aria-hidden="true"
                  ></span>
                </a>
              </div>
            </template>
            <template v-else-if="column.type === 'contact'">
              <div
                class="top-bar-contact-column"
                :class="contactColumnClass(column)"
                :style="{ ...contactColumnStyle(column), ...getFlexAlignStyle(column) }"
              >
                <template
                  v-for="(entry, i) in column.contacts.filter(e => e.value.trim() !== '')"
                  :key="`${column.id}-c-${i}`"
                >
                  <a
                    v-if="contactHref(entry.kind, entry.value) !== '#'"
                    class="top-bar-contact-column__link"
                    :href="contactHref(entry.kind, entry.value)"
                    :title="`${contactLabel(entry.kind)}`"
                    :target="entry.kind === 'website' || entry.kind === 'location' || entry.kind === 'support' || entry.kind === 'chat' ? '_blank' : undefined"
                    :rel="entry.kind === 'website' || entry.kind === 'location' || entry.kind === 'support' || entry.kind === 'chat' ? 'noopener noreferrer' : undefined"
                  >
                    <span
                      class="top-bar-icon top-bar-icon--contact"
                      :style="iconStyleFromClass(contactIconClass(entry.kind), column.icon_style, usesIconColors(column.icon_style) ? column.icon_color : '')"
                      aria-hidden="true"
                    ></span>
                  </a>
                  <span v-else class="top-bar-contact-column__text">
                    <span
                      class="top-bar-icon top-bar-icon--contact"
                      :style="iconStyleFromClass(contactIconClass(entry.kind), column.icon_style, usesIconColors(column.icon_style) ? column.icon_color : '')"
                      aria-hidden="true"
                    ></span>
                  </span>
                </template>
              </div>
            </template>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import type {
  Bar,
  BarColumn,
  ContactBarColumn,
  ContactKind,
  SocialBarColumn,
  SocialPlatform,
  TextBarColumn,
} from '@/types'
import { CONTACT_ICONS_BY_KIND, ICONS, SOCIAL_ICONS_BY_PLATFORM } from '@/constants/icons'

const props = defineProps<{
  bar: Bar
}>()

const currentMessageIndex = ref<Record<string, number>>({})
const intervals = ref<Record<string, number>>({})

// Keep preview stable and always visible inside admin.
const bar = computed<Bar>(() => ({
  ...props.bar,
  visible: true,
  scheduled_enabled: false,
  hide_on_scroll: false,
  position: 'top',
}))

function getColumns(b: Bar): BarColumn[] {
  if (b.columns?.length) return b.columns
  return [
    {
      id: `${b.id}-legacy`,
      type: 'text',
      effect: b.effect,
      messages: b.messages,
      size_percent: 100,
      content_position: 'center',
      messages_mobile_visible: b.messages_mobile_visible,
    },
  ]
}

function getBarEffect(b: Bar): string {
  const textCol = getColumns(b).find((c): c is TextBarColumn => c.type === 'text')
  return textCol?.effect ?? b.effect
}

function columnKey(barId: string, columnId: string): string {
  return `${barId}:${columnId}`
}

function getColumnStyle(column: BarColumn) {
  return { flex: `0 0 ${column.size_percent}%`, maxWidth: `${column.size_percent}%` }
}

function getFlexAlignStyle(column: BarColumn): Record<string, string> {
  const justifyContent =
    column.content_position === 'left'
      ? 'flex-start'
      : column.content_position === 'right'
        ? 'flex-end'
        : 'center'
  return { justifyContent }
}

function getTextAlignStyle(column: Extract<BarColumn, { type: 'text' }>): Record<string, string> {
  const textAlign =
    column.content_position === 'left' ? 'left' : column.content_position === 'right' ? 'right' : 'center'
  return { textAlign }
}

function getBarStyles(b: Bar) {
  const styles: Record<string, string> = { backgroundColor: b.bg_color || '#1d2327' }
  if (b.frame_width > 0 && b.frame_color) {
    styles.border = `${b.frame_width}px solid ${b.frame_color}`
  }
  return styles
}

function getConcatenatedMessage(column: TextBarColumn): string {
  return (column.messages ?? []).map(m => (m ?? '').trim()).filter(Boolean).join(' ')
}

function getCurrentMessage(b: Bar, column: TextBarColumn): string {
  const key = columnKey(b.id, column.id)
  const idx = currentMessageIndex.value[key] ?? 0
  return (column.messages?.[idx] ?? '').trim()
}

function safeHref(url: string): string {
  const trimmed = (url ?? '').trim()
  if (!trimmed) return '#'
  if (/^https?:\/\//i.test(trimmed) || trimmed.startsWith('mailto:') || trimmed.startsWith('tel:')) return trimmed
  return `https://${trimmed}`
}

function usesIconColors(style: SocialBarColumn['icon_style']): boolean {
  return style === 'rounded' || style === 'square'
}

function socialColumnClass(column: SocialBarColumn): string {
  return `top-bar-social-column--${column.icon_style}`
}

function socialColumnStyle(column: SocialBarColumn): Record<string, string> {
  if (!usesIconColors(column.icon_style)) return {}
  return {
    '--top-bar-social-bg': column.background_color,
    '--top-bar-social-fg': column.icon_color,
  }
}

function socialPlatformLabel(platform: SocialPlatform | ''): string {
  const labels: Record<SocialPlatform, string> = {
    facebook: 'Facebook',
    twitterX: 'TwitterX',
    instagram: 'Instagram',
    linkedin: 'Linkedin',
    google: 'Google',
    youtube: 'Youtube',
    apple: 'Apple',
    snapchat: 'Snapchat',
    pinterest: 'Pinterest',
    medium: 'Medium',
    github: 'Github',
    threads: 'Threads',
    whatsapp: 'Whatsapp',
    figma: 'Figma',
    dribbble: 'Dribbble',
    reddit: 'Reddit',
    discord: 'Discord',
    tiktok: 'Tiktok',
    tumblr: 'Tumblr',
    telegram: 'Telegram',
    bluesky: 'Bluesky',
    signal: 'Signal',
    vk: 'Vk',
    spotify: 'Spotify',
    twitch: 'Twitch',
    messenger: 'Messenger',
  }
  return platform ? labels[platform] ?? platform : 'Link'
}

function socialIconClass(platform: SocialPlatform | ''): string {
  return platform ? SOCIAL_ICONS_BY_PLATFORM[platform] ?? '' : ''
}

function contactColumnClass(column: ContactBarColumn): string {
  return `top-bar-contact-column--${column.icon_style}`
}

function contactColumnStyle(column: ContactBarColumn): Record<string, string> {
  if (!usesIconColors(column.icon_style)) return {}
  return {
    '--top-bar-contact-bg': column.background_color,
    '--top-bar-contact-fg': column.icon_color,
  }
}

function contactLabel(kind: ContactKind | ''): string {
  const labels: Record<ContactKind, string> = {
    email: 'E-mail address',
    phone: 'Phone',
    mobile: 'Mobile phone',
    website: 'WWW',
    location: 'Location',
    support: 'Support',
    chat: 'Chat',
  }
  return kind ? labels[kind] ?? kind : ''
}

function contactIconClass(kind: ContactKind | ''): string {
  return kind ? CONTACT_ICONS_BY_KIND[kind] ?? '' : ''
}

function contactHref(kind: ContactKind | '', value: string): string {
  const v = (value ?? '').trim()
  if (!kind || !v) return '#'
  if (kind === 'email') return `mailto:${encodeURIComponent(v).replace(/%40/g, '@')}`
  if (kind === 'phone' || kind === 'mobile') return `tel:${v.replace(/\s/g, '')}`
  if (kind === 'location') return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(v)}`
  if (kind === 'website' || kind === 'support' || kind === 'chat') return safeHref(v)
  return '#'
}

function iconStyleFromClass(iconClass: string, style: SocialBarColumn['icon_style'], color: string): Record<string, string> {
  const svg = ICONS[iconClass]
  if (!svg) return {}
  if (style === 'color') {
    return {
      backgroundImage: `url("${svg}")`,
      backgroundColor: 'transparent',
      backgroundRepeat: 'no-repeat',
      backgroundPosition: 'center',
      backgroundSize: 'contain',
    }
  }
  const forced = style === 'black' ? '#000000' : style === 'white' ? '#ffffff' : ''
  return {
    backgroundColor: forced || color || 'currentColor',
    WebkitMaskImage: `url("${svg}")`,
    maskImage: `url("${svg}")`,
  }
}

function getTransitionName(effect: string): string {
  if (effect === 'slider') return 'slide'
  if (effect === 'fadein') return 'fade'
  if (effect === 'blink') return 'blink'
  return 'fade'
}

function startMessageRotation(b: Bar, column: TextBarColumn) {
  const key = columnKey(b.id, column.id)
  intervals.value[key] = window.setInterval(() => {
    const cur = currentMessageIndex.value[key] ?? 0
    currentMessageIndex.value[key] = (cur + 1) % column.messages.length
  }, 5000)
}

function stopMessageRotation(key: string) {
  if (intervals.value[key]) {
    clearInterval(intervals.value[key])
    delete intervals.value[key]
  }
}

function initRotations() {
  Object.keys(intervals.value).forEach(stopMessageRotation)
  currentMessageIndex.value = {}

  getColumns(bar.value).forEach(col => {
    if (col.type === 'text' && col.effect !== 'none' && col.messages.length > 1) {
      const key = columnKey(bar.value.id, col.id)
      currentMessageIndex.value[key] = 0
      startMessageRotation(bar.value, col)
    }
  })
}

onMounted(() => {
  initRotations()
})

onUnmounted(() => {
  Object.keys(intervals.value).forEach(stopMessageRotation)
})

watch(
  () => JSON.stringify(props.bar.columns ?? null),
  () => initRotations()
)
</script>

<style scoped>
/* Ensure preview does NOT pin to viewport. */
.top-bar-container--preview {
  position: relative;
  z-index: auto;
}

.top-bar--preview {
  position: relative !important;
  top: auto !important;
  bottom: auto !important;
  left: auto !important;
  right: auto !important;
  z-index: auto !important;
}

/* Minimal layout styles (admin does not load frontend.css). */
.top-bar {
  width: 100%;
  padding: 12px 20px;
  box-sizing: border-box;
  border-radius: 6px;
}

.top-bar__inner {
  max-width: 1200px;
  margin: 0 auto;
  color: #fff;
  font-size: 16px;
  line-height: 1.5;
}

.top-bar__columns {
  display: flex;
  flex-wrap: wrap;
  width: 100%;
  justify-content: space-between;
  align-items: center;
  gap: 0;
  text-align: center;
}

.top-bar__column {
  box-sizing: border-box;
  padding: 0 8px;
  min-width: 0;
  margin: 4px 0;
  display: flex;
}

.top-bar-text-column {
  width: 100%;
}

@media screen and (max-width: 782px) {
  .top-bar__column--mobile-hidden {
    display: none !important;
  }
}

.top-bar-social-column {
  display: flex;
  width: 100%;
  flex-wrap: wrap;
  gap: 8px;
  justify-content: center;
  align-items: center;
  border-radius: 6px;
  box-sizing: border-box;
}

.top-bar-social-column__link {
  display: inline-flex;
  align-items: center;
  margin: 0 2px;
  padding: 4px;
  text-decoration: none;
  font-weight: 600;
  font-size: 16px;
  line-height: 1.4;
}

.top-bar-social-column--rounded .top-bar-social-column__link,
.top-bar-social-column--square .top-bar-social-column__link {
  background: var(--top-bar-social-bg, transparent);
  color: var(--top-bar-social-fg, currentColor);
}

.top-bar-social-column--rounded .top-bar-social-column__link {
  border-radius: 100%;
}

.top-bar-social-column--square .top-bar-social-column__link {
  border-radius: 4px;
}

.top-bar-contact-column {
  display: flex;
  width: 100%;
  align-items: center;
  justify-content: center;
  box-sizing: border-box;
  text-align: center;
}

.top-bar-contact-column__link,
.top-bar-contact-column__text {
  display: inline-flex;
  align-items: center;
  margin: 0 4px;
  font-size: 16px;
  padding: 2px 6px;
  box-sizing: border-box;
}

.top-bar-contact-column--rounded .top-bar-contact-column__link,
.top-bar-contact-column--rounded .top-bar-contact-column__text,
.top-bar-contact-column--square .top-bar-contact-column__link,
.top-bar-contact-column--square .top-bar-contact-column__text {
  background: var(--top-bar-contact-bg, transparent);
  color: var(--top-bar-contact-fg, currentColor);
}

.top-bar-contact-column__link {
  width: 32px;
  height: 32px;
  max-width: 32px;
  max-height: 32px;
  display: flex;
  justify-content: center;
  align-items: center;
}

.top-bar-contact-column--rounded .top-bar-contact-column__link,
.top-bar-contact-column--rounded .top-bar-contact-column__text {
  border-radius: 100%;
}

.top-bar-contact-column--square .top-bar-contact-column__link,
.top-bar-contact-column--square .top-bar-contact-column__text {
  border-radius: 4px;
}

.top-bar-icon {
  display: inline-block;
  width: 24px;
  height: 24px;
  background-color: currentColor;
  -webkit-mask-repeat: no-repeat;
  mask-repeat: no-repeat;
  -webkit-mask-size: contain;
  mask-size: auto 80%;
  -webkit-mask-position: center;
  mask-position: center;
}
</style>

