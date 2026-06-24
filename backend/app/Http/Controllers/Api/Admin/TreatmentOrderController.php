<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TreatmentOrderRequest;
use App\Http\Resources\Admin\TreatmentOrderResource;
use App\Models\Disease;
use App\Models\TreatmentOrder;
use Illuminate\Http\Request;

/**
 * @tags Admin TreatmentOrderController
 */

class TreatmentOrderController extends Controller
{
    public function index(Request $request, Disease $disease)
    {
        $orders = TreatmentOrder::query()
            ->where('disease_id', $disease->disease_id)
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderBy('order')
            ->get();

        return TreatmentOrderResource::collection($orders);
    }

    public function store(TreatmentOrderRequest $request, Disease $disease)
    {
        $order = TreatmentOrder::create([
            'order_id'        => $this->generateId(),
            'order_name'      => $request->order_name,
            'order_name_en'   => $request->order_name_en,
            'description'     => $request->description,
            'urgency_type'    => $request->urgency_type,
            'order_sequence'  => $request->order_sequence ?? 0,
            'status'          => $request->status ?? '1',
            'disease_id'      => $disease->disease_id,
            'created_by'      => $request->user()->user_id,
            'updated_by'      => $request->user()->user_id,
        ]);

        return new TreatmentOrderResource($order->load('disease'));
    }

    public function show(Disease $disease, TreatmentOrder $treatmentOrder)
    {
        return new TreatmentOrderResource($treatmentOrder->load('disease'));
    }

    public function update(TreatmentOrderRequest $request, Disease $disease, TreatmentOrder $treatmentOrder)
    {
        $treatmentOrder->update([
            'order_name'      => $request->order_name,
            'order_name_en'   => $request->order_name_en,
            'description'     => $request->description,
            'urgency_type'    => $request->urgency_type,
            'order_sequence'  => $request->order_sequence ?? $treatmentOrder->order_sequence,
            'status'          => $request->status ?? $treatmentOrder->status,
            'updated_by'      => $request->user()->user_id,
        ]);

        return new TreatmentOrderResource($treatmentOrder->load('disease'));
    }

    public function destroy(Disease $disease, TreatmentOrder $treatmentOrder)
    {
        $treatmentOrder->delete();

        return response()->json(['message' => 'ลบคำสั่งการรักษาสำเร็จ']);
    }

    private function generateId(): string
    {
        $last = TreatmentOrder::max('order_id');
        $next = $last ? (int)$last + 1 : 1;
        return str_pad($next, 10, '0', STR_PAD_LEFT);
    }
}
