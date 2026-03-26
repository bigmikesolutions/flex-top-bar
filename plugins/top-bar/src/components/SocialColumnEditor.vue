<template>
  <div class="item-creator lg">
    <fieldset class="line">
      <legend class="bold">{{ __('Choose the icon appearance', 'top-bar') }}</legend>
      <label v-for="opt in iconStyleOptions" :key="opt.value" class="top-bar-radio-line">
        <input
          type="radio"
          :name="`social_icon_style_${barId}_${columnId}`"
          :checked="column.icon_style === opt.value"
          @change="patchIconStyle(opt.value)"
        />
        {{ opt.label }}
      </label>
    </fieldset>

    <div class="top-bar-grid">
      <div class="item">
        <fieldset class="line">
          <legend class="bold">{{ __('Background color', 'top-bar') }}</legend>
          <input
            :id="`social_bg_${barId}_${columnId}`"
            type="color"
            :value="column.background_color"
            @input="onBgInput"
            @blur="emit('commit')"
          />
        </fieldset>
      </div>
      <div class="item">
        <fieldset class="line">
          <legend class="bold">{{ __('Color icon', 'top-bar') }}</legend>
          <input
            :id="`social_icon_${barId}_${columnId}`"
            type="color"
            :value="column.icon_color"
            @input="onIconColorInput"
            @blur="emit('commit')"
          />
        </fieldset>
      </div>
    </div>

    <fieldset class="line">
      <legend class="bold">{{ __('Social links', 'top-bar') }}</legend>
      <div class="top-bar-message-list">
        <div
          v-for="(_link, index) in column.links"
          :key="index"
          class="top-bar-column-creator-grid"
        >
          <div class="item-creator no">
            <p class="bold md">{{ index + 1 }}</p>
            <button
              v-if="column.links.length > 1"
              type="button"
              class="top-bar-btn amber sm"
              @click="removeLink(index)"
            >
              X
            </button>
          </div>
          <div class="item-creator vertical">
            <label class="screen-reader-text" :for="`social_platform_${barId}_${columnId}_${index}`">
              {{ __('Social network', 'top-bar') }}
            </label>
            <select
              :id="`social_platform_${barId}_${columnId}_${index}`"
              :value="column.links[index]?.platform ?? ''"
              @change="onPlatformChange(index, $event)"
            >
              <option value="">{{ __('Select social media', 'top-bar') }}</option>
              <option v-for="p in SOCIAL_PLATFORMS" :key="p" :value="p">
                {{ platformLabel(p) }}
              </option>
            </select>
            <label class="screen-reader-text" :for="`social_url_${barId}_${columnId}_${index}`">
              {{ __('Profile link', 'top-bar') }}
            </label>
            <input
              :id="`social_url_${barId}_${columnId}_${index}`"
              type="url"
              :value="column.links[index]?.url ?? ''"
              :placeholder="__('Your profile link', 'top-bar')"
              @input="onUrlInput(index, $event)"
              @blur="emit('commit')"
            />
          </div>
        </div>
      </div>
    </fieldset>

    <div class="top-bar-row rt">
      <button
        v-if="column.links.length < maxLinks"
        type="button"
        class="top-bar-btn amber sm right"
        @click="addLink"
      >
        {{ __('Add new social media', 'top-bar') }}
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { __ } from '@wordpress/i18n'
import { SOCIAL_PLATFORMS, type IconStyle, type SocialBarColumn, type SocialPlatform } from '@/types'
import { getIconStyleOptions } from '@/constants/iconStyleOptions'

const props = defineProps<{
  barId: string
  columnId: string
  column: SocialBarColumn
  maxLinks: number
}>()

const emit = defineEmits<{
  patch: [updates: Partial<SocialBarColumn>]
  commit: []
}>()

const iconStyleOptions = getIconStyleOptions(__)

function platformLabel(p: SocialPlatform): string {
  const labels: Record<SocialPlatform, string> = {
    facebook: __('Facebook', 'top-bar'),
    instagram: __('Instagram', 'top-bar'),
    x: __('X (Twitter)', 'top-bar'),
    linkedin: __('LinkedIn', 'top-bar'),
    youtube: __('YouTube', 'top-bar'),
    tiktok: __('TikTok', 'top-bar'),
    pinterest: __('Pinterest', 'top-bar'),
    snapchat: __('Snapchat', 'top-bar'),
    reddit: __('Reddit', 'top-bar'),
    tumblr: __('Tumblr', 'top-bar'),
    whatsapp: __('WhatsApp', 'top-bar'),
    telegram: __('Telegram', 'top-bar'),
    discord: __('Discord', 'top-bar'),
    threads: __('Threads', 'top-bar'),
    mastodon: __('Mastodon', 'top-bar'),
    medium: __('Medium', 'top-bar'),
    github: __('GitHub', 'top-bar'),
    dribbble: __('Dribbble', 'top-bar'),
    behance: __('Behance', 'top-bar'),
    flickr: __('Flickr', 'top-bar'),
  }
  return labels[p]
}

function patchIconStyle(style: IconStyle) {
  emit('patch', { icon_style: style })
  emit('commit')
}

function onBgInput(e: Event) {
  emit('patch', { background_color: (e.target as HTMLInputElement).value })
}

function onIconColorInput(e: Event) {
  emit('patch', { icon_color: (e.target as HTMLInputElement).value })
}

function onPlatformChange(index: number, e: Event) {
  const platform = (e.target as HTMLSelectElement).value as SocialPlatform | ''
  const next = [...props.column.links]
  next[index] = { ...next[index], platform }
  emit('patch', { links: next })
  emit('commit')
}

function onUrlInput(index: number, e: Event) {
  const url = (e.target as HTMLInputElement).value
  const next = [...props.column.links]
  next[index] = { ...next[index], url }
  emit('patch', { links: next })
}

function addLink() {
  if (props.column.links.length < props.maxLinks) {
    emit('patch', { links: [...props.column.links, { platform: '', url: '' }] })
    emit('commit')
  }
}

function removeLink(index: number) {
  if (props.column.links.length <= 1) {
    return
  }
  const next = props.column.links.filter((_, i) => i !== index)
  emit('patch', { links: next.length ? next : [{ platform: '', url: '' }] })
  emit('commit')
}
</script>

<style scoped>
.top-bar-radio-line {
  display: block;
  margin-bottom: 6px;
}

.screen-reader-text {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}
</style>
