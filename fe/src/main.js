import { createApp } from 'vue'
import { createPinia } from 'pinia'
import * as Sentry from '@sentry/vue'
import { createSentryPiniaPlugin } from '@sentry/vue'
import './index.css'

import App from './App.vue'
import router from './router'

const app = createApp(App)

// Sentry error monitoring & performance tracing
Sentry.init({
    app,
    dsn: import.meta.env.VITE_SENTRY_DSN || '',
    integrations: [
        Sentry.browserTracingIntegration({ router }),
        Sentry.replayIntegration(),
    ],
    // Performance tracing
    tracesSampleRate: parseFloat(import.meta.env.VITE_SENTRY_TRACES_SAMPLE_RATE || '1.0'),
    tracePropagationTargets: ['localhost', /^https:\/\/api\./],
    // Session Replay
    replaysSessionSampleRate: 0.1,
    replaysOnErrorSampleRate: 1.0,
    // Privacy — do not send PII by default
    sendDefaultPii: false,
})

// Pinia with Sentry state capture
const pinia = createPinia()
pinia.use(createSentryPiniaPlugin())

app.use(pinia)
app.use(router)

app.mount('#app')
