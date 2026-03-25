// lib/services/notification_service.dart

import 'dart:io';
import 'package:flutter/foundation.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:go_router/go_router.dart';
import 'api_service.dart';

/// Handles background FCM messages (must be top-level function).
/// Note: When the message contains a `notification` payload, the system
/// automatically displays it in the tray — no need to show a local
/// notification here (doing so would cause duplicates).
@pragma('vm:entry-point')
Future<void> firebaseMessagingBackgroundHandler(RemoteMessage message) async {
  debugPrint('[FCM] Background message received: ${message.messageId}');
}

class NotificationService {
  static final _messaging = FirebaseMessaging.instance;
  static final _localNotifications = FlutterLocalNotificationsPlugin();
  static ApiService? _api;
  static GoRouter? _router;

  /// Whether init() has completed (auth ready, safe to navigate).
  static bool _isReady = false;

  /// Route from the notification that launched/resumed the app.
  /// Consumed by the splash screen to navigate after setup.
  static String? pendingRoute;

  static const _channel = AndroidNotificationChannel(
    'ipl_poll_channel',
    'IPL Poll Notifications',
    description: 'Match updates, poll results, and more',
    importance: Importance.high,
  );

  /// Check if app was launched from a notification tap (call early, before auth).
  static Future<void> checkInitialNotification() async {
    _isReady = false; // Reset — splash will set ready after init()
    try {
      debugPrint('[FCM] Checking getInitialMessage...');
      final initialMessage = await _messaging.getInitialMessage()
          .timeout(const Duration(seconds: 3), onTimeout: () {
        debugPrint('[FCM] getInitialMessage timed out');
        return null;
      });
      debugPrint('[FCM] getInitialMessage = ${initialMessage?.messageId}, data = ${initialMessage?.data}');
      if (initialMessage != null) {
        final route = initialMessage.data['route'];
        if (route != null && route.toString().isNotEmpty) {
          debugPrint('[FCM] Setting pendingRoute = $route');
          pendingRoute = route.toString();
        }
      }
      // Also check if onMessageOpenedApp already set pendingRoute
      debugPrint('[FCM] After check, pendingRoute = $pendingRoute');
    } catch (e) {
      debugPrint('[FCM] checkInitialNotification error: $e');
    }
  }

  /// Set up early FCM listeners (call from main, before auth).
  /// These store the route as pendingRoute instead of navigating directly,
  /// so splash can handle navigation after auth is ready.
  static void setupListeners() {
    // When user taps a notification (app was in background)
    FirebaseMessaging.onMessageOpenedApp.listen((message) {
      debugPrint('[FCM] onMessageOpenedApp fired, data=${message.data}');
      final route = message.data['route'];
      if (route != null && route.toString().isNotEmpty) {
        if (_isReady) {
          // App is already running and authenticated — navigate directly
          debugPrint('[FCM] App is ready, navigating to $route');
          _navigateTo(route.toString());
        } else {
          // App is restarting — store route for splash to handle after auth
          debugPrint('[FCM] App not ready, saving pendingRoute=$route');
          pendingRoute = route.toString();
        }
      }
    });
  }

  /// Initialize notifications. Call after Firebase.initializeApp() and successful auth.
  static Future<void> init(ApiService api, GoRouter router) async {
    _api = api;
    _router = router;

    // Set up local notifications
    await _setupLocalNotifications();

    // iOS: tell the system to show banners even when app is in foreground
    await _messaging.setForegroundNotificationPresentationOptions(
      alert: true,
      badge: true,
      sound: true,
    );

    // Request permission
    final settings = await _messaging.requestPermission(
      alert: true,
      badge: true,
      sound: true,
    );

    if (settings.authorizationStatus == AuthorizationStatus.denied) {
      _isReady = true;
      return;
    }

    // Get and register FCM token
    final token = await _messaging.getToken();
    debugPrint('FCM Token: $token');
    if (token != null) {
      await _registerToken(token);
    }

    // Listen for token refreshes
    _messaging.onTokenRefresh.listen(_registerToken);

    // Foreground messages
    FirebaseMessaging.onMessage.listen(_handleForegroundMessage);

    // Mark as ready — future notification taps can navigate directly
    _isReady = true;
    debugPrint('[FCM] NotificationService ready');
  }

  static Future<void> _setupLocalNotifications() async {
    const androidSettings = AndroidInitializationSettings('@mipmap/ic_launcher');
    const iosSettings = DarwinInitializationSettings(
      requestAlertPermission: false,
      requestBadgePermission: false,
      requestSoundPermission: false,
    );

    await _localNotifications.initialize(
      const InitializationSettings(
        android: androidSettings,
        iOS: iosSettings,
      ),
      onDidReceiveNotificationResponse: _onLocalNotificationTap,
    );

    // Create Android notification channel
    if (Platform.isAndroid) {
      await _localNotifications
          .resolvePlatformSpecificImplementation<
              AndroidFlutterLocalNotificationsPlugin>()
          ?.createNotificationChannel(_channel);
    }
  }

  static void _onLocalNotificationTap(NotificationResponse details) {
    final route = details.payload;
    debugPrint('[FCM] Local notification tapped, payload=$route, isReady=$_isReady');
    if (route != null && route.isNotEmpty) {
      if (_isReady) {
        _navigateTo(route);
      } else {
        debugPrint('[FCM] App not ready, saving pendingRoute=$route');
        pendingRoute = route;
      }
    }
  }

  static Future<void> _registerToken(String token) async {
    try {
      await _api?.registerFcmToken(token);
      debugPrint('FCM token registered with backend');
    } catch (e) {
      debugPrint('FCM token registration failed: $e');
    }
  }

  static Future<void> _handleForegroundMessage(RemoteMessage message) async {
    debugPrint('[FCM] Foreground message received: ${message.messageId}, data=${message.data}');
    await _showLocalNotification(message);
  }

  static Future<void> _showLocalNotification(RemoteMessage message) async {
    final notification = message.notification;
    if (notification == null) return;

    await _localNotifications.show(
      notification.hashCode,
      notification.title,
      notification.body,
      NotificationDetails(
        android: AndroidNotificationDetails(
          _channel.id,
          _channel.name,
          channelDescription: _channel.description,
          importance: Importance.high,
          priority: Priority.high,
          icon: '@mipmap/ic_launcher',
        ),
        iOS: const DarwinNotificationDetails(
          presentAlert: true,
          presentBadge: true,
          presentSound: true,
        ),
      ),
      payload: message.data['route'],
    );
  }

  static void _handleNotificationTap(RemoteMessage message) {
    debugPrint('[FCM] onMessageOpenedApp tapped, data=${message.data}');
    final route = message.data['route'];
    if (route != null && route.toString().isNotEmpty) {
      _navigateTo(route.toString());
    } else {
      debugPrint('[FCM] onMessageOpenedApp — no route in data');
    }
  }

  static void _navigateTo(String route) {
    debugPrint('[FCM] _navigateTo called, route=$route, router=${_router != null ? "SET" : "NULL"}');
    try {
      // Go to /home first to ensure a clean back stack, then push the route
      _router?.go('/home');
      Future.delayed(const Duration(milliseconds: 300), () {
        _router?.push(route);
        debugPrint('[FCM] _navigateTo push completed');
      });
    } catch (e) {
      debugPrint('[FCM] _navigateTo FAILED: $e');
    }
  }
}