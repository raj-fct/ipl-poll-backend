// lib/models/user_model.dart

class UserModel {
  final int id;
  final String name;
  final String mobile;
  final int coinBalance;
  final bool isAdmin;
  final bool mustChangePassword;

  const UserModel({
    required this.id,
    required this.name,
    required this.mobile,
    required this.coinBalance,
    required this.isAdmin,
    required this.mustChangePassword,
  });

  factory UserModel.fromJson(Map<String, dynamic> json) => UserModel(
        id:                  json['id'],
        name:                json['name'],
        mobile:              json['mobile'],
        coinBalance:         json['coin_balance'],
        isAdmin:             json['is_admin'] ?? false,
        mustChangePassword:  json['must_change_password'] ?? false,
      );

  UserModel copyWith({int? coinBalance, bool? mustChangePassword}) => UserModel(
        id:                 id,
        name:               name,
        mobile:             mobile,
        coinBalance:        coinBalance ?? this.coinBalance,
        isAdmin:            isAdmin,
        mustChangePassword: mustChangePassword ?? this.mustChangePassword,
      );
}

// ─────────────────────────────────────────────────────────────
// lib/models/match_model.dart

class MatchModel {
  final int id;
  final int matchNumber;
  final String teamA;
  final String teamB;
  final String teamAShort;
  final String teamBShort;
  final String? teamALogo;
  final String? teamBLogo;
  final DateTime matchDate;
  final String? venue;
  final String season;
  final String status;       // upcoming | live | completed | cancelled
  final String? winningTeam;
  final String? scoreA;
  final String? scoreB;
  final String? tossWinner;
  final String? tossDecision;
  final String? notes;
  final double winMultiplier;
  final bool isLocked;
  final UserPollModel? userPoll;

  const MatchModel({
    required this.id,
    required this.matchNumber,
    required this.teamA,
    required this.teamB,
    required this.teamAShort,
    required this.teamBShort,
    this.teamALogo,
    this.teamBLogo,
    required this.matchDate,
    this.venue,
    required this.season,
    required this.status,
    this.winningTeam,
    this.scoreA,
    this.scoreB,
    this.tossWinner,
    this.tossDecision,
    this.notes,
    required this.winMultiplier,
    required this.isLocked,
    this.userPoll,
  });

  factory MatchModel.fromJson(Map<String, dynamic> json) => MatchModel(
        id:            json['id'],
        matchNumber:   json['match_number'],
        teamA:         json['team_a'],
        teamB:         json['team_b'],
        teamAShort:    json['team_a_short'],
        teamBShort:    json['team_b_short'],
        teamALogo:     json['team_a_logo'],
        teamBLogo:     json['team_b_logo'],
        matchDate:     DateTime.parse(json['match_date']),
        venue:         json['venue'],
        season:        json['season'] ?? 'IPL 2026',
        status:        json['status'],
        winningTeam:   json['winning_team'],
        scoreA:        json['score_a'],
        scoreB:        json['score_b'],
        tossWinner:    json['toss_winner'],
        tossDecision:  json['toss_decision'],
        notes:         json['notes'],
        winMultiplier: (json['win_multiplier'] as num).toDouble(),
        isLocked:      json['is_locked'] ?? false,
        userPoll:      json['user_poll'] != null
            ? UserPollModel.fromJson(json['user_poll'])
            : null,
      );

  bool get isUpcoming   => status == 'upcoming';
  bool get isLive       => status == 'live';
  bool get isCompleted  => status == 'completed';
  bool get isCancelled  => status == 'cancelled';
  bool get hasVoted     => userPoll != null;
}

// ─────────────────────────────────────────────────────────────
// lib/models/poll_model.dart

class UserPollModel {
  final int id;
  final String selectedTeam;
  final int bidAmount;
  final String status;     // pending | won | lost | refunded
  final int coinsEarned;

  const UserPollModel({
    required this.id,
    required this.selectedTeam,
    required this.bidAmount,
    required this.status,
    required this.coinsEarned,
  });

