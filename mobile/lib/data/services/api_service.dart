import 'dart:convert';
import 'package:http/http.dart' as http;
import '../../core/constants/api_constants.dart';
import '../../core/errors/app_exception.dart';
import 'central_http_client.dart';

class ApiService {
  final http.Client _client;
  String? _token;
  String? _sessionToken;
  final Map<String, _CachedResponse> _getCache = {};
  final Map<String, Future<dynamic>> _inFlightGets = {};

  ApiService({http.Client? client})
    : _client = client ?? CentralHttpClient.client {
    CentralHttpClient.client = _client;
    _syncDefaultHeaders();
  }

  void setToken(String token) {
    if (_token != token) clearCache();
    _token = token;
    _syncDefaultHeaders();
  }

  void clearToken() {
    _token = null;
    _syncDefaultHeaders();
    clearCache();
  }

  void setSessionToken(String? token) {
    _sessionToken = token;
    _syncDefaultHeaders();
  }

  void _syncDefaultHeaders() {
    CentralHttpClient.defaultHeaders = Map.of(_headers)..remove('Content-Type');
  }

  void clearCache() => _getCache.clear();

  Map<String, String> get _headers => {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    if (_token != null) 'Authorization': 'Bearer $_token',
    if (_sessionToken != null) 'X-Session-Token': _sessionToken!,
  };

  // ---- GET ----
  Future<dynamic> get(
    String endpoint, {
    Map<String, dynamic>? params,
    Duration? cacheDuration,
    bool forceRefresh = false,
  }) async {
    final uri = Uri.parse(ApiConstants.baseUrl + endpoint).replace(
      queryParameters: params?.map((k, v) => MapEntry(k, v.toString())),
    );
    final cacheKey = '${_token ?? ''}|$uri';
    final cached = _getCache[cacheKey];
    if (!forceRefresh &&
        cacheDuration != null &&
        cached != null &&
        DateTime.now().isBefore(cached.expiresAt)) {
      return cached.value;
    }

    // Multiple widgets often ask for the same reference data at startup. Share
    // that request instead of opening several identical HTTP connections.
    final inFlightRequest = _inFlightGets[cacheKey];
    if (!forceRefresh && inFlightRequest != null) {
      return inFlightRequest;
    }

    final request = _performGet(uri).then((value) {
      if (cacheDuration != null) {
        _getCache[cacheKey] = _CachedResponse(
          value,
          DateTime.now().add(cacheDuration),
        );
      }
      return value;
    });
    _inFlightGets[cacheKey] = request;
    try {
      return await request;
    } finally {
      if (identical(_inFlightGets[cacheKey], request)) {
        _inFlightGets.remove(cacheKey);
      }
    }
  }

  Future<dynamic> _performGet(Uri uri) async {
    try {
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

class _CachedResponse {
  final dynamic value;
  final DateTime expiresAt;

  const _CachedResponse(this.value, this.expiresAt);
}
