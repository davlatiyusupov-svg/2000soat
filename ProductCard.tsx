import { useState } from 'react'
import { ChevronLeft, ChevronRight, ExternalLink } from 'lucide-react'
import { Product } from '../lib/supabase'
import { openInMarketplace, telegramWebApp } from '../lib/telegram'

interface ProductCardProps {
  product: Product
}

export default function ProductCard({ product }: ProductCardProps) {
  const [currentImageIndex, setCurrentImageIndex] = useState(0)

  const nextImage = () => {
    setCurrentImageIndex((prev) => 
      prev === product.image_urls.length - 1 ? 0 : prev + 1
    )
    telegramWebApp?.HapticFeedback.selectionChanged()
  }

  const prevImage = () => {
    setCurrentImageIndex((prev) => 
      prev === 0 ? product.image_urls.length - 1 : prev - 1
    )
    telegramWebApp?.HapticFeedback.selectionChanged()
  }

  const handleOpenMarketplace = () => {
    telegramWebApp?.HapticFeedback.impactOccurred('light')
    openInMarketplace(product.marketplace_link)
  }

  return (
    <div className="bg-white rounded-xl shadow-md overflow-hidden transition-all duration-300 hover:shadow-lg">
      {/* Image Carousel */}
      <div className="relative h-48 overflow-hidden">
        {product.image_urls.length > 0 && (
          <img
            src={product.image_urls[currentImageIndex]}
            alt={product.name}
            className="w-full h-full object-cover transition-transform duration-300"
          />
        )}
        
        {product.image_urls.length > 1 && (
          <>
            <button
              onClick={prevImage}
              className="absolute left-2 top-1/2 -translate-y-1/2 bg-black/50 text-white rounded-full p-1.5 backdrop-blur-sm transition-opacity hover:bg-black/70"
            >
              <ChevronLeft className="w-4 h-4" />
            </button>
            <button
              onClick={nextImage}
              className="absolute right-2 top-1/2 -translate-y-1/2 bg-black/50 text-white rounded-full p-1.5 backdrop-blur-sm transition-opacity hover:bg-black/70"
            >
              <ChevronRight className="w-4 h-4" />
            </button>
          </>
        )}

        {/* Image indicators */}
        {product.image_urls.length > 1 && (
          <div className="absolute bottom-2 left-1/2 -translate-x-1/2 flex space-x-1">
            {product.image_urls.map((_, index) => (
              <button
                key={index}
                onClick={() => setCurrentImageIndex(index)}
                className={`w-1.5 h-1.5 rounded-full transition-colors ${
                  index === currentImageIndex ? 'bg-white' : 'bg-white/50'
                }`}
              />
            ))}
          </div>
        )}

        {/* Discount Badge */}
        <div className="absolute top-3 left-3 bg-red-500 text-white px-2 py-1 rounded-lg text-sm font-bold shadow-lg">
          -{Math.round(product.discount_percent)}%
        </div>
      </div>

      {/* Product Info */}
      <div className="p-4">
        <div className="flex items-center justify-between mb-2">
          <span className="text-xs font-medium text-gray-500 uppercase tracking-wide">
            {product.category}
          </span>
          <span className="text-xs text-gray-400">
            {product.marketplace}
          </span>
        </div>

        <h3 className="font-semibold text-gray-900 mb-3 line-clamp-2 text-sm leading-tight">
          {product.name}
        </h3>

        {/* Pricing */}
        <div className="flex items-baseline space-x-2 mb-4">
          <span className="text-lg font-bold text-red-600">
            ${product.new_price.toFixed(2)}
          </span>
          <span className="text-sm text-gray-500 line-through">
            ${product.old_price.toFixed(2)}
          </span>
        </div>

        {/* CTA Button */}
        <button
          onClick={handleOpenMarketplace}
          className="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 px-4 rounded-lg transition-colors duration-200 flex items-center justify-center space-x-2 text-sm"
        >
          <span>Open in marketplace</span>
          <ExternalLink className="w-4 h-4" />
        </button>
      </div>
    </div>
  )
}