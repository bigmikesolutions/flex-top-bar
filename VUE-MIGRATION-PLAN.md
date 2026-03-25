# Vue.js Migration Plan for Top Bar Admin Interface

## Overview

Migrate the Top Bar admin interface from PHP-rendered HTML to a modern Vue.js 3 SPA, while keeping all data persistence in WordPress/PHP backend via REST API.

## Current State

### PHP Admin (905 lines)
- `plugins/top-bar/includes/class-admin.php`
- Traditional form-based WordPress admin
- Direct POST to `options.php`
- Server-side rendering with PHP
- jQuery for basic interactions

### Current Assets
- `assets/js/top-bar-admin.js` (Chosen library for selects)
- `assets/css/top-bar-admin.css`
- Basic jQuery-based UI

## Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                     Vue.js 3 Frontend                        │
│  - Single Page Application (SPA)                            │
│  - Reactive state management (Pinia)                        │
│  - TypeScript                                               │
│  - Component-based UI                                       │
└────────────────────┬────────────────────────────────────────┘
                     │ REST API (JSON)
                     │
┌────────────────────▼────────────────────────────────────────┐
│              WordPress REST API Endpoints                    │
│  - GET  /wp-json/top-bar/v1/bars                           │
│  - POST /wp-json/top-bar/v1/bars                           │
│  - PUT  /wp-json/top-bar/v1/bars/{id}                      │
│  - DELETE /wp-json/top-bar/v1/bars/{id}                    │
│  - GET  /wp-json/top-bar/v1/feature-flags                  │
└────────────────────┬────────────────────────────────────────┘
                     │
┌────────────────────▼────────────────────────────────────────┐
│                 PHP Backend (Unchanged)                      │
│  - FeatureFlags class                                       │
│  - Options class                                            │
│  - Database persistence (wp_options)                        │
│  - Validation & sanitization                               │
└─────────────────────────────────────────────────────────────┘
```

## Tech Stack

### Frontend
- **Vue 3** (Composition API)
- **TypeScript** - Type safety
- **Pinia** - State management
- **Vite** - Build tool (fast HMR)
- **TailwindCSS** or keep existing CSS
- **VeeValidate** - Form validation
- **@wordpress/i18n** - Internationalization

### Build Setup
- **Vite** for development and production builds
- **@vitejs/plugin-vue** - Vue 3 support
- **vite-plugin-wordpress** - WordPress integration
- **TypeScript** for type safety

## Implementation Phases

### Phase 1: Setup & Infrastructure (2-3 hours)

#### 1.1 Install Dependencies
```bash
cd plugins/top-bar
npm init -y
npm install -D vue@^3.4 typescript vite @vitejs/plugin-vue
npm install -D @types/wordpress__i18n
npm install pinia @vueuse/core
```

#### 1.2 Project Structure
```
plugins/top-bar/
├── src/                    # Vue source code
│   ├── main.ts            # Entry point
│   ├── App.vue            # Root component
│   ├── types/             # TypeScript types
│   │   └── index.ts
│   ├── stores/            # Pinia stores
│   │   ├── bars.ts        # Bar state management
│   │   └── featureFlags.ts
│   ├── components/        # Vue components
│   │   ├── BarList.vue
│   │   ├── BarForm.vue
│   │   ├── MessageList.vue
│   │   ├── ScheduleFields.vue
│   │   └── ColorPicker.vue
│   ├── composables/       # Reusable logic
│   │   ├── useApi.ts
│   │   └── useValidation.ts
│   └── api/               # API client
│       └── client.ts
├── vite.config.ts         # Vite configuration
├── tsconfig.json          # TypeScript config
└── includes/
    └── class-api.php      # NEW: REST API endpoints
```

#### 1.3 Vite Configuration
```typescript
// vite.config.ts
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import path from 'path'

export default defineConfig({
  plugins: [vue()],
  build: {
    outDir: 'assets/dist',
    rollupOptions: {
      input: {
        admin: path.resolve(__dirname, 'src/main.ts'),
      },
      output: {
        entryFileNames: 'js/[name].js',
        chunkFileNames: 'js/[name]-[hash].js',
        assetFileNames: 'css/[name][extname]',
      },
    },
  },
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
    },
  },
})
```

### Phase 2: REST API Backend (3-4 hours)

#### 2.1 Create REST API Controller
```php
// includes/class-api.php
<?php
namespace TopBar;

final class API {
    private const NAMESPACE = 'top-bar/v1';

