class ReverbConfig {
  static const String appKey = String.fromEnvironment(
    'REVERB_APP_KEY',
    defaultValue: 'mami-local-key',
  );

  static const String host = String.fromEnvironment(
    'REVERB_HOST',
    defaultValue: '63.142.241.105',
  );

  static const int port = int.fromEnvironment(
    'REVERB_PORT',
    defaultValue: 8080,
  );

  static const String scheme = String.fromEnvironment(
    'REVERB_SCHEME',
    defaultValue: 'http',
  );

  static bool get useTls => scheme == 'https';

  /// Requis par le SDK Pusher (valeur factice si Reverb self-hosted).
  static const String pusherCluster = String.fromEnvironment(
    'REVERB_PUSHER_CLUSTER',
    defaultValue: 'mt1',
  );

  static String broadcastAuthUrl(String apiBaseUrl) {
    final root = apiBaseUrl.replaceAll(RegExp(r'/api$'), '');
    return '$root/broadcasting/auth';
  }
}
