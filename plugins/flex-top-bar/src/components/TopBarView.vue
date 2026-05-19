<template>
  <div
    v-if="visibleBars.length > 0"
    :class="['top-bar-container', { 'top-bar-container--preview': !!props.preview }]"
  >
    <div
      v-for="bar in visibleBars"
      :key="bar.id"
      :id="`top-bar-${bar.id}`"
      :class="['top-bar', props.preview ? 'top-bar--preview' : `top-bar--${bar.position}`]"
      :style="getBarStyles(bar)"
      role="banner"
      :data-top-bar-id="bar.id"
      :data-top-bar-position="bar.position"
      :data-top-bar-effect="getBarEffect(bar)"
      :data-top-bar-hide-on-scroll="bar.hide_on_scroll ? '1' : '0'"
    >
    <div class="top-bar__inner">
        <div class="top-bar__columns" >
          <div
            v-for="column in getColumns(bar)"
            :key="column.id"
            class="top-bar__column"
            :class="{ 'top-bar__column--mobile-hidden': !column.messages_mobile_visible }"
            :style="getColumnStyle(column)"
          >
            <template v-if="column.type === 'text'">
              <div class="top-bar-text-column" :style="getTextAlignStyle(column)">
                <template v-if="column.effect === 'none'">
                  {{ getFirstMessage(column) }}
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
                    :style="iconStyleFromClass(socialIconClass(link.platform, column.icon_style), column.icon_style, usesIconColors(column.icon_style) ? column.icon_color : '')"
                    aria-hidden="true"
                  ></span>
                  <!-- {{ socialPlatformLabel(link.platform) }} -->
                </a>
              </div>
            </template>
            <template v-else-if="column.type === 'contact'">
              <div
                class="top-bar-contact-column"
                :class="contactColumnClass(column)"
                :style="{ ...contactColumnStyle(column), ...getFlexAlignStyle(column) }"
              >
                <template v-for="(entry, i) in column.contacts.filter(e => e.value.trim() !== '')" :key="`${column.id}-c-${i}`">
                  <a
                    v-if="contactHref(entry.kind, entry.value) !== '#'"
                    class="top-bar-contact-column__link"
                    :href="contactHref(entry.kind, entry.value)"
                    :title="`${contactLabel(entry.kind)}`"
                    :target="entry.kind === 'website' || entry.kind === 'location'  || entry.kind === 'support' || entry.kind === 'chat' ? '_blank' : undefined"
                    :rel="entry.kind === 'website' || entry.kind === 'location'  || entry.kind === 'support' || entry.kind === 'chat' ? 'noopener noreferrer' : undefined"
                  >
                    <span
                      class="top-bar-icon top-bar-icon--contact"
                      :style="iconStyleFromClass(contactIconClass(entry.kind), column.icon_style, usesIconColors(column.icon_style) ? column.icon_color : '')"
                      aria-hidden="true"
                    ></span>
                    <!-- {{ contactDisplayLabel(entry.kind, entry.value) }} -->
                  </a>
                  <span
                    v-else
                    class="top-bar-contact-column__text"
                  >
                    <span
                      class="top-bar-icon top-bar-icon--contact"
                      :style="iconStyleFromClass(contactIconClass(entry.kind), column.icon_style, usesIconColors(column.icon_style) ? column.icon_color : '')"
                      aria-hidden="true"
                    ></span>
                    <!-- {{ contactDisplayLabel(entry.kind, entry.value) }} -->
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
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import type {
  Bar,
  BarColumn,
  ContactBarColumn,
  ContactKind,
  SocialBarColumn,
  SocialPlatform,
  TextBarColumn,
} from '@/types'
import {
  CONTACT_ICONS_BY_KIND,
  ICONS,
  SOCIAL_ICONS_BY_PLATFORM,
} from '@/constants/icons'
import { isWithinScheduleWindowForVisitor } from '@/utils/scheduleDateTime'

const props = defineProps<{
  barsOverride?: Bar[]
  preview?: boolean
}>()

const bars = ref<Bar[]>([])
const currentMessageIndex = ref<Record<string, number>>({})
const intervals = ref<Record<string, number>>({})
const lastScrollY = ref(0)
const hideScrollBars = ref<Set<string>>(new Set())

function getColumns(bar: Bar): BarColumn[] {
  if (bar.columns?.length) {
    return bar.columns
  }
  return [
    {
      id: `${bar.id}-legacy`,
      type: 'text',
      effect: bar.effect,
      messages: bar.messages,
      size_percent: 100,
      content_position: 'center',
      messages_mobile_visible: bar.messages_mobile_visible,
    },
  ]
}

