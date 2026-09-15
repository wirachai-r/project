<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BodyAreaGroupRequest;
use App\Http\Requests\Admin\ReorderBodyAreaGroupsRequest;
use App\Http\Resources\Admin\BodyAreaGroupResource;
use App\Models\BodyAreaGroup;
use App\Support\ImageStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BodyAreaGroupController extends Controller
{
    public function index(Request $request)
    {
        $groups = BodyAreaGroup::query()
            ->withCount('symptoms')
            ->with(['symptoms:symptom_id,symptom_name', 'subgroups.symptoms:symptom_id,symptom_name'])
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
            $this->syncSubgroups($group, $request->validated()['subgroups'] ?? []);

            return $group;
        });

        return new BodyAreaGroupResource($group->load('symptoms', 'subgroups.symptoms')->loadCount('symptoms'));
    }

    public function show(BodyAreaGroup $bodyAreaGroup)
    {
        return new BodyAreaGroupResource($bodyAreaGroup->load('symptoms', 'subgroups.symptoms')->loadCount('symptoms'));
    }

    public function update(BodyAreaGroupRequest $request, BodyAreaGroup $bodyAreaGroup)
    {
        DB::transaction(function () use ($request, $bodyAreaGroup) {
            $bodyAreaGroup->update($this->attributes($request, $bodyAreaGroup));
            $bodyAreaGroup->symptoms()->sync($this->symptomSync($request->validated()['symptom_ids'] ?? []));
            $this->syncSubgroups($bodyAreaGroup, $request->validated()['subgroups'] ?? []);
        });

        return new BodyAreaGroupResource($bodyAreaGroup->load('symptoms', 'subgroups.symptoms')->loadCount('symptoms'));
    }

    public function destroy(BodyAreaGroup $bodyAreaGroup)
    {
        if ($bodyAreaGroup->image_path) {
            ImageStorage::disk()->delete($bodyAreaGroup->image_path);
        }
        foreach ($bodyAreaGroup->subgroups as $subgroup) {
            if ($subgroup->image_path) {
                ImageStorage::disk()->delete($subgroup->image_path);
            }
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

    public function updateStatus(Request $request, BodyAreaGroup $bodyAreaGroup)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['1', '2'])],
        ]);

        $bodyAreaGroup->update([
            'status' => $validated['status'],
            'updated_by' => $request->user()->user_id,
        ]);

        return new BodyAreaGroupResource(
            $bodyAreaGroup->load('symptoms', 'subgroups.symptoms')->loadCount('symptoms'),
        );
    }

    private function attributes(BodyAreaGroupRequest $request, ?BodyAreaGroup $group = null): array
    {
        $imagePath = $group?->image_path;
        if ($request->boolean('remove_image') && $imagePath) {
            ImageStorage::disk()->delete($imagePath);
            $imagePath = null;
        }
        if ($request->hasFile('image')) {
            if ($imagePath) {
                ImageStorage::disk()->delete($imagePath);
            }
            $filename = Str::uuid().'.'.$request->file('image')->extension();
            $imagePath = $request->file('image')->storeAs('body_area_groups', $filename, ImageStorage::diskName());
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

    private function syncSubgroups(BodyAreaGroup $group, array $items): void
    {
        $keptIds = [];
        $groupSymptomIds = $group->symptoms()->pluck('main_symptoms.symptom_id')->all();

        foreach (array_values($items) as $index => $item) {
            $subgroup = isset($item['id'])
                ? $group->subgroups()->find($item['id'])
                : null;
            $imagePath = $subgroup?->image_path;
            if (($item['remove_image'] ?? false) && $imagePath) {
                ImageStorage::disk()->delete($imagePath);
                $imagePath = null;
            }
            if ($requestImage = request()->file("subgroups.$index.image")) {
                if ($imagePath) {
                    ImageStorage::disk()->delete($imagePath);
                }
                $filename = Str::uuid().'.'.$requestImage->extension();
                $imagePath = $requestImage->storeAs('body_area_subgroups', $filename, ImageStorage::diskName());
            }
            $subgroup = $group->subgroups()->updateOrCreate(
                ['id' => $subgroup?->id],
                [
                    'name' => $item['name'],
                    'name_en' => $item['name_en'] ?? null,
                    'description' => $item['description'] ?? null,
                    'image_path' => $imagePath,
                    'display_order' => $index,
                    'status' => $item['status'] ?? '1',
                ],
            );
            $ids = array_values(array_intersect($item['symptom_ids'] ?? [], $groupSymptomIds));
            $subgroup->symptoms()->sync($this->symptomSync($ids));
            $keptIds[] = $subgroup->id;
        }

        $removed = $group->subgroups()->whereNotIn('id', $keptIds)->get();
        foreach ($removed as $subgroup) {
            if ($subgroup->image_path) {
                ImageStorage::disk()->delete($subgroup->image_path);
            }
            $subgroup->delete();
        }
    }
}
