import { Navigate, Route, Routes } from 'react-router-dom'
import { HealthPage } from './pages/HealthPage'
import { TelegramGrowthPage } from './pages/TelegramGrowthPage'

export default function App() {
  return (
    <Routes>
      <Route path="/" element={<Navigate to="/health" replace />} />
      <Route path="/health" element={<HealthPage />} />
      <Route path="/shops/:shopId/growth/telegram" element={<TelegramGrowthPage />} />
      <Route path="*" element={<Navigate to="/health" replace />} />
    </Routes>
  )
}
