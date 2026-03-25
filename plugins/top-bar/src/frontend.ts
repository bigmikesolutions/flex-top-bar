import { createApp } from 'vue'
import TopBarFrontend from './components/TopBarFrontend.vue'

// Mount the app when DOM is ready
function mountApp() {
  console.log('[Top Bar] Attempting to mount app...')
  const mountPoint = document.getElementById('top-bar-frontend-mount')
  console.log('[Top Bar] Mount point:', mountPoint)

  if (mountPoint) {
    console.log('[Top Bar] Creating Vue app...')
    const app = createApp(TopBarFrontend)
    app.mount(mountPoint)
    console.log('[Top Bar] Vue app mounted successfully')
  } else {
    console.error('[Top Bar] Mount point not found!')
  }
}

// If DOM is already loaded, mount immediately. Otherwise, wait for DOMContentLoaded
console.log('[Top Bar] Frontend script loaded, readyState:', document.readyState)
if (document.readyState === 'loading') {
  console.log('[Top Bar] Waiting for DOMContentLoaded...')
  document.addEventListener('DOMContentLoaded', mountApp)
} else {
  console.log('[Top Bar] DOM already loaded, mounting immediately')
  mountApp()
}
