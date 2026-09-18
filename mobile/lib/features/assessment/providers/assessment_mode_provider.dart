import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../../../core/constants/api_constants.dart';
import '../../../data/services/api_service.dart';

enum AssessmentMode { classic, adaptive }

class AssessmentModeProvider extends ChangeNotifier {
  static const _storageKey = 'assessment_mode';
  final ApiService _api;

  AssessmentModeProvider(this._api);

  AssessmentMode mode = AssessmentMode.classic;
  bool saving = false;

  bool get isAdaptive => mode == AssessmentMode.adaptive;

  Future<void> load() async {
    final prefs = await SharedPreferences.getInstance();
    mode = prefs.getString(_storageKey) == 'adaptive'
        ? AssessmentMode.adaptive
        : AssessmentMode.classic;
    notifyListeners();
  }

  Future<void> setAdaptive(bool enabled, {bool syncProfile = false}) async {
    final previous = mode;
    mode = enabled ? AssessmentMode.adaptive : AssessmentMode.classic;
    saving = true;
    notifyListeners();
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString(_storageKey, mode.name);
      if (syncProfile) {
        await _api.put(ApiConstants.profile, body: {'assessment_mode': mode.name});
      }
    } catch (_) {
      mode = previous;
      rethrow;
    } finally {
      saving = false;
      notifyListeners();
    }
  }
}
