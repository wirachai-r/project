export interface User {
  user_id: string;
  first_name: string;
  last_name: string;
  email: string;
  phone: string | null;
  date_of_birth: string | null;
  sex: "M" | "F" | null;
  role: "User" | "Admin";
  status: "1" | "2";
  profile_image: string | null;
  system_profile_image: string | null;
  avatar: string | null;
  google_id: string | null;
  last_login_at: string | null;
  created_at: string;
  updated_at: string;
}
