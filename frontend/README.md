# React + TypeScript + Vite

This template provides a minimal setup to get React working in Vite with HMR and some ESLint rules.

Currently, two official plugins are available:

- [@vitejs/plugin-react](https://github.com/vitejs/vite-plugin-react/blob/main/packages/plugin-react) uses [Oxc](https://oxc.rs)
- [@vitejs/plugin-react-swc](https://github.com/vitejs/vite-plugin-react/blob/main/packages/plugin-react-swc) uses [SWC](https://swc.rs/)

## React Compiler

The React Compiler is not enabled on this template because of its impact on dev & build performances. To add it, see [this documentation](https://react.dev/learn/react-compiler/installation).

## Expanding the ESLint configuration

If you are developing a production application, we recommend updating the configuration to enable type-aware lint rules:

```js
export default defineConfig([
  globalIgnores(['dist']),
  {
    files: ['**/*.{ts,tsx}'],
    extends: [
      // Other configs...

      // Remove tseslint.configs.recommended and replace with this
      tseslint.configs.recommendedTypeChecked,
      // Alternatively, use this for stricter rules
      tseslint.configs.strictTypeChecked,
      // Optionally, add this for stylistic rules
      tseslint.configs.stylisticTypeChecked,

      // Other configs...
    ],
    languageOptions: {
      parserOptions: {
        project: ['./tsconfig.node.json', './tsconfig.app.json'],
        tsconfigRootDir: import.meta.dirname,
      },
      // other options...
    },
  },
])
```

You can also install [eslint-plugin-react-x](https://github.com/Rel1cx/eslint-react/tree/main/packages/plugins/eslint-plugin-react-x) and [eslint-plugin-react-dom](https://github.com/Rel1cx/eslint-react/tree/main/packages/plugins/eslint-plugin-react-dom) for React-specific lint rules:

```js
// eslint.config.js
import reactX from 'eslint-plugin-react-x'
import reactDom from 'eslint-plugin-react-dom'

export default defineConfig([
  globalIgnores(['dist']),
  {
    files: ['**/*.{ts,tsx}'],
    extends: [
      // Other configs...
      // Enable lint rules for React
      reactX.configs['recommended-typescript'],
      // Enable lint rules for React DOM
      reactDom.configs.recommended,
    ],
    languageOptions: {
      parserOptions: {
        project: ['./tsconfig.node.json', './tsconfig.app.json'],
        tsconfigRootDir: import.meta.dirname,
      },
      // other options...
    },
  },
])
```
# Checkup Admin Panel — Folder Structure

```
src/
├── assets/
│   └── logo.svg
│
├── components/
│   ├── ui/                          # Reusable UI primitives
│   │   ├── Badge.tsx
│   │   ├── Button.tsx
│   │   ├── Card.tsx
│   │   ├── DataTable.tsx            # ตาราง + pagination
│   │   ├── EmptyState.tsx
│   │   ├── Input.tsx
│   │   ├── Modal.tsx
│   │   ├── Pagination.tsx
│   │   ├── SearchBar.tsx
│   │   ├── Select.tsx
│   │   ├── StatusToggle.tsx         # toggle สถานะ Active/Inactive
│   │   └── Spinner.tsx
│   │
│   └── layout/
│       ├── AdminLayout.tsx          # Sidebar + Topbar wrapper
│       ├── Sidebar.tsx
│       └── Topbar.tsx
│
├── features/
│   ├── auth/
│   │   ├── components/
│   │   │   └── LoginForm.tsx
│   │   ├── hooks/
│   │   │   └── useAuth.ts
│   │   └── pages/
│   │       └── LoginPage.tsx
│   │
│   ├── dashboard/
│   │   ├── components/
│   │   │   ├── AssessmentChart.tsx  # กราฟแนวโน้มการประเมิน
│   │   │   ├── StatsCard.tsx
│   │   │   ├── TopDiseasesList.tsx
│   │   │   └── UrgencySummary.tsx
│   │   └── pages/
│   │       └── DashboardPage.tsx
│   │
│   ├── users/
│   │   ├── components/
│   │   │   ├── UserFilters.tsx
│   │   │   └── UserTable.tsx
│   │   └── pages/
│   │       └── UsersPage.tsx
│   │
│   ├── symptoms/
│   │   ├── components/
│   │   │   ├── SymptomCategoryForm.tsx
│   │   │   └── SymptomForm.tsx
│   │   └── pages/
│   │       ├── SymptomCategoriesPage.tsx
│   │       └── SymptomsPage.tsx
│   │
│   ├── diagrams/
│   │   ├── components/
│   │   │   ├── AnswerChoiceForm.tsx
│   │   │   ├── DiagramForm.tsx
│   │   │   └── QuestionBoxForm.tsx
│   │   └── pages/
│   │       ├── DiagramDetailPage.tsx  # question-boxes + answer-choices
│   │       └── DiagramsPage.tsx
│   │
│   ├── diagnosis-rules/
│   │   ├── components/
│   │   │   ├── RuleConditionForm.tsx
│   │   │   └── RuleForm.tsx
│   │   └── pages/
│   │       └── DiagnosisRulesPage.tsx
│   │
│   ├── diseases/
│   │   ├── components/
│   │   │   ├── DiseaseCategoryForm.tsx
│   │   │   ├── DiseaseForm.tsx
│   │   │   └── TreatmentOrderForm.tsx
│   │   └── pages/
│   │       ├── DiseaseCategoriesPage.tsx
│   │       ├── DiseaseDetailPage.tsx  # treatment-orders
│   │       └── DiseasesPage.tsx
│   │
│   ├── articles/
│   │   ├── components/
│   │   │   ├── ArticleCategoryForm.tsx
│   │   │   └── ArticleForm.tsx
│   │   └── pages/
│   │       ├── ArticleCategoriesPage.tsx
│   │       └── ArticlesPage.tsx
│   │
│   ├── first-aids/
│   │   ├── components/
│   │   │   ├── FirstAidCategoryForm.tsx
│   │   │   └── FirstAidForm.tsx
│   │   └── pages/
│   │       ├── FirstAidCategoriesPage.tsx
│   │       └── FirstAidsPage.tsx
│   │
│   ├── healthcare-facilities/
│   │   ├── components/
│   │   │   └── FacilityForm.tsx
│   │   └── pages/
│   │       └── HealthcareFacilitiesPage.tsx
│   │
│   └── notifications/
│       ├── components/
│       │   └── NotificationForm.tsx
│       └── pages/
│           └── NotificationsPage.tsx
│
├── hooks/
│   ├── useDebounce.ts
│   └── usePagination.ts
│
├── lib/
│   ├── api.ts                       # axios instance + interceptors
│   └── constants.ts                 # URGENCY_LABELS, STATUS_OPTIONS ฯลฯ
│
├── routes/
│   └── index.tsx                    # React Router config
│
├── stores/
│   └── authStore.ts                 # Zustand หรือ Context สำหรับ auth state
│
├── types/
│   ├── api.ts                       # Response wrapper types
│   ├── assessment.ts
│   ├── disease.ts
│   ├── symptom.ts
│   └── user.ts
│
├── App.tsx
├── main.tsx
└── vite-env.d.ts
```

## หมายเหตุ

- **features/** — แต่ละ feature มี `pages/` และ `components/` ของตัวเอง ไม่ข้าม feature
- **components/ui/** — เฉพาะ primitive ที่ใช้ร่วมกันทุก feature
- **lib/api.ts** — ตั้ง base URL, header `Authorization: Bearer {token}`, และ intercept 401
- **stores/authStore.ts** — เก็บ token + user, persist ใน localStorage
- **types/** — interface ที่ match กับ Laravel Resource response

# Runtime
npm install react-router-dom axios zustand recharts react-hook-form zod @hookform/resolvers
npm install lucide-react
npm install class-variance-authority clsx tailwind-merge
npm install sonner @tanstack/react-table
npm install @radix-ui/react-dialog @radix-ui/react-select @radix-ui/react-tooltip @radix-ui/react-slot @radix-ui/react-label
npm install @radix-ui/react-dropdown-menu
npm install @radix-ui/react-checkbox
npm install @radix-ui/react-alert-dialog

npm install @tiptap/react @tiptap/pm @tiptap/starter-kit @tiptap/extension-image @tiptap/extension-placeholder @tiptap/extension-underline @tiptap/extension-link @tiptap/extension-text-align

npm install @tiptap/react @tiptap/pm @tiptap/starter-kit @tiptap/extension-image @tiptap/extension-placeholder @tiptap/extension-underline @tiptap/extension-link @tiptap/extension-text-align --legacy-peer-deps

npm install react-is

npm install @xyflow/react dagre

npm install tailwindcss-animate
@plugin "tailwindcss-animate";

# Dev
npm install -D tailwindcss @tailwindcss/vite
npm install -D @types/node
npm install -D @tailwindcss/typography