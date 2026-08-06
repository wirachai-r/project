import 'package:flutter/material.dart';
import '../../../data/models/assessment_model.dart';
import '../../../data/repositories/assessment_repository.dart';
import '../../../core/errors/app_exception.dart';

class AssessmentProvider extends ChangeNotifier {
  static const noneChoiceId = '__none_of_the_above__';
  final AssessmentRepository _repository;

  AssessmentProvider({required AssessmentRepository repository})
    : _repository = repository;

  dynamic assessmentId;
  String? symptomId;
  String? diagramId;
  QuestionBoxModel? currentBox;
  List<AssessmentResultModel> results = [];
  bool isLoading = false;
  String? error;
  bool isCompleted = false;

  // true เมื่อ token หมดอายุ/ไม่ถูกต้อง (401) — ให้ UI เด้งไปหน้า login
  bool sessionExpired = false;

  // Track answered choices per box (for multi-select)
  final Map<String, List<String>> _selectedChoices = {};

  // ประวัติ box ที่ผ่านมา เพื่อให้กดย้อนกลับได้ (ฝั่ง UI เท่านั้น)
  final List<QuestionBoxModel> _boxHistory = [];

  bool get canGoBack => _boxHistory.isNotEmpty;

  List<String> selectedChoicesFor(String boxId) =>
      _selectedChoices[boxId] ?? [];

  void toggleChoice(String boxId, String choiceId, bool isMultiple) {
    final current = _selectedChoices[boxId] ?? [];
    if (isMultiple) {
      if (choiceId == noneChoiceId) {
        _selectedChoices[boxId] = current.contains(noneChoiceId)
            ? []
            : [noneChoiceId];
        notifyListeners();
        return;
      }

      final selectable = current.where((c) => c != noneChoiceId).toList();
      if (selectable.contains(choiceId)) {
        _selectedChoices[boxId] = selectable.where((c) => c != choiceId).toList();
      } else {
        _selectedChoices[boxId] = [...selectable, choiceId];
      }
    } else {
      _selectedChoices[boxId] = [choiceId];
    }
    notifyListeners();
  }

  Future<void> startAssessment(String symptomId) async {
    isLoading = true;
    error = null;
    sessionExpired = false;
    // เคลียร์ค่าของ assessment รอบก่อนหน้าทันที กันกรณี request ใหม่ล้มเหลว
    // แล้ว currentBox/results เก่าค้างโชว์ทับ error message (เช่น "ไม่พบแผนภูมิสำหรับอาการนี้")
    currentBox = null;
    results = [];
    isCompleted = false;
    assessmentId = null;
    diagramId = null;
    _selectedChoices.clear();
    _boxHistory.clear();
    notifyListeners();

    try {
      final result = await _repository.start(symptomId: symptomId);

      this.symptomId = symptomId;
      assessmentId = result.assessmentId;
      diagramId = result.diagramId;
      currentBox = result.firstBox;
      isCompleted = false;
      results = [];
      _selectedChoices.clear();
      _boxHistory.clear();
    } on AppException catch (e) {
      if (e.statusCode == 401) {
        sessionExpired = true;
        error = 'เซสชันหมดอายุ กรุณาเข้าสู่ระบบอีกครั้ง';
      } else {
        currentBox = null;
        error = e.message;
      }
    } catch (e) {
      currentBox = null; // กันค้างซ้ำกรณี exception เกิดหลัง assign บางส่วน
      error = e.toString();
    } finally {
      isLoading = false;
      notifyListeners();
    }
  }

  Future<bool> continueAssessment(
    String nextDiagramId, {
    dynamic parentAssessmentId,
  }) async {
    final assessmentToContinue = parentAssessmentId ?? assessmentId;
    if (assessmentToContinue == null) return false;

    isLoading = true;
    error = null;
    sessionExpired = false;
    notifyListeners();

    try {
      final result = await _repository.continueAssessment(
        assessmentId: assessmentToContinue,
        diagramId: nextDiagramId,
      );
      assessmentId = result.assessmentId;
      diagramId = result.diagramId;
      currentBox = result.firstBox;
      results = [];
      isCompleted = false;
      _selectedChoices.clear();
      _boxHistory.clear();
      return true;
    } on AppException catch (e) {
      sessionExpired = e.statusCode == 401;
      error = e.message;
      return false;
    } catch (e) {
      error = e.toString();
      return false;
    } finally {
      isLoading = false;
      notifyListeners();
    }
  }

