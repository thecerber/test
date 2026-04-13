import type {
  ConnectTelegramPayload,
  ConnectTelegramResponse,
} from '../model/types'

export async function connectTelegram(
  shopId: string,
  payload: ConnectTelegramPayload,
): Promise<ConnectTelegramResponse> {
  const response = await fetch(`/api/shops/${shopId}/telegram/connect`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(payload),
  })

  if (!response.ok) {
    throw new Error(`${response.status} ${response.statusText}`)
  }

  return (await response.json()) as ConnectTelegramResponse
}