  factory UserPollModel.fromJson(Map<String, dynamic> json) => UserPollModel(
        id:           json['id'],
        selectedTeam: json['selected_team'],
        bidAmount:    json['bid_amount'],
        status:       json['status'],
        coinsEarned:  json['coins_earned'] ?? 0,
      );

  bool get isWon      => status == 'won';
  bool get isLost     => status == 'lost';
  bool get isPending  => status == 'pending';
  bool get isRefunded => status == 'refunded';
}

class PollModel {
  final int id;
  final int matchId;
  final String selectedTeam;
  final int bidAmount;
  final String status;
  final int coinsEarned;
  final PollMatchInfo? match;
  final DateTime createdAt;

  const PollModel({
    required this.id,
    required this.matchId,
    required this.selectedTeam,
    required this.bidAmount,
    required this.status,
    required this.coinsEarned,
    this.match,
    required this.createdAt,
  });

  factory PollModel.fromJson(Map<String, dynamic> json) => PollModel(
        id:           json['id'],
        matchId:      json['match_id'],
        selectedTeam: json['selected_team'],
        bidAmount:    json['bid_amount'],
        status:       json['status'],
        coinsEarned:  json['coins_earned'] ?? 0,
        match:        json['match'] != null ? PollMatchInfo.fromJson(json['match']) : null,
        createdAt:    DateTime.parse(json['created_at']),
      );

  bool get isWon      => status == 'won';
  bool get isLost     => status == 'lost';
  bool get isPending  => status == 'pending';
}

class PollMatchInfo {
  final int matchNumber;
  final String teamAShort;
  final String teamBShort;
  final String? teamALogo;
  final String? teamBLogo;
  final DateTime matchDate;
  final String status;
  final String? winningTeam;

  const PollMatchInfo({
    required this.matchNumber,
    required this.teamAShort,
    required this.teamBShort,
    this.teamALogo,
    this.teamBLogo,
    required this.matchDate,
    required this.status,
    this.winningTeam,
  });

  factory PollMatchInfo.fromJson(Map<String, dynamic> json) => PollMatchInfo(
        matchNumber: json['match_number'],
        teamAShort:  json['team_a_short'],
        teamBShort:  json['team_b_short'],
        teamALogo:   json['team_a_logo'],
        teamBLogo:   json['team_b_logo'],
        matchDate:   DateTime.parse(json['match_date']),
        status:      json['status'],
        winningTeam: json['winning_team'],
      );
}

// ─────────────────────────────────────────────────────────────
// lib/models/transaction_model.dart

class TransactionModel {
  final int id;
  final String type;
  final int amount;
  final int balanceAfter;
  final String? description;
  final DateTime createdAt;

  const TransactionModel({
    required this.id,
    required this.type,
    required this.amount,
    required this.balanceAfter,
    this.description,
    required this.createdAt,
  });

  factory TransactionModel.fromJson(Map<String, dynamic> json) => TransactionModel(
        id:           json['id'],
        type:         json['type'],
        amount:       json['amount'],
        balanceAfter: json['balance_after'],
        description:  json['description'],
        createdAt:    DateTime.parse(json['created_at']),
      );

  bool get isCredit => amount > 0;
}

// ─────────────────────────────────────────────────────────────
// lib/models/leaderboard_model.dart

class LeaderboardEntry {
  final int rank;
  final int id;
  final String name;
  final String mobileMasked;
  final int coinBalance;
  final int totalWins;
  final int totalPolls;
  final double winRate;

  const LeaderboardEntry({
    required this.rank,
    required this.id,
    required this.name,
    required this.mobileMasked,
    this.coinBalance = 0,
    this.totalWins = 0,
    this.totalPolls = 0,
    this.winRate = 0,
  });

  factory LeaderboardEntry.fromJson(Map<String, dynamic> json) => LeaderboardEntry(
        rank:         json['rank'],
        id:           json['id'],
        name:         json['name'],
        mobileMasked: json['mobile_masked'],
        coinBalance:  json['coin_balance'] ?? 0,
        totalWins:    json['total_wins'] ?? 0,
        totalPolls:   json['total_polls'] ?? 0,
        winRate:      (json['win_rate'] ?? 0).toDouble(),
      );
}
