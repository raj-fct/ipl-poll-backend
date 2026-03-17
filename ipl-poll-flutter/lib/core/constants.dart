// lib/core/constants.dart

class AppConstants {
  AppConstants._();

  // ── API Base URL ────────────────────────────────────────────
  static const String baseUrl = 'https://ipl.flyingcaps.com/api';
  // static const String baseUrl = 'http://10.0.2.2:8000/api'; // Android emulator
  // static const String baseUrl = 'http://localhost:8000/api'; // iOS simulator

  static const String appName   = 'IPL Poll';
  static const String tokenKey  = 'auth_token';
  static const String userIdKey = 'user_id';

  // IPL team colors
  static const Map<String, int> teamColors = {
    'MI':  0xFF004BA0, // Mumbai Indians - Blue
    'CSK': 0xFFFFCC00, // Chennai Super Kings - Yellow
    'RCB': 0xFFD10000, // Royal Challengers - Red
    'KKR': 0xFF3A225D, // Kolkata Knight Riders - Purple
    'DC':   0xFF00368D, // Delhi Capitals - Blue
    'PBKS': 0xFFED1B24, // Punjab Kings - Red
    'RR':  0xFFE91E8C, // Rajasthan Royals - Pink
    'SRH': 0xFFFF822A, // Sunrisers Hyderabad - Orange
    'GT':  0xFF1C1C6E, // Gujarat Titans - Navy
    'LSG': 0xFF1C4F9F, // Lucknow Super Giants - Blue
  };

  static int teamColor(String shortCode) =>
      teamColors[shortCode.toUpperCase()] ?? 0xFF1565C0;
}