    public function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes(): void {
        // Get all bars
        register_rest_route(self::NAMESPACE, '/bars', [
            'methods'  => 'GET',
            'callback' => [$this, 'get_bars'],
            'permission_callback' => [$this, 'check_permissions'],
        ]);

        // Create new bar
        register_rest_route(self::NAMESPACE, '/bars', [
            'methods'  => 'POST',
            'callback' => [$this, 'create_bar'],
            'permission_callback' => [$this, 'check_permissions'],
            'args' => $this->get_bar_schema(),
        ]);

        // Update bar
        register_rest_route(self::NAMESPACE, '/bars/(?P<id>[a-z0-9_]+)', [
            'methods'  => 'PUT',
            'callback' => [$this, 'update_bar'],
            'permission_callback' => [$this, 'check_permissions'],
            'args' => $this->get_bar_schema(),
        ]);

        // Delete bar
        register_rest_route(self::NAMESPACE, '/bars/(?P<id>[a-z0-9_]+)', [
            'methods'  => 'DELETE',
            'callback' => [$this, 'delete_bar'],
            'permission_callback' => [$this, 'check_permissions'],
        ]);

        // Get feature flags
        register_rest_route(self::NAMESPACE, '/feature-flags', [
            'methods'  => 'GET',
            'callback' => [$this, 'get_feature_flags'],
            'permission_callback' => [$this, 'check_permissions'],
        ]);
    }

    public function check_permissions(): bool {
        return current_user_can('manage_options');
    }

    public function get_bars(\WP_REST_Request $request): \WP_REST_Response {
        $bars = Options::get_bars();
        return new \WP_REST_Response($bars, 200);
    }

    public function create_bar(\WP_REST_Request $request): \WP_REST_Response {
        $bars = Options::get_bars();

        if (count($bars) >= FeatureFlags::instance()->max_bars()) {
            return new \WP_REST_Response(
                ['error' => 'Maximum number of bars reached'],
                403
            );
        }

        $new_bar = Options::normalize_bar($request->get_json_params());
        $bars[] = $new_bar;
        update_option(Options::OPTION_BARS, $bars);

        return new \WP_REST_Response($new_bar, 201);
    }

    public function update_bar(\WP_REST_Request $request): \WP_REST_Response {
        $id = $request->get_param('id');
        $bars = Options::get_bars();

        foreach ($bars as $idx => $bar) {
            if ($bar['id'] === $id) {
                $bars[$idx] = Options::normalize_bar(
                    array_merge($bar, $request->get_json_params())
                );
                update_option(Options::OPTION_BARS, $bars);
                return new \WP_REST_Response($bars[$idx], 200);
            }
        }

        return new \WP_REST_Response(
            ['error' => 'Bar not found'],
            404
        );
    }

    public function delete_bar(\WP_REST_Request $request): \WP_REST_Response {
        $id = $request->get_param('id');
        $bars = Options::get_bars();

        if (count($bars) <= Options::MIN_BARS) {
            return new \WP_REST_Response(
                ['error' => 'Cannot delete last bar'],
                403
            );
        }

        $filtered = array_filter($bars, fn($bar) => $bar['id'] !== $id);

        if (count($filtered) === count($bars)) {
            return new \WP_REST_Response(
                ['error' => 'Bar not found'],
                404
            );
        }

        update_option(Options::OPTION_BARS, array_values($filtered));
        return new \WP_REST_Response(null, 204);
    }

    public function get_feature_flags(\WP_REST_Request $request): \WP_REST_Response {
        $flags = FeatureFlags::instance();
        return new \WP_REST_Response([
            'max_bars' => $flags->max_bars(),
            'max_messages' => $flags->max_messages(),
            'schedule_enabled' => $flags->is_schedule_enabled(),
        ], 200);
    }

    private function get_bar_schema(): array {
        return [
            'id' => [
                'type' => 'string',
                'required' => false,
            ],
            'name' => [
                'type' => 'string',
                'required' => false,
            ],
            'visible' => [
                'type' => 'boolean',
                'required' => false,
            ],
            'position' => [
                'type' => 'string',
                'enum' => ['top', 'bottom'],
                'required' => false,
            ],
            'messages' => [
                'type' => 'array',
                'items' => ['type' => 'string'],
                'required' => false,
            ],
            // ... other fields
        ];
    }
}
```

### Phase 3: Vue.js Frontend (6-8 hours)

#### 3.1 TypeScript Types
```typescript
// src/types/index.ts
export interface Bar {
  id: string
  name: string
  visible: boolean
  admin_visibile: boolean
  scheduled_enabled: boolean
  scheduled_from_datetime: string
  scheduled_to_datetime: string
  position: 'top' | 'bottom'
  effect: 'none' | 'slider' | 'fadein' | 'blink'
  messages: string[]
  messages_mobile_visible: boolean
  bg_color: string
  frame_color: string
  frame_width: number
  hide_on_scroll: boolean
}

