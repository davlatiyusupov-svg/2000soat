import { createClient } from '@supabase/supabase-js'

const supabaseUrl = import.meta.env.VITE_SUPABASE_URL
const supabaseAnonKey = import.meta.env.VITE_SUPABASE_ANON_KEY

export const supabase = createClient(supabaseUrl, supabaseAnonKey)

export interface Product {
  product_id: string
  name: string
  category: string
  marketplace: string
  old_price: number
  new_price: number
  discount_percent: number
  image_urls: string[]
  marketplace_link: string
  is_active: boolean
  created_at: string
  updated_at: string
}

export interface Admin {
  admin_id: string
  username: string
  telegram_id?: string
  role: string
  created_at: string
}