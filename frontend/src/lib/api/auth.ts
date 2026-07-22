import { api } from '@/lib/api'
import type { User } from '@/types/user'

export interface LoginPayload {
  email: string
  password: string
}

export interface LoginResponse {
  message: string
  token: string
  user: User
}

export const authApi = {
  login: (payload: LoginPayload) =>
    api.post<LoginResponse>('/auth/login', payload).then((res) => res.data),

  logout: () => api.post('/auth/logout').then((res) => res.data),

  me: () => api.get<{ data: User }>('/auth/me').then((res) => res.data),
}