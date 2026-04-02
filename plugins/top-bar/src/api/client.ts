import type { Bar, FeatureFlags, ApiError } from '@/types'

class ApiClient {
  private baseUrl: string
  private nonce: string

  constructor() {
    this.baseUrl = window.topBarConfig?.apiRoot || '/wp-json/top-bar/v1'
    this.nonce = window.topBarConfig?.nonce || ''
  }

  private async request<T>(
    endpoint: string,
    options: RequestInit = {}
  ): Promise<T> {
    const url = `${this.baseUrl}${endpoint}`

    const headers: HeadersInit = {
      'Content-Type': 'application/json',
      'X-WP-Nonce': this.nonce,
      ...options.headers,
    }

    const response = await fetch(url, {
      ...options,
      headers,
    })

    if (!response.ok) {
      const error: ApiError = await response.json().catch(() => ({
        error: `HTTP Error ${response.status}`,
      }))
      throw new Error(error.error || 'An error occurred')
    }

    // Handle 204 No Content
    if (response.status === 204) {
      return null as T
    }

    return response.json()
  }

  async getBars(): Promise<Bar[]> {
    return this.request<Bar[]>('/bars')
  }

  async getPublishedBars(): Promise<Bar[]> {
    return this.request<Bar[]>('/published-bars')
  }

  async createBar(bar: Partial<Bar>): Promise<Bar> {
    return this.request<Bar>('/bars', {
      method: 'POST',
      body: JSON.stringify(bar),
    })
  }

  async updateBar(id: string, updates: Partial<Bar>): Promise<Bar> {
    return this.request<Bar>(`/bars/${id}`, {
      method: 'PUT',
      body: JSON.stringify(updates),
    })
  }

  async deleteBar(id: string): Promise<void> {
    return this.request<void>(`/bars/${id}`, {
      method: 'DELETE',
    })
  }

  async publish(): Promise<Bar[]> {
    return this.request<Bar[]>('/publish', { method: 'POST' })
  }

  async getFeatureFlags(): Promise<FeatureFlags> {
    return this.request<FeatureFlags>('/feature-flags')
  }
}

export const api = new ApiClient()
