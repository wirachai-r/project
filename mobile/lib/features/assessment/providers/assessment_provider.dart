import 'package:flutter/material.dart';
import '../../../data/models/assessment_model.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/errors/app_exception.dart';
import 'dart:convert';
import 'package:http/http.dart' as http;

class AssessmentProvider extends ChangeNotifier {
  int? assessmentId;
  QuestionBoxModel? currentBox;
  List<AssessmentResultModel> results = [];
  bool isLoading = false;
  String? error;
  bool isCompleted = false;

  // Track answered choices per box (for multi-select)
  final Map<String, List<String>> _selectedChoices = {};

  List<String> selectedChoicesFor(String boxId) =>
      _selectedChoices[boxId] ?? [];

  void toggleChoice(String boxId, String choiceId, bool isMultiple) {
    final current = _selectedChoices[boxId] ?? [];
    if (isMultiple) {
      if (current.contains(choiceId)) {
        _selectedChoices[boxId] = current.where((c) => c != choiceId).toList();
      } else {
        _selectedChoices[boxId] = [...current, choiceId];
      }
    } else {
      _selectedChoices[boxId] = [choiceId];
    }
    notifyListeners();
  }

  Future<void> startAssessment(String symptomId, String? token) async {
    isLoading = true;
    error = null;
    notifyListeners();

    try {
      final response = await http.post(
        Uri.parse('${ApiConstants.baseUrl}${ApiConstants.assessmentStart}'),
        headers: _headers(token),
        body: jsonEncode({'symptom_id': symptomId}),
      );

      final data = jsonDecode(response.body);

      if (response.statusCode == 201) {
        assessmentId = data['assessment_id'];
        currentBox = QuestionBoxModel.fromJson(data['first_box']);
        isCompleted = false;
        results = [];
        _selectedChoices.clear();
      } else {
        throw AppException.fromStatus(response.statusCode, data['message']);
      }
    } catch (e) {
      error = e.toString();
    } finally {
      isLoading = false;
      notifyListeners();
    }
  }

  Future<void> submitAnswers(String? token) async {
    if (assessmentId == null || currentBox == null) return;

    final boxId = currentBox!.boxId;
    final choices = _selectedChoices[boxId] ?? [];
    if (choices.isEmpty) return;

    isLoading = true;
    error = null;
    notifyListeners();

    try {
      final answers = choices
          .map((c) => {'box_id': boxId, 'choice_id': c})
          .toList();

      final response = await http.post(
        Uri.parse(
          '${ApiConstants.baseUrl}${ApiConstants.assessmentAnswer(assessmentId)}',
        ),
        headers: _headers(token),
        body: jsonEncode({'answers': answers}),
      );

      final data = jsonDecode(response.body);

      if (response.statusCode == 200) {
        if (data['status'] == 'next') {
          currentBox = QuestionBoxModel.fromJson(data['next_box']);
        } else if (data['status'] == 'completed') {
          isCompleted = true;
          results = (data['results'] as List)
              .map((r) => AssessmentResultModel.fromJson(r))
              .toList();
        }
      } else {
        throw AppException.fromStatus(response.statusCode, data['message']);
      }
    } catch (e) {
      error = e.toString();
    } finally {
      isLoading = false;
      notifyListeners();
    }
  }

  void reset() {
    assessmentId = null;
    currentBox = null;
    results = [];
    isLoading = false;
    error = null;
    isCompleted = false;
    _selectedChoices.clear();
    notifyListeners();
  }

  Map<String, String> _headers(String? token) => {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    if (token != null) 'Authorization': 'Bearer $token',
  };
}
