<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <style>
        @font-face { font-family: Sarabun; src: url("{{ resource_path('fonts/Sarabun-Regular.ttf') }}") format("truetype"); font-weight: 400; }
        @font-face { font-family: Sarabun; src: url("{{ resource_path('fonts/Sarabun-Bold.ttf') }}") format("truetype"); font-weight: 700; }
        @page { margin: 42px 46px 52px; }
        * { box-sizing: border-box; }
        body { font-family: Sarabun, sans-serif; color: #253238; font-size: 11px; line-height: 1.55; }
        h1 { margin: 0; color: #087f78; font-size: 23px; }
        h2 { margin: 24px 0 9px; color: #087f78; font-size: 15px; border-bottom: 1px solid #b9dfdc; padding-bottom: 5px; }
        .muted { color: #637477; }
        .header { border-bottom: 3px solid #19a39a; padding-bottom: 14px; margin-bottom: 18px; }
        .profile { background: #edf8f7; border-radius: 7px; padding: 11px 14px; }
        .notice { margin: 15px 0; padding: 10px 13px; background: #fff8e5; border-left: 4px solid #e9a825; }
        table { width: 100%; border-collapse: collapse; margin-top: 5px; }
        th { text-align: left; background: #e4f3f2; color: #155f5b; font-weight: 700; }
        th, td { border: 1px solid #d5e2e1; padding: 7px 8px; vertical-align: top; }
        tr { page-break-inside: avoid; }
        .empty { color: #718183; padding: 12px 0; }
        .footer { position: fixed; bottom: -35px; left: 0; right: 0; color: #7b898b; font-size: 9px; text-align: center; }
    </style>
</head>
<body>
    <div class="footer">รายงานสร้างจากข้อมูลที่ผู้ใช้บันทึกในระบบ - {{ now()->format('d/m/Y H:i') }}</div>
    <div class="header">
        <h1>รายงานประวัติสุขภาพ</h1>
        <div class="muted">ช่วงวันที่ {{ $from->format('d/m/Y') }} ถึง {{ $to->format('d/m/Y') }}</div>
    </div>

    <div class="profile">
        <strong>{{ $user->first_name }} {{ $user->last_name }}</strong><br>
        อีเมล: {{ $user->email }}
        @if($user->date_of_birth)<br>วันเกิด: {{ \Carbon\Carbon::parse($user->date_of_birth)->format('d/m/Y') }}@endif
    </div>

    <div class="notice"><strong>ข้อควรทราบ:</strong> รายงานนี้เป็นข้อมูลที่บันทึกในระบบเพื่อประกอบการดูแลสุขภาพ ไม่ใช่เอกสารวินิจฉัยหรือคำแนะนำแทนบุคลากรทางการแพทย์</div>

    @if($assessments->isNotEmpty())
        <h2>ประวัติการประเมินอาการ</h2>
        <table>
            <thead><tr><th width="18%">วันที่</th><th width="27%">อาการหลัก</th><th>ผลที่ระบบบันทึก</th></tr></thead>
            <tbody>
            @foreach($assessments as $assessment)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($assessment->completed_at ?? $assessment->created_at)->format('d/m/Y H:i') }}</td>
                    <td>{{ $assessment->symptom?->symptom_name ?? 'ไม่ระบุ' }}</td>
                    <td>
                        @forelse($assessment->results as $result)
                            @php($names = $result->diseases->pluck('disease_name')->filter()->join(', '))
                            {{ $names ?: ($result->recommendation ?: 'ไม่มีรายละเอียด') }}@if(!$loop->last)<br>@endif
                        @empty ไม่มีผลลัพธ์ที่บันทึก @endforelse
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif

    @if($followUps->isNotEmpty())
        <h2>การติดตามอาการ</h2>
        <table>
            <thead><tr><th width="18%">วันที่</th><th width="24%">อาการ</th><th width="14%">ระดับ</th><th width="16%">อุณหภูมิ</th><th>บันทึก</th></tr></thead>
            <tbody>
            @foreach($followUps as $item)
                <tr><td>{{ $item->recorded_at->format('d/m/Y H:i') }}</td><td>{{ $item->episodeSymptom?->symptom?->symptom_name ?? $item->episodeSymptom?->custom_symptom_text ?? 'ไม่ระบุ' }}</td><td>{{ $item->severity }}/10</td><td>{{ $item->temperature !== null ? number_format($item->temperature, 1).' °C' : '-' }}</td><td>{{ $item->note ?: '-' }}</td></tr>
            @endforeach
            </tbody>
        </table>
    @endif

    @if($dailyRecords->isNotEmpty())
        <h2>บันทึกสุขภาพรายวัน</h2>
        <table>
            <thead><tr><th width="22%">วันที่</th><th width="24%">สถานะ</th><th>บันทึก</th></tr></thead>
            <tbody>
            @foreach($dailyRecords as $item)
                <tr><td>{{ $item->recorded_on->format('d/m/Y') }}</td><td>{{ $item->status === 'well' ? 'สบายดี' : 'มีอาการ' }}</td><td>{{ $item->note ?: '-' }}</td></tr>
            @endforeach
            </tbody>
        </table>
    @endif

    @if($assessments->isEmpty() && $followUps->isEmpty() && $dailyRecords->isEmpty())
        <div class="empty">ไม่พบข้อมูลในช่วงวันที่และหมวดหมู่ที่เลือก</div>
    @endif
</body>
</html>
