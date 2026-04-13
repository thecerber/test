import { useCallback, useEffect, useState } from 'react'
import { connectTelegram } from '../api/connectTelegram'
import { getTelegramStatus } from '../api/getTelegramStatus'
import type { TelegramStatusResponse } from './types'

type SaveTelegramFormPayload = {
  botTokenInput: string
  chatIdInput: string
  enabled: boolean
}

type UseTelegramGrowthResult = {
  status: TelegramStatusResponse | null
  isLoading: boolean
  isSaving: boolean
  error: string | null
  saveError: string | null
  saveSuccess: string | null
  save: (payload: SaveTelegramFormPayload) => Promise<void>
}

export function useTelegramGrowth(shopId?: string): UseTelegramGrowthResult {
  const [status, setStatus] = useState<TelegramStatusResponse | null>(null)
  const [isLoading, setIsLoading] = useState(false)
  const [isSaving, setIsSaving] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [saveError, setSaveError] = useState<string | null>(null)
  const [saveSuccess, setSaveSuccess] = useState<string | null>(null)

  const loadStatus = useCallback(async () => {
    if (!shopId) {
      setError('Некорректный shopId в URL.')
      return
    }

    setIsLoading(true)
    setError(null)

    try {
      const nextStatus = await getTelegramStatus(shopId)
      setStatus(nextStatus)
    } catch (e: unknown) {
      setError(e instanceof Error ? e.message : String(e))
    } finally {
      setIsLoading(false)
    }
  }, [shopId])

  const save = useCallback(
    async ({ botTokenInput, chatIdInput, enabled }: SaveTelegramFormPayload) => {
      if (!shopId || !status) {
        setSaveError('Нельзя сохранить: статус интеграции ещё не загружен.')
        return
      }

      const botToken = botTokenInput.trim() || status.botToken || ''
      const chatId = chatIdInput.trim() || status.chatId || ''

      if (!botToken || !chatId) {
        setSaveError(
          'Введите botToken и chatId или убедитесь, что у магазина уже есть сохранённые значения.',
        )
        return
      }

      setIsSaving(true)
      setSaveError(null)
      setSaveSuccess(null)

      try {
        await connectTelegram(shopId, {
          botToken,
          chatId,
          enabled,
        })
        await loadStatus()
        setSaveSuccess('Настройки Telegram сохранены.')
      } catch (e: unknown) {
        setSaveError(e instanceof Error ? e.message : String(e))
      } finally {
        setIsSaving(false)
      }
    },
    [loadStatus, shopId, status],
  )

  useEffect(() => {
    void loadStatus()
  }, [loadStatus])

  return {
    status,
    isLoading,
    isSaving,
    error,
    saveError,
    saveSuccess,
    save,
  }
}
