import 'package:flutter/material.dart';
import '../../core/theme/app_colors.dart';
import '../../core/theme/app_text_styles.dart';

class AppButton extends StatelessWidget {
  final String label;
  final VoidCallback? onTap;
  final bool loading;
  final bool outlined;
  final Widget? icon;
  final Color? backgroundColor;
  final Color? foregroundColor;
  final double height;
  final bool expand;

  const AppButton({
    super.key,
    required this.label,
    this.onTap,
    this.loading = false,
    this.outlined = false,
    this.icon,
    this.backgroundColor,
    this.foregroundColor,
    this.height = 52,
    this.expand = true,
  });

  @override
  Widget build(BuildContext context) {
    // 🎨 คำนวณสีพื้นหลังและสีตัวอักษรให้ฉลาดขึ้นตามประเภทปุ่ม
    final bg = backgroundColor ?? AppColors.primary;
    final fg = foregroundColor ?? (outlined ? bg : AppColors.white);

    final child = loading
        ? SizedBox(
            width: 22,
            height: 22,
            child: CircularProgressIndicator(
              strokeWidth: 2,
              color: fg, // ปรับตามสีตัวอักษรหลัก
            ),
          )
        : Row(
            mainAxisAlignment: MainAxisAlignment.center,
            mainAxisSize: MainAxisSize.min,
            children: [
              if (icon != null) ...[
                // เปลี่ยนสีไอคอนให้ล้อตามสีตัวหนังสือโดยอัตโนมัติ (ถ้าสามารถใส่สีได้)
                Theme(
                  data: Theme.of(
                    context,
                  ).copyWith(iconTheme: IconThemeData(color: fg)),
                  child: icon!,
                ),
                const SizedBox(width: 8),
              ],
              // ✅ ใช้ฟอนต์ Prompt จาก AppTextStyles ตัวใหม่ พร้อมสีที่คำนวณถูกต้อง
              Flexible(
                child: Text(
                  label,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  textAlign: TextAlign.center,
                  style: AppTextStyles.body1Bold.copyWith(color: fg),
                ),
              ),
            ],
          );

    if (outlined) {
      return Semantics(
        button: true,
        enabled: onTap != null && !loading,
        label: loading ? '$label กำลังดำเนินการ' : label,
        excludeSemantics: true,
        child: SizedBox(
          width: expand ? double.infinity : null,
          height: height,
          child: OutlinedButton(
            onPressed: loading ? null : onTap,
            style: OutlinedButton.styleFrom(
              side: BorderSide(color: bg, width: 1.5),
              foregroundColor: bg,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(16),
              ),
            ),
            child: child,
          ),
        ),
      );
    }

    return Semantics(
      button: true,
      enabled: onTap != null && !loading,
      label: loading ? '$label กำลังดำเนินการ' : label,
      excludeSemantics: true,
      child: SizedBox(
        width: expand ? double.infinity : null,
        height: height,
        child: ElevatedButton(
          onPressed: loading ? null : onTap,
          style: ElevatedButton.styleFrom(
            backgroundColor: bg,
            foregroundColor: fg,
            elevation: 0,
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(16),
            ),
          ),
          child: child,
        ),
      ),
    );
  }
}
