import 'dart:convert' show Encoding;

import 'package:http/http.dart' as package_http;

export 'package:http/http.dart'
    show MultipartFile, MultipartRequest, Response, StreamedResponse;

/// Single low-level transport for features that need raw responses, downloads,
/// or uploads. JSON domain repositories should prefer ApiService.
class CentralHttpClient {
  CentralHttpClient._();

  static package_http.Client client = package_http.Client();
  static Map<String, String> defaultHeaders = const {};

  static Map<String, String> headers(Map<String, String>? headers) => {
    ...defaultHeaders,
    ...?headers,
  };
}

Future<package_http.Response> get(Uri url, {Map<String, String>? headers}) =>
    CentralHttpClient.client.get(
      url,
      headers: CentralHttpClient.headers(headers),
    );

Future<package_http.Response> post(
  Uri url, {
  Map<String, String>? headers,
  Object? body,
  Encoding? encoding,
}) => CentralHttpClient.client.post(
  url,
  headers: CentralHttpClient.headers(headers),
  body: body,
  encoding: encoding,
);

Future<package_http.Response> put(
  Uri url, {
  Map<String, String>? headers,
  Object? body,
  Encoding? encoding,
}) => CentralHttpClient.client.put(
  url,
  headers: CentralHttpClient.headers(headers),
  body: body,
  encoding: encoding,
);

Future<package_http.Response> patch(
  Uri url, {
  Map<String, String>? headers,
  Object? body,
  Encoding? encoding,
}) => CentralHttpClient.client.patch(
  url,
  headers: CentralHttpClient.headers(headers),
  body: body,
  encoding: encoding,
);

Future<package_http.Response> delete(
  Uri url, {
  Map<String, String>? headers,
  Object? body,
  Encoding? encoding,
}) => CentralHttpClient.client.delete(
  url,
  headers: CentralHttpClient.headers(headers),
  body: body,
  encoding: encoding,
);

Future<package_http.StreamedResponse> send(package_http.BaseRequest request) {
  request.headers.addAll(CentralHttpClient.headers(request.headers));
  return CentralHttpClient.client.send(request);
}
