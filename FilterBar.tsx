import { useState } from 'react'
import { Filter, ChevronDown } from 'lucide-react'

interface FilterBarProps {
  marketplaces: string[]
  categories: string[]
  onFilterChange: (filters: {
    marketplace: string
    category: string
    minDiscount: number
  }) => void
}

export default function FilterBar({ marketplaces, categories, onFilterChange }: FilterBarProps) {
  const [isOpen, setIsOpen] = useState(false)
  const [selectedMarketplace, setSelectedMarketplace] = useState('')
  const [selectedCategory, setSelectedCategory] = useState('')
  const [minDiscount, setMinDiscount] = useState(0)

  const handleFilterUpdate = () => {
    onFilterChange({
      marketplace: selectedMarketplace,
      category: selectedCategory,
      minDiscount
    })
  }

  const clearFilters = () => {
    setSelectedMarketplace('')
    setSelectedCategory('')
    setMinDiscount(0)
    onFilterChange({
      marketplace: '',
      category: '',
      minDiscount: 0
    })
  }

  const hasActiveFilters = selectedMarketplace || selectedCategory || minDiscount > 0

  return (
    <div className="bg-white border-b border-gray-200">
      <div className="px-4 py-3">
        <button
          onClick={() => setIsOpen(!isOpen)}
          className="flex items-center justify-between w-full text-left"
        >
          <div className="flex items-center space-x-2">
            <Filter className="w-4 h-4 text-gray-500" />
            <span className="font-medium text-gray-700">
              Filters
              {hasActiveFilters && (
                <span className="ml-1 text-xs text-blue-600 font-semibold">
                  (Active)
                </span>
              )}
            </span>
          </div>
          <ChevronDown className={`w-4 h-4 text-gray-500 transition-transform ${
            isOpen ? 'rotate-180' : ''
          }`} />
        </button>
      </div>

      {isOpen && (
        <div className="px-4 pb-4 space-y-4 border-t border-gray-100">
          {/* Marketplace Filter */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Marketplace
            </label>
            <select
              value={selectedMarketplace}
              onChange={(e) => {
                setSelectedMarketplace(e.target.value)
                setTimeout(handleFilterUpdate, 0)
              }}
              className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value="">All marketplaces</option>
              {marketplaces.map((marketplace) => (
                <option key={marketplace} value={marketplace}>
                  {marketplace}
                </option>
              ))}
            </select>
          </div>

          {/* Category Filter */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Category
            </label>
            <select
              value={selectedCategory}
              onChange={(e) => {
                setSelectedCategory(e.target.value)
                setTimeout(handleFilterUpdate, 0)
              }}
              className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value="">All categories</option>
              {categories.map((category) => (
                <option key={category} value={category}>
                  {category}
                </option>
              ))}
            </select>
          </div>

          {/* Discount Filter */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Minimum discount: {minDiscount}%
            </label>
            <input
              type="range"
              min="0"
              max="90"
              step="5"
              value={minDiscount}
              onChange={(e) => {
                setMinDiscount(Number(e.target.value))
                setTimeout(handleFilterUpdate, 0)
              }}
              className="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer slider"
            />
            <div className="flex justify-between text-xs text-gray-500 mt-1">
              <span>0%</span>
              <span>90%</span>
            </div>
          </div>

          {/* Clear Filters */}
          {hasActiveFilters && (
            <button
              onClick={clearFilters}
              className="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-2 px-4 rounded-lg transition-colors duration-200 text-sm"
            >
              Clear all filters
            </button>
          )}
        </div>
      )}
    </div>
  )
}