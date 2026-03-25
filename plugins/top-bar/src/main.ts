import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'

// Import existing styles
import '../assets/css/top-bar-admin.css'

// Create and mount the app
const pinia = createPinia()
const app = createApp(App)

app.use(pinia)

// Mount when DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => {
    mountApp()
  })
} else {
  mountApp()
}

function mountApp() {
  const mountPoint = document.getElementById('top-bar-app')
  if (mountPoint) {
    app.mount(mountPoint)
  } else {
    console.error('Top Bar admin mount point not found')
  }
}
