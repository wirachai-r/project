class UserModel {
  final String userId;
  final String firstName;
  final String lastName;
  final String email;
  final String? phone;
  final String? dateOfBirth;
  final String? sex;
  final String role;
  final String status;
  final String? profileImage;

  const UserModel({
    required this.userId,
    required this.firstName,
    required this.lastName,
    required this.email,
    this.phone,
    this.dateOfBirth,
    this.sex,
    required this.role,
    required this.status,
    this.profileImage,
  });

  String get fullName => '$firstName $lastName';
  bool get isAdmin => role == 'Admin';
  bool get isActive => status == '1';

  factory UserModel.fromJson(Map<String, dynamic> json) => UserModel(
        userId: json['user_id'],
        firstName: json['first_name'],
        lastName: json['last_name'],
        email: json['email'],
        phone: json['phone'],
        dateOfBirth: json['date_of_birth'],
        sex: json['sex'],
        role: json['role'] ?? 'User',
        status: json['status'] ?? '1',
        profileImage: json['profile_image'],
      );
}
