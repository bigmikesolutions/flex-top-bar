import { createApp } from 'vue'
import TopBarView from './components/TopBarView.vue'

// Mount the app when DOM is ready
function mountApp() {
  const mountPoint = document.getElementById('top-bar-frontend-mount')

  if (mountPoint) {
    const app = createApp(TopBarView)
    app.mount(mountPoint)
  }
}

// If DOM is already loaded, mount immediately. Otherwise, wait for DOMContentLoaded
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', mountApp)
} else {
  mountApp()
}
