// src/features/auth/hooks/useAuth.ts
import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import axios from 'axios'
import { api } from '@/lib/api'
import { useAuthStore } from '@/stores/authStore'

interface LoginPayload {
  email: string
  password: string
}

export function useAuth() {
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const setAuth = useAuthStore((s) => s.setAuth)
  const navigate = useNavigate()

  const login = async ({ email, password }: LoginPayload) => {
    setLoading(true)
    setError(null)
    try {
      const { data } = await api.post('/auth/login', { email, password })

      if (data.user?.role !== 'Admin') {
        setError('บัญชีนี้ไม่มีสิทธิ์เข้าสู่ระบบผู้ดูแล')
        return
      }

      setAuth(data.token, data.user)
      navigate('/dashboard')
    } catch (err: unknown) {
      const message = axios.isAxiosError(err)
        ? err.response?.data?.message
        : undefined
      setError(message ?? 'อีเมลหรือรหัสผ่านไม่ถูกต้อง')
    } finally {
      setLoading(false)
    }
  }

  return { login, loading, error }
}