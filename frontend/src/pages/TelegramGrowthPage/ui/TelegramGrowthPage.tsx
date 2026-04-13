import { useEffect, useMemo, useState } from 'react'
import type { FormEvent } from 'react'
import { useParams } from 'react-router-dom'
import '../../../App.css'
import { useTelegramGrowth } from '../model/useTelegramGrowth'

function formatDate(value: string | null): string {
  if (!value) {
    return '—'
  }

  const date = new Date(value)
  if (Number.isNaN(date.getTime())) {
    return value
  }

  return date.toLocaleString('ru-RU')
}

export function TelegramGrowthPage() {
  const { shopId } = useParams<{ shopId: string }>()
  const {
    status,
    isLoading,
    isSaving,
    error,
    saveError,
    saveSuccess,
    save
  } = useTelegramGrowth(shopId)

  const [botTokenInput, setBotTokenInput] = useState('')
  const [chatIdInput, setChatIdInput] = useState('')
  const [enabled, setEnabled] = useState(false)

  useEffect(() => {
    if (!status) {
      return
    }
    setEnabled(status.enabled)
  }, [status])

  const formPlaceholder = useMemo(
    () => ({
      botToken: status?.botTokenMasked ?? 'Введите botToken',
      chatId: status?.chatIdMasked ?? 'Введите chatId',
    }),
    [status],
  )

  const handleSubmit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    const isSaved = await save({ botTokenInput, chatIdInput, enabled })

    if (isSaved) {
      setBotTokenInput('')
      setChatIdInput('')
    }
  }

  return (
    <main className="app">
      <h1>Telegram Growth</h1>
      <p className="muted">Магазин: {shopId ?? '—'}</p>

      {error && <p className="error">Ошибка загрузки: {error}</p>}
      {isLoading && <p>Загрузка статуса…</p>}

      <section className="card-light">
        <h2>Настройки интеграции</h2>
        <form onSubmit={(event) => void handleSubmit(event)}>
          <label className="field">
            <span>botToken</span>
            <input
              type="text"
              value={botTokenInput}
              placeholder={formPlaceholder.botToken}
              onChange={(event) => setBotTokenInput(event.target.value)}
            />
          </label>

          <label className="field">
            <span>chatId</span>
            <input
              type="text"
              value={chatIdInput}
              placeholder={formPlaceholder.chatId}
              onChange={(event) => setChatIdInput(event.target.value)}
            />
          </label>

          <label className="switch">
            <input
              type="checkbox"
              checked={enabled}
              onChange={(event) => setEnabled(event.target.checked)}
            />
            <span>enabled</span>
          </label>

          <div>
            <button type="submit" disabled={isSaving || isLoading}>
              {isSaving ? 'Сохраняем…' : 'Сохранить'}
            </button>
          </div>
        </form>
        {saveError && <p className="error">{saveError}</p>}
        {saveSuccess && <p>{saveSuccess}</p>}
      </section>

      <section className="card-light">
        <h2>Статус</h2>
        <dl className="status-grid">
          <dt>Включена</dt>
          <dd>{status ? (status.enabled ? 'Да' : 'Нет') : '—'}</dd>
          <dt>Дата и время последней успешной отправки</dt>
          <dd>{formatDate(status?.lastSentAt ?? null)}</dd>
          <dt>Количество успешных отправок за последние 7 дней</dt>
          <dd>{status?.sentCount ?? 0}</dd>
          <dt>Количество неуспешных отправок за последние 7 дней</dt>
          <dd>{status?.failedCount ?? 0}</dd>
        </dl>
      </section>

      <section className="card-light">
        <h2>Как узнать chatId</h2>
        <p>
          Напишите сообщение вашему боту, затем откройте в браузере:
          <br />
          <code>https://api.telegram.org/bot&lt;BOT_TOKEN&gt;/getUpdates</code>
        </p>
        <p className="muted">
          В ответе найдите <code>chat.id</code> и вставьте это значение в поле
          chatId.
        </p>
      </section>
    </main>
  )
}
