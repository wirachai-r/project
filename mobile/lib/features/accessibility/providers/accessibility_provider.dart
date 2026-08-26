import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';

class AccessibilityProvider extends ChangeNotifier {
  static const _textScaleKey = 'accessibility_text_scale';
  static const _highContrastKey = 'accessibility_high_contrast';
  static const _reduceMotionKey = 'accessibility_reduce_motion';

  double textScale = 1;
  bool highContrast = false;
  bool reduceMotion = false;

  Future<void> load() async {
    final prefs = await SharedPreferences.getInstance();
    textScale = prefs.getDouble(_textScaleKey) ?? 1;
    highContrast = prefs.getBool(_highContrastKey) ?? false;
    reduceMotion = prefs.getBool(_reduceMotionKey) ?? false;
    notifyListeners();
  }

  Future<void> setTextScale(double value) async {
    textScale = value.clamp(0.85, 1.3).toDouble();
    notifyListeners();
    final prefs = await SharedPreferences.getInstance();
    await prefs.setDouble(_textScaleKey, textScale);
  }

  Future<void> setHighContrast(bool value) async {
    highContrast = value;
    notifyListeners();
    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool(_highContrastKey, value);
  }

  Future<void> setReduceMotion(bool value) async {
    reduceMotion = value;
    notifyListeners();
    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool(_reduceMotionKey, value);
  }

  Future<void> reset() async {
    textScale = 1;
    highContrast = false;
    reduceMotion = false;
    notifyListeners();
    final prefs = await SharedPreferences.getInstance();
    await Future.wait([
      prefs.remove(_textScaleKey),
      prefs.remove(_highContrastKey),
      prefs.remove(_reduceMotionKey),
    ]);
  }
}
