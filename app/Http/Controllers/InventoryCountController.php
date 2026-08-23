<?php

namespace App\Http\Controllers;

use App\Models\InventoryCount;
use App\Models\InventoryCountItem;
use App\Models\Warehouse;
use App\Models\Category;
use App\Services\InventoryCountService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InventoryCountController extends Controller
{
    protected $inventoryCountService;

    public function __construct(InventoryCountService $inventoryCountService)
    {
        $this->inventoryCountService = $inventoryCountService;
    }

    public function index(Request $request)
    {
        $query = InventoryCount::with(['warehouse', 'category'])
            ->search($request->search)
            ->status($request->status)
            ->when($request->warehouse_id, function ($q) use ($request) {
                return $q->where('warehouse_id', $request->warehouse_id);
            })
            ->when($request->start_date, function ($q) use ($request) {
                return $q->whereDate('count_date', '>=', $request->start_date);
            })
            ->when($request->end_date, function ($q) use ($request) {
                return $q->whereDate('count_date', '<=', $request->end_date);
            });

        $counts = $query->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        // إضافة الملخص لكل جرد
        $counts->getCollection()->transform(function ($count) {
            $count->summary = $count->summary;
            $count->progress = $count->progress;
            return $count;
        });

        // الإحصائيات
        $stats = [
            'total' => InventoryCount::count(),
            'in_progress' => InventoryCount::where('status', 'in_progress')->count(),
            'completed' => InventoryCount::where('status', 'completed')->count(),
            'total_surplus' => InventoryCount::where('status', 'completed')
                ->with('items')
                ->get()
                ->sum(function ($count) {
                    return $count->items->where('difference', '>', 0)->sum('difference_value');
                }),
            'total_shortage' => InventoryCount::where('status', 'completed')
                ->with('items')
                ->get()
                ->sum(function ($count) {
                    return abs($count->items->where('difference', '<', 0)->sum('difference_value'));
                }),
        ];

        $warehouses = Warehouse::all();
        $statuses = \App\Enums\InventoryCountStatus::labels();

        return Inertia::render('InventoryCount/Index', [
            'counts' => $counts,
            'stats' => $stats,
            'filters' => [
                'search' => $request->search,
                'status' => $request->status,
                'warehouse_id' => $request->warehouse_id,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
            ],
            'warehouses' => $warehouses,
            'statuses' => $statuses,
        ]);
    }

    public function create()
    {
        $warehouses = Warehouse::all();
        $categories = Category::active()->get(['id', 'name']);

        return Inertia::render('InventoryCount/Create', [
            'warehouses' => $warehouses,
            'categories' => $categories,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'warehouse_id' => ['required', 'exists:warehouses,id,is_active,1'],
            'category_id' => ['nullable', 'exists:categories,id,deleted_at,NULL'],
            'count_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
            'scope' => ['required', 'string', 'in:all,category'],
        ]);

        $inventoryCount = $this->inventoryCountService->create($validated);

        return redirect()->route('inventory-counts.show', $inventoryCount)
            ->with('success', 'تم بدء جلسة الجرد بنجاح');
    }

    public function show(InventoryCount $inventoryCount)
    {
        $inventoryCount->load(['items.product', 'warehouse', 'category']);
        $inventoryCount->summary = $inventoryCount->summary;
        $inventoryCount->progress = $inventoryCount->progress;

        $statuses = \App\Enums\InventoryCountStatus::labels();

        return Inertia::render('InventoryCount/Show', [
            'count' => $inventoryCount,
            'statuses' => $statuses,
        ]);
    }

    public function updateItem(Request $request, InventoryCountItem $item)
    {
        $validated = $request->validate([
            'actual_quantity' => ['required', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->inventoryCountService->updateItem(
                $item,
                $validated['actual_quantity'],
                $validated['notes'] ?? null
            );

            return redirect()->back()->with('success', 'تم تحديث الكمية بنجاح');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function complete(InventoryCount $inventoryCount)
    {
        try {
            $this->inventoryCountService->complete($inventoryCount);

            return redirect()->route('inventory-counts.show', $inventoryCount)
                ->with('success', 'تم اعتماد الجرد بنجاح');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function cancel(Request $request, InventoryCount $inventoryCount)
    {
        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->inventoryCountService->cancel(
                $inventoryCount,
                $validated['notes'] ?? null
            );

            return redirect()->route('inventory-counts.index')
                ->with('success', 'تم إلغاء الجرد بنجاح');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
