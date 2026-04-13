import type { TelegramStatusResponse } from '../model/types'

export async function getTelegramStatus(
  shopId: string,
): Promise<TelegramStatusResponse> {
  const response = await fetch(`/api/shops/${shopId}/telegram/status`)
  if (!response.ok) {
    throw new Error(`${response.status} ${response.statusText}`)
  }

  return (await response.json()) as TelegramStatusResponse
}
