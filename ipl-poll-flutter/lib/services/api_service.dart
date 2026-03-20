// lib/services/api_service.dart

import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import '../core/constants.dart';

class ApiService {
  late final Dio _dio;
  final FlutterSecureStorage _storage = const FlutterSecureStorage();

  ApiService() {
    _dio = Dio(BaseOptions(
      baseUrl: AppConstants.baseUrl,
      connectTimeout: const Duration(seconds: 15),
      receiveTimeout: const Duration(seconds: 15),
      headers: {'Accept': 'application/json', 'Content-Type': 'application/json'},
    ));

    _dio.interceptors.add(InterceptorsWrapper(
      onError: (DioException e, handler) {
        final msg = _extractError(e);
        return handler.reject(DioException(
          requestOptions: e.requestOptions,
          error: msg,
          message: msg,
          type: e.type,
          response: e.response,
        ));
      },
    ));
  }

  Future<void> restoreToken() async {
    final token = await _storage.read(key: AppConstants.tokenKey);
    if (token != null) {
      _dio.options.headers['Authorization'] = 'Bearer $token';
    }
  }

  void setToken(String token) {
    _dio.options.headers['Authorization'] = 'Bearer $token';
  }

  void clearToken() {
    _dio.options.headers.remove('Authorization');
  }

  // ─── Auth ────────────────────────────────────────────────────

  Future<Map<String, dynamic>> login(String mobile, String password) async {
    final res = await _dio.post('/auth/login', data: {
      'mobile': mobile,
      'password': password,
    });
    final token = res.data['token'] as String;
    await _storage.write(key: AppConstants.tokenKey, value: token);
    setToken(token);
    return res.data;
  }

  Future<Map<String, dynamic>> changePassword(
      String oldPassword, String newPassword) async {
    final res = await _dio.post('/auth/change-password', data: {
      'old_password': oldPassword,
      'new_password': newPassword,
      'new_password_confirmation': newPassword,
    });
    // Update stored token (new one issued after password change)
    final token = res.data['token'] as String;
    await _storage.write(key: AppConstants.tokenKey, value: token);
    setToken(token);
    return res.data;
  }

  Future<Map<String, dynamic>> getProfile() async {
    final res = await _dio.get('/auth/profile');
    return res.data;
  }

  Future<void> logout() async {
    try { await _dio.post('/auth/logout'); } catch (_) {}
    await _storage.deleteAll();
    clearToken();
  }

  Future<bool> hasStoredToken() async {
    final token = await _storage.read(key: AppConstants.tokenKey);
    return token != null;
  }

  // ─── Matches ─────────────────────────────────────────────────

  Future<List<dynamic>> getMatches() async {
    final res = await _dio.get('/matches');
    return res.data['matches'];
  }

  Future<Map<String, dynamic>> getMatch(int id) async {
    final res = await _dio.get('/matches/$id');
    return res.data;
  }

  // ─── Polls ───────────────────────────────────────────────────

  Future<Map<String, dynamic>> placePoll({
    required int matchId,
    required String selectedTeam,
    required int bidAmount,
  }) async {
    final res = await _dio.post('/polls', data: {
      'match_id':      matchId,
      'selected_team': selectedTeam,
      'bid_amount':    bidAmount,
    });
    return res.data;
  }

  Future<Map<String, dynamic>> updatePoll({
    required int pollId,
    required String selectedTeam,
    int? bidAmount,
  }) async {
    final res = await _dio.put('/polls/$pollId', data: {
      'selected_team': selectedTeam,
      if (bidAmount != null) 'bid_amount': bidAmount,
    });
    return res.data;
  }

  Future<Map<String, dynamic>> cancelPoll(int pollId) async {
    final res = await _dio.delete('/polls/$pollId');
    return res.data;
  }

  Future<Map<String, dynamic>> getMyPolls({int page = 1}) async {
    final res = await _dio.get('/polls/my', queryParameters: {'page': page});
    return res.data;
  }

  Future<Map<String, dynamic>> getMatchPolls(int matchId) async {
    final res = await _dio.get('/matches/$matchId/polls');
    return res.data;
  }

  // ─── Wallet ──────────────────────────────────────────────────

  Future<Map<String, dynamic>> getWalletBalance() async {
    final res = await _dio.get('/wallet/balance');
    return res.data;
  }

  Future<Map<String, dynamic>> getTransactions({int page = 1}) async {
    final res = await _dio.get('/wallet/transactions',
        queryParameters: {'page': page});
    return res.data;
  }

  // ─── Leaderboard ─────────────────────────────────────────────

  Future<List<dynamic>> getLeaderboard() async {
    final res = await _dio.get('/leaderboard');
    return res.data['leaderboard'];
  }

  Future<List<dynamic>> getWinsLeaderboard() async {
    final res = await _dio.get('/leaderboard/wins');
    return res.data['leaderboard'];
  }

  Future<Map<String, dynamic>> getMyRank() async {
    final res = await _dio.get('/leaderboard/my-rank');
    return res.data;
  }

  // ─── Error helper ────────────────────────────────────────────

  static String humanError(Object e) {
    if (e is DioException) {
      return e.message ?? 'Something went wrong. Please try again.';
    }
    final s = e.toString();
    if (s.startsWith('Exception: ')) return s.substring(11);
    return s;
  }

  String _extractError(DioException e) {
    if (e.response != null) {
      final data = e.response!.data;
      if (data is Map) {
        if (data.containsKey('errors')) {
          final errors = data['errors'] as Map;
          return errors.values.first.first.toString();
        }
        if (data.containsKey('message')) {
          return data['message'].toString();
        }
      }
    }
    switch (e.type) {
      case DioExceptionType.connectionTimeout:
      case DioExceptionType.receiveTimeout:
        return 'Connection timed out. Check your internet.';
      case DioExceptionType.connectionError:
        return 'Cannot connect to server. Check the API URL.';
      default:
        return 'Something went wrong. Please try again.';
    }
  }
}
