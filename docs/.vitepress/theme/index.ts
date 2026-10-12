import DefaultTheme from 'vitepress/theme'
import type { Theme } from 'vitepress'
import ProNotice from './components/ProNotice.vue'
import './custom.css'

export default {
  extends: DefaultTheme,
  enhanceApp({ app }) {
    app.component('ProNotice', ProNotice)
  },
} satisfies Theme
