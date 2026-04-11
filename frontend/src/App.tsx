import { useEffect, useState } from 'react'
import './App.css'

type HealthResponse = {
  status: string
  database: string
}

export default function App() {
  const [health, setHealth] = useState<HealthResponse | null>(null)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    fetch('/api/health')
      .then(async (r) => {
        if (!r.ok) {
          throw new Error(`${r.status} ${r.statusText}`)
        }
        return r.json() as Promise<HealthResponse>
      })
      .then(setHealth)
      .catch((e: unknown) => setError(e instanceof Error ? e.message : String(e)))
  }, [])

  return (
    <main className="app">
      <h1>React + TypeScript</h1>
      <p className="muted">
        API через относительный путь <code>/api</code> (Vite proxy в dev, Nginx в prod).
      </p>
      {error && <p className="error">Ошибка: {error}</p>}
      {health && (
        <pre className="card">{JSON.stringify(health, null, 2)}</pre>
      )}
      {!health && !error && <p>Загрузка…</p>}
    </main>
  )
}
