import { createBrowserRouter, Navigate } from "react-router-dom";
import type { ComponentType } from "react";
import { AdminLayout }     from "@/components/layout/AdminLayout";
import { AuthGuard }       from "./AuthGuard";
import { LoginPage }       from "@/features/auth/pages/LoginPage";
import { NotFoundPage }    from "@/features/not-found/pages/NotFoundPage";
import { ComingSoon }      from "@/components/ui/ComingSoon";
import { Spinner }         from "@/components/ui/Spinner";

const lazyPage = <T extends Record<string, unknown>, K extends keyof T>(
  load: () => Promise<T>,
  exportName: K,
) => async () => {
  const module = await load();
  return { Component: module[exportName] as ComponentType };
};

export const router = createBrowserRouter([
  {
    path: "/login",
    element: <LoginPage />,
  },
  {
    path: "/",
    hydrateFallbackElement: <Spinner fullscreen label="กำลังโหลดระบบ..." />,
    element: (
      <AuthGuard>
        <AdminLayout />
      </AuthGuard>
    ),
    children: [
      { index: true,               element: <Navigate to="/dashboard" replace /> },
      { path: "dashboard", lazy: lazyPage(() => import("@/features/dashboard/pages/DashboardPage"), "DashboardPage") },
      { path: "users", lazy: lazyPage(() => import("@/features/users/pages/UsersPage"), "default") },
      { path: "assessments",       element: <ComingSoon title="การประเมิน" /> },

      { path: "symptoms", lazy: lazyPage(() => import("@/features/symptoms/pages/SymptomsPage"), "SymptomsPage") },
      { path: "symptoms/categories", lazy: lazyPage(() => import("@/features/symptom-categories/pages/SymptomCategoriesPage"), "SymptomCategoriesPage") },
      { path: "symptoms/body-areas", lazy: lazyPage(() => import("@/features/body-area-groups/pages/BodyAreaGroupsPage"), "BodyAreaGroupsPage") },
      { path: "symptoms/follow-up-questions", lazy: lazyPage(() => import("@/features/follow-up-questions/pages/FollowUpQuestionsStandardPage"), "FollowUpQuestionsPage") },

      { path: "diseases", lazy: lazyPage(() => import("@/features/diseases/pages/DiseasesPage"), "DiseasesPage") },
      { path: "diseases/create", lazy: lazyPage(() => import("@/features/diseases/pages/DiseaseFormPage"), "DiseaseFormPage") },
      { path: "diseases/edit/:diseaseId", lazy: lazyPage(() => import("@/features/diseases/pages/DiseaseFormPage"), "DiseaseFormPage") },
      { path: "diseases/categories", lazy: lazyPage(() => import("@/features/disease-categories/pages/DiseaseCategoriesPage"), "DiseaseCategoriesPage") },

      { path: "diagrams", lazy: lazyPage(() => import("@/features/diagrams/pages/DiagramsPage"), "DiagramsPage") },
      { path: "diagrams/create", lazy: lazyPage(() => import("@/features/diagrams/pages/DiagramFormPage"), "DiagramFormPage") },
      { path: "diagrams/edit/:diagramId", lazy: lazyPage(() => import("@/features/diagrams/pages/DiagramFormPage"), "DiagramFormPage") },
      { path: "diagrams/flow/:diagramId", lazy: lazyPage(() => import("@/features/diagrams/pages/DiagramFlowPage"), "DiagramFlowPage") },

      { path: "diagnosis-rules", lazy: lazyPage(() => import("@/features/diagnosis-rules/pages/DiagnosisRulesPage"), "DiagnosisRulesPage") },
      { path: "diagnosis-rules/create", lazy: lazyPage(() => import("@/features/diagnosis-rules/pages/DiagnosisRuleFormPage"), "DiagnosisRuleFormPage") },
      { path: "diagnosis-rules/edit/:ruleId", lazy: lazyPage(() => import("@/features/diagnosis-rules/pages/DiagnosisRuleFormPage"), "DiagnosisRuleFormPage") },

      { path: "articles", lazy: lazyPage(() => import("@/features/articles/pages/ArticlesPage"), "ArticlesPage") },
      { path: "articles/create", lazy: lazyPage(() => import("@/features/articles/pages/ArticleFormPage"), "ArticleFormPage") },
      { path: "articles/edit/:articleId", lazy: lazyPage(() => import("@/features/articles/pages/ArticleFormPage"), "ArticleFormPage") },
      { path: "articles/categories", lazy: lazyPage(() => import("@/features/article-categories/pages/ArticleCategoriesPage"), "ArticleCategoriesPage") },
      { path: "articles/comments", lazy: lazyPage(() => import("@/features/article-comments/pages/ArticleCommentsPage"), "ArticleCommentsPage") },
      { path: "articles/comments/:articleId", lazy: lazyPage(() => import("@/features/article-comments/pages/ArticleCommentsPage"), "ArticleCommentsPage") },
      { path: "articles/comment-reports", lazy: lazyPage(() => import("@/features/article-comments/pages/ArticleCommentReportsPage"), "ArticleCommentReportsPage") },
      { path: "feedback", lazy: lazyPage(() => import("@/features/feedback/pages/UserFeedbackPage"), "UserFeedbackPage") },
      { path: "notifications", lazy: lazyPage(() => import("@/features/notifications/pages/NotificationsPage"), "NotificationsPage") },
      { path: "my-notifications", lazy: lazyPage(() => import("@/features/account/pages/MyNotificationsPage"), "MyNotificationsPage") },
      { path: "profile", lazy: lazyPage(() => import("@/features/account/pages/ProfilePage"), "ProfilePage") },

      { path: "first-aids", lazy: lazyPage(() => import("@/features/firstaids/pages/FirstAidsPage"), "FirstAidsPage") },
      { path: "first-aids/create", lazy: lazyPage(() => import("@/features/firstaids/pages/FirstAidFormPage"), "FirstAidFormPage") },
      { path: "first-aids/edit/:firstAidId", lazy: lazyPage(() => import("@/features/firstaids/pages/FirstAidFormPage"), "FirstAidFormPage") },
      { path: "first-aids/categories", lazy: lazyPage(() => import("@/features/firstaid-categories/pages/FirstAidCategoriesPage"), "FirstAidCategoriesPage") },

      { path: "facilities", lazy: lazyPage(() => import("@/features/healthcare-facilities/pages/HealthcareFacilitiesPage"), "HealthcareFacilitiesPage") },
      { path: "*",                element: <NotFoundPage /> },
    ],
  },
]);
