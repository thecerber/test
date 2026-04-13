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
      'Accept': 'application/json',
    },
    body: JSON.stringify(payload),
  })

  if (!response.ok) {
    let detail = ''

    try {
      const body = (await response.json()) as { detail?: unknown }

      if (typeof body.detail === 'string' && body.detail.trim()) {
        detail = body.detail.trim()
      }
    } catch {
      // ignore non-json error body
    }

    throw new Error(detail ? detail : `${response.status} ${response.statusText}`)
  }

  return (await response.json()) as ConnectTelegramResponse
}
