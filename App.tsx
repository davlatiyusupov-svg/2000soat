import { useState, useEffect } from 'react'
import { Target, Settings } from 'lucide-react'
import { supabase, Product } from './lib/supabase'
import { initTelegramApp, getTelegramUser } from './lib/telegram'
import ProductCard from './components/ProductCard'
import FilterBar from './components/FilterBar'
import AdminPanel from './components/AdminPanel'

function App() {
  const [products, setProducts] = useState<Product[]>([])
  const [filteredProducts, setFilteredProducts] = useState<Product[]>([])
  const [loading, setLoading] = useState(true)
  const [showAdmin, setShowAdmin] = useState(false)
  const [user, setUser] = useState<any>(null)

  // Unique values for filters
  const marketplaces = [...new Set(products.map(p => p.marketplace))]
  const categories = [...new Set(products.map(p => p.category))]

  useEffect(() => {
    // Initialize Telegram WebApp
    initTelegramApp()
    setUser(getTelegramUser())
    
    fetchProducts()
  }, [])

  const fetchProducts = async () => {
    try {
      const { data, error } = await supabase
        .from('products')
        .select('*')
        .eq('is_active', true)
        .order('discount_percent', { ascending: false })

      if (error) throw error
      setProducts(data || [])
      setFilteredProducts(data || [])
    } catch (error) {
      console.error('Error fetching products:', error)
    } finally {
      setLoading(false)
    }
  }

  const handleFilterChange = (filters: {
    marketplace: string
    category: string
    minDiscount: number
  }) => {
    let filtered = products

    if (filters.marketplace) {
      filtered = filtered.filter(p => p.marketplace === filters.marketplace)
    }

    if (filters.category) {
      filtered = filtered.filter(p => p.category === filters.category)
    }

    if (filters.minDiscount > 0) {
      filtered = filtered.filter(p => p.discount_percent >= filters.minDiscount)
    }

    setFilteredProducts(filtered)
  }

  if (showAdmin) {
    return <AdminPanel />
  }

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <div className="bg-white border-b border-gray-200 sticky top-0 z-50">
        <div className="px-4 py-3 flex items-center justify-between">
          <div className="flex items-center space-x-3">
            <div className="bg-red-500 p-2 rounded-lg">
              <Target className="w-6 h-6 text-white" />
            </div>
            <div>
              <h1 className="text-lg font-bold text-gray-900">Market Sniper</h1>
              {user && (
                <p className="text-xs text-gray-500">
                  Welcome, {user.first_name}!
                </p>
              )}
            </div>
          </div>
          
          <button
            onClick={() => setShowAdmin(!showAdmin)}
            className="p-2 text-gray-500 hover:text-gray-700 transition-colors"
          >
            <Settings className="w-5 h-5" />
          </button>
        </div>
      </div>

      {/* Filter Bar */}
      <FilterBar
        marketplaces={marketplaces}
        categories={categories}
        onFilterChange={handleFilterChange}
      />

      {/* Products Grid */}
      <div className="px-4 py-6">
        {loading ? (
          <div className="flex items-center justify-center py-12">
            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-red-500"></div>
          </div>
        ) : filteredProducts.length === 0 ? (
          <div className="text-center py-12">
            <Target className="w-12 h-12 text-gray-400 mx-auto mb-4" />
            <h3 className="text-lg font-medium text-gray-900 mb-2">
              No deals found
            </h3>
            <p className="text-gray-500">
              Try adjusting your filters or check back later for new deals!
            </p>
          </div>
        ) : (
          <>
            <div className="flex items-center justify-between mb-4">
              <h2 className="text-lg font-semibold text-gray-900">
                🔥 Hot Deals ({filteredProducts.length})
              </h2>
            </div>
            
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
              {filteredProducts.map((product) => (
                <ProductCard key={product.product_id} product={product} />
              ))}
            </div>
          </>
        )}
      </div>

      {/* Footer */}
      <div className="bg-white border-t border-gray-200 p-4 text-center">
        <p className="text-sm text-gray-500">
          Market Sniper • Find the best deals across all marketplaces
        </p>
      </div>
    </div>
  )
}

export default App