class ApiConstants {
  static const String baseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'http://localhost:8000/api',
  );

  // Auth
  static const String register = '/auth/register';
  static const String verifyRegistrationOtp = '/auth/verify-registration-otp';
  static const String resendRegistrationOtp = '/auth/resend-registration-otp';
  static const String login = '/auth/login';
  static const String googleLogin = '/auth/google';
  static const String logout = '/auth/logout';
  static const String me = '/auth/me';
  static const String forgotPassword = '/auth/forgot-password';
  static const String verifyPasswordOtp = '/auth/verify-password-otp';
  static const String resetPassword = '/auth/reset-password';

  // Symptoms (Client)
  static const String symptomCategories = '/symptom-categories';
  static const String symptoms = '/symptoms';
  static String symptomDetail(String id) => '/symptoms/$id';
  static const String bodyAreaGroups = '/body-area-groups';
  static String bodyAreaGroupSymptoms(int id) =>
      '/body-area-groups/$id/symptoms';
  static String bodyAreaSubgroupSymptoms(int groupId, int subgroupId) =>
      '/body-area-groups/$groupId/subgroups/$subgroupId/symptoms';

  // Diseases (Client)
  static const String diseaseCategories = '/disease-categories';
  static const String diseases = '/diseases';
  static String diseaseDetail(String id) => '/diseases/$id';

  // Articles (Client)
  static const String articleCategories = '/article-categories';
  static const String articles = '/articles';
  static String articleDetail(String id) => '/articles/$id';
  static String articleView(String id) => '/articles/$id/view';
  static String articleComments(String id) => '/articles/$id/comments';
  static String articleLike(String id) => '/articles/$id/like';
  static String articleEngagement(String id) => '/articles/$id/engagement';
  static String articleCommentDelete(dynamic id) => '/article-comments/$id';
  static String articleCommentLike(dynamic id) => '/article-comments/$id/like';
  static String articleCommentReport(dynamic id) =>
      '/article-comments/$id/report';

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
  static String aiClarifyQuestion(dynamic id) =>
      '/ai/assessments/$id/clarify-question';
  static String aiAnswerClarificationQuestion(dynamic id) =>
      '/ai/clarification-questions/$id/answer';
  static String aiMarkClarificationUnresolved(dynamic id) =>
      '/ai/clarification-sessions/$id/unresolved';
  static String aiAssessmentGuidance(dynamic id) =>
      '/ai/assessments/$id/guidance';
  static const String aiHealthTrendSummary = '/ai/health-trends/summary';

  // Profile
  static const String profile = '/profile';
  static const String changePassword = '/profile/password';
  static const String privacyExport = '/privacy/export';
  static const String privacyAccount = '/privacy/account';
  static const String sessions = '/sessions';
  static const String sessionsOthers = '/sessions/others';
  static String session(dynamic id) => '/sessions/$id';
  static const String healthReminders = '/health-reminders';
  static String healthReminder(dynamic id) => '/health-reminders/$id';
  static const String healthReport = '/health-report';
  static const String feedback = '/feedback';
  static const String accountActivities = '/account-activities';
  static const String unifiedSearch = '/search';
  static const String firstAidsOffline = '/first-aids/offline';

  // Notifications
  static const String notifications = '/notifications';
  static const String notificationsUnreadCount = '/notifications/unread-count';
  static String notificationRead(dynamic id) => '/notifications/$id/read';
  static String notificationDismiss(dynamic id) => '/notifications/$id/dismiss';
  static String notificationRestore(dynamic id) => '/notifications/$id/restore';
  static const String notificationsReadAll = '/notifications/read-all';

  // Bookmarks
  static const String bookmarks = '/bookmarks';
  static String bookmarkDelete(dynamic id) => '/bookmarks/$id';

  // Personal health
  static const String healthDashboard = '/health-dashboard';
  static const String dailyHealthRecords = '/daily-health-records';
  static String dailyHealthRecord(dynamic recordId) =>
      '/daily-health-records/$recordId';
  static String followUps(dynamic assessmentId) =>
      '/assessments/$assessmentId/follow-ups';
  static String followUpDelete(dynamic id) => '/follow-ups/$id';
  static const String healthEpisodes = '/health-episodes';
  static String healthEpisode(dynamic id) => '/health-episodes/$id';
  static String healthEpisodeStatus(dynamic id) =>
      '/health-episodes/$id/status';
  static String assessmentHealthEpisode(dynamic assessmentId) =>
      '/assessments/$assessmentId/health-episode';
  static String healthEpisodeSymptoms(dynamic episodeId) =>
      '/health-episodes/$episodeId/symptoms';
  static String episodeSymptomFollowUps(dynamic episodeSymptomId) =>
      '/episode-symptoms/$episodeSymptomId/follow-ups';
  static String followUpEntry(dynamic entryId) => '/follow-up-entries/$entryId';
  static String episodeSymptomStatus(dynamic episodeSymptomId) =>
      '/episode-symptoms/$episodeSymptomId/status';
  static String followUpEntryDelete(dynamic id) => '/follow-up-entries/$id';
}
