<template>
  <div class="item-creator">
    <fieldset>
      <legend class="bold">{{ __('Type', 'top-bar') }}</legend>
      <label><input type="radio" checked disabled /> {{ __('Text Editor', 'top-bar') }}</label>
    </fieldset>
  </div>
</template>

<script setup lang="ts">
import { __ } from '@wordpress/i18n'
</script>