  Future<void> submitAnswers() async {
    if (assessmentId == null || currentBox == null) return;

    final boxId = currentBox!.boxId;
    final choices = _selectedChoices[boxId] ?? [];
    if (choices.isEmpty) return;
    final noneSelected = choices.contains(noneChoiceId);

    isLoading = true;
    error = null;
    sessionExpired = false;
    notifyListeners();

    try {
      final answers = noneSelected
          ? <({String boxId, String choiceId})>[]
          : choices.map((c) => (boxId: boxId, choiceId: c)).toList();

      final result = await _repository.answer(
        assessmentId: assessmentId,
        answers: answers,
        boxId: boxId,
        noneSelected: noneSelected,
      );

      if (result.status == 'next' && result.nextBox != null) {
        // เก็บ box ปัจจุบันไว้ในประวัติก่อนเปลี่ยนไป box ถัดไป
        _boxHistory.add(currentBox!);
        currentBox = result.nextBox;
      } else if (result.status == 'completed') {
        isCompleted = true;
        results = result.results ?? [];
      }
      // ถ้า status ไม่ตรงทั้งสองเคส ปล่อย currentBox เดิมไว้ (ไม่ใช่ error
      // ของ network แต่เป็นข้อมูลตอบกลับไม่คาดคิด) ไม่ throw เพื่อไม่ทำลาย
      // คำตอบที่ผู้ใช้กรอกไว้แล้วฝั่ง server
    } on AppException catch (e) {
      if (e.statusCode == 401) {
        sessionExpired = true;
        error = 'เซสชันหมดอายุ กรุณาเข้าสู่ระบบอีกครั้ง';
      } else {
        error = e.message;
      }
    } catch (e) {
      // ไม่เคลียร์ currentBox ที่นี่ เพราะ user ยังอยู่ในคำถามเดิม
      // (ต่างจาก startAssessment ที่ error แปลว่ายังไม่มีคำถามให้แสดงเลย)
      error = e.toString();
    } finally {
      isLoading = false;
      notifyListeners();
    }
  }

  /// ย้อนกลับไป box คำถามก่อนหน้า (ฝั่ง UI เท่านั้น คำตอบที่บันทึกไว้ฝั่ง server
  /// จะถูกเขียนทับใหม่เมื่อตอบ box นี้ซ้ำผ่าน submitAnswers อีกครั้ง)
  void goBack() {
    if (_boxHistory.isEmpty) return;
    currentBox = _boxHistory.removeLast();
    error = null;
    notifyListeners();
  }

  void reset() {
    _resetState();
    notifyListeners();
  }

  /// ใช้เฉพาะตอน widget dispose (เช่นใน State.dispose()) เท่านั้น
  /// ห้ามเรียก notifyListeners() ตรงนี้ เพราะตอน dispose นั้น widget tree
  /// อาจถูก lock อยู่ (กำลัง unmount/finalize) การเรียก notifyListeners()
  /// ระหว่างนั้นจะทำให้เกิด exception "setState() or markNeedsBuild()
  /// called when widget tree was locked" — และไม่มีใคร listen ต่อแล้วด้วย
  /// เพราะ widget กำลังจะถูกทำลาย จึงไม่จำเป็นต้อง notify
  void disposeReset() {
    _resetState();
  }

  void _resetState() {
    assessmentId = null;
    symptomId = null;
    diagramId = null;
    currentBox = null;
    results = [];
    isLoading = false;
    error = null;
    isCompleted = false;
    sessionExpired = false;
    _selectedChoices.clear();
    _boxHistory.clear();
  }
}