function getBarEffect(bar: Bar): string {
  const textCol = getColumns(bar).find((c): c is TextBarColumn => c.type === 'text')
  return textCol?.effect ?? bar.effect
}

function columnKey(barId: string, columnId: string): string {
  return `${barId}:${columnId}`
}

function getColumnStyle(column: BarColumn) {
  return {
    flex: `0 0 ${column.size_percent}%`,
    maxWidth: `${column.size_percent}%`,
  }
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
  const textAlign = column.content_position === 'left' ? 'left' : column.content_position === 'right' ? 'right' : 'center'
  return { textAlign }
}

// Fetch bars from public API endpoint
async function fetchBars() {
  try {
    // Use public endpoint (no auth required for frontend)
    // Using query string format for compatibility with sites without pretty permalinks
    const response = await fetch('/?rest_route=/flex-top-bar/v1/public-bars')
    if (!response.ok) {
      throw new Error('Failed to fetch bars')
    }
    bars.value = await response.json()

    // Initialize message rotation for columns with effects
    bars.value.forEach(bar => {
      getColumns(bar).forEach(column => {
        if (
          column.type === 'text' &&
          column.effect !== 'none' &&
          column.messages.length > 1
        ) {
          const key = columnKey(bar.id, column.id)
          currentMessageIndex.value[key] = 0
          startMessageRotation(bar, column)
        }
      })
    })
  } catch (error) {
    console.error('Failed to load top bars:', error)
  }
}

function stopAllRotations() {
  Object.keys(intervals.value).forEach(stopMessageRotation)
  currentMessageIndex.value = {}
  intervals.value = {}
}

function initRotations(nextBars: Bar[]) {
  stopAllRotations()
  nextBars.forEach(b => {
    getColumns(b).forEach(column => {
      if (column.type === 'text' && column.effect !== 'none' && column.messages.length > 1) {
        const key = columnKey(b.id, column.id)
        currentMessageIndex.value[key] = 0
        startMessageRotation(b, column)
      }
    })
  })
}

// Filter bars by visibility and scheduling (except in admin preview mode).
const visibleBars = computed(() => {
  if (props.preview) {
    // In admin preview we always render the provided bar(s), even if invisible/scheduled.
    return bars.value
  }
  return bars.value.filter(bar => {
    // Check if visible
    if (!bar.visible) return false

    // Public site: evaluate schedule in the visitor's browser timezone.
    if (bar.scheduled_enabled && bar.scheduled_from_datetime && bar.scheduled_to_datetime) {
      if (!isWithinScheduleWindowForVisitor(
        bar.scheduled_from_datetime,
        bar.scheduled_to_datetime,
        bar.scheduled_timezone,
      )) {
        return false
      }
    }

    // Check hide on scroll
    if (bar.hide_on_scroll && hideScrollBars.value.has(bar.id)) {
      return false
    }

    return true
  })
})

function getBarStyles(bar: Bar) {
  const styles: Record<string, string> = {
    backgroundColor: bar.bg_color || '#1d2327',
  }

  if (bar.frame_width > 0 && bar.frame_color) {
    styles.border = `${bar.frame_width}px solid ${bar.frame_color}`
  }

  return styles
}

function getFirstMessage(column: TextBarColumn): string {
  return column.messages[0] ?? ''
}

function getCurrentMessage(bar: Bar, column: TextBarColumn): string {
  const key = columnKey(bar.id, column.id)
  const index = currentMessageIndex.value[key] || 0
  return column.messages[index] || ''
}

function socialColumnClass(column: SocialBarColumn): string {
  return `top-bar-social-column--${column.icon_style}`
}

function usesIconColors(style: SocialBarColumn['icon_style']): boolean {
  return style === 'rounded' || style === 'square'
}

function socialColumnStyle(column: SocialBarColumn): Record<string, string> {
  if (!usesIconColors(column.icon_style)) {
    return {}
  }
  return {
    // Use CSS vars so we can style per-link pills (rounded vs square) visibly.
    '--top-bar-social-bg': column.background_color,
    '--top-bar-social-fg': column.icon_color,
    '--top-bar-social-bw': `${column.icon_border_width ?? 0}px`,
    '--top-bar-social-bc': column.icon_border_color || 'transparent',
  }
}

