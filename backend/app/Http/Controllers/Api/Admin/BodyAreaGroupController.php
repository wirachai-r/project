<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BodyAreaGroupRequest;
use App\Http\Requests\Admin\ReorderBodyAreaGroupsRequest;
use App\Http\Resources\Admin\BodyAreaGroupResource;
use App\Models\BodyAreaGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BodyAreaGroupController extends Controller
{
    public function index(Request $request)
    {
        $groups = BodyAreaGroup::query()
            ->withCount('symptoms')
            ->with('symptoms:symptom_id,symptom_name')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('search'), fn ($query) => $query->where(fn ($nested) => $nested
                ->where('name', 'like', '%'.$request->search.'%')
                ->orWhere('name_en', 'like', '%'.$request->search.'%')))
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();

        return BodyAreaGroupResource::collection($groups);
    }

    public function store(BodyAreaGroupRequest $request)
    {
        $group = DB::transaction(function () use ($request) {
            $group = BodyAreaGroup::create($this->attributes($request));
            $group->symptoms()->sync($this->symptomSync($request->validated()['symptom_ids'] ?? []));

            return $group;
        });

        return new BodyAreaGroupResource($group->load('symptoms')->loadCount('symptoms'));
    }

    public function show(BodyAreaGroup $bodyAreaGroup)
    {
        return new BodyAreaGroupResource($bodyAreaGroup->load('symptoms')->loadCount('symptoms'));
    }

    public function update(BodyAreaGroupRequest $request, BodyAreaGroup $bodyAreaGroup)
    {
        DB::transaction(function () use ($request, $bodyAreaGroup) {
            $bodyAreaGroup->update($this->attributes($request, $bodyAreaGroup));
            $bodyAreaGroup->symptoms()->sync($this->symptomSync($request->validated()['symptom_ids'] ?? []));
        });

        return new BodyAreaGroupResource($bodyAreaGroup->load('symptoms')->loadCount('symptoms'));
    }

    public function destroy(BodyAreaGroup $bodyAreaGroup)
    {
        if ($bodyAreaGroup->image_path) {
            Storage::disk('public')->delete($bodyAreaGroup->image_path);
        }
        $bodyAreaGroup->delete();

        return response()->json(['message' => 'ลบกลุ่มบริเวณสำเร็จ']);
    }

    public function reorder(ReorderBodyAreaGroupsRequest $request)
    {
        $ids = $request->validated('ids');
        $userId = $request->user()->user_id;

        if (count($ids) !== BodyAreaGroup::count()) {
            throw ValidationException::withMessages([
                'ids' => ['รายการกลุ่มบริเวณมีการเปลี่ยนแปลง กรุณาโหลดหน้าใหม่แล้วลองอีกครั้ง'],
            ]);
        }

        DB::transaction(function () use ($ids, $userId) {
            foreach ($ids as $index => $id) {
                BodyAreaGroup::whereKey($id)->update([
                    'display_order' => $index + 1,
                    'updated_by' => $userId,
                ]);
            }
        });

        return response()->json(['message' => 'บันทึกลำดับกลุ่มบริเวณสำเร็จ']);
    }

    private function attributes(BodyAreaGroupRequest $request, ?BodyAreaGroup $group = null): array
    {
        $imagePath = $group?->image_path;
        if ($request->boolean('remove_image') && $imagePath) {
            Storage::disk('public')->delete($imagePath);
            $imagePath = null;
        }
        if ($request->hasFile('image')) {
            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }
            $filename = Str::uuid().'.png';
            $imagePath = $request->file('image')->storeAs('body_area_groups', $filename, 'public');
        }

        return [
            'name' => $request->validated('name'),
            'name_en' => $request->validated('name_en'),
            'description' => $request->validated('description'),
            'image_path' => $imagePath,
            'display_order' => $request->integer('display_order', 0),
            'status' => $request->validated('status') ?? '1',
            'created_by' => $group?->created_by ?? $request->user()->user_id,
            'updated_by' => $request->user()->user_id,
        ];
    }

    private function symptomSync(array $ids): array
    {
        return collect($ids)->values()->mapWithKeys(fn ($id, $index) => [
            $id => ['display_order' => $index],
        ])->all();
    }
}