export interface FeatureFlags {
  max_bars: number
  max_messages: number
  schedule_enabled: boolean
}

export interface ApiError {
  error: string
}
```

#### 3.2 Pinia Store
```typescript
// src/stores/bars.ts
import { defineStore } from 'pinia'
import type { Bar } from '@/types'
import { api } from '@/api/client'

export const useBarsStore = defineStore('bars', {
  state: () => ({
    bars: [] as Bar[],
    loading: false,
    error: null as string | null,
  }),

  actions: {
    async fetchBars() {
      this.loading = true
      this.error = null
      try {
        this.bars = await api.getBars()
      } catch (error) {
        this.error = error.message
      } finally {
        this.loading = false
      }
    },

    async createBar(bar: Partial<Bar>) {
      this.loading = true
      try {
        const newBar = await api.createBar(bar)
        this.bars.push(newBar)
        return newBar
      } catch (error) {
        this.error = error.message
        throw error
      } finally {
        this.loading = false
      }
    },

    async updateBar(id: string, updates: Partial<Bar>) {
      this.loading = true
      try {
        const updated = await api.updateBar(id, updates)
        const index = this.bars.findIndex(b => b.id === id)
        if (index !== -1) {
          this.bars[index] = updated
        }
      } catch (error) {
        this.error = error.message
        throw error
      } finally {
        this.loading = false
      }
    },

    async deleteBar(id: string) {
      this.loading = true
      try {
        await api.deleteBar(id)
        this.bars = this.bars.filter(b => b.id !== id)
      } catch (error) {
        this.error = error.message
        throw error
      } finally {
        this.loading = false
      }
    },
  },
})
```

#### 3.3 Main Vue Component
```vue
<!-- src/App.vue -->
<template>
  <div id="top-bar-admin" class="wrap">
    <h1>{{ __('Top Bar', 'top-bar') }}</h1>

    <div v-if="loading" class="notice notice-info">
      <p>{{ __('Loading...', 'top-bar') }}</p>
    </div>

    <div v-else-if="error" class="notice notice-error">
      <p>{{ error }}</p>
    </div>

    <template v-else>
      <!-- Empty state -->
      <div v-if="bars.length === 0" class="top-bar-row center empty">
        <p class="xlg bold">{{ __('Welcome to Top Bar plugin', 'top-bar') }}</p>
        <p class="xs">{{ __('Click the button to add your first Top Bar', 'top-bar') }}</p>
        <button v-if="canAddBar" @click="addBar" class="top-bar-btn mint md">
          {{ __('Add new Top Bar', 'top-bar') }}
        </button>
      </div>

      <!-- Bar list -->
      <template v-else>
        <div class="top-bar-row rt">
          <button v-if="canAddBar" @click="addBar" class="top-bar-btn mint sm">
            {{ __('Add new Top Bar', 'top-bar') }}
          </button>
        </div>

        <BarList :bars="bars" @update="updateBar" @delete="deleteBar" />

        <div class="top-bar-row rt">
          <button type="submit" class="button button-primary" @click="saveChanges">
            {{ __('Save Changes', 'top-bar') }}
          </button>
        </div>
      </template>
    </template>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useBarsStore } from '@/stores/bars'
import { useFeatureFlagsStore } from '@/stores/featureFlags'
import BarList from '@/components/BarList.vue'
import { __ } from '@wordpress/i18n'

const barsStore = useBarsStore()
const flagsStore = useFeatureFlagsStore()

const bars = computed(() => barsStore.bars)
const loading = computed(() => barsStore.loading)
const error = computed(() => barsStore.error)
const canAddBar = computed(() =>
  bars.value.length < flagsStore.flags.max_bars
)

onMounted(async () => {
  await Promise.all([
    barsStore.fetchBars(),
    flagsStore.fetchFlags(),
  ])
})

async function addBar() {
  try {
    await barsStore.createBar({})
  } catch (error) {
    console.error('Failed to add bar:', error)
  }
}

async function updateBar(id: string, updates: Partial<Bar>) {
  await barsStore.updateBar(id, updates)
}

