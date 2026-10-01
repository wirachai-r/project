<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 40px 42px 54px; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Garuda, sans-serif; color: #17233c; font-size: 10px; line-height: 1.5; }
        h1, h2 { margin-top: 0; }
        h1 { margin-bottom: 3px; color: #2f27ce; font-size: 22px; line-height: 1.25; }
        h2 { margin: 22px 0 8px; padding-bottom: 5px; border-bottom: 1px solid #dedcff; color: #2f27ce; font-size: 14px; line-height: 1.3; page-break-after: avoid; }
        .header { width: 100%; margin-bottom: 16px; padding-bottom: 13px; border-bottom: 3px solid #433bff; }
        .header td { padding: 0; border: 0; vertical-align: middle; }
        .brand-mark { position: relative; width: 44px; height: 44px; border-radius: 8px; background: #2f27ce; }
        .cross-h, .cross-v { position: absolute; display: block; background: #fff; border-radius: 2px; }
        .cross-h { top: 20px; left: 12px; width: 20px; height: 4px; }
        .cross-v { top: 12px; left: 20px; width: 4px; height: 20px; }
        .header-copy { padding-left: 12px !important; }
        .period { margin-top: 3px; color: #607377; font-size: 10px; }
        .profile { width: 100%; margin-bottom: 13px; border: 1px solid #d0d8e5; border-radius: 8px; background: #f1f6ff; }
        .profile td { padding: 6px 13px; border: 0; }
        .profile tr:first-child td { padding-top: 11px; }
        .profile tr:last-child td { padding-bottom: 11px; }
        .profile-label { width: 88px; color: #597174; }
        .profile-name { color: #1649b0; font-size: 13px; font-weight: 700; }
        .profile-photo-cell { width: 72px; padding: 9px 13px 9px 0 !important; text-align: right; }
        .profile-photo { width: 54px; height: 54px; border: 2px solid #fff; border-radius: 27px; object-fit: cover; }
        .notice { margin-bottom: 17px; padding: 9px 12px; border-left: 4px solid #e6a21e; background: #fff8e7; color: #4f4a3c; }
        .summary { width: 100%; margin-bottom: 5px; border-collapse: collapse; table-layout: fixed; }
        .summary td { width: 25%; padding: 9px 6px; border: 3px solid #fff; background: #f6f8fc; text-align: center; }
        .summary strong { display: block; color: #2f27ce; font-size: 17px; line-height: 1.1; }
        .summary span { display: block; color: #667a7d; font-size: 9px; line-height: 1.35; }
        table.data { width: 100%; margin-top: 4px; border-collapse: collapse; table-layout: fixed; }
        table.data thead { display: table-header-group; }
        table.data th { padding: 7px 8px; border: 1px solid #c8c5ff; background: #dedcff; color: #2f27ce; font-weight: 700; text-align: left; }
        table.data td { padding: 7px 8px; border: 1px solid #d0d8e5; vertical-align: top; overflow-wrap: break-word; }
        table.data tbody tr:nth-child(even) { background: #f6f8fc; }
        table.data tr { page-break-inside: avoid; }
        .disease { display: table; width: 100%; }
        .disease + .disease { margin-top: 4px; }
        .disease-image, .disease-name { display: table-cell; vertical-align: middle; }
        .disease-image { width: 34px; padding-right: 6px; }
        .disease-image img { display: block; width: 28px; height: 28px; border-radius: 4px; object-fit: cover; }
        .nowrap { white-space: nowrap; }
        .empty { margin-top: 20px; padding: 18px; border: 1px dashed #d0d8e5; border-radius: 8px; background: #f6f8fc; color: #667085; text-align: center; }
        .footer { position: fixed; right: 0; bottom: -35px; left: 0; color: #748588; font-size: 8px; text-align: center; }
    </style>
</head>
<body>
    @php
        $reportCount = $assessments->count() + $followUps->count() + $dailyRecords->count();
        $logoPath = resource_path('images/logo.png');
        $logo = is_file($logoPath) ? 'data:image/png;base64,'.base64_encode(file_get_contents($logoPath)) : null;
        $displaySeverity = static fn ($value) => $value === null || $value === '' ? 'ไม่ได้ระบุ' : $value.'/10';
        $displayDateTime = static fn ($value) => $value
            ? \Carbon\Carbon::parse($value)->setTimezone(\App\Support\HealthTime::TIMEZONE)->format('d/m/Y H:i')
            : '-';
    @endphp

    <div class="footer">รายงานสร้างจากข้อมูลที่ผู้ใช้บันทึกในระบบ | สร้างเมื่อ {{ now()->setTimezone(\App\Support\HealthTime::TIMEZONE)->format('d/m/Y H:i') }}</div>
    <table class="header"><tr>
        <td style="width: 44px">@if($logo)<img src="{{ $logo }}" alt="" style="display: block; width: 44px; height: 44px; object-fit: contain;">@else<div class="brand-mark"><span class="cross-h"></span><span class="cross-v"></span></div>@endif</td>
        <td class="header-copy"><h1>รายงานประวัติสุขภาพ</h1><div class="period">ช่วงวันที่ {{ $from->format('d/m/Y') }} ถึง {{ $to->format('d/m/Y') }}</div></td>
    </tr></table>

    <table class="profile">
        <tr><td class="profile-label">ชื่อผู้ใช้</td><td class="profile-name">{{ trim($user->first_name.' '.$user->last_name) ?: 'ไม่ได้ระบุ' }}</td>@if($profileImage)<td class="profile-photo-cell" rowspan="3"><img class="profile-photo" src="{{ $profileImage }}" alt=""></td>@endif</tr>
        <tr><td class="profile-label">อีเมล</td><td>{{ $user->email ?: 'ไม่ได้ระบุ' }}</td></tr>
        <tr><td class="profile-label">วันเกิด</td><td>{{ $user->date_of_birth ? \Carbon\Carbon::parse($user->date_of_birth)->format('d/m/Y') : 'ไม่ได้ระบุ' }}</td></tr>
    </table>

    <div class="notice"><strong>ข้อควรทราบ:</strong> รายงานนี้เป็นข้อมูลที่บันทึกในระบบเพื่อประกอบการดูแลสุขภาพ ไม่ใช่เอกสารวินิจฉัยหรือคำแนะนำแทนบุคลากรทางการแพทย์</div>
    <table class="summary"><tr>
        <td><strong>{{ $reportCount }}</strong><span>รายการทั้งหมด</span></td>
        <td><strong>{{ $assessments->count() }}</strong><span>การประเมินอาการ</span></td>
        <td><strong>{{ $followUps->count() }}</strong><span>บันทึกติดตามอาการ</span></td>
        <td><strong>{{ $dailyRecords->count() }}</strong><span>บันทึกสุขภาพรายวัน</span></td>
    </tr></table>

    @if($episodes->isNotEmpty())
        <h2>สรุปรายการติดตาม</h2>
        <table class="data">
            <thead><tr><th style="width: 37%">อาการ</th><th style="width: 19%">เริ่มติดตาม</th><th style="width: 20%">สถานะ</th><th style="width: 24%">ระดับแรก - ล่าสุด</th></tr></thead>
            <tbody>
            @foreach($episodes as $episode)
                @php($entries = $episode->symptoms->flatMap->entries->sortBy('recorded_at'))
                <tr>
                    <td>{{ $episode->symptoms->map(fn($item) => $item->symptom?->symptom_name ?? $item->custom_symptom_text)->filter()->join(', ') ?: 'ไม่ได้ระบุ' }}</td>
                    <td class="nowrap">{{ $displayDateTime($episode->started_at) }}</td>
                    <td>{{ $episode->status === 'A' ? 'กำลังติดตาม' : ($episode->status === 'P' ? 'หยุดชั่วคราว' : 'สิ้นสุดแล้ว') }}</td>
                    <td>{{ $entries->isEmpty() ? 'ยังไม่มีข้อมูล' : $displaySeverity($entries->first()->severity).' - '.$displaySeverity($entries->last()->severity) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif

    @if($assessments->isNotEmpty())
        <h2>ประวัติการประเมินอาการ</h2>
        <table class="data"><thead><tr><th style="width: 18%">วันที่</th><th style="width: 22%">อาการหลัก</th><th style="width: 17%">รูปแบบ</th><th>ผลที่ระบบบันทึก</th></tr></thead><tbody>
        @foreach($assessments as $assessment)
            <tr>
                <td class="nowrap">{{ $displayDateTime($assessment->completed_at ?? $assessment->created_at) }}</td>
                <td>{{ $assessment->symptom?->symptom_name ?? 'ไม่ได้ระบุ' }}</td>
                <td>{{ ($assessment->assessment_type ?? 'classic') === 'adaptive' ? 'ตามคำตอบ' : 'แบบแผนผัง' }}</td>
                <td>@forelse($assessment->results as $result)
                    @if($result->diseases->isNotEmpty())
                        @foreach($result->diseases as $disease)
                            <div class="disease">
                                @if($diseaseImages->has($disease->disease_id))<span class="disease-image"><img src="{{ $diseaseImages->get($disease->disease_id) }}" alt=""></span>@endif
                                <span class="disease-name">{{ $disease->disease_name }}</span>
                            </div>
                        @endforeach
                    @else
                        {{ $result->recommendation ?: 'ไม่มีรายละเอียด' }}
                    @endif
                    @if(!$loop->last)<br>@endif
                @empty ไม่มีผลลัพธ์ที่บันทึก @endforelse</td>
            </tr>
        @endforeach
        </tbody></table>
    @endif

    @if($followUps->isNotEmpty())
        <h2>การติดตามอาการ</h2>
        <table class="data"><thead><tr><th style="width: 20%">วันที่</th><th style="width: 23%">อาการ</th><th style="width: 14%">ระดับ</th><th style="width: 15%">อุณหภูมิ</th><th>บันทึก</th></tr></thead><tbody>
        @foreach($followUps as $item)
            <tr>
                <td class="nowrap">{{ $displayDateTime($item->recorded_at) }}</td>
                <td>{{ $item->episodeSymptom?->symptom?->symptom_name ?? $item->episodeSymptom?->custom_symptom_text ?? 'ไม่ได้ระบุ' }}</td>
                <td>{{ $displaySeverity($item->severity) }}</td>
                <td>{{ $item->temperature !== null ? number_format($item->temperature, 1).' °C' : 'ไม่ได้ระบุ' }}</td>
                <td>{{ $item->note ?: '-' }}</td>
            </tr>
        @endforeach
        </tbody></table>
    @endif

    @if($dailyRecords->isNotEmpty())
        <h2>บันทึกสุขภาพรายวัน</h2>
        <table class="data"><thead><tr><th style="width: 22%">วันที่</th><th style="width: 24%">สถานะ</th><th>บันทึก</th></tr></thead><tbody>
        @foreach($dailyRecords as $item)
            <tr><td class="nowrap">{{ $displayDateTime($item->recorded_at ?? $item->created_at) }}</td><td>{{ match($item->status) { 'well' => 'ดี', 'normal' => 'ปกติ', default => 'ไม่ค่อยดี' } }}</td><td>{{ $item->note ?: '-' }}</td></tr>
        @endforeach
        </tbody></table>
    @endif

    @if($reportCount === 0)
        <div class="empty">ไม่พบข้อมูลในช่วงวันที่และหมวดหมู่ที่เลือก</div>
    @endif
</body>
</html>
