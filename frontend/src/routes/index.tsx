import { createBrowserRouter, Navigate } from "react-router-dom";
import { AdminLayout }     from "@/components/layout/AdminLayout";
import { AuthGuard }       from "./AuthGuard";
import { LoginPage }       from "@/features/auth/pages/LoginPage";
import { NotFoundPage }    from "@/features/not-found/pages/NotFoundPage";
import { DashboardPage }   from "@/features/dashboard/pages/DashboardPage";
import { UsersPage }       from "@/features/users/pages/UsersPage";
import { SymptomsPage }    from "@/features/symptoms/pages/SymptomsPage";
import { DiseasesPage }    from "@/features/diseases/pages/DiseasesPage";
import { DiagramsPage }    from "@/features/diagrams/pages/DiagramsPage";
import { DiagnosisRulesPage } from "@/features/diagnosis-rules/pages/DiagnosisRulesPage";
import { ArticlesPage }    from "@/features/articles/pages/ArticlesPage";
import { FirstAidsPage }   from "@/features/first-aids/pages/FirstAidsPage";
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
      { index: true,             element: <Navigate to="/dashboard" replace /> },
      { path: "dashboard",       element: <DashboardPage /> },
      { path: "users",           element: <UsersPage /> },
      { path: "assessments",     element: <ComingSoon title="การประเมิน" /> },
      { path: "symptoms",        element: <SymptomsPage /> },
      { path: "diseases",        element: <DiseasesPage /> },
      { path: "diagrams",        element: <DiagramsPage /> },
      { path: "diagnosis-rules", element: <DiagnosisRulesPage /> },
      { path: "articles",        element: <ArticlesPage /> },
      { path: "first-aids",      element: <FirstAidsPage /> },
      { path: "facilities",      element: <HealthcareFacilitiesPage /> },
      { path: "*",               element: <NotFoundPage /> },
    ],
  },
]);