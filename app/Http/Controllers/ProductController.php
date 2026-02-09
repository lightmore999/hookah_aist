<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductRecipeItem;
use Illuminate\Http\Request;

class ProductController extends Controller
{
   public function index(Request $request)
    {
        $categories = ProductCategory::orderBy('name')->get();
        
        // Загружаем продукты с их компонентами
        $query = Product::with(['category', 'recipeComponents.component'])->latest();
        
        if ($request->filled('category_id')) {
            $query->where('product_category_id', $request->category_id);
        }
        
        // Фильтр по активности
        if ($request->filled('is_active')) {
            $isActive = $request->is_active === 'true';
            $query->where('is_active', $isActive);
        }
        
        // Сортировка по популярности (по желанию)
        if ($request->filled('sort_by')) {
            if ($request->sort_by === 'popularity') {
                $query->orderBy('popularity', 'desc');
            } elseif ($request->sort_by === 'name') {
                $query->orderBy('name');
            }
        }
        
        $products = $query->get();
        
        // Все продукты для селектов (только активные)
        $allProducts = Product::active()->orderBy('name')->get(['id', 'name', 'unit', 'cost']);
        
        return view('products.index', compact('products', 'categories', 'allProducts'));
    }

    public function create()
    {
        $categories = ProductCategory::orderBy('name')->get();
        $units = ['шт', 'г', 'мл', 'кг', 'л'];
        
        return view('products.create', compact('categories', 'units'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'product_category_id' => 'required|exists:product_categories,id',
            'price' => 'required|numeric|min:0',
            'cost' => 'required|numeric|min:0',
            'unit' => 'required|in:шт,г,мл,кг,л',
            'barcode' => 'nullable|string|max:255|unique:products,barcode',
            'article_number' => 'nullable|string|max:255|unique:products,article_number',
            'is_active' => 'boolean', // Добавляем валидацию
            'popularity' => 'integer|min:0', // Добавляем валидацию
            'components_data' => 'nullable|string',
        ]);
        
        // Установка значений по умолчанию, если не переданы
        $validated['is_active'] = $request->has('is_active') ? $request->is_active : true;
        $validated['popularity'] = $request->filled('popularity') ? $request->popularity : 0;
        
        // Создаем продукт
        $product = Product::create($validated);
        
        // Добавляем компоненты, если они есть
        if ($request->filled('components_data')) {
            try {
                $components = json_decode($request->components_data, true);
                
                if (is_array($components) && count($components) > 0) {
                    foreach ($components as $component) {
                        if ($product->id != $component['product_id']) {
                            ProductRecipeItem::create([
                                'parent_product_id' => $product->id,
                                'component_product_id' => $component['product_id'],
                                'quantity' => $component['quantity'],
                            ]);
                        }
                    }
                }
            } catch (\Exception $e) {
                \Log::error('Ошибка при добавлении компонентов: ' . $e->getMessage());
            }
        }
        
        return redirect()->route('products.index')
            ->with('success', 'Товар успешно добавлен!');
    }

    public function show(Product $product)
    {
        $product->load(['recipeComponents.component', 'category']);
        
        return view('products.show', compact('product'));
    }

    public function edit(Product $product)
    {
        $categories = ProductCategory::orderBy('name')->get();
        $units = ['шт', 'г', 'мл', 'кг', 'л'];
        
        $product->load('recipeComponents.component');
        
        return view('products.edit', compact('product', 'categories', 'units'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'product_category_id' => 'required|exists:product_categories,id',
            'price' => 'required|numeric|min:0',
            'cost' => 'required|numeric|min:0',
            'unit' => 'required|in:шт,г,мл,кг,л',
            'barcode' => 'nullable|string|max:255|unique:products,barcode,' . $product->id,
            'article_number' => 'nullable|string|max:255|unique:products,article_number,' . $product->id,
            'is_active' => 'boolean', // Добавляем валидацию
            'popularity' => 'integer|min:0', // Добавляем валидацию
            'components_data' => 'nullable|string',
        ]);
        
        // Убеждаемся, что значение popularity есть (если не передано, оставляем текущее)
        if (!$request->filled('popularity')) {
            unset($validated['popularity']); // Не обновляем, если не передано
        }
        
        // Обработка checkbox (если не отмечен, значит false)
        $validated['is_active'] = $request->has('is_active');
        
        // Обновляем продукт
        $product->update($validated);
        
        // Обработка компонентов
        if ($request->filled('components_data')) {
            try {
                $components = json_decode($request->components_data, true);
                
                // Удаляем старые компоненты
                ProductRecipeItem::where('parent_product_id', $product->id)->delete();
                
                // Добавляем новые компоненты
                if (is_array($components) && count($components) > 0) {
                    foreach ($components as $component) {
                        if ($product->id != $component['product_id']) {
                            ProductRecipeItem::create([
                                'parent_product_id' => $product->id,
                                'component_product_id' => $component['product_id'],
                                'quantity' => $component['quantity'],
                            ]);
                        }
                    }
                }
                
            } catch (\Exception $e) {
                \Log::error('Ошибка при обновлении компонентов: ' . $e->getMessage());
                return back()->with('error', 'Ошибка при обновлении компонентов: ' . $e->getMessage());
            }
        } else {
            // Если поле пустое, удаляем все компоненты
            ProductRecipeItem::where('parent_product_id', $product->id)->delete();
        }
        
        return redirect()->route('products.index')
            ->with('success', 'Товар успешно обновлён!');
    }

