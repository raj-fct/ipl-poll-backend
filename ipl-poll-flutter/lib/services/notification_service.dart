// lib/services/notification_service.dart

import 'dart:io';
import 'package:flutter/foundation.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:go_router/go_router.dart';
import 'api_service.dart';

/// Handles background FCM messages (must be top-level function).
@pragma('vm:entry-point')
Future<void> firebaseMessagingBackgroundHandler(RemoteMessage message) async {
  // Show local notification for background messages
  await NotificationService._showLocalNotification(message);
}

class NotificationService {
  static final _messaging = FirebaseMessaging.instance;
  static final _localNotifications = FlutterLocalNotificationsPlugin();
  static ApiService? _api;
  static GoRouter? _router;

  static const _channel = AndroidNotificationChannel(
    'ipl_poll_channel',
    'IPL Poll Notifications',
    description: 'Match updates, poll results, and more',
    importance: Importance.high,
  );

  /// Initialize notifications. Call after Firebase.initializeApp().
  static Future<void> init(ApiService api, GoRouter router) async {
    _api = api;
    _router = router;

    // Set up local notifications
    await _setupLocalNotifications();

    // Request permission
    final settings = await _messaging.requestPermission(
      alert: true,
      badge: true,
      sound: true,
    );

    if (settings.authorizationStatus == AuthorizationStatus.denied) return;

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

    // When user taps a notification (app was in background)
    FirebaseMessaging.onMessageOpenedApp.listen(_handleNotificationTap);

    // Check if app was launched from a notification
    final initialMessage = await _messaging.getInitialMessage();
    if (initialMessage != null) {
      _handleNotificationTap(initialMessage);
    }
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
    if (route != null && route.isNotEmpty) {
      _navigateTo(route);
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
    final route = message.data['route'];
    if (route != null && route.toString().isNotEmpty) {
      _navigateTo(route.toString());
    }
  }

  static void _navigateTo(String route) {
    try {
      _router?.push(route);
    } catch (e) {
      debugPrint('Notification navigation failed: $e');
    }
  }
}