<?php

namespace App\Support;

use App\Models\Notification;

final class UserResponseNotification
{
    public static function create(
        string $userId,
        string $title,
        string $body,
        string $targetType,
        string|int $targetId,
    ): Notification {
        return Notification::create([
            'user_id' => $userId,
            'title' => $title,
            'body' => $body,
            'type' => 'U',
            'is_read' => 'N',
            'target_type' => $targetType,
            'target_id' => (string) $targetId,
            'delivery_status' => 'sent',
            'delivered_at' => now(),
            'visible_in_app' => true,
        ]);
    }

    public static function feedback(
        string $userId,
        int $feedbackId,
        string $status,
        ?string $adminNote,
    ): Notification {
        $title = match ($status) {
            'in_review' => 'กำลังตรวจสอบข้อเสนอแนะของคุณ',
            'resolved' => 'ดำเนินการตามข้อเสนอแนะแล้ว',
            'dismissed' => 'ผลการตรวจสอบข้อเสนอแนะ',
            default => 'อัปเดตข้อเสนอแนะของคุณ',
        };
        $body = $status === 'in_review'
            ? 'เราได้รับข้อเสนอแนะของคุณแล้วและกำลังดำเนินการตรวจสอบ'
            : trim((string) $adminNote);

        return self::create($userId, $title, $body, 'user_feedback', $feedbackId);
    }

    public static function commentReport(
        string $userId,
        string $articleId,
        string $action,
    ): Notification {
        $body = $action === 'dismiss'
            ? 'เราได้ตรวจสอบรายงานของคุณแล้ว และไม่พบว่าความคิดเห็นดังกล่าวละเมิดแนวทางการใช้งาน'
            : 'เราได้ตรวจสอบและดำเนินการกับความคิดเห็นที่คุณรายงานแล้ว ขอบคุณที่ช่วยดูแลชุมชนของเรา';

        return self::create(
            $userId,
            'ผลการตรวจสอบรายงานความคิดเห็น',
            $body,
            'article_comment_report',
            $articleId,
        );
    }
}
