class AppException implements Exception {
  final String message;
  final int? statusCode;

  const AppException(this.message, {this.statusCode});

  // เพิ่ม factory นี้
  factory AppException.fromStatus(int statusCode, String? message) {
    switch (statusCode) {
      case 401:
        return UnauthorizedException(message ?? 'กรุณาเข้าสู่ระบบใหม่');
      case 403:
        return ForbiddenException(message ?? 'ไม่มีสิทธิ์เข้าถึง');
      case 404:
        return NotFoundException(message ?? 'ไม่พบข้อมูล');
      case 422:
        return ValidationException(message ?? 'ข้อมูลไม่ถูกต้อง', {});
      default:
        return ServerException(message ?? 'เกิดข้อผิดพลาด');
    }
  }

  @override
  String toString() => message;
}

class UnauthorizedException extends AppException {
  const UnauthorizedException([String message = 'กรุณาเข้าสู่ระบบใหม่'])
    : super(message, statusCode: 401);
}

class ForbiddenException extends AppException {
  const ForbiddenException([String message = 'ไม่มีสิทธิ์เข้าถึงข้อมูลนี้'])
    : super(message, statusCode: 403);
}

class NotFoundException extends AppException {
  const NotFoundException([String message = 'ไม่พบข้อมูล'])
    : super(message, statusCode: 404);
}

class ValidationException extends AppException {
  final Map<String, List<String>> errors;

  const ValidationException(String message, this.errors)
    : super(message, statusCode: 422);
}

class ServerException extends AppException {
  const ServerException([String message = 'เกิดข้อผิดพลาดจากเซิร์ฟเวอร์'])
    : super(message, statusCode: 500);
}

class NetworkException extends AppException {
  const NetworkException([String message = 'ไม่สามารถเชื่อมต่ออินเทอร์เน็ตได้'])
    : super(message);
}