    public function destroy(Product $product)
    {   
        if ($product->usedInRecipes()->exists()) {
            return redirect()->route('products.index')
                ->with('error', 'Нельзя удалить товар, так как он используется в составе других продуктов!');
        }
        
        if ($product->stocks()->exists()) {
            return redirect()->route('products.index')
                ->with('error', 'Нельзя удалить товар, так как у него есть остатки на складе!');
        }
        
        $product->delete();

        return redirect()->route('products.index')
            ->with('success', 'Товар успешно удалён!');
    }
    
    public function byCategory($categoryId)
    {
        $products = Product::where('product_category_id', $categoryId)
            ->orderBy('name')
            ->get(['id', 'name', 'unit', 'price']);
        
        return response()->json($products);
    }

    public function addComponent(Request $request, Product $product)
    {
        $validated = $request->validate([
            'component_product_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric|min:0.001',
        ]);
        
        if ($product->id == $validated['component_product_id']) {
            return response()->json([
                'success' => false,
                'message' => 'Нельзя добавить продукт сам в себя'
            ], 422);
        }
        
        if ($product->recipeComponents()
            ->where('component_product_id', $validated['component_product_id'])
            ->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Этот продукт уже добавлен в состав'
            ], 422);
        }
        
        $component = ProductRecipeItem::create([
            'parent_product_id' => $product->id,
            'component_product_id' => $validated['component_product_id'],
            'quantity' => $validated['quantity'],
        ]);
        
        $component->load('component');
        
        return response()->json([
            'success' => true,
            'message' => 'Компонент добавлен',
            'component' => $component,
            'formatted_quantity' => $component->formatted_quantity,
        ]);
    }

    public function removeComponent(Product $product, ProductRecipeItem $component)
    {
        if ($component->parent_product_id != $product->id) {
            return response()->json([
                'success' => false,
                'message' => 'Компонент не принадлежит этому продукту'
            ], 403);
        }
        
        $component->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Компонент удален'
        ]);
    }

    public function getAvailableComponents(Product $product)
    {
        // Для создания продукта (product=0) показываем все активные продукты
        if ($product->id == 0) {
            $availableProducts = Product::active()->orderBy('name')
                ->get(['id', 'name', 'unit', 'price', 'cost', 'is_active']);
        } else {
            // Для редактирования - исключаем уже добавленные и сам продукт
            $existingIds = $product->recipeComponents()->pluck('component_product_id')->toArray();
            
            // Добавляем ID самого продукта
            $existingIds[] = $product->id;
            
            // Исключаем также продукты, которые уже содержат текущий продукт
            $productsThatContainThis = ProductRecipeItem::where('component_product_id', $product->id)
                ->pluck('parent_product_id')
                ->toArray();
            $existingIds = array_merge($existingIds, $productsThatContainThis);
            
            $availableProducts = Product::whereNotIn('id', array_unique($existingIds))
                ->active() // Только активные товары
                ->orderBy('name')
                ->get(['id', 'name', 'unit', 'price', 'cost', 'is_active']);
        }
        
        return response()->json($availableProducts);
    }

    public function getComponents(Product $product)
    {
        $components = $product->recipeComponents()->with('component')->get();
        
        $componentsData = $components->map(function($component) {
            return [
                'id' => $component->id,
                'product_id' => $component->component_product_id,
                'product_name' => $component->component->name,
                'quantity' => $component->quantity,
                'unit' => $component->component->unit,
                'cost' => $component->component->cost,
                'total_cost' => $this->calculateComponentCost($component->component, $component->quantity),
            ];
        });
        
        return response()->json([
            'components' => $componentsData
        ]);
    }

    /**
     * Рассчитать стоимость компонента
     */
    private function calculateComponentCost($product, $quantity)
    {
        $cost = $product->cost * $quantity;
        return round($cost, 2);
    }

    /**
     * Быстрое изменение активности товара (AJAX)
     */
    public function toggleActive(Request $request, Product $product)
    {
        $request->validate([
            'is_active' => 'required|boolean',
        ]);
        
        $product->update(['is_active' => $request->is_active]);
        
        return response()->json([
            'success' => true,
            'message' => 'Статус товара обновлен',
            'is_active' => $product->is_active,
        ]);
    }

    /**
     * Обновление популярности (AJAX)
     */
    public function updatePopularity(Request $request, Product $product)
    {
        $request->validate([
            'popularity' => 'required|integer|min:0',
        ]);
        
        $product->update(['popularity' => $request->popularity]);
        
        return response()->json([
            'success' => true,
            'message' => 'Популярность обновлена',
            'popularity' => $product->popularity,
        ]);
    }

    /**
     * Сброс популярности (AJAX)
     */
    public function resetPopularity(Product $product)
    {
        $product->resetPopularity();
        
        return response()->json([
            'success' => true,
            'message' => 'Популярность сброшена',
            'popularity' => $product->popularity,
        ]);
    }

    /**
     * Получить популярные товары (API)
     */
    public function getPopularProducts(Request $request)
    {
        $limit = $request->get('limit', 10);
        $categoryId = $request->get('category_id');
        
        $query = Product::active()->popular($limit);
        
        if ($categoryId) {
            $query->where('product_category_id', $categoryId);
        }
        
        $products = $query->get(['id', 'name', 'price', 'unit', 'popularity']);
        
        return response()->json([
            'success' => true,
            'products' => $products,
        ]);
    }
}