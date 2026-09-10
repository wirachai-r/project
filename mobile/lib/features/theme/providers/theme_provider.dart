import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';

class ThemeProvider extends ChangeNotifier {
  static const _themeModeKey = 'theme_mode';

  // Start in light mode for first-time users. The saved preference still wins.
  ThemeMode themeMode = ThemeMode.light;

  Future<void> load() async {
    final prefs = await SharedPreferences.getInstance();
    themeMode = switch (prefs.getString(_themeModeKey)) {
      'light' => ThemeMode.light,
      'dark' => ThemeMode.dark,
      _ => ThemeMode.light,
    };
    notifyListeners();
  }

  Future<void> setThemeMode(ThemeMode value) async {
    if (themeMode == value) return;
    themeMode = value;
    notifyListeners();
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_themeModeKey, value.name);
  }
}
