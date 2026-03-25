// lib/services/remote_config_service.dart

import 'package:firebase_remote_config/firebase_remote_config.dart';
import 'package:flutter/foundation.dart';

class RemoteConfigService {
  static final _remoteConfig = FirebaseRemoteConfig.instance;

  // Default values — used when remote config hasn't been fetched yet
  static const _defaults = {
    'api_base_url': 'https://ipl.flyingcaps.com/api',
    'force_update': false,
    'min_app_version': '1.0.0',
    'maintenance_mode': false,
  };

  /// Initialize and fetch remote config. Call after Firebase.initializeApp().
  static Future<void> init() async {
    try {
      await _remoteConfig.setConfigSettings(RemoteConfigSettings(
        fetchTimeout: const Duration(seconds: 10),
        minimumFetchInterval: const Duration(hours: 1),
      ));
      await _remoteConfig.setDefaults(_defaults);
      await _remoteConfig.fetchAndActivate();
      debugPrint('[RemoteConfig] Fetched and activated');
    } catch (e) {
      debugPrint('[RemoteConfig] Init failed, using defaults: $e');
    }
  }

  // ── Getters ──────────────────────────────────────────────────

  static String get apiBaseUrl => _remoteConfig.getString('api_base_url');

  static bool get forceUpdate => _remoteConfig.getBool('force_update');

  static String get minAppVersion => _remoteConfig.getString('min_app_version');

  static bool get maintenanceMode => _remoteConfig.getBool('maintenance_mode');
}