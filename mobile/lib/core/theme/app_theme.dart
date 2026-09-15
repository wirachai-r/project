import 'package:flutter/material.dart';
import 'app_colors.dart';
import 'app_text_styles.dart';

class AppTheme {
  static ThemeData get darkTheme {
    final base = theme;
    const colors = ColorScheme.dark(
      primary: Color(0xFFB39DFF),
      onPrimary: Color(0xFF21163D),
      primaryContainer: Color(0xFF5B3CC4),
      onPrimaryContainer: Color(0xFFF1EDFF),
      secondary: Color(0xFFD5C8FF),
      onSecondary: Color(0xFF24183F),
      secondaryContainer: Color(0xFF443568),
      onSecondaryContainer: Color(0xFFF1EDFF),
      surface: Color(0xFF1C1C20),
      onSurface: Color(0xFFF4F4F5),
      onSurfaceVariant: Color(0xFFC7C7D0),
      surfaceContainerLowest: Color(0xFF101012),
      surfaceContainerLow: Color(0xFF17171A),
      surfaceContainer: Color(0xFF27272C),
      outline: Color(0xFF71717B),
      outlineVariant: Color(0xFF3A3A41),
      error: Color(0xFFFF6B75),
      errorContainer: Color(0xFF5C1F28),
      onErrorContainer: Color(0xFFFFDADF),
      surfaceTint: Colors.transparent,
    );

    return base.copyWith(
      brightness: Brightness.dark,
      scaffoldBackgroundColor: colors.surfaceContainerLowest,
      canvasColor: colors.surface,
      disabledColor: colors.onSurface.withValues(alpha: 0.46),
      focusColor: colors.primary.withValues(alpha: 0.14),
      hoverColor: colors.primary.withValues(alpha: 0.08),
      highlightColor: colors.primary.withValues(alpha: 0.10),
      splashColor: colors.primary.withValues(alpha: 0.12),
      colorScheme: colors,
      iconTheme: IconThemeData(color: colors.onSurfaceVariant),
      primaryIconTheme: IconThemeData(color: colors.onPrimary),
      textTheme: base.textTheme.apply(
        bodyColor: colors.onSurface,
        displayColor: colors.onSurface,
      ),
      appBarTheme: base.appBarTheme.copyWith(
        backgroundColor: colors.surfaceContainerLowest,
        foregroundColor: colors.onSurface,
        titleTextStyle: base.appBarTheme.titleTextStyle?.copyWith(
          color: colors.onSurface,
        ),
        iconTheme: base.appBarTheme.iconTheme?.copyWith(
          color: colors.onSurface,
        ),
        actionsIconTheme: base.appBarTheme.actionsIconTheme?.copyWith(
          color: colors.onSurface,
        ),
      ),
      cardTheme: base.cardTheme.copyWith(
        color: colors.surface,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(16),
          side: BorderSide(color: colors.outlineVariant),
        ),
      ),
      dividerTheme: base.dividerTheme.copyWith(color: colors.outlineVariant),
      dialogTheme: base.dialogTheme.copyWith(backgroundColor: colors.surface),
      datePickerTheme: DatePickerThemeData(
        backgroundColor: colors.surface,
        surfaceTintColor: Colors.transparent,
        headerBackgroundColor: colors.surface,
        headerForegroundColor: colors.onSurface,
        weekdayStyle: AppTextStyles.body3Bold.copyWith(
          color: colors.onSurfaceVariant,
        ),
        dayStyle: AppTextStyles.body2.copyWith(color: colors.onSurface),
        yearStyle: AppTextStyles.body2.copyWith(color: colors.onSurface),
        todayForegroundColor: WidgetStatePropertyAll(colors.primary),
        todayBorder: BorderSide(color: colors.primary),
        cancelButtonStyle: TextButton.styleFrom(
          foregroundColor: colors.primary,
        ),
        confirmButtonStyle: TextButton.styleFrom(
          foregroundColor: colors.primary,
        ),
      ),
      timePickerTheme: TimePickerThemeData(
        backgroundColor: colors.surface,
        hourMinuteColor: colors.surfaceContainer,
        hourMinuteTextColor: colors.onSurface,
        dialBackgroundColor: colors.surfaceContainer,
        dialHandColor: colors.primary,
        dialTextColor: colors.onSurface,
        dayPeriodColor: colors.surfaceContainer,
        dayPeriodTextColor: colors.onSurface,
        entryModeIconColor: colors.onSurfaceVariant,
      ),
      popupMenuTheme: PopupMenuThemeData(
        color: colors.surface,
        surfaceTintColor: Colors.transparent,
        textStyle: AppTextStyles.body2.copyWith(color: colors.onSurface),
        iconColor: colors.onSurfaceVariant,
      ),
      dropdownMenuTheme: DropdownMenuThemeData(
        textStyle: AppTextStyles.body2.copyWith(color: colors.onSurface),
        menuStyle: MenuStyle(
          backgroundColor: WidgetStatePropertyAll(colors.surface),
          surfaceTintColor: const WidgetStatePropertyAll(Colors.transparent),
        ),
      ),
      menuTheme: MenuThemeData(
        style: MenuStyle(
          backgroundColor: WidgetStatePropertyAll(colors.surface),
          surfaceTintColor: const WidgetStatePropertyAll(Colors.transparent),
        ),
      ),
      bottomSheetTheme: base.bottomSheetTheme.copyWith(
        backgroundColor: colors.surface,
      ),
      listTileTheme: base.listTileTheme.copyWith(
        iconColor: colors.onSurfaceVariant,
        textColor: colors.onSurface,
      ),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: base.elevatedButtonTheme.style?.copyWith(
          backgroundColor: WidgetStateProperty.resolveWith(
            (states) => states.contains(WidgetState.disabled)
                ? colors.outlineVariant
                : colors.primary,
          ),
          foregroundColor: WidgetStateProperty.resolveWith(
            (states) => states.contains(WidgetState.disabled)
                ? colors.onSurfaceVariant
                : colors.onPrimary,
          ),
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: base.outlinedButtonTheme.style?.copyWith(
          foregroundColor: WidgetStatePropertyAll(colors.primary),
          side: WidgetStatePropertyAll(BorderSide(color: colors.primary)),
        ),
      ),
      filledButtonTheme: FilledButtonThemeData(
        style: base.filledButtonTheme.style?.copyWith(
          backgroundColor: WidgetStatePropertyAll(colors.primary),
          foregroundColor: WidgetStatePropertyAll(colors.onPrimary),
        ),
      ),
      textButtonTheme: TextButtonThemeData(
        style: base.textButtonTheme.style?.copyWith(
          foregroundColor: WidgetStatePropertyAll(colors.primary),
        ),
      ),
      chipTheme: base.chipTheme.copyWith(
        backgroundColor: colors.surface,
        selectedColor: colors.primaryContainer,
        side: BorderSide(color: colors.outlineVariant),
        labelStyle: base.chipTheme.labelStyle?.copyWith(
          color: colors.onSurface,
        ),
      ),
      tabBarTheme: base.tabBarTheme.copyWith(
        labelColor: colors.primary,
        unselectedLabelColor: colors.onSurfaceVariant,
        dividerColor: colors.outlineVariant,
        indicatorColor: colors.primary,
      ),
      textSelectionTheme: base.textSelectionTheme.copyWith(
        cursorColor: colors.primary,
        selectionColor: colors.primary.withValues(alpha: 0.3),
        selectionHandleColor: colors.primary,
      ),
      checkboxTheme: CheckboxThemeData(
        fillColor: WidgetStateProperty.resolveWith(
          (states) => states.contains(WidgetState.selected)
              ? colors.primary
              : colors.surface,
        ),
        side: BorderSide(color: colors.outline, width: 1.5),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(5)),
      ),
      radioTheme: RadioThemeData(
        fillColor: WidgetStateProperty.resolveWith(
          (states) => states.contains(WidgetState.selected)
              ? colors.primary
              : colors.onSurfaceVariant,
        ),
      ),
      switchTheme: SwitchThemeData(
        thumbColor: WidgetStatePropertyAll(colors.onSurface),
        trackColor: WidgetStateProperty.resolveWith(
          (states) => states.contains(WidgetState.selected)
              ? colors.primary
              : colors.outline,
        ),
      ),
      searchBarTheme: base.searchBarTheme.copyWith(
        backgroundColor: WidgetStatePropertyAll(colors.surface),
        side: WidgetStatePropertyAll(BorderSide(color: colors.outlineVariant)),
        hintStyle: WidgetStatePropertyAll(
          AppTextStyles.body2.copyWith(color: colors.onSurfaceVariant),
        ),
      ),
      floatingActionButtonTheme: base.floatingActionButtonTheme.copyWith(
        backgroundColor: colors.primary,
        foregroundColor: colors.onPrimary,
      ),
      navigationBarTheme: base.navigationBarTheme.copyWith(
        height: 76,
        backgroundColor: colors.surfaceContainerLowest,
        indicatorColor: Colors.transparent,
        iconTheme: WidgetStateProperty.resolveWith(
          (states) => IconThemeData(
            size: states.contains(WidgetState.selected) ? 26 : 24,
            color: states.contains(WidgetState.selected)
                ? colors.primary
                : colors.onSurfaceVariant,
          ),
        ),
        labelTextStyle: WidgetStateProperty.resolveWith(
          (states) => TextStyle(
            fontFamily: 'Prompt',
            fontSize: 13,
            fontWeight: states.contains(WidgetState.selected)
                ? FontWeight.w700
                : FontWeight.w500,
            color: states.contains(WidgetState.selected)
                ? colors.primary
                : colors.onSurfaceVariant,
          ),
        ),
      ),
      bottomNavigationBarTheme: base.bottomNavigationBarTheme.copyWith(
        backgroundColor: colors.surface,
        selectedItemColor: colors.primary,
        unselectedItemColor: colors.onSurfaceVariant,
      ),
      snackBarTheme: base.snackBarTheme.copyWith(
        backgroundColor: colors.surfaceContainer,
        contentTextStyle: AppTextStyles.body2.copyWith(color: colors.onSurface),
      ),
      progressIndicatorTheme: base.progressIndicatorTheme.copyWith(
        color: colors.primary,
        linearTrackColor: colors.surfaceContainer,
        circularTrackColor: colors.surfaceContainer,
      ),
      inputDecorationTheme: base.inputDecorationTheme.copyWith(
        fillColor: colors.surface,
        iconColor: colors.onSurfaceVariant,
        prefixIconColor: colors.onSurfaceVariant,
        suffixIconColor: colors.onSurfaceVariant,
        hintStyle: base.inputDecorationTheme.hintStyle?.copyWith(
          color: colors.onSurfaceVariant,
        ),
        labelStyle: base.inputDecorationTheme.labelStyle?.copyWith(
          color: colors.onSurface,
        ),
        border: _darkInputBorder(colors.outline),
        enabledBorder: _darkInputBorder(colors.outline),
      ),
    );
  }

  static OutlineInputBorder _darkInputBorder(Color color) => OutlineInputBorder(
    borderRadius: BorderRadius.circular(16),
    borderSide: BorderSide(color: color),
  );

  static ThemeData get highContrast => theme.copyWith(
    scaffoldBackgroundColor: Colors.white,
    colorScheme: const ColorScheme.light(
      primary: Color(0xFF1400A8),
      secondary: Color(0xFF1400A8),
      surface: Colors.white,
      error: Color(0xFFB00020),
      onPrimary: Colors.white,
      onSecondary: Colors.white,
      onSurface: Colors.black,
      onError: Colors.white,
    ),
    dividerTheme: const DividerThemeData(color: Colors.black, thickness: 1.5),
    cardTheme: CardThemeData(
      color: Colors.white,
      elevation: 0,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(18),
        side: const BorderSide(color: Colors.black, width: 1.5),
      ),
    ),
  );

  static ThemeData get darkHighContrast {
    const colors = ColorScheme.dark(
      primary: Color(0xFFC4B5FD),
      onPrimary: Colors.black,
      secondary: Color(0xFFE9D5FF),
      onSecondary: Colors.black,
      surface: Color(0xFF151515),
      onSurface: Colors.white,
      onSurfaceVariant: Color(0xFFE5E7EB),
      surfaceContainerLowest: Colors.black,
      surfaceContainerLow: Color(0xFF0A0A0A),
      surfaceContainer: Color(0xFF242424),
      outline: Colors.white,
      outlineVariant: Color(0xFF9CA3AF),
      error: Color(0xFFFF8A8A),
      onError: Colors.black,
    );

    return darkTheme.copyWith(
      scaffoldBackgroundColor: Colors.black,
      colorScheme: colors,
      appBarTheme: darkTheme.appBarTheme.copyWith(
        backgroundColor: Colors.black,
        foregroundColor: Colors.white,
      ),
      dividerTheme: const DividerThemeData(
        color: Color(0xFF9CA3AF),
        thickness: 1.5,
      ),
      cardTheme: CardThemeData(
        color: colors.surface,
        elevation: 0,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(16),
          side: const BorderSide(color: Colors.white, width: 1.5),
        ),
      ),
      inputDecorationTheme: darkTheme.inputDecorationTheme.copyWith(
        fillColor: colors.surface,
        border: _darkInputBorder(colors.outline),
        enabledBorder: _darkInputBorder(colors.outline),
        focusedBorder: _darkInputBorder(colors.primary),
      ),
    );
  }

  static ThemeData get theme => ThemeData(
    useMaterial3: true,
    fontFamily: 'Prompt',
    textTheme: TextTheme(
      displayLarge: AppTextStyles.h1,
      displayMedium: AppTextStyles.h2,
      headlineLarge: AppTextStyles.h2,
      headlineMedium: AppTextStyles.h3,
      headlineSmall: AppTextStyles.h4,
      titleLarge: AppTextStyles.h4,
      titleMedium: AppTextStyles.body1Bold,
      titleSmall: AppTextStyles.body2Bold,
      bodyLarge: AppTextStyles.body1,
      bodyMedium: AppTextStyles.body2,
      bodySmall: AppTextStyles.body3,
      labelLarge: AppTextStyles.body1Bold,
      labelMedium: AppTextStyles.body2Bold,
      labelSmall: AppTextStyles.body3Bold,
    ),
    scaffoldBackgroundColor: AppColors.background,
    colorScheme: const ColorScheme.light(
      primary: AppColors.primary,
      onPrimary: AppColors.white,
      secondary: AppColors.primaryLight,
      onSecondary: AppColors.primaryDark,
      surface: AppColors.surfaceElevated,
      onSurface: AppColors.textPrimary,
      surfaceContainerLowest: AppColors.white,
      surfaceContainerLow: AppColors.background,
      surfaceContainer: AppColors.surface,
      outline: AppColors.borderStrong,
      outlineVariant: AppColors.border,
      error: AppColors.danger,
    ),
    visualDensity: VisualDensity.standard,
    materialTapTargetSize: MaterialTapTargetSize.padded,

    // AppBar
    appBarTheme: const AppBarTheme(
      backgroundColor: AppColors.background,
      foregroundColor: AppColors.textPrimary,
      elevation: 0,
      scrolledUnderElevation: 0,
      centerTitle: true,
      toolbarHeight: 60,
      leadingWidth: 52,
      titleTextStyle: TextStyle(
        fontFamily: 'Prompt',
        fontSize: 18,
        fontWeight: FontWeight.w700,
        color: AppColors.textPrimary,
      ),
      iconTheme: IconThemeData(color: AppColors.textPrimary, size: 23),
      actionsIconTheme: IconThemeData(color: AppColors.textPrimary, size: 23),
    ),

    iconButtonTheme: IconButtonThemeData(
      style: IconButton.styleFrom(
        minimumSize: const Size(44, 44),
        padding: EdgeInsets.zero,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      ),
    ),

    // ElevatedButton
    elevatedButtonTheme: ElevatedButtonThemeData(
      style: ElevatedButton.styleFrom(
        backgroundColor: AppColors.primary,
        foregroundColor: AppColors.white,
        disabledBackgroundColor: AppColors.border,
        disabledForegroundColor: AppColors.textSecondary,
        minimumSize: const Size(double.infinity, 52),
        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 14),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        textStyle: AppTextStyles.body1Bold,
        elevation: 0,
      ),
    ),

    // OutlinedButton
    outlinedButtonTheme: OutlinedButtonThemeData(
      style: OutlinedButton.styleFrom(
        foregroundColor: AppColors.primary,
        minimumSize: const Size(double.infinity, 52),
        side: const BorderSide(color: AppColors.primary),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        textStyle: AppTextStyles.body1Bold,
      ),
    ),

    filledButtonTheme: FilledButtonThemeData(
      style: FilledButton.styleFrom(
        backgroundColor: AppColors.primary,
        foregroundColor: AppColors.white,
        minimumSize: const Size(48, 48),
        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 13),
        elevation: 0,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        textStyle: AppTextStyles.body2Bold,
      ),
    ),

    textButtonTheme: TextButtonThemeData(
      style: TextButton.styleFrom(
        foregroundColor: AppColors.primary,
        minimumSize: const Size(44, 44),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        textStyle: AppTextStyles.body2Bold,
      ),
    ),

    // Input
    inputDecorationTheme: InputDecorationTheme(
      filled: true,
      fillColor: AppColors.white,
      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 15),
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(16),
        borderSide: const BorderSide(color: AppColors.border),
      ),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(16),
        borderSide: const BorderSide(color: AppColors.border),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(16),
        borderSide: const BorderSide(color: AppColors.primary, width: 1.5),
      ),
      errorBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(16),
        borderSide: const BorderSide(color: AppColors.danger),
      ),
      hintStyle: AppTextStyles.body2.copyWith(color: AppColors.textHint),
      labelStyle: AppTextStyles.body2,
    ),

    textSelectionTheme: const TextSelectionThemeData(
      cursorColor: AppColors.primary,
      selectionColor: AppColors.primaryLight,
      selectionHandleColor: AppColors.primary,
    ),

    listTileTheme: ListTileThemeData(
      iconColor: AppColors.textSecondary,
      textColor: AppColors.textPrimary,
      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
    ),

    chipTheme: ChipThemeData(
      backgroundColor: AppColors.surfaceElevated,
      selectedColor: AppColors.primaryLight,
      side: const BorderSide(color: AppColors.border),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(999)),
      labelStyle: AppTextStyles.body2,
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 7),
    ),

    tabBarTheme: TabBarThemeData(
      labelColor: AppColors.primary,
      unselectedLabelColor: AppColors.textSecondary,
      labelStyle: AppTextStyles.body2Bold,
      unselectedLabelStyle: AppTextStyles.body2,
      dividerColor: AppColors.border,
      indicatorColor: AppColors.primary,
      indicatorSize: TabBarIndicatorSize.tab,
    ),

    checkboxTheme: CheckboxThemeData(
      fillColor: WidgetStateProperty.resolveWith(
        (states) => states.contains(WidgetState.selected)
            ? AppColors.primary
            : AppColors.white,
      ),
      side: const BorderSide(color: AppColors.borderStrong, width: 1.5),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(5)),
    ),

    radioTheme: RadioThemeData(
      fillColor: WidgetStateProperty.resolveWith(
        (states) => states.contains(WidgetState.selected)
            ? AppColors.primary
            : AppColors.textHint,
      ),
    ),

    switchTheme: SwitchThemeData(
      thumbColor: const WidgetStatePropertyAll(AppColors.white),
      trackColor: WidgetStateProperty.resolveWith(
        (states) => states.contains(WidgetState.selected)
            ? AppColors.primary
            : AppColors.borderStrong,
      ),
    ),

    searchBarTheme: SearchBarThemeData(
      backgroundColor: const WidgetStatePropertyAll(AppColors.surfaceElevated),
      elevation: const WidgetStatePropertyAll(0),
      side: const WidgetStatePropertyAll(BorderSide(color: AppColors.border)),
      hintStyle: WidgetStatePropertyAll(
        AppTextStyles.body2.copyWith(color: AppColors.textHint),
      ),
      shape: WidgetStatePropertyAll(
        RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      ),
    ),

    floatingActionButtonTheme: const FloatingActionButtonThemeData(
      backgroundColor: AppColors.primary,
      foregroundColor: AppColors.white,
      elevation: 2,
      focusElevation: 2,
      highlightElevation: 3,
    ),

    cardTheme: CardThemeData(
      color: AppColors.surfaceElevated,
      elevation: 0,
      shadowColor: Colors.transparent,
      margin: EdgeInsets.zero,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(16),
        side: const BorderSide(color: AppColors.border),
      ),
    ),

    dividerTheme: const DividerThemeData(
      color: AppColors.border,
      thickness: 1,
      space: 1,
    ),

    snackBarTheme: SnackBarThemeData(
      behavior: SnackBarBehavior.floating,
      backgroundColor: AppColors.textPrimary,
      contentTextStyle: AppTextStyles.body2.copyWith(color: AppColors.white),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
    ),

    dialogTheme: DialogThemeData(
      backgroundColor: AppColors.surfaceElevated,
      surfaceTintColor: Colors.transparent,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(28)),
      insetPadding: const EdgeInsets.symmetric(horizontal: 24, vertical: 24),
      iconColor: AppColors.primary,
      titleTextStyle: AppTextStyles.h4.copyWith(color: AppColors.textPrimary),
      contentTextStyle: AppTextStyles.body2.copyWith(
        color: AppColors.textSecondary,
        height: 1.5,
      ),
      actionsPadding: const EdgeInsets.fromLTRB(24, 8, 24, 24),
    ),

    bottomSheetTheme: const BottomSheetThemeData(
      backgroundColor: AppColors.surfaceElevated,
      surfaceTintColor: Colors.transparent,
      showDragHandle: true,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(28)),
      ),
    ),

    progressIndicatorTheme: const ProgressIndicatorThemeData(
      color: AppColors.primary,
      linearTrackColor: AppColors.primaryLight,
      circularTrackColor: AppColors.primaryLight,
      strokeWidth: 3,
    ),

    navigationBarTheme: NavigationBarThemeData(
      height: 76,
      elevation: 0,
      backgroundColor: AppColors.white,
      indicatorColor: Colors.transparent,
      overlayColor: const WidgetStatePropertyAll(Colors.transparent),
      iconTheme: WidgetStateProperty.resolveWith(
        (states) => IconThemeData(
          size: states.contains(WidgetState.selected) ? 26 : 24,
          color: states.contains(WidgetState.selected) ||
                  states.contains(WidgetState.focused)
              ? AppColors.primary
              : AppColors.textSecondary,
        ),
      ),
      labelTextStyle: WidgetStateProperty.resolveWith(
        (states) => TextStyle(
          fontFamily: 'Prompt',
          fontSize: 13,
          fontWeight: states.contains(WidgetState.selected)
              ? FontWeight.w700
              : FontWeight.w500,
          color: states.contains(WidgetState.selected) ||
                  states.contains(WidgetState.focused)
              ? AppColors.primary
              : AppColors.textSecondary,
        ),
      ),
    ),

    // BottomNav
    bottomNavigationBarTheme: const BottomNavigationBarThemeData(
      backgroundColor: AppColors.white,
      selectedItemColor: AppColors.primary,
      unselectedItemColor: AppColors.textSecondary,
      showSelectedLabels: true,
      showUnselectedLabels: true,
      type: BottomNavigationBarType.fixed,
      elevation: 0,
      selectedLabelStyle: TextStyle(
        fontFamily: 'Prompt',
        fontSize: 13,
        fontWeight: FontWeight.w600,
      ),
      unselectedLabelStyle: TextStyle(fontFamily: 'Prompt', fontSize: 13),
    ),
  );
}
