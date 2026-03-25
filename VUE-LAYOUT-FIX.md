# Vue Layout Fix - Grid Structure

## Problem

Vue component was using 2-column layout everywhere:
```vue
<div class="top-bar-grid">
  <div class="item"><!-- Label --></div>
  <div class="item"><!-- Input --></div>
</div>
```

This creates equal-width columns, making labels take 50% width.

## Old PHP Structure

The CSS uses: `grid-template-columns: repeat(auto-fit, minmax(0, 1fr))`

This means:
- 1 `.item` = full width
- 2 `.item` divs = 50/50 split
- 3+ `.item` divs = equal split

**Name field** (full width):
```php
<div class="top-bar-grid">
  <div class="item">
    <fieldset class="clear">
      <legend>Name</legend>
      <input type="text" />
    </fieldset>
  </div>
</div>
```

**Basic Settings** (multi-column grid with 5 items):
```php
<div class="top-bar-grid bg bg-blue">
  <div class="item"><fieldset><legend>Position</legend><select></select></fieldset></div>
  <div class="item"><fieldset><legend>Fonts</legend><select></select></fieldset></div>
  <div class="item"><fieldset><legend>Background</legend><input type="color" /></fieldset></div>
  <div class="item"><fieldset><legend>Border frame</legend><input/><select/></fieldset></div>
  <div class="item"><fieldset><legend>On scroll</legend><select></select></fieldset></div>
</div>
```

## Solution

Match the PHP structure exactly:
1. Single `.item` with fieldset for full-width fields
2. Multiple `.item` divs for multi-column grids
3. Use `fieldset` + `legend` inside each `.item`

## Files to Update

- `src/components/BarItem.vue` - All field layouts
