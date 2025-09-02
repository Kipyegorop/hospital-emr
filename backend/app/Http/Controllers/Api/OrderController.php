<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\LoincCode;
use App\Models\Patient;
use App\Models\Encounter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Display a listing of orders
     */
    public function index(Request $request)
    {
        $query = Order::with(['patient', 'orderingPhysician', 'department', 'targetDepartment', 'orderItems']);

        // Filter by patient
        if ($request->has('patient_id')) {
            $query->where('patient_id', $request->patient_id);
        }

        // Filter by order type
        if ($request->has('order_type')) {
            $query->where('order_type', $request->order_type);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by priority
        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        // Filter by target department (for worklists)
        if ($request->has('target_department_id')) {
            $query->where('target_department_id', $request->target_department_id);
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->whereDate('ordered_at', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('ordered_at', '<=', $request->date_to);
        }

        // Show only overdue orders
        if ($request->boolean('overdue_only')) {
            $query->overdue();
        }

        // Order by priority and date
        $orders = $query->byPriority()->paginate(50);

        return response()->json([
            'status' => 'success',
            'data' => [
                'orders' => $orders->items(),
                'pagination' => [
                    'current_page' => $orders->currentPage(),
                    'last_page' => $orders->lastPage(),
                    'per_page' => $orders->perPage(),
                    'total' => $orders->total(),
                ]
            ]
        ]);
    }

    /**
     * Store a newly created order (CPOE)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'encounter_id' => 'nullable|exists:encounters,id',
            'target_department_id' => 'required|exists:departments,id',
            'order_type' => 'required|in:laboratory,radiology,procedure,medication,consultation,other',
            'priority' => 'required|in:routine,urgent,stat,asap',
            'clinical_indication' => 'required|string|max:2000',
            'special_instructions' => 'nullable|string|max:2000',
            'scheduled_at' => 'nullable|date|after:now',
            'billable' => 'boolean',
            'order_items' => 'required|array|min:1',
            'order_items.*.item_name' => 'required|string|max:255',
            'order_items.*.item_code' => 'nullable|string|max:50',
            'order_items.*.loinc_code' => 'nullable|string|max:20',
            'order_items.*.quantity' => 'integer|min:1|max:100',
            'order_items.*.unit_of_measure' => 'nullable|string|max:50',
            'order_items.*.specimen_type' => 'nullable|string|max:100',
            'order_items.*.collection_instructions' => 'nullable|string|max:1000',
            'order_items.*.preparation_instructions' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Initialize variables
        $order = null;
        $totalCost = 0;

        DB::transaction(function () use ($request, &$order, &$totalCost) {
            // Generate order number
            $orderNumber = Order::generateOrderNumber($request->order_type);

            // Create the order
            $order = Order::create([
                'order_number' => $orderNumber,
                'patient_id' => $request->patient_id,
                'encounter_id' => $request->encounter_id,
                'ordering_physician_id' => $request->user()->id,
                'department_id' => $request->user()->department_id,
                'target_department_id' => $request->target_department_id,
                'order_type' => $request->order_type,
                'priority' => $request->priority,
                'status' => 'pending',
                'ordered_at' => now(),
                'scheduled_at' => $request->scheduled_at,
                'clinical_indication' => $request->clinical_indication,
                'special_instructions' => $request->special_instructions,
                'billable' => $request->boolean('billable', true),
            ]);

            // Create order items
            foreach ($request->order_items as $itemData) {
                // Look up LOINC code if provided
                $loincCode = null;
                if (!empty($itemData['loinc_code'])) {
                    $loincCode = LoincCode::where('loinc_num', $itemData['loinc_code'])->first();
                }

                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'loinc_code_id' => $loincCode?->id,
                    'item_code' => $itemData['item_code'] ?? null,
                    'item_name' => $itemData['item_name'],
                    'item_description' => $itemData['item_description'] ?? null,
                    'item_category' => $itemData['item_category'] ?? null,
                    'loinc_code' => $itemData['loinc_code'] ?? null,
                    'loinc_display_name' => $loincCode?->display_name,
                    'quantity' => $itemData['quantity'] ?? 1,
                    'unit_of_measure' => $itemData['unit_of_measure'] ?? null,
                    'specimen_type' => $itemData['specimen_type'] ?? null,
                    'collection_instructions' => $itemData['collection_instructions'] ?? null,
                    'preparation_instructions' => $itemData['preparation_instructions'] ?? null,
                    'unit_price' => $loincCode?->standard_price ?? 0,
                    'billing_code' => $itemData['billing_code'] ?? null,
                ]);

                // Calculate total price
                $orderItem->calculateTotalPrice();
                $orderItem->save();

                $totalCost += $orderItem->total_price ?? 0;
            }

            // Update order with estimated cost
            $order->update(['estimated_cost' => $totalCost]);
        });

        $order->load(['patient', 'orderingPhysician', 'department', 'targetDepartment', 'orderItems']);

        return response()->json([
            'status' => 'success',
            'message' => 'Order created successfully',
            'data' => [
                'order' => $order,
                'estimated_cost' => $totalCost,
            ]
        ], 201);
    }

    /**
     * Display the specified order
     */
    public function show(Order $order)
    {
        $order->load(['patient', 'orderingPhysician', 'department', 'targetDepartment', 'orderItems.loincCode', 'acknowledgedBy', 'completedBy']);

        return response()->json([
            'status' => 'success',
            'data' => [
                'order' => $order,
                'is_overdue' => $order->is_overdue,
                'time_remaining' => $order->time_remaining,
            ]
        ]);
    }

    /**
     * Update order status and workflow
     */
    public function update(Request $request, Order $order)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|in:pending,acknowledged,in_progress,completed,cancelled,discontinued',
            'special_instructions' => 'nullable|string|max:2000',
            'notes' => 'nullable|string|max:2000',
            'scheduled_at' => 'nullable|date',
            'cancellation_reason' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $updateData = $request->only(['special_instructions', 'notes', 'scheduled_at', 'cancellation_reason']);

        // Handle status changes with appropriate timestamps and user tracking
        if ($request->has('status')) {
            $newStatus = $request->status;
            $currentUserId = $request->user()->id;

            switch ($newStatus) {
                case 'acknowledged':
                    $updateData['acknowledged_at'] = now();
                    $updateData['acknowledged_by_user_id'] = $currentUserId;
                    break;
                case 'in_progress':
                    $updateData['started_at'] = now();
                    break;
                case 'completed':
                    $updateData['completed_at'] = now();
                    $updateData['completed_by_user_id'] = $currentUserId;
                    break;
                case 'cancelled':
                case 'discontinued':
                    $updateData['cancelled_at'] = now();
                    $updateData['cancelled_by_user_id'] = $currentUserId;
                    if (!$request->has('cancellation_reason')) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Cancellation reason is required'
                        ], 422);
                    }
                    break;
            }

            $updateData['status'] = $newStatus;
        }

        $order->update($updateData);
        $order->load(['patient', 'orderingPhysician', 'department', 'targetDepartment', 'orderItems']);

        return response()->json([
            'status' => 'success',
            'message' => 'Order updated successfully',
            'data' => ['order' => $order]
        ]);
    }

    /**
     * Get department worklist
     */
    public function worklist(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'department_id' => 'required|exists:departments,id',
            'status' => 'nullable|in:pending,acknowledged,in_progress',
            'priority' => 'nullable|in:routine,urgent,stat,asap',
            'order_type' => 'nullable|in:laboratory,radiology,procedure,medication,consultation,other',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $query = Order::with(['patient', 'orderingPhysician', 'orderItems'])
            ->forDepartment($request->department_id)
            ->whereNotIn('status', ['completed', 'cancelled', 'discontinued']);

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->has('order_type')) {
            $query->where('order_type', $request->order_type);
        }

        // Get today's orders by default, or specific date range
        if ($request->has('date_from')) {
            $query->whereDate('ordered_at', '>=', $request->date_from);
        } else {
            $query->whereDate('ordered_at', today());
        }

        if ($request->has('date_to')) {
            $query->whereDate('ordered_at', '<=', $request->date_to);
        }

        $orders = $query->byPriority()->get();

        // Group orders by status for better workflow management
        $worklist = [
            'pending' => $orders->where('status', 'pending')->values(),
            'acknowledged' => $orders->where('status', 'acknowledged')->values(),
            'in_progress' => $orders->where('status', 'in_progress')->values(),
            'overdue' => $orders->filter(fn($order) => $order->is_overdue)->values(),
            'statistics' => [
                'total_orders' => $orders->count(),
                'pending_count' => $orders->where('status', 'pending')->count(),
                'acknowledged_count' => $orders->where('status', 'acknowledged')->count(),
                'in_progress_count' => $orders->where('status', 'in_progress')->count(),
                'overdue_count' => $orders->filter(fn($order) => $order->is_overdue)->count(),
                'stat_priority_count' => $orders->where('priority', 'stat')->count(),
                'urgent_priority_count' => $orders->where('priority', 'urgent')->count(),
            ]
        ];

        return response()->json([
            'status' => 'success',
            'data' => $worklist
        ]);
    }

    /**
     * Search LOINC codes for order entry
     */
    public function searchLoinc(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'term' => 'required|string|min:2',
            'department' => 'nullable|string',
            'class' => 'nullable|string',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $results = LoincCode::search(
            $request->term,
            $request->department,
            $request->get('limit', 50)
        );

        if ($request->has('class')) {
            $results = $results->where('class', $request->class);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'loinc_codes' => $results->map(function ($code) {
                    return [
                        'id' => $code->id,
                        'loinc_num' => $code->loinc_num,
                        'component' => $code->component,
                        'display_name' => $code->display_name,
                        'long_common_name' => $code->long_common_name,
                        'class' => $code->class,
                        'department' => $code->department,
                        'standard_price' => $code->standard_price,
                        'specimen_requirements' => $code->specimen_requirements,
                        'reference_range' => $code->reference_range,
                        'turnaround_time_hours' => $code->turnaround_time_hours,
                    ];
                })
            ]
        ]);
    }

    /**
     * Get common LOINC codes for quick selection
     */
    public function commonLoinc(Request $request)
    {
        $department = $request->get('department');
        $codes = LoincCode::getCommonCodes($department);

        return response()->json([
            'status' => 'success',
            'data' => [
                'common_codes' => $codes->map(function ($code) {
                    return [
                        'id' => $code->id,
                        'loinc_num' => $code->loinc_num,
                        'component' => $code->component,
                        'display_name' => $code->display_name,
                        'class' => $code->class,
                        'department' => $code->department,
                        'standard_price' => $code->standard_price,
                    ];
                })
            ]
        ]);
    }

    /**
     * Update order item results
     */
    public function updateResults(Request $request, Order $order)
    {
        $validator = Validator::make($request->all(), [
            'order_items' => 'required|array',
            'order_items.*.id' => 'required|exists:order_items,id',
            'order_items.*.result_value' => 'nullable|string',
            'order_items.*.result_unit' => 'nullable|string',
            'order_items.*.result_status' => 'nullable|in:normal,abnormal,critical,pending',
            'order_items.*.result_notes' => 'nullable|string|max:2000',
            'order_items.*.status' => 'sometimes|in:pending,collected,in_progress,completed,cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::transaction(function () use ($request, $order) {
            foreach ($request->order_items as $itemData) {
                $orderItem = OrderItem::findOrFail($itemData['id']);

                // Verify the item belongs to this order
                if ($orderItem->order_id !== $order->id) {
                    throw new \Exception('Order item does not belong to this order');
                }

                $updateData = array_filter([
                    'result_value' => $itemData['result_value'] ?? null,
                    'result_unit' => $itemData['result_unit'] ?? null,
                    'result_status' => $itemData['result_status'] ?? null,
                    'result_notes' => $itemData['result_notes'] ?? null,
                    'performed_by_user_id' => $request->user()->id,
                ]);

                if (isset($itemData['status'])) {
                    $updateData['status'] = $itemData['status'];

                    if ($itemData['status'] === 'completed') {
                        $updateData['resulted_at'] = now();
                    }
                }

                $orderItem->update($updateData);
            }

            // Check if all items are completed to update order status
            $allItemsCompleted = $order->orderItems()->where('status', '!=', 'completed')->count() === 0;
            if ($allItemsCompleted && $order->status !== 'completed') {
                $order->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'completed_by_user_id' => $request->user()->id,
                ]);
            }
        });

        $order->load(['orderItems', 'patient']);

        return response()->json([
            'status' => 'success',
            'message' => 'Results updated successfully',
            'data' => ['order' => $order]
        ]);
    }
}
