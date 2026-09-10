<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รหัส OTP สำหรับยืนยันการสมัครสมาชิก</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f5fb;font-family:Arial,'Noto Sans Thai',Tahoma,sans-serif;color:#17172b;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#f4f5fb;">
        <tr>
            <td align="center" style="padding:40px 16px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:600px;">
                    <tr>
                        <td style="background-color:#ffffff;border-radius:24px;padding:44px 48px;box-shadow:0 12px 32px rgba(47,39,206,0.08);border:1px solid #ebeafc;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td align="center">
                                        <div style="display:inline-block;width:64px;height:64px;line-height:64px;border-radius:20px;background-color:#dedcff;color:#2f27ce;font-size:30px;text-align:center;">✉</div>
                                        <h1 style="margin:24px 0 10px;font-size:26px;line-height:36px;color:#17172b;">ยืนยันการสมัครสมาชิก</h1>
                                        <p style="margin:0;color:#62677a;font-size:16px;line-height:26px;">สวัสดี {{ $firstName }} ขอบคุณที่สมัครสมาชิก<br>กรอกรหัสด้านล่างในแอป Checkup เพื่อยืนยันอีเมล</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center" style="padding:30px 0 14px;">
                                        <div style="display:inline-block;padding:18px 30px;border-radius:16px;background-color:#f0efff;border:1px solid #dedcff;color:#2f27ce;font-family:'Courier New',monospace;font-size:38px;line-height:46px;font-weight:700;letter-spacing:10px;">{{ $otp }}</div>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center">
                                        <p style="margin:0;color:#62677a;font-size:14px;line-height:22px;">รหัสนี้จะหมดอายุภายใน <strong style="color:#17172b;">5 นาที</strong> ใช้ได้เพียงครั้งเดียว และกรอกผิดได้ไม่เกิน 5 ครั้ง</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding-top:30px;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#fff8e6;border-radius:14px;border:1px solid #ffe4a3;">
                                            <tr>
                                                <td style="padding:16px 18px;color:#70551a;font-size:14px;line-height:22px;">
                                                    <strong>เพื่อความปลอดภัย</strong><br>
                                                    อย่าแจ้งรหัสนี้กับผู้อื่น รวมถึงเจ้าหน้าที่ของ Checkup
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding-top:28px;border-bottom:1px solid #e7e8ef;"></td>
                                </tr>
                                <tr>
                                    <td align="center" style="padding-top:24px;color:#8e93a4;font-size:13px;line-height:21px;">
                                        หากคุณไม่ได้สมัครสมาชิก สามารถละเว้นอีเมลฉบับนี้ได้
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding:24px 12px 0;color:#9a9eae;font-size:12px;line-height:20px;">
                            © {{ date('Y') }} Checkup · ดูแลสุขภาพของคุณในทุกวัน<br>
                            อีเมลนี้ส่งโดยระบบอัตโนมัติ กรุณาอย่าตอบกลับ
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
