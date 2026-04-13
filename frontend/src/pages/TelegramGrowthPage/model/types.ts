export type TelegramStatusResponse = {
  enabled: boolean
  botToken: string | null
  botTokenMasked: string | null
  chatId: string | null
  chatIdMasked: string | null
  lastSentAt: string | null
  sentCount: number
  failedCount: number
}

export type ConnectTelegramPayload = {
  botToken: string
  chatId: string
  enabled: boolean
}

export type ConnectTelegramResponse = {
  id: number
  shopId: number
  botToken: string
  chatId: string
  enabled: boolean
  createdAt: string
  updatedAt: string
}
