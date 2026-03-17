// lib/providers/providers.dart

import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../models/models.dart';
import '../services/api_service.dart';

// ─── Core ────────────────────────────────────────────────────

final apiServiceProvider = Provider<ApiService>((ref) => ApiService());

// ─── Auth ────────────────────────────────────────────────────

class AuthNotifier extends StateNotifier<UserModel?> {
  final ApiService _api;
  AuthNotifier(this._api) : super(null);

  Future<bool> tryRestoreSession() async {
    final hasToken = await _api.hasStoredToken();
    if (!hasToken) return false;
    await _api.restoreToken();
    try {
      final data = await _api.getProfile();
      state = UserModel.fromJson(data['user']);
      return true;
    } catch (_) {
      return false;
    }
  }

  Future<LoginResult> login(String mobile, String password) async {
    final data = await _api.login(mobile, password);
    state = UserModel.fromJson(data['user']);
    return LoginResult(
      user: state!,
      mustChangePassword: data['must_change_password'] as bool,
    );
  }

  Future<void> changePassword(String oldPassword, String newPassword) async {
    await _api.changePassword(oldPassword, newPassword);
    if (state != null) {
      state = state!.copyWith(mustChangePassword: false);
    }
  }

  Future<void> logout() async {
    await _api.logout();
    state = null;
  }

  void updateBalance(int newBalance) {
    if (state != null) state = state!.copyWith(coinBalance: newBalance);
  }
}

final authProvider = StateNotifierProvider<AuthNotifier, UserModel?>(
    (ref) => AuthNotifier(ref.read(apiServiceProvider)));

class LoginResult {
  final UserModel user;
  final bool mustChangePassword;
  const LoginResult({required this.user, required this.mustChangePassword});
}

// ─── Matches ─────────────────────────────────────────────────

final matchesProvider = FutureProvider<List<MatchModel>>((ref) async {
  final api = ref.read(apiServiceProvider);
  final data = await api.getMatches();
  return data.map((j) => MatchModel.fromJson(j)).toList();
});

final matchDetailProvider =
    FutureProvider.family<Map<String, dynamic>, int>((ref, id) async {
  final api = ref.read(apiServiceProvider);
  return api.getMatch(id);
});

// ─── Polls ───────────────────────────────────────────────────

final myPollsProvider = FutureProvider<List<PollModel>>((ref) async {
  final api = ref.read(apiServiceProvider);
  final data = await api.getMyPolls();
  return (data['polls'] as List).map((j) => PollModel.fromJson(j)).toList();
});

// ─── Wallet ──────────────────────────────────────────────────

final walletBalanceProvider = FutureProvider<Map<String, dynamic>>((ref) async {
  final data = await ref.read(apiServiceProvider).getWalletBalance();
  // Keep auth state's coin balance in sync
  final balance = data['balance'] as int;
  ref.read(authProvider.notifier).updateBalance(balance);
  return data;
});

final transactionsProvider = FutureProvider<List<TransactionModel>>((ref) async {
  final api = ref.read(apiServiceProvider);
  final data = await api.getTransactions();
  return (data['transactions'] as List)
      .map((j) => TransactionModel.fromJson(j))
      .toList();
});

// ─── Profile ────────────────────────────────────────────────

final profileProvider = FutureProvider<Map<String, dynamic>>((ref) async {
  return ref.read(apiServiceProvider).getProfile();
});

// ─── Leaderboard ─────────────────────────────────────────────

final leaderboardProvider = FutureProvider<List<LeaderboardEntry>>((ref) async {
  final api = ref.read(apiServiceProvider);
  final data = await api.getLeaderboard();
  return data.map((j) => LeaderboardEntry.fromJson(j)).toList();
});

final myRankProvider = FutureProvider<Map<String, dynamic>>((ref) async {
  return ref.read(apiServiceProvider).getMyRank();
});