function socialPlatformLabel(platform: SocialPlatform | ''): string {
  if (!platform) {
    return 'Link'
  }
  const map: Record<SocialPlatform, string> = {
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
  return map[platform] ?? platform
}

function socialIconClass(platform: SocialPlatform | '', style: SocialBarColumn['icon_style']): string {
  if (!platform) {
    return ''
  }
  if (platform === 'instagram') {
    return style === 'color' ? 'instagramColor' : 'instagramMono'
  }
  if (platform === 'dribbble') {
    return style === 'color' ? 'dribbbleColor' : 'dribbbleMono'
  }
  return SOCIAL_ICONS_BY_PLATFORM[platform] ?? ''
}

function safeHref(url: string): string {
  const t = url.trim()
  if (!t) {
    return '#'
  }
  if (/^https?:\/\//i.test(t)) {
    return t
  }
  if (t.startsWith('mailto:') || t.startsWith('tel:')) {
    return t
  }
  return `https://${t}`
}

function contactColumnClass(column: ContactBarColumn): string {
  return `top-bar-contact-column--${column.icon_style}`
}

function contactColumnStyle(column: ContactBarColumn): Record<string, string> {
  if (!usesIconColors(column.icon_style)) {
    return {}
  }
  return {
    // Use CSS vars so we can style per-entry pills (rounded vs square) visibly.
    '--top-bar-contact-bg': column.background_color,
    '--top-bar-contact-fg': column.icon_color,
    '--top-bar-contact-bw': `${column.icon_border_width ?? 0}px`,
    '--top-bar-contact-bc': column.icon_border_color || 'transparent',
  }
}

function contactHref(kind: ContactKind | '', value: string): string {
  const v = value.trim()
  if (!v || !kind) {
    return '#'
  }
  if (kind === 'email') {
    return `mailto:${encodeURIComponent(v)}`
  }
  if (kind === 'phone' || kind === 'mobile') {
    return `tel:${v.replace(/\s/g, '')}`
  }
  if (kind === 'website') {
    return safeHref(v)
  }
  if (kind === 'location') {
    const q = encodeURIComponent(v)
    return `https://www.google.com/maps/search/?api=1&query=${q}`
  }
  if (kind === 'support') {
    return safeHref(v)
  }
  if (kind === 'chat') {
    return safeHref(v)
  }
  
  return '#'
}

function contactLabel(kind: ContactKind | ''): string {
  if (!kind) {
    return ''
  }
  const map: Record<ContactKind, string> = {
    email: 'E-mail address',
    phone: 'Phone',
    mobile: 'Mobile phone',
    website: 'WWW',
    location: 'Locaction',
    support: 'Support',
    chat: 'Chat',
  }

  return map[kind] ?? kind
}

function contactIconClass(kind: ContactKind | ''): string {
  if (!kind) {
    return ''
  }
  return CONTACT_ICONS_BY_KIND[kind] ?? ''
}

function iconStyleFromClass(iconClass: string, style: SocialBarColumn['icon_style'], color: string): Record<string, string> {
  if (!iconClass) {
    return {}
  }
  const svg = ICONS[iconClass]
  if (!svg) {
    return {}
  }
  // "color" means: show the original SVG colors (no mask/currentColor).
  if (style === 'color') {
    return {
      backgroundImage: `url("${svg}")`,
      backgroundColor: 'transparent',
      backgroundRepeat: 'no-repeat',
      backgroundPosition: 'center',
      backgroundSize: 'contain',
    }
  }
  // "black"/"white" should be fixed and not inherit text color.
  const forced =
    style === 'black' ? '#000000' : style === 'white' ? '#ffffff' : ''
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

function startMessageRotation(bar: Bar, column: TextBarColumn) {
  const duration = 5000 // 5 seconds per message
  const key = columnKey(bar.id, column.id)

  intervals.value[key] = window.setInterval(() => {
    const current = currentMessageIndex.value[key] || 0
    currentMessageIndex.value[key] = (current + 1) % column.messages.length
  }, duration)
}

function stopMessageRotation(key: string) {
  if (intervals.value[key]) {
    clearInterval(intervals.value[key])
    delete intervals.value[key]
  }
}

// Handle scroll hide behavior
function handleScroll() {
  const currentScrollY = window.scrollY
  const threshold = 30

  bars.value.forEach(bar => {
    if (bar.hide_on_scroll) {
      if (currentScrollY > threshold) {
        hideScrollBars.value.add(bar.id)
      } else {
        hideScrollBars.value.delete(bar.id)
      }
    }
  })

  lastScrollY.value = currentScrollY
}

onMounted(() => {
  if (props.barsOverride?.length) {
    bars.value = props.barsOverride
    initRotations(bars.value)
  } else {
    fetchBars()
  }
  window.addEventListener('scroll', handleScroll, { passive: true })
})

onUnmounted(() => {
  stopAllRotations()
  window.removeEventListener('scroll', handleScroll)
})

watch(
  () => props.barsOverride,
  next => {
    if (next?.length) {
      bars.value = next
      initRotations(bars.value)
    }
  }
)
</script>

<style scoped>
.top-bar-container {
  position: relative;
  z-index: 99998;
}

.top-bar {
  width: 100%;
  padding: 12px 20px;
  box-sizing: border-box;
  transition: transform 0.3s ease;
  z-index: 99998;
}

.top-bar--top {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
}

/* Adjust for WordPress admin bar (32px on desktop, 46px on mobile) */
body.admin-bar .top-bar--top {
  top: 32px;
}

@media screen and (max-width: 782px) {
  body.admin-bar .top-bar--top {
    top: 46px;
  }
}

.top-bar--bottom {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
}

/* Admin preview must not be fixed to viewport */
.top-bar--preview {
  position: relative !important;
  top: auto !important;
  bottom: auto !important;
  left: auto !important;
  right: auto !important;
  z-index: auto !important;
}

.top-bar-container--preview {
  position: relative;
  z-index: auto;
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
  display:flex;
}

.top-bar-text-column {
  width: 100%;
  overflow: hidden;
}

/* Transitions for effects */
.slide-enter-active,
.slide-leave-active {
  transition: transform 0.5s ease;
}

.slide-enter-from {
  transform: translateX(100%);
}

.slide-leave-to {
  transform: translateX(-100%);
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.5s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.blink-enter-active,
.blink-leave-active {
  transition: opacity 0.3s ease;
}

.blink-enter-from,
.blink-leave-to {
  opacity: 0;
}

/* Mobile visibility per column */
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
  margin:0 2px;
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

.top-bar-social-column--icon_only .top-bar-social-column__link {
  padding: 0 8px;
}

.top-bar-social-column--rounded .top-bar-social-column__link span,
.top-bar-social-column--square .top-bar-social-column__link span{
  mask-size:65%
}

.top-bar-social-column--rounded .top-bar-social-column__link,
.top-bar-social-column--square .top-bar-social-column__link {
  border: var(--top-bar-social-bw, 0px) solid var(--top-bar-social-bc, transparent);
}

/* "color" icons render their native SVG colors (no mask). */

.top-bar-contact-column {
  display: flex;
  width: 100%;
  align-items: center;
  justify-content: center;
  /* padding: 6px 10px; */
  box-sizing: border-box;
  text-align: center;
}

.top-bar-contact-column__link,
.top-bar-contact-column__text {
  display: inline-flex;
  align-items: center;
  margin:0 4px;
  font-size: 16px;
  /* line-height: 1.4; */
  /* word-break: break-word; */
  padding: 2px 6px;
  box-sizing:border-box;
}

.top-bar-contact-column--rounded .top-bar-contact-column__link,
.top-bar-contact-column--rounded .top-bar-contact-column__text,
.top-bar-contact-column--square .top-bar-contact-column__link,
.top-bar-contact-column--square .top-bar-contact-column__text {
  background: var(--top-bar-contact-bg, transparent);
  color: var(--top-bar-contact-fg, currentColor);
  border: var(--top-bar-contact-bw, 0px) solid var(--top-bar-contact-bc, transparent);
}

.top-bar-contact-column__link {
  width: 32px;
  height:32px;
  max-width:32px;
  max-height:32px;
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

.top-bar-contact-column--icon_only .top-bar-contact-column__link,
.top-bar-contact-column--icon_only .top-bar-contact-column__text {
  gap: 0;
  font-size: 0;
  line-height: 0;
  text-decoration: none;
}

.top-bar-contact-column--icon_only .top-bar-icon {
  width: 22px;
  height: 22px;
  flex-basis: 22px;
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

.top-bar-social-column--icon_only .top-bar-social-column__link {
  font-size: 0;
  line-height: 0;
}

.top-bar-social-column--icon_only .top-bar-icon {
  width: 18px;
  height: 18px;
  flex-basis: 18px;
}
</style>
