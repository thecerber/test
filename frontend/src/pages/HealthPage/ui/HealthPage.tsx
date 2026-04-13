import '../../../App.css'
import { useHealth } from '../model/useHealth'

export function HealthPage() {
  const { health, error, isLoading } = useHealth()

  return (
    <main className="app">
      <h1>React + TypeScript</h1>
      <p className="muted">
        API через относительный путь <code>/api</code> (Vite proxy в dev, Nginx в
        prod).
      </p>
      {error && <p className="error">Ошибка: {error}</p>}
      {health && (
        <pre className="card">{JSON.stringify(health, null, 2)}</pre>
      )}
      {isLoading && <p>Загрузка…</p>}
    </main>
  )
}
