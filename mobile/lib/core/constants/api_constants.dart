class ApiConstants {
  static const String baseUrl = 'http://localhost:8000/api';

  // Auth
  static const String register = '/auth/register';
  static const String login = '/auth/login';
  static const String logout = '/auth/logout';
  static const String me = '/auth/me';

  // Symptoms (Client)
  static const String symptomCategories = '/symptom-categories';
  static const String symptoms = '/symptoms';
  static String symptomDetail(String id) => '/symptoms/$id';

  // Diseases (Client)
  static const String diseaseCategories = '/disease-categories';
  static const String diseases = '/diseases';
  static String diseaseDetail(String id) => '/diseases/$id';

  // Articles (Client)
  static const String articleCategories = '/article-categories';
  static const String articles = '/articles';
  static String articleDetail(String id) => '/articles/$id';
  static String articleView(String id) => '/articles/$id/view';

  // First Aids (Client)
  static const String firstAidCategories = '/first-aid-categories';
  static const String firstAids = '/first-aids';
  static String firstAidDetail(String id) => '/first-aids/$id';

  // Facilities (Client)
  static const String facilities = '/healthcare-facilities';
  static String facilityDetail(String id) => '/healthcare-facilities/$id';

  // Assessments (Client)
  static const String assessments = '/assessments';
  static const String assessmentStart = '/assessments/start';
  static String assessmentAnswer(dynamic id) => '/assessments/$id/answer';
  static String assessmentContinue(dynamic id) => '/assessments/$id/continue';
  static String assessmentResult(dynamic id) => '/assessments/$id/result';
  static String assessmentSave(dynamic id) => '/assessments/$id/save';
  static const String assessmentHistory = '/assessments';
  static String assessmentDetail(dynamic id) => '/assessments/$id';

  // Profile
  static const String profile = '/profile';

  // Notifications
  static const String notifications = '/notifications';
  static const String notificationsUnreadCount = '/notifications/unread-count';
  static String notificationRead(dynamic id) => '/notifications/$id/read';
  static const String notificationsReadAll = '/notifications/read-all';

  // Bookmarks
  static const String bookmarks = '/bookmarks';
  static String bookmarkDelete(dynamic id) => '/bookmarks/$id';

  // Personal health
  static const String healthDashboard = '/health-dashboard';
  static String followUps(dynamic assessmentId) =>
      '/assessments/$assessmentId/follow-ups';
  static String followUpDelete(dynamic id) => '/follow-ups/$id';
}