async function deleteBar(id: string) {
  await barsStore.deleteBar(id)
}

async function saveChanges() {
  // In Vue, changes are saved immediately via API
  // This button can show a success message
  alert(__('Changes saved successfully!', 'top-bar'))
}
</script>
```

### Phase 4: Integration (2-3 hours)

#### 4.1 Update Admin Class
```php
// includes/class-admin.php (modified)
public function __construct() {
    add_action('admin_menu', [$this, 'add_settings_page']);
    add_action('admin_enqueue_scripts', [$this, 'enqueue_vue_app']);
}

public function enqueue_vue_app($hook): void {
    if ($hook !== 'settings_page_top-bar') {
        return;
    }

    // Enqueue Vue app
    wp_enqueue_script(
        'top-bar-admin-vue',
        plugins_url('assets/dist/js/admin.js', TOP_BAR_PLUGIN_FILE),
        [],
        TOP_BAR_VERSION,
        true
    );

    wp_enqueue_style(
        'top-bar-admin-vue',
        plugins_url('assets/dist/css/admin.css', TOP_BAR_PLUGIN_FILE),
        [],
        TOP_BAR_VERSION
    );

    // Pass data to Vue
    wp_localize_script('top-bar-admin-vue', 'topBarConfig', [
        'apiRoot' => esc_url_raw(rest_url('top-bar/v1')),
        'nonce' => wp_create_nonce('wp_rest'),
        'i18n' => [
            'welcome' => __('Welcome to Top Bar plugin', 'top-bar'),
            // ... other translations
        ],
    ]);
}

public function render_settings_page(): void {
    if (!current_user_can('manage_options')) {
        return;
    }
    // Simple mount point for Vue
    echo '<div id="top-bar-app"></div>';
}
```

### Phase 5: Testing & Migration (3-4 hours)

#### 5.1 Update E2E Tests
- Modify Playwright tests to work with Vue
- Update selectors to match Vue components
- Add tests for API endpoints

#### 5.2 Backward Compatibility
- Keep old admin for one release (feature flag toggle)
- Gradual rollout to users
- Fallback to PHP admin if Vue fails to load

## Benefits

### Developer Experience
- **Hot Module Replacement (HMR)** - Instant feedback during development
- **TypeScript** - Type safety, better IDE support
- **Component Reusability** - Easier to maintain and extend
- **Modern Tooling** - Vue DevTools, Vite, etc.

### User Experience
- **Instant Feedback** - No page reloads
- **Better Performance** - Client-side rendering
- **Smoother Animations** - Transitions between states
- **Optimistic Updates** - UI updates before server confirms

### Code Quality
- **Separation of Concerns** - Frontend/backend clearly separated
- **Testability** - Unit test Vue components independently
- **Maintainability** - Smaller, focused components
- **Scalability** - Easy to add new features

## Migration Steps

1. **Week 1: Setup & API**
   - Install dependencies
   - Create REST API endpoints
   - Test API with Postman/curl

2. **Week 2: Core Vue Components**
   - Build basic Vue app
   - Implement bar list and form
   - Connect to API

3. **Week 3: Advanced Features**
   - Add scheduling UI
   - Implement color picker
   - Add message management

4. **Week 4: Testing & Polish**
   - Update E2E tests
   - Fix bugs
   - Polish UI/UX

5. **Week 5: Beta Testing**
   - Deploy to staging
   - User testing
   - Fix issues

6. **Week 6: Production**
   - Feature flag rollout
   - Monitor for issues
   - Remove old PHP admin

## Risks & Mitigation

| Risk | Mitigation |
|------|------------|
| Vue app fails to load | Keep PHP admin as fallback |
| API security issues | Use WordPress nonces, permissions |
| Breaking changes | Maintain backward compatibility |
| Learning curve | Good documentation, examples |
| Bundle size | Code splitting, lazy loading |

## Success Metrics

- **Performance**: Page load < 1s
- **Bundle Size**: JS < 200KB (gzipped)
- **User Satisfaction**: 90%+ positive feedback
- **Bug Rate**: < 5 bugs per 100 users
- **Test Coverage**: > 80%

## Next Steps

1. **Approve this plan**
2. **Create development branch**: `feature/vue-migration`
3. **Set up project structure**
4. **Begin Phase 1: Infrastructure**

---

**Estimated Total Time**: 16-22 hours
**Risk Level**: Medium
**Complexity**: Medium-High
**Impact**: High (significant UX improvement)
