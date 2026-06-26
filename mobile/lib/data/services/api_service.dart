import 'dart:convert';
import 'package:http/http.dart' as http;
import '../../core/constants/api_constants.dart';
import '../../core/errors/app_exception.dart';

class ApiService {
  final http.Client _client;
  String? _token;

  ApiService({http.Client? client}) : _client = client ?? http.Client();

  void setToken(String token) => _token = token;
  void clearToken() => _token = null;

  Map<String, String> get _headers => {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    if (_token != null) 'Authorization': 'Bearer $_token',
  };

  // ---- GET ----
  Future<dynamic> get(String endpoint, {Map<String, dynamic>? params}) async {
    try {
      final uri = Uri.parse(ApiConstants.baseUrl + endpoint).replace(
        queryParameters: params?.map((k, v) => MapEntry(k, v.toString())),
      );

      final response = await _client
          .get(uri, headers: _headers)
          .timeout(const Duration(seconds: 30));

      return _handleResponse(response);
    } on AppException {
      rethrow;
    } catch (_) {
      throw const NetworkException();
    }
  }

  // ---- POST ----
  Future<dynamic> post(String endpoint, {Map<String, dynamic>? body}) async {
    try {
      final response = await _client
          .post(
            Uri.parse(ApiConstants.baseUrl + endpoint),
            headers: _headers,
            body: json.encode(body ?? {}),
          )
          .timeout(const Duration(seconds: 30));

      return _handleResponse(response);
    } on AppException {
      rethrow;
    } catch (_) {
      throw const NetworkException();
    }
  }

  // ---- PUT ----
  Future<dynamic> put(String endpoint, {Map<String, dynamic>? body}) async {
    try {
      final response = await _client
          .put(
            Uri.parse(ApiConstants.baseUrl + endpoint),
            headers: _headers,
            body: json.encode(body ?? {}),
          )
          .timeout(const Duration(seconds: 30));

      return _handleResponse(response);
    } on AppException {
      rethrow;
    } catch (_) {
      throw const NetworkException();
    }
  }

  // ---- PATCH ----
  Future<dynamic> patch(String endpoint, {Map<String, dynamic>? body}) async {
    try {
      final response = await _client
          .patch(
            Uri.parse(ApiConstants.baseUrl + endpoint),
            headers: _headers,
            body: json.encode(body ?? {}),
          )
          .timeout(const Duration(seconds: 30));

      return _handleResponse(response);
    } on AppException {
      rethrow;
    } catch (_) {
      throw const NetworkException();
    }
  }

  // ---- DELETE ----
  Future<dynamic> delete(String endpoint) async {
    try {
      final response = await _client
          .delete(Uri.parse(ApiConstants.baseUrl + endpoint), headers: _headers)
          .timeout(const Duration(seconds: 30));

      return _handleResponse(response);
    } on AppException {
      rethrow;
    } catch (_) {
      throw const NetworkException();
    }
  }

  // ---- Response handler ----
  dynamic _handleResponse(http.Response response) {
    final body = utf8.decode(response.bodyBytes);
    final decoded = body.isNotEmpty ? json.decode(body) : null;

    switch (response.statusCode) {
      case 200:
      case 201:
        return decoded;
      case 401:
        throw const UnauthorizedException();
      case 403:
        throw const ForbiddenException();
      case 404:
        throw const NotFoundException();
      case 422:
        final errors = (decoded?['errors'] as Map<String, dynamic>?)?.map(
          (k, v) => MapEntry(k, List<String>.from(v)),
        );
        throw ValidationException(
          decoded?['message'] ?? 'ข้อมูลไม่ถูกต้อง',
          errors ?? {},
        );
      case 429:
        throw AppException(
          decoded?['message'] ?? 'คุณทำรายการบ่อยเกินไป กรุณารอสักครู่',
          statusCode: 429,
        );
      default:
        throw const ServerException();
    }
  }
}
