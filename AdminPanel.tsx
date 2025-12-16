import { useState, useEffect } from 'react'
import { Plus, CreditCard as Edit, Trash2, Eye, EyeOff, Save, X } from 'lucide-react'
import { supabase, Product } from '../lib/supabase'

export default function AdminPanel() {
  const [products, setProducts] = useState<Product[]>([])
  const [isAddingProduct, setIsAddingProduct] = useState(false)
  const [editingProduct, setEditingProduct] = useState<string | null>(null)
  const [loading, setLoading] = useState(true)

  const [formData, setFormData] = useState({
    name: '',
    category: '',
    marketplace: '',
    old_price: '',
    new_price: '',
    marketplace_link: '',
    image_urls: ['', '', '', '']
  })

  useEffect(() => {
    fetchProducts()
  }, [])

  const fetchProducts = async () => {
    try {
      const { data, error } = await supabase
        .from('products')
        .select('*')
        .order('created_at', { ascending: false })

      if (error) throw error
      setProducts(data || [])
    } catch (error) {
      console.error('Error fetching products:', error)
    } finally {
      setLoading(false)
    }
  }

  const handleSaveProduct = async () => {
    try {
      const productData = {
        name: formData.name,
        category: formData.category,
        marketplace: formData.marketplace,
        old_price: parseFloat(formData.old_price),
        new_price: parseFloat(formData.new_price),
        marketplace_link: formData.marketplace_link,
        image_urls: formData.image_urls.filter(url => url.trim() !== '')
      }

      if (editingProduct) {
        const { error } = await supabase
          .from('products')
          .update(productData)
          .eq('product_id', editingProduct)

        if (error) throw error
      } else {
        const { error } = await supabase
          .from('products')
          .insert([productData])

        if (error) throw error
      }

      await fetchProducts()
      resetForm()
    } catch (error) {
      console.error('Error saving product:', error)
      alert('Error saving product')
    }
  }

  const handleEditProduct = (product: Product) => {
    setEditingProduct(product.product_id)
    setFormData({
      name: product.name,
      category: product.category,
      marketplace: product.marketplace,
      old_price: product.old_price.toString(),
      new_price: product.new_price.toString(),
      marketplace_link: product.marketplace_link,
      image_urls: [...product.image_urls, '', '', '', ''].slice(0, 4)
    })
    setIsAddingProduct(true)
  }

  const handleToggleActive = async (productId: string, isActive: boolean) => {
    try {
      const { error } = await supabase
        .from('products')
        .update({ is_active: !isActive })
        .eq('product_id', productId)

      if (error) throw error
      await fetchProducts()
    } catch (error) {
      console.error('Error updating product status:', error)
    }
  }

  const handleDeleteProduct = async (productId: string) => {
    if (!confirm('Are you sure you want to delete this product?')) return

    try {
      const { error } = await supabase
        .from('products')
        .delete()
        .eq('product_id', productId)

      if (error) throw error
      await fetchProducts()
    } catch (error) {
      console.error('Error deleting product:', error)
    }
  }

  const resetForm = () => {
    setFormData({
      name: '',
      category: '',
      marketplace: '',
      old_price: '',
      new_price: '',
      marketplace_link: '',
      image_urls: ['', '', '', '']
    })
    setIsAddingProduct(false)
    setEditingProduct(null)
  }

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      </div>
    )
  }

  return (
    <div className="min-h-screen bg-gray-50 p-4">
      <div className="max-w-6xl mx-auto">
        {/* Header */}
        <div className="flex justify-between items-center mb-6">
          <h1 className="text-2xl font-bold text-gray-900">Admin Panel</h1>
          <button
            onClick={() => setIsAddingProduct(true)}
            className="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200 flex items-center space-x-2"
          >
            <Plus className="w-4 h-4" />
            <span>Add Product</span>
          </button>
        </div>

        {/* Add/Edit Product Form */}
        {isAddingProduct && (
          <div className="bg-white rounded-lg shadow-md p-6 mb-6">
            <div className="flex justify-between items-center mb-4">
              <h2 className="text-lg font-semibold">
                {editingProduct ? 'Edit Product' : 'Add New Product'}
              </h2>
              <button
                onClick={resetForm}
                className="text-gray-500 hover:text-gray-700"
              >
                <X className="w-5 h-5" />
              </button>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <input
                type="text"
                placeholder="Product Name"
                value={formData.name}
                onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                className="border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
              
              <input
                type="text"
                placeholder="Category"
                value={formData.category}
                onChange={(e) => setFormData({ ...formData, category: e.target.value })}
                className="border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
              />

              <input
                type="text"
                placeholder="Marketplace"
                value={formData.marketplace}
                onChange={(e) => setFormData({ ...formData, marketplace: e.target.value })}
                className="border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
              />

              <input
                type="url"
                placeholder="Marketplace Link"
                value={formData.marketplace_link}
                onChange={(e) => setFormData({ ...formData, marketplace_link: e.target.value })}
                className="border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
              />

              <input
                type="number"
                step="0.01"
                placeholder="Old Price"
                value={formData.old_price}
                onChange={(e) => setFormData({ ...formData, old_price: e.target.value })}
                className="border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
              />

              <input
                type="number"
                step="0.01"
                placeholder="New Price"
                value={formData.new_price}
                onChange={(e) => setFormData({ ...formData, new_price: e.target.value })}
                className="border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            {/* Image URLs */}
            <div className="mt-4">
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Image URLs (up to 4)
              </label>
              {formData.image_urls.map((url, index) => (
                <input
                  key={index}
                  type="url"
                  placeholder={`Image URL ${index + 1}`}
                  value={url}
                  onChange={(e) => {
                    const newUrls = [...formData.image_urls]
                    newUrls[index] = e.target.value
                    setFormData({ ...formData, image_urls: newUrls })
                  }}
                  className="w-full border border-gray-300 rounded-lg px-3 py-2 mb-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              ))}
            </div>

            {/* Discount Preview */}
            {formData.old_price && formData.new_price && (
              <div className="mt-4 p-3 bg-green-50 rounded-lg border border-green-200">
                <span className="text-green-800 font-medium">
                  Discount: {Math.round(((parseFloat(formData.old_price) - parseFloat(formData.new_price)) / parseFloat(formData.old_price)) * 100)}%
                </span>
              </div>
            )}

            <div className="flex justify-end space-x-3 mt-6">
              <button
                onClick={resetForm}
                className="px-4 py-2 text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-lg transition-colors"
              >
                Cancel
              </button>
              <button
                onClick={handleSaveProduct}
                className="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors flex items-center space-x-2"
              >
                <Save className="w-4 h-4" />
                <span>{editingProduct ? 'Update' : 'Save'}</span>
              </button>
            </div>
          </div>
        )}

        {/* Products List */}
        <div className="bg-white rounded-lg shadow-md overflow-hidden">
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Product
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Pricing
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Discount
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Status
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-200">
                {products.map((product) => (
                  <tr key={product.product_id}>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div className="flex items-center">
                        {product.image_urls[0] && (
                          <img
                            className="h-10 w-10 rounded object-cover mr-4"
                            src={product.image_urls[0]}
                            alt={product.name}
                          />
                        )}
                        <div>
                          <div className="text-sm font-medium text-gray-900">
                            {product.name}
                          </div>
                          <div className="text-sm text-gray-500">
                            {product.category} • {product.marketplace}
                          </div>
                        </div>
                      </div>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div className="text-sm text-gray-900">
                        <span className="font-medium">${product.new_price}</span>
                        <span className="text-gray-500 line-through ml-2">
                          ${product.old_price}
                        </span>
                      </div>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <span className="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                        -{Math.round(product.discount_percent)}%
                      </span>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${
                        product.is_active 
                          ? 'bg-green-100 text-green-800' 
                          : 'bg-gray-100 text-gray-800'
                      }`}>
                        {product.is_active ? 'Active' : 'Inactive'}
                      </span>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                      <div className="flex space-x-2">
                        <button
                          onClick={() => handleToggleActive(product.product_id, product.is_active)}
                          className="text-blue-600 hover:text-blue-900"
                        >
                          {product.is_active ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                        </button>
                        <button
                          onClick={() => handleEditProduct(product)}
                          className="text-indigo-600 hover:text-indigo-900"
                        >
                          <Edit className="w-4 h-4" />
                        </button>
                        <button
                          onClick={() => handleDeleteProduct(product.product_id)}
                          className="text-red-600 hover:text-red-900"
                        >
                          <Trash2 className="w-4 h-4" />
                        </button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  )
}