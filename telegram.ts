interface TelegramWebApp {
  initData: string
  initDataUnsafe: {
    user?: {
      id: number
      first_name: string
      last_name?: string
      username?: string
      photo_url?: string
    }
    start_param?: string
  }
  version: string
  platform: string
  colorScheme: 'light' | 'dark'
  themeParams: {
    bg_color: string
    text_color: string
    hint_color: string
    link_color: string
    button_color: string
    button_text_color: string
    secondary_bg_color: string
  }
  isExpanded: boolean
  viewportHeight: number
  viewportStableHeight: number
  ready(): void
  expand(): void
  close(): void
  MainButton: {
    text: string
    color: string
    textColor: string
    isVisible: boolean
    isActive: boolean
    setText(text: string): void
    onClick(callback: () => void): void
    show(): void
    hide(): void
  }
  BackButton: {
    isVisible: boolean
    onClick(callback: () => void): void
    show(): void
    hide(): void
  }
  HapticFeedback: {
    impactOccurred(style: 'light' | 'medium' | 'heavy' | 'rigid' | 'soft'): void
    notificationOccurred(type: 'error' | 'success' | 'warning'): void
    selectionChanged(): void
  }
  openLink(url: string): void
}

declare global {
  interface Window {
    Telegram?: {
      WebApp: TelegramWebApp
    }
  }
}

export const telegramWebApp = window.Telegram?.WebApp

export const initTelegramApp = () => {
  if (telegramWebApp) {
    telegramWebApp.ready()
    telegramWebApp.expand()
    return true
  }
  return false
}

export const getTelegramUser = () => {
  return telegramWebApp?.initDataUnsafe.user
}

export const openInMarketplace = (url: string) => {
  if (telegramWebApp) {
    telegramWebApp.openLink(url)
  } else {
    window.open(url, '_blank')
  }
}