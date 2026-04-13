import { useEffect, useState } from 'react'
import type { HealthResponse } from './types'

type UseHealthResult = {
  health: HealthResponse | null
  error: string | null
  isLoading: boolean
}

export function useHealth(): UseHealthResult {
  const [health, setHealth] = useState<HealthResponse | null>(null)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    fetch('/api/health')
      .then(async (response) => {
        if (!response.ok) {
          throw new Error(`${response.status} ${response.statusText}`)
        }
        return response.json() as Promise<HealthResponse>
      })
      .then(setHealth)
      .catch((e: unknown) => {
        setError(e instanceof Error ? e.message : String(e))
      })
  }, [])

  return {
    health,
    error,
    isLoading: !health && !error,
  }
}
