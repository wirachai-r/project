import '../constants/api_constants.dart';

/// Resolves API media paths and upgrades stale HTTP API URLs before Flutter
/// Web requests them. Avoiding an HTTP -> HTTPS redirect is important because
/// browsers apply CORS to the redirect response as well.
String? resolveMediaUrl(Object? rawValue) {
  final value = rawValue?.toString().trim();
  if (value == null || value.isEmpty) return null;

  final apiUri = Uri.tryParse(ApiConstants.baseUrl);
  if (apiUri == null || !apiUri.hasScheme || apiUri.host.isEmpty) return value;

  final mediaUri = Uri.tryParse(value);
  if (mediaUri != null && mediaUri.hasScheme && mediaUri.host.isNotEmpty) {
    if (mediaUri.host == apiUri.host && mediaUri.scheme != apiUri.scheme) {
      return mediaUri.replace(scheme: apiUri.scheme).toString();
    }
    return value;
  }

  var path = value.startsWith('/') ? value : '/api/media/$value';
  if (path.startsWith('/storage/')) {
    path = '/api/media/${path.substring('/storage/'.length)}';
  }

  return Uri(
    scheme: apiUri.scheme,
    host: apiUri.host,
    port: apiUri.hasPort ? apiUri.port : null,
    path: path,
  ).toString();
}
