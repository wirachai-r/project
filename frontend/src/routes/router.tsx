import { createBrowserRouter, Navigate } from "react-router-dom";
import { AdminLayout }     from "@/components/layout/AdminLayout";
import { AuthGuard }       from "./AuthGuard";
import { LoginPage }       from "@/features/auth/pages/LoginPage";
import { NotFoundPage }    from "@/features/not-found/pages/NotFoundPage";
import { DashboardPage }   from "@/features/dashboard/pages/DashboardPage";
import UsersPage from "@/features/users/pages/UsersPage";
import { SymptomsPage }    from "@/features/symptoms/pages/SymptomsPage";
import { SymptomCategoriesPage }    from "@/features/symptom-categories/pages/SymptomCategoriesPage";

import { DiseaseCategoriesPage } from "@/features/disease-categories/pages/DiseaseCategoriesPage";
import { DiseasesPage }    from "@/features/diseases/pages/DiseasesPage";
import { DiseaseFormPage } from "@/features/diseases/pages/DiseaseFormPage";

import { DiagramsPage }    from "@/features/diagrams/pages/DiagramsPage";
import { DiagramFormPage } from "@/features/diagrams/pages/DiagramFormPage";
import { DiagramFlowPage } from "@/features/diagrams/pages/DiagramFlowPage";

import { DiagnosisRulesPage } from "@/features/diagnosis-rules/pages/DiagnosisRulesPage";
import { DiagnosisRuleFormPage } from "@/features/diagnosis-rules/pages/DiagnosisRuleFormPage";

import { ArticleCategoriesPage }    from "@/features/article-categories/pages/ArticleCategoriesPage";
import { ArticlesPage }    from "@/features/articles/pages/ArticlesPage";
import { ArticleFormPage } from "@/features/articles/pages/ArticleFormPage";

import { FirstAidCategoriesPage }    from "@/features/firstaid-categories/pages/FirstAidCategoriesPage";
import { FirstAidsPage }   from "@/features/firstaids/pages/FirstAidsPage";
import { FirstAidFormPage } from "@/features/firstaids/pages/FirstAidFormPage";

import { HealthcareFacilitiesPage }  from "@/features/healthcare-facilities/pages/HealthcareFacilitiesPage";
import { ComingSoon }      from "@/components/ui/ComingSoon";

export const router = createBrowserRouter([
  {
    path: "/login",
    element: <LoginPage />,
  },
  {
    path: "/",
    element: (
      <AuthGuard>
        <AdminLayout />
      </AuthGuard>
    ),
    children: [
      { index: true,               element: <Navigate to="/dashboard" replace /> },
      { path: "dashboard",         element: <DashboardPage /> },
      { path: "users",             element: <UsersPage /> },
      { path: "assessments",       element: <ComingSoon title="การประเมิน" /> },

      { path: "symptoms",            element: <SymptomsPage /> },
      { path: "symptoms/categories", element: <SymptomCategoriesPage /> },

      { path: "diseases",                  element: <DiseasesPage /> },
      { path: "diseases/create",           element: <DiseaseFormPage /> },
      { path: "diseases/edit/:diseaseId",  element: <DiseaseFormPage /> },
      { path: "diseases/categories",       element: <DiseaseCategoriesPage /> },

      { path: "diagrams",         element: <DiagramsPage /> },
      { path: "diagrams/create",  element: <DiagramFormPage /> },
      { path: "diagrams/edit/:diagramId", element: <DiagramFormPage /> },
      { path: "diagrams/flow/:diagramId", element: <DiagramFlowPage /> },

      { path: "diagnosis-rules",  element: <DiagnosisRulesPage /> },
      { path: "diagnosis-rules/create",  element: <DiagnosisRuleFormPage /> },
      { path: "diagnosis-rules/edit/:ruleId",  element: <DiagnosisRuleFormPage /> },

      { path: "articles",            element: <ArticlesPage /> },
      { path: "articles/create",     element: <ArticleFormPage /> },
      { path: "articles/edit/:articleId", element: <ArticleFormPage /> },
      { path: "articles/categories", element: <ArticleCategoriesPage /> },

      { path: "first-aids",            element: <FirstAidsPage /> },
      { path: "first-aids/create",     element: <FirstAidFormPage /> },
      { path: "first-aids/edit/:firstAidId", element: <FirstAidFormPage /> },
      { path: "first-aids/categories", element: <FirstAidCategoriesPage /> },

      { path: "facilities",       element: <HealthcareFacilitiesPage /> },
      { path: "*",                element: <NotFoundPage /> },
    ],
  },
]);