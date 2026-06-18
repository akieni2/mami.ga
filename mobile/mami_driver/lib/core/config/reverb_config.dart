import 'app_config.dart';

class ReverbConfig {
  static const String appKey = String.fromEnvironment(
    'REVERB_APP_KEY',
    defaultValue: 'mami-local-key',
  );

  static const String _hostOverride = String.fromEnvironment('REVERB_HOST');
  static const int _portOverride = int.fromEnvironment('REVERB_PORT', defaultValue: 0);
  static const String _schemeOverride = String.fromEnvironment('REVERB_SCHEME');

  static String get host =>
      _hostOverride.isNotEmpty ? _hostOverride : _websocketUri.host;

  static int get port {
    if (_portOverride > 0) {
      return _portOverride;
    }
    final uriPort = _websocketUri.port;
    if (uriPort > 0) {
      return uriPort;
    }
    return useTls ? 443 : 80;
  }

  static String get scheme => _schemeOverride.isNotEmpty
      ? _schemeOverride
      : (AppConfig.websocketUrl.startsWith('wss') ? 'https' : 'http');

  static bool get useTls => scheme == 'https';

  /// Requis par le SDK Pusher (valeur factice si Reverb self-hosted).
  static const String pusherCluster = String.fromEnvironment(
    'REVERB_PUSHER_CLUSTER',
    defaultValue: 'mt1',
  );

  static Uri get _websocketUri {
    final raw = AppConfig.websocketUrl;
    if (raw.startsWith('ws://') || raw.startsWith('wss://')) {
      return Uri.parse(raw);
    }
    return Uri.parse('wss://$raw');
  }

  static String broadcastAuthUrl(String apiBaseUrl) {
    final root = apiBaseUrl.replaceAll(RegExp(r'/api$'), '');
    return '$root/broadcasting/auth';
  }
}
