// lib/screens/home_screen.dart

import 'dart:async';
import 'dart:ui';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../core/constants.dart';
import '../models/models.dart';
import '../providers/providers.dart';
import '../services/api_service.dart';
import '../main.dart';

class HomeScreen extends ConsumerStatefulWidget {
  const HomeScreen({super.key});

  @override
  ConsumerState<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends ConsumerState<HomeScreen>
    with SingleTickerProviderStateMixin {
  late final TabController _tabController;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 3, vsync: this);
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final matchesAsync = ref.watch(matchesProvider);
    final user = ref.watch(authProvider);

    return Scaffold(
      appBar: AppBar(
        title: Row(children: [
          ClipRRect(
            borderRadius: BorderRadius.circular(6),
            child: Image.asset('assets/icons/app_icon.png', width: 28, height: 28),
          ),
          const SizedBox(width: 10),
          const Text('IPL POLL 2026'),
        ]),
        actions: [
          GestureDetector(
            onTap: () => context.go('/wallet'),
            child: Container(
              margin: const EdgeInsets.only(right: 14),
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
              decoration: BoxDecoration(
                color: IPLColors.cardDark,
                borderRadius: BorderRadius.circular(20),
                border: Border.all(color: IPLColors.border),
              ),
              child: Row(children: [
                const CoinIcon(size: 16),
                const SizedBox(width: 4),
                Text('${user?.coinBalance ?? 0}',
                    style: const TextStyle(
                        color: Colors.white, fontWeight: FontWeight.w700, fontSize: 13)),
              ]),
            ),
          ),
        ],
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(48),
          child: Padding(
            padding: const EdgeInsets.fromLTRB(14, 0, 14, 8),
            child: _PillTabBar(
              controller: _tabController,
              labels: const ['FIXTURES', 'RESULTS', 'LEADERBOARD'],
            ),
          ),
        ),
      ),
      body: IPLBackground(
        showBottomSpiral: false,
        child: TabBarView(
          controller: _tabController,
          children: [
            _FixturesTab(matchesAsync: matchesAsync, ref: ref),
            _ResultsTab(matchesAsync: matchesAsync, ref: ref),
            const _LeaderboardTab(),
          ],
        ),
      ),
    );
  }
}

class _PillTabBar extends StatelessWidget {
  final TabController controller;
  final List<String> labels;
  const _PillTabBar({required this.controller, required this.labels});

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: controller,
      builder: (context, _) {
        return Container(
          height: 36,
          decoration: BoxDecoration(
            color: Colors.transparent,
            borderRadius: BorderRadius.circular(10),
          ),
          child: Row(
            children: List.generate(labels.length, (i) {
              final selected = controller.index == i;
              return Expanded(
                child: GestureDetector(
                  onTap: () => controller.animateTo(i),
                  child: AnimatedContainer(
                    duration: const Duration(milliseconds: 200),
                    alignment: Alignment.center,
                    decoration: BoxDecoration(
                      color: selected ? IPLColors.accent : Colors.transparent,
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: Text(labels[i],
                        style: TextStyle(
                          fontSize: 11,
                          fontWeight: FontWeight.w700,
                          letterSpacing: 0.5,
                          color: selected ? Colors.white : IPLColors.textMuted,
                        )),
                  ),
                ),
              );
            }),
          ),
        );
      },
    );
  }
}

class _FixturesTab extends StatelessWidget {
  final AsyncValue<List<MatchModel>> matchesAsync;
  final WidgetRef ref;
  const _FixturesTab({required this.matchesAsync, required this.ref});

  @override
  Widget build(BuildContext context) {
    return RefreshIndicator(
      color: IPLColors.accent,
      onRefresh: () => ref.refresh(matchesProvider.future),
      child: matchesAsync.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => Center(
          child: Column(mainAxisAlignment: MainAxisAlignment.center, children: [
            const Icon(Icons.error_outline, color: IPLColors.red, size: 48),
            const SizedBox(height: 12),
            Text(ApiService.humanError(e), textAlign: TextAlign.center,
                style: const TextStyle(color: IPLColors.textMuted)),
            const SizedBox(height: 16),
            ElevatedButton(
                onPressed: () => ref.refresh(matchesProvider),
                child: const Text('Retry')),
          ]),
        ),
        data: (matches) {
          final fixtures = matches
              .where((m) => (m.isUpcoming || m.isLive) && m.season == 'IPL 2026')
              .toList();
          if (fixtures.isEmpty) {
            return ListView(children: const [
              SizedBox(height: 200),
              Center(child: Text('No upcoming fixtures.',
                  style: TextStyle(color: IPLColors.textMuted))),
            ]);
          }
          return ListView.builder(
            padding: const EdgeInsets.fromLTRB(14, 14, 14, 14),
            itemCount: fixtures.length,
            itemBuilder: (ctx, i) => MatchCard(match: fixtures[i]),
          );
        },
      ),
    );
  }
}

class _ResultsTab extends StatelessWidget {
  final AsyncValue<List<MatchModel>> matchesAsync;
  final WidgetRef ref;
  const _ResultsTab({required this.matchesAsync, required this.ref});

  @override
  Widget build(BuildContext context) {
    return RefreshIndicator(
      color: IPLColors.accent,
      onRefresh: () => ref.refresh(matchesProvider.future),
      child: matchesAsync.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => Center(
          child: Column(mainAxisAlignment: MainAxisAlignment.center, children: [
            const Icon(Icons.error_outline, color: IPLColors.red, size: 48),
            const SizedBox(height: 12),
            Text(ApiService.humanError(e), textAlign: TextAlign.center,
                style: const TextStyle(color: IPLColors.textMuted)),
            const SizedBox(height: 16),
            ElevatedButton(
                onPressed: () => ref.refresh(matchesProvider),
                child: const Text('Retry')),
          ]),
        ),
        data: (matches) {
          final results = matches
              .where((m) => (m.isCompleted || m.isCancelled) && m.season == 'IPL 2026')
              .toList()
            ..sort((a, b) => b.matchDate.compareTo(a.matchDate));
          if (results.isEmpty) {
            return ListView(children: const [
              SizedBox(height: 200),
              Center(child: Text('No results yet.',
                  style: TextStyle(color: IPLColors.textMuted))),
            ]);
          }
          return ListView.builder(
            padding: const EdgeInsets.fromLTRB(14, 14, 14, 14),
            itemCount: results.length,
            itemBuilder: (ctx, i) => MatchCard(match: results[i]),
          );
        },
      ),
    );
  }
}

class _LeaderboardTab extends ConsumerWidget {
  const _LeaderboardTab();

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final rankAsync = ref.watch(myRankProvider);
    final boardAsync = ref.watch(leaderboardProvider);
    final currentUser = ref.watch(authProvider);

    return RefreshIndicator(
      color: IPLColors.accent,
      onRefresh: () async {
        ref.refresh(leaderboardProvider);
        ref.refresh(myRankProvider);
      },
      child: ListView(
        padding: const EdgeInsets.all(14),
        children: [
          rankAsync.when(
            loading: () => const SizedBox(),
            error: (_, __) => const SizedBox(),
            data: (data) => Container(
              margin: const EdgeInsets.only(bottom: 16),
              padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
              decoration: BoxDecoration(
                gradient: const LinearGradient(
                  colors: [IPLColors.navy, IPLColors.cardDark]),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: IPLColors.accent.withOpacity(0.3)),
              ),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                    const Text('YOUR RANK', style: TextStyle(
                        color: IPLColors.textMuted, fontSize: 10,
                        fontWeight: FontWeight.w600, letterSpacing: 1)),
                    const SizedBox(height: 4),
                    Text('#${data['rank']}',
                        style: const TextStyle(color: Colors.white,
                            fontSize: 28, fontWeight: FontWeight.w800)),
                  ]),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                    decoration: BoxDecoration(
                      color: IPLColors.accent.withOpacity(0.15),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Row(children: [
                      const CoinIcon(size: 18),
                      const SizedBox(width: 6),
                      Text('${data['coin_balance']}',
                          style: const TextStyle(color: Colors.white,
                              fontWeight: FontWeight.w700, fontSize: 16)),
                    ]),
                  ),
                ],
              ),
            ),
          ),
          boardAsync.when(
            loading: () => const Center(child: CircularProgressIndicator()),
            error: (e, _) => Text(ApiService.humanError(e)),
            data: (entries) => Column(
              children: entries.map((e) => _LeaderRowInline(
                entry: e,
                isMe: e.id == currentUser?.id,
              )).toList(),
            ),
          ),
        ],
      ),
    );
  }
}

class _LeaderRowInline extends StatelessWidget {
  final LeaderboardEntry entry;
  final bool isMe;
  const _LeaderRowInline({required this.entry, required this.isMe});

  @override
  Widget build(BuildContext context) {
    final rankColor = entry.rank == 1 ? const Color(0xFFFFD700)
        : entry.rank == 2 ? const Color(0xFFC0C0C0)
        : entry.rank == 3 ? const Color(0xFFCD7F32)
        : IPLColors.textMuted;

    return Container(
      margin: const EdgeInsets.only(bottom: 6),
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(
        color: isMe ? IPLColors.accent.withOpacity(0.08) : IPLColors.cardDark,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(
            color: isMe ? IPLColors.accent.withOpacity(0.3) : IPLColors.border.withOpacity(0.3)),
      ),
      child: Row(children: [
        SizedBox(width: 32,
            child: Text('#${entry.rank}',
                style: TextStyle(color: rankColor,
                    fontWeight: FontWeight.w800, fontSize: 14))),
        const SizedBox(width: 8),
        CircleAvatar(radius: 16, backgroundColor: IPLColors.navy,
            child: Text(entry.name[0].toUpperCase(),
                style: const TextStyle(color: Colors.white, fontSize: 12,
                    fontWeight: FontWeight.w600))),
        const SizedBox(width: 10),
        Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text(entry.name + (isMe ? ' (You)' : ''),
              style: TextStyle(
                  color: isMe ? IPLColors.accent : Colors.white,
                  fontWeight: FontWeight.w600, fontSize: 13)),
          Text(entry.mobileMasked,
              style: const TextStyle(color: IPLColors.textMuted, fontSize: 11)),
        ])),
        Row(children: [
          const CoinIcon(size: 14),
          const SizedBox(width: 4),
          Text('${entry.coinBalance}',
              style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w700)),
        ]),
      ]),
    );
  }
}

// ─── Match Card ──────────────────────────────────────────────

class MatchCard extends StatelessWidget {
  final MatchModel match;
  const MatchCard({super.key, required this.match});

  @override
  Widget build(BuildContext context) {
    final isLive = match.isLive;
    final dateStr = DateFormat('EEE, d MMM · h:mm a').format(match.matchDate.toLocal());
    final teamAColor = Color(AppConstants.teamColor(match.teamAShort));
    final teamBColor = Color(AppConstants.teamColor(match.teamBShort));

    return GestureDetector(
      onTap: () => context.push('/match/${match.id}'),
      child: Container(
        margin: const EdgeInsets.only(bottom: 12),
        decoration: BoxDecoration(
          color: IPLColors.cardDark,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(
            color: isLive
                ? Colors.greenAccent.withOpacity(0.4)
                : IPLColors.border.withOpacity(0.4),
          ),
        ),
        child: Column(
          children: [
            // Header: gradient strip with team colors
            Container(
              height: 3,
              decoration: BoxDecoration(
                borderRadius: const BorderRadius.vertical(top: Radius.circular(12)),
                gradient: LinearGradient(colors: [
                  teamAColor, IPLColors.accent, teamBColor,
                ]),
              ),
            ),

            // Match info row
            Padding(
              padding: const EdgeInsets.fromLTRB(14, 10, 14, 0),
              child: Row(
                children: [
                  Text('Match ${match.matchNumber}',
                      style: const TextStyle(
                          color: IPLColors.textMuted,
                          fontSize: 11,
                          fontWeight: FontWeight.w500)),
                  if (match.venue != null) ...[
                    const Padding(
                      padding: EdgeInsets.symmetric(horizontal: 6),
                      child: Text('·', style: TextStyle(color: IPLColors.textMuted)),
                    ),
                    Expanded(child: Text(match.venue!,
                        maxLines: 1, overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                            color: IPLColors.textMuted, fontSize: 11))),
                  ] else
                    const Spacer(),
                  const SizedBox(width: 8),
                  _StatusBadge(
                    label: _statusLabel(match.status),
                    color: _statusColor(match.status),
                    isLive: isLive,
                  ),
                ],
              ),
            ),

            // Teams section
            Padding(
              padding: const EdgeInsets.fromLTRB(8, 14, 8, 14),
              child: Row(
                children: [
                  // Team A
                  Expanded(
                    child: Row(children: [
                      const SizedBox(width: 6),
                      TeamLogo(
                        shortCode: match.teamAShort,
                        logoUrl: match.teamALogo,
                        size: 44,
                      ),
                      const SizedBox(width: 10),
                      Expanded(child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(match.teamAShort,
                              style: const TextStyle(
                                  fontSize: 16,
                                  fontWeight: FontWeight.w800,
                                  color: Colors.white)),
                        ],
                      )),
                    ]),
                  ),

                  // Score / VS
                  SizedBox(
                    width: 80,
                    child: Column(children: [
                      if (match.scoreA != null || match.scoreB != null) ...[
                        if (match.scoreA != null)
                          Text(match.scoreA!,
                              textAlign: TextAlign.center,
                              style: const TextStyle(fontSize: 11, color: Colors.white,
                                  fontWeight: FontWeight.w600)),
                        const Text('vs',
                            style: TextStyle(fontSize: 10, color: IPLColors.textMuted)),
                        if (match.scoreB != null)
                          Text(match.scoreB!,
                              textAlign: TextAlign.center,
                              style: const TextStyle(fontSize: 11, color: Colors.white,
                                  fontWeight: FontWeight.w600)),
                      ] else
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
                          decoration: BoxDecoration(
                            border: Border.all(color: IPLColors.border),
                            borderRadius: BorderRadius.circular(4),
                          ),
                          child: const Text('VS',
                              style: TextStyle(
                                  fontSize: 12, fontWeight: FontWeight.w800,
                                  color: IPLColors.textMuted, letterSpacing: 2)),
                        ),
                    ]),
                  ),

                  // Team B
                  Expanded(
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.end,
                      children: [
                        Expanded(child: Column(
                          crossAxisAlignment: CrossAxisAlignment.end,
                          children: [
                            Text(match.teamBShort,
                                style: const TextStyle(
                                    fontSize: 16,
                                    fontWeight: FontWeight.w800,
                                    color: Colors.white)),
                          ],
                        )),
                        const SizedBox(width: 10),
                        TeamLogo(
                          shortCode: match.teamBShort,
                          logoUrl: match.teamBLogo,
                          size: 44,
                        ),
                        const SizedBox(width: 6),
                      ],
                    ),
                  ),
                ],
              ),
            ),

            // Date row
            Container(
              width: double.infinity,
              padding: const EdgeInsets.symmetric(vertical: 8, horizontal: 14),
              decoration: const BoxDecoration(
                border: Border(top: BorderSide(color: IPLColors.border, width: 0.5)),
              ),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Row(children: [
                    const Icon(Icons.schedule, size: 12, color: IPLColors.textMuted),
                    const SizedBox(width: 4),
                    Text(dateStr,
                        style: const TextStyle(
                            fontSize: 11, color: IPLColors.textMuted)),
                  ]),

                  // Poll status or CTA
                  if (match.userPoll != null)
                    _PollChip(
                      status: match.userPoll!.status,
                      selectedTeam: match.userPoll!.selectedTeam,
                      isWon: match.userPoll!.isWon,
                      coinsEarned: match.userPoll!.coinsEarned,
                    )
                  else if (match.isUpcoming)
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                      decoration: BoxDecoration(
                        color: IPLColors.accent.withOpacity(0.12),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: const Text('PREDICT',
                          style: TextStyle(fontSize: 10, fontWeight: FontWeight.w700,
                              color: IPLColors.accent, letterSpacing: 0.5)),
                    ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Color _statusColor(String status) => switch (status) {
        'live'      => Colors.greenAccent,
        'completed' => IPLColors.textMuted,
        'cancelled' => IPLColors.red,
        _           => IPLColors.accent,
      };

  String _statusLabel(String status) => switch (status) {
        'live'      => 'LIVE',
        'completed' => 'COMPLETED',
        'cancelled' => 'CANCELLED',
        _           => 'UPCOMING',
      };
}

class _StatusBadge extends StatelessWidget {
  final String label;
  final Color color;
  final bool isLive;
  const _StatusBadge({required this.label, required this.color, this.isLive = false});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: color.withOpacity(0.12),
        borderRadius: BorderRadius.circular(4),
      ),
      child: Row(mainAxisSize: MainAxisSize.min, children: [
        if (isLive) ...[
          Container(
            width: 5, height: 5,
            decoration: BoxDecoration(color: color, shape: BoxShape.circle),
          ),
          const SizedBox(width: 4),
        ],
        Text(label,
            style: TextStyle(
                color: color, fontSize: 9, fontWeight: FontWeight.w700,
                letterSpacing: 0.5)),
      ]),
    );
  }
}

class _PollChip extends StatelessWidget {
  final String status;
  final String selectedTeam;
  final bool isWon;
  final int coinsEarned;
  const _PollChip({
    required this.status, required this.selectedTeam,
    required this.isWon, required this.coinsEarned,
  });

  @override
  Widget build(BuildContext context) {
    final color = switch (status) {
      'won'      => Colors.greenAccent,
      'lost'     => IPLColors.red,
      'pending'  => IPLColors.accent,
      _          => IPLColors.textMuted,
    };
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: color.withOpacity(0.1),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Row(mainAxisSize: MainAxisSize.min, children: [
        Text(selectedTeam, style: TextStyle(color: color, fontSize: 10,
            fontWeight: FontWeight.w700)),
        if (isWon) ...[
          const SizedBox(width: 3),
          const CoinIcon(size: 10),
          const SizedBox(width: 1),
          Text('+$coinsEarned', style: const TextStyle(color: Colors.greenAccent,
              fontSize: 10, fontWeight: FontWeight.w700)),
        ],
      ]),
    );
  }
}

// ─────────────────────────────────────────────────────────────
// lib/screens/match_detail_screen.dart

class MatchDetailScreen extends ConsumerStatefulWidget {
  final int matchId;
  const MatchDetailScreen({super.key, required this.matchId});

  @override
  ConsumerState<MatchDetailScreen> createState() => _MatchDetailScreenState();
}

class _MatchDetailScreenState extends ConsumerState<MatchDetailScreen> {
  String? _selectedTeam;
  double _bidSlider = 100;
  bool _loading = false;
  bool _cancelLoading = false;
  bool _initialized = false;
  late final Timer _countdownTimer;
  Duration _remaining = Duration.zero;
  DateTime? _cutoffTime;

  @override
  void initState() {
    super.initState();
    _countdownTimer = Timer.periodic(const Duration(seconds: 1), (_) {
      if (_cutoffTime == null) return;
      final now = DateTime.now().toUtc();
      final diff = _cutoffTime!.difference(now);
      if (diff != _remaining) {
        setState(() => _remaining = diff.isNegative ? Duration.zero : diff);
      }
    });
  }

  @override
  void dispose() {
    _countdownTimer.cancel();
    super.dispose();
  }

  bool get _isPredictionLocked => _remaining <= Duration.zero;

  @override
  Widget build(BuildContext context) {
    final detailAsync = ref.watch(matchDetailProvider(widget.matchId));
    final user = ref.watch(authProvider);

    return Scaffold(
      body: IPLBackground(child: detailAsync.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => Center(child: Text(ApiService.humanError(e))),
        data: (data) {
          final match = MatchModel.fromJson(data['match']);
          final stats = data['stats'] as Map<String, dynamic>;
          // Treat refunded poll as no active poll
          final activePoll = (match.userPoll != null && !match.userPoll!.isRefunded)
              ? match.userPoll : null;
          // Add back current bid so user sees their full available balance
          final currentBid = activePoll?.bidAmount ?? 0;
          final maxBid = ((user?.coinBalance ?? 100) + currentBid).toDouble().clamp(10.0, 99999.0);
          if (!_initialized) {
            _initialized = true;
            _selectedTeam = activePoll?.selectedTeam;
            if (activePoll != null) {
              _bidSlider = activePoll.bidAmount.toDouble();
            }
          }
          if (_bidSlider > maxBid) _bidSlider = maxBid;

          // Set cutoff to 30 min before match
          _cutoffTime = match.matchDate.toUtc().subtract(const Duration(minutes: 30));
          final now = DateTime.now().toUtc();
          final diff = _cutoffTime!.difference(now);
          _remaining = diff.isNegative ? Duration.zero : diff;

          final dateStr = _formatMatchDate(match.matchDate);
          final canPredict = match.isUpcoming && !_isPredictionLocked;

          return Column(children: [
              // ── Header ──
              Container(
                width: double.infinity,
                padding: EdgeInsets.only(
                  top: MediaQuery.of(context).padding.top + 8,
                  bottom: 14, left: 16, right: 16,
                ),
                decoration: const BoxDecoration(
                  gradient: LinearGradient(
                    begin: Alignment.topCenter,
                    end: Alignment.bottomCenter,
                    colors: [Color(0xFF003366), IPLColors.darkNavy],
                  ),
                  borderRadius: BorderRadius.only(
                    bottomLeft: Radius.circular(24),
                    bottomRight: Radius.circular(24),
                  ),
                ),
                child: Column(children: [
                  // Top bar: back, match#, countdown/status
                  Row(children: [
                    GestureDetector(
                      onTap: () => Navigator.of(context).pop(),
                      child: Container(
                        padding: const EdgeInsets.all(6),
                        decoration: BoxDecoration(
                          color: Colors.white.withOpacity(0.08),
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: const Icon(Icons.arrow_back, color: Colors.white, size: 18),
                      ),
                    ),
                    Expanded(
                      child: Text('Match ${match.matchNumber} : ${match.teamAShort} vs ${match.teamBShort}',
                          textAlign: TextAlign.center,
                          style: const TextStyle(color: Colors.white,
                              fontSize: 15, fontWeight: FontWeight.w700, letterSpacing: 0.5)),
                    ),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                      decoration: BoxDecoration(
                        color: match.isLive ? Colors.red
                            : match.isCompleted ? Colors.green.withOpacity(0.8)
                            : _isPredictionLocked ? IPLColors.red.withOpacity(0.8)
                            : IPLColors.accent.withOpacity(0.8),
                        borderRadius: BorderRadius.circular(6),
                      ),
                      child: Text(
                        match.isUpcoming && _isPredictionLocked
                            ? 'LOCKED' : match.status.toUpperCase(),
                        style: const TextStyle(color: Colors.white,
                            fontSize: 10, fontWeight: FontWeight.w700, letterSpacing: 0.5)),
                      ),
                  ]),
                  const SizedBox(height: 14),
                  // Teams row
                  Row(children: [
                    Expanded(
                      child: Column(children: [
                        TeamLogo(shortCode: match.teamAShort, logoUrl: match.teamALogo, size: 48),
                        const SizedBox(height: 6),
                        Text(match.teamAShort, style: const TextStyle(
                            color: Colors.white, fontSize: 16, fontWeight: FontWeight.w800)),
                        if (match.scoreA != null)
                          Text(match.scoreA!, style: const TextStyle(
                              color: Colors.white, fontSize: 14, fontWeight: FontWeight.w700)),
                      ]),
                    ),
                    Column(children: [
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                        decoration: BoxDecoration(
                          color: Colors.white.withOpacity(0.06),
                          borderRadius: BorderRadius.circular(6),
                        ),
                        child: const Text('VS', style: TextStyle(fontSize: 12,
                            fontWeight: FontWeight.w800, color: IPLColors.textMuted,
                            letterSpacing: 2)),
                      ),
                      const SizedBox(height: 4),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                        decoration: BoxDecoration(
                          color: IPLColors.accent.withOpacity(0.15),
                          borderRadius: BorderRadius.circular(4),
                        ),
                        child: Text('${match.winMultiplier}x',
                            style: const TextStyle(color: IPLColors.accent,
                                fontSize: 10, fontWeight: FontWeight.w700)),
                      ),
                    ]),
                    Expanded(
                      child: Column(children: [
                        TeamLogo(shortCode: match.teamBShort, logoUrl: match.teamBLogo, size: 48),
                        const SizedBox(height: 6),
                        Text(match.teamBShort, style: const TextStyle(
                            color: Colors.white, fontSize: 16, fontWeight: FontWeight.w800)),
                        if (match.scoreB != null)
                          Text(match.scoreB!, style: const TextStyle(
                              color: Colors.white, fontSize: 14, fontWeight: FontWeight.w700)),
                      ]),
                    ),
                  ]),
                  const SizedBox(height: 10),
                  // Date & venue in one line
                  Text(dateStr, style: const TextStyle(
                      color: IPLColors.textSecondary, fontSize: 11, fontWeight: FontWeight.w500)),
                  if (match.venue != null)
                    Text(match.venue!, textAlign: TextAlign.center,
                        maxLines: 1, overflow: TextOverflow.ellipsis,
                        style: const TextStyle(color: IPLColors.textMuted, fontSize: 10)),
                  // Toss info
                  if (match.tossWinner != null)
                    Padding(
                      padding: const EdgeInsets.only(top: 4),
                      child: Text('Toss: ${match.tossWinner} elected to ${match.tossDecision}',
                          style: const TextStyle(color: IPLColors.textMuted, fontSize: 10)),
                    ),
                  if (match.notes != null)
                    Padding(
                      padding: const EdgeInsets.only(top: 3),
                      child: Text(match.notes!, textAlign: TextAlign.center,
                          style: const TextStyle(color: IPLColors.accent, fontSize: 11,
                              fontWeight: FontWeight.w600)),
                    ),
                  // Countdown blocks
                  if (match.isUpcoming && _remaining > Duration.zero) ...[
                    const SizedBox(height: 10),
                    const Text('POLL ENDS IN', style: TextStyle(
                        color: IPLColors.textMuted, fontSize: 9,
                        fontWeight: FontWeight.w600, letterSpacing: 1.5)),
                    const SizedBox(height: 6),
                    _CountdownBlocks(remaining: _remaining),
                  ],
                ]),
              ),

              // ── Body ──
              Expanded(
                child: SingleChildScrollView(
                  physics: const BouncingScrollPhysics(),
                  padding: const EdgeInsets.fromLTRB(16, 12, 16, 8),
                  child: Column(children: [
                    // Community prediction (compact)
                    _VoteSplitBar(
                        teamA: match.teamAShort,
                        teamB: match.teamBShort,
                        teamALogo: match.teamALogo,
                        teamBLogo: match.teamBLogo,
                        teamAPercent: (stats['team_a_percentage'] as num).toInt(),
                        total: (stats['total_polls'] as num).toInt()),

                    const SizedBox(height: 8),

                    // View all predictions link (only after polls close)
                    if (!canPredict)
                      Align(
                        alignment: Alignment.centerRight,
                        child: GestureDetector(
                          onTap: () => context.push('/match/${match.id}/biddings'),
                          child: Padding(
                            padding: const EdgeInsets.symmetric(vertical: 4),
                            child: Row(
                              mainAxisSize: MainAxisSize.min,
                              children: const [
                                Text('View All Predictions',
                                    style: TextStyle(color: IPLColors.accent,
                                        fontSize: 12, fontWeight: FontWeight.w600)),
                                SizedBox(width: 4),
                                Icon(Icons.arrow_forward_ios,
                                    color: IPLColors.accent, size: 12),
                              ],
                            ),
                          ),
                        ),
                      ),

                    const SizedBox(height: 8),

                    // Prediction / Result / Locked / Cancelled
                    if (canPredict)
                      _PredictionPanel(
                        match: match,
                        selectedTeam: _selectedTeam,
                        bidSlider: _bidSlider,
                        maxBid: maxBid,
                        loading: _loading,
                        cancelLoading: _cancelLoading,
                        onSelectTeam: (t) => setState(() => _selectedTeam = t),
                        onBidChanged: (v) => setState(() => _bidSlider = v),
                        onSubmit: () => _submitPoll(match),
                        onCancel: activePoll != null ? () => _cancelPoll(match) : null,
                      )
                    else if (match.isUpcoming && _isPredictionLocked)
                      Container(
                        width: double.infinity,
                        padding: const EdgeInsets.all(20),
                        decoration: BoxDecoration(
                          color: IPLColors.red.withOpacity(0.08),
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(color: IPLColors.red.withOpacity(0.2)),
                        ),
                        child: Column(mainAxisSize: MainAxisSize.min, children: [
                          const Icon(Icons.lock_clock, color: IPLColors.red, size: 28),
                          const SizedBox(height: 6),
                          const Text('Predictions Closed',
                              style: TextStyle(color: IPLColors.red, fontSize: 15,
                                  fontWeight: FontWeight.w700)),
                          const SizedBox(height: 4),
                          const Text('Window closed 30 min before match.',
                              style: TextStyle(color: IPLColors.textMuted, fontSize: 11)),
                          if (activePoll != null) ...[
                            const SizedBox(height: 8),
                            Text('Your pick: ${activePoll.selectedTeam}  ·  ${activePoll.bidAmount} coins',
                                style: const TextStyle(color: IPLColors.textSecondary, fontSize: 12,
                                    fontWeight: FontWeight.w600)),
                          ],
                        ]),
                      )
                    else if (match.isLocked && activePoll != null)
                      _ResultCard(poll: activePoll, match: match)
                    else if (match.isCancelled)
                      Container(
                        width: double.infinity,
                        padding: const EdgeInsets.all(20),
                        decoration: BoxDecoration(
                          color: IPLColors.red.withOpacity(0.08),
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(color: IPLColors.red.withOpacity(0.3)),
                        ),
                        child: Column(mainAxisSize: MainAxisSize.min, children: [
                          const Icon(Icons.cancel, color: IPLColors.red, size: 36),
                          const SizedBox(height: 6),
                          const Text('Match Cancelled',
                              style: TextStyle(color: IPLColors.red, fontSize: 16,
                                  fontWeight: FontWeight.w700)),
                          if (match.userPoll != null) ...[
                            const SizedBox(height: 4),
                            const Text('Your bid has been refunded.',
                                style: TextStyle(color: IPLColors.textMuted, fontSize: 12)),
                          ],
                        ]),
                      ),
                  ]),
                ),
              ),
            ]);
        },
      )),
    );
  }

  String _formatMatchDate(DateTime dt) {
    // Convert to IST (UTC+5:30)
    final ist = dt.toUtc().add(const Duration(hours: 5, minutes: 30));
    const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    const days = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
    final day = days[ist.weekday - 1];
    final month = months[ist.month - 1];
    final h = ist.hour % 12;
    final hour = h == 0 ? 12 : h;
    final ampm = ist.hour >= 12 ? 'PM' : 'AM';
    final min = ist.minute.toString().padLeft(2, '0');
    return '$day, ${ist.day} $month ${ist.year}  ·  $hour:$min $ampm IST';
  }

  Future<void> _submitPoll(MatchModel match) async {
    if (_selectedTeam == null) return;
    setState(() => _loading = true);

    try {
      final api = ref.read(apiServiceProvider);
      final hasActivePoll = match.userPoll != null && !match.userPoll!.isRefunded;
      if (!hasActivePoll) {
        await api.placePoll(
            matchId: match.id,
            selectedTeam: _selectedTeam!,
            bidAmount: _bidSlider.toInt());
      } else {
        await api.updatePoll(
            pollId: match.userPoll!.id,
            selectedTeam: _selectedTeam!,
            bidAmount: _bidSlider.toInt());
      }
      // Refresh match detail & wallet balance, await to ensure UI updates
      await Future.wait([
        ref.refresh(matchDetailProvider(match.id).future),
        ref.refresh(walletBalanceProvider.future),
      ]);
      ref.invalidate(matchesProvider);
      ref.invalidate(myPollsProvider);
      ref.invalidate(transactionsProvider);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Prediction saved!'), backgroundColor: Colors.green),
      );
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(ApiService.humanError(e)), backgroundColor: Colors.red),
      );
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _cancelPoll(MatchModel match) async {
    if (match.userPoll == null) return;

    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor: IPLColors.cardDark,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
        title: const Text('Cancel Bid', style: TextStyle(color: Colors.white, fontSize: 16)),
        content: Text(
          'Are you sure you want to cancel your bid of ${match.userPoll!.bidAmount} coins on ${match.userPoll!.selectedTeam}?',
          style: const TextStyle(color: IPLColors.textSecondary, fontSize: 14),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text('No', style: TextStyle(color: IPLColors.textMuted)),
          ),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Yes, Cancel', style: TextStyle(color: IPLColors.red)),
          ),
        ],
      ),
    );

    if (confirmed != true) return;

    setState(() => _cancelLoading = true);
    try {
      final api = ref.read(apiServiceProvider);
      await api.cancelPoll(match.userPoll!.id);
      // Reset selection state — keep _initialized true so stale data doesn't repopulate
      _selectedTeam = null;
      _bidSlider = 100;
      // Refresh data
      await Future.wait([
        ref.refresh(matchDetailProvider(match.id).future),
        ref.refresh(walletBalanceProvider.future),
      ]);
      ref.invalidate(matchesProvider);
      ref.invalidate(myPollsProvider);
      ref.invalidate(transactionsProvider);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Bid cancelled. Coins refunded!'), backgroundColor: Colors.green),
      );
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(ApiService.humanError(e)), backgroundColor: Colors.red),
      );
    } finally {
      if (mounted) setState(() => _cancelLoading = false);
    }
  }
}

// Helper widgets for match detail

class _VoteSplitBar extends StatelessWidget {
  final String teamA, teamB;
  final String? teamALogo, teamBLogo;
  final int teamAPercent, total;
  const _VoteSplitBar({required this.teamA, required this.teamB,
    this.teamALogo, this.teamBLogo,
    required this.teamAPercent, required this.total});

  // Ensure color is visible on dark bg (lighten if too dark)
  static Color _visibleColor(Color c) {
    final hsl = HSLColor.fromColor(c);
    return hsl.lightness < 0.4 ? hsl.withLightness(0.55).toColor() : c;
  }

  @override
  Widget build(BuildContext context) {
    final aPercent = total == 0 ? 50 : teamAPercent;
    final bPercent = total == 0 ? 50 : 100 - teamAPercent;
    final rawA = Color(AppConstants.teamColor(teamA));
    final rawB = Color(AppConstants.teamColor(teamB));
    final colorA = _visibleColor(rawA);
    final colorB = _visibleColor(rawB);

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(
        color: IPLColors.cardDark,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: IPLColors.border.withOpacity(0.3)),
      ),
      child: Column(children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            const Text('COMMUNITY PREDICTION', style: TextStyle(
                color: IPLColors.textMuted, fontSize: 10,
                fontWeight: FontWeight.w600, letterSpacing: 1)),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
              decoration: BoxDecoration(
                color: IPLColors.accent.withOpacity(0.1),
                borderRadius: BorderRadius.circular(4),
              ),
              child: Text('$total votes', style: const TextStyle(
                  color: IPLColors.accent, fontSize: 10, fontWeight: FontWeight.w600)),
            ),
          ],
        ),
        const SizedBox(height: 12),
        // Team rows with percentages
        Row(children: [
          // Team A
          TeamLogo(shortCode: teamA, logoUrl: teamALogo, size: 28),
          const SizedBox(width: 6),
          Text(teamA, style: TextStyle(color: colorA,
              fontSize: 13, fontWeight: FontWeight.w800)),
          const SizedBox(width: 4),
          Text('$aPercent%', style: TextStyle(color: colorA,
              fontSize: 16, fontWeight: FontWeight.w800)),
          const Spacer(),
          // Team B
          Text('$bPercent%', style: TextStyle(color: colorB,
              fontSize: 16, fontWeight: FontWeight.w800)),
          const SizedBox(width: 4),
          Text(teamB, style: TextStyle(color: colorB,
              fontSize: 13, fontWeight: FontWeight.w800)),
          const SizedBox(width: 6),
          TeamLogo(shortCode: teamB, logoUrl: teamBLogo, size: 28),
        ]),
        const SizedBox(height: 10),
        // Progress bar
        ClipRRect(
          borderRadius: BorderRadius.circular(4),
          child: Row(children: [
            Expanded(flex: aPercent > 0 ? aPercent : 1,
                child: Container(height: 6, color: colorA)),
            const SizedBox(width: 3),
            Expanded(flex: bPercent > 0 ? bPercent : 1,
                child: Container(height: 6, color: colorB)),
          ]),
        ),
      ]),
    );
  }
}

class _TeamPicker extends StatelessWidget {
  final MatchModel match;
  final String? selected;
  final ValueChanged<String> onSelect;
  const _TeamPicker({required this.match, required this.selected, required this.onSelect});

  @override
  Widget build(BuildContext context) {
    return Row(children: [
      _PickButton(match.teamAShort, match.teamA, selected == match.teamAShort,
          () => onSelect(match.teamAShort), logoUrl: match.teamALogo),
      const SizedBox(width: 12),
      _PickButton(match.teamBShort, match.teamB, selected == match.teamBShort,
          () => onSelect(match.teamBShort), logoUrl: match.teamBLogo),
    ]);
  }
}

class _PickButton extends StatelessWidget {
  final String short, name;
  final String? logoUrl;
  final bool selected;
  final VoidCallback onTap;
  const _PickButton(this.short, this.name, this.selected, this.onTap, {this.logoUrl});

  @override
  Widget build(BuildContext context) {
    final raw = Color(AppConstants.teamColor(short));
    final color = _VoteSplitBar._visibleColor(raw);
    return Expanded(
      child: GestureDetector(
        onTap: onTap,
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 200),
          padding: const EdgeInsets.symmetric(vertical: 16, horizontal: 12),
          decoration: BoxDecoration(
            gradient: selected ? LinearGradient(
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
              colors: [color.withOpacity(0.2), color.withOpacity(0.08)],
            ) : null,
            color: selected ? null : IPLColors.cardDark,
            borderRadius: BorderRadius.circular(14),
            border: Border.all(
                color: selected ? color : IPLColors.border.withOpacity(0.4),
                width: selected ? 2 : 1),
            boxShadow: selected ? [
              BoxShadow(color: color.withOpacity(0.15), blurRadius: 12, offset: const Offset(0, 4)),
            ] : null,
          ),
          child: Row(children: [
            TeamLogo(shortCode: short, logoUrl: logoUrl, size: 36),
            const SizedBox(width: 10),
            Expanded(
              child: Text(short, style: TextStyle(color: selected ? color : Colors.white,
                  fontSize: 16, fontWeight: FontWeight.w800)),
            ),
            if (selected)
              Container(
                padding: const EdgeInsets.all(2),
                decoration: BoxDecoration(
                  color: color,
                  shape: BoxShape.circle,
                ),
                child: const Icon(Icons.check, color: Colors.white, size: 14),
              )
            else
              Container(
                width: 20, height: 20,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  border: Border.all(color: IPLColors.border, width: 1.5),
                ),
              ),
          ]),
        ),
      ),
    );
  }
}

class _BidSlider extends StatelessWidget {
  final double value;
  final double maxBid;
  final ValueChanged<double> onChanged;
  const _BidSlider({required this.value, required this.maxBid, required this.onChanged});

  @override
  Widget build(BuildContext context) {
    final divisions = ((maxBid - 10) / 10).floor().clamp(1, 9999);
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: IPLColors.cardDark,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: IPLColors.border.withOpacity(0.3)),
      ),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
          const Text('BID AMOUNT', style: TextStyle(
              color: IPLColors.textMuted, fontSize: 10,
              fontWeight: FontWeight.w600, letterSpacing: 1)),
          Text('Balance: ${maxBid.toInt()}',
              style: const TextStyle(color: IPLColors.textMuted, fontSize: 11)),
        ]),
        const SizedBox(height: 12),
        // Coin display
        Center(
          child: Container(
            padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
            decoration: BoxDecoration(
              color: IPLColors.accent.withOpacity(0.1),
              borderRadius: BorderRadius.circular(12),
              border: Border.all(color: IPLColors.accent.withOpacity(0.2)),
            ),
            child: Row(mainAxisSize: MainAxisSize.min, children: [
              const CoinIcon(size: 22),
              const SizedBox(width: 8),
              Text('${value.toInt()}',
                  style: const TextStyle(color: IPLColors.accent,
                      fontSize: 24, fontWeight: FontWeight.w800)),
              const Text(' coins',
                  style: TextStyle(color: IPLColors.accentLight,
                      fontSize: 13, fontWeight: FontWeight.w500)),
            ]),
          ),
        ),
        const SizedBox(height: 12),
        SliderTheme(
          data: SliderThemeData(
            trackHeight: 6,
            activeTrackColor: IPLColors.accent,
            inactiveTrackColor: IPLColors.border,
            thumbColor: IPLColors.accent,
            thumbShape: const RoundSliderThumbShape(enabledThumbRadius: 8),
            overlayColor: IPLColors.accent.withOpacity(0.15),
          ),
          child: Slider(
            value: value, min: 10, max: maxBid, divisions: divisions,
            onChanged: onChanged,
          ),
        ),
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 4),
          child: Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
            const Text('10', style: TextStyle(color: IPLColors.textMuted, fontSize: 11)),
            // Quick pick buttons
            Row(children: [
              _QuickBidChip('25%', (maxBid * 0.25).roundToDouble().clamp(10.0, maxBid), onChanged),
              const SizedBox(width: 6),
              _QuickBidChip('50%', (maxBid * 0.5).roundToDouble().clamp(10.0, maxBid), onChanged),
              const SizedBox(width: 6),
              _QuickBidChip('MAX', maxBid, onChanged),
            ]),
            Text('${maxBid.toInt()}', style: const TextStyle(color: IPLColors.textMuted, fontSize: 11)),
          ]),
        ),
      ]),
    );
  }
}

class _QuickBidChip extends StatelessWidget {
  final String label;
  final double value;
  final ValueChanged<double> onChanged;
  const _QuickBidChip(this.label, this.value, this.onChanged);

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: () => onChanged(value),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
        decoration: BoxDecoration(
          color: IPLColors.accent.withOpacity(0.08),
          borderRadius: BorderRadius.circular(4),
          border: Border.all(color: IPLColors.accent.withOpacity(0.2)),
        ),
        child: Text(label, style: const TextStyle(
            color: IPLColors.accent, fontSize: 10, fontWeight: FontWeight.w700)),
      ),
    );
  }
}

// Compact inline countdown for header bar
class _InlineCountdown extends StatelessWidget {
  final Duration remaining;
  const _InlineCountdown({required this.remaining});

  @override
  Widget build(BuildContext context) {
    final days = remaining.inDays;
    final h = (remaining.inHours % 24).toString().padLeft(2, '0');
    final m = (remaining.inMinutes % 60).toString().padLeft(2, '0');
    final s = (remaining.inSeconds % 60).toString().padLeft(2, '0');
    final isUrgent = remaining.inHours < 1;
    final color = isUrgent ? IPLColors.red : IPLColors.accent;
    final timeStr = days > 0 ? '${days}d $h:$m:$s' : '$h:$m:$s';

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
      decoration: BoxDecoration(
        color: color.withOpacity(0.12),
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: color.withOpacity(0.25)),
      ),
      child: Row(mainAxisSize: MainAxisSize.min, children: [
        Icon(Icons.timer, size: 12, color: color),
        const SizedBox(width: 5),
        Text(timeStr, style: TextStyle(
          color: color, fontSize: 12, fontWeight: FontWeight.w800,
          fontFeatures: const [FontFeature.tabularFigures()],
        )),
      ]),
    );
  }
}

// Countdown blocks: DAY  HR  MIN  SEC
class _CountdownBlocks extends StatelessWidget {
  final Duration remaining;
  const _CountdownBlocks({required this.remaining});

  @override
  Widget build(BuildContext context) {
    final days = remaining.inDays;
    final hours = remaining.inHours % 24;
    final minutes = remaining.inMinutes % 60;
    final seconds = remaining.inSeconds % 60;

    return Row(
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        if (days > 0) ...[
          _block('$days', 'DAY'),
          const SizedBox(width: 8),
        ],
        _block(hours.toString().padLeft(2, '0'), 'HR'),
        const SizedBox(width: 8),
        _block(minutes.toString().padLeft(2, '0'), 'MIN'),
        const SizedBox(width: 8),
        _block(seconds.toString().padLeft(2, '0'), 'SEC'),
      ],
    );
  }

  Widget _block(String value, String label) {
    return Column(children: [
      Container(
        width: 42, height: 36,
        alignment: Alignment.center,
        decoration: BoxDecoration(
          color: Colors.white.withOpacity(0.06),
          borderRadius: BorderRadius.circular(8),
          border: Border.all(color: Colors.white.withOpacity(0.08)),
        ),
        child: Text(value, style: const TextStyle(
          color: Colors.white, fontSize: 16, fontWeight: FontWeight.w800,
          fontFeatures: [FontFeature.tabularFigures()],
        )),
      ),
      const SizedBox(height: 3),
      Text(label, style: const TextStyle(
        color: IPLColors.textMuted, fontSize: 8, fontWeight: FontWeight.w600, letterSpacing: 0.5,
      )),
    ]);
  }
}

// Prediction panel that fills available space without scroll
class _PredictionPanel extends StatelessWidget {
  final MatchModel match;
  final String? selectedTeam;
  final double bidSlider;
  final double maxBid;
  final bool loading;
  final bool cancelLoading;
  final ValueChanged<String> onSelectTeam;
  final ValueChanged<double> onBidChanged;
  final VoidCallback onSubmit;
  final VoidCallback? onCancel;

  const _PredictionPanel({
    required this.match,
    required this.selectedTeam,
    required this.bidSlider,
    required this.maxBid,
    required this.loading,
    this.cancelLoading = false,
    required this.onSelectTeam,
    required this.onBidChanged,
    required this.onSubmit,
    this.onCancel,
  });

  @override
  Widget build(BuildContext context) {
    final divisions = ((maxBid - 10) / 10).floor().clamp(1, 9999);
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: IPLColors.cardDark,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: IPLColors.border.withOpacity(0.3)),
      ),
      child: Column(mainAxisSize: MainAxisSize.min, children: [
        // Section label
        const Align(
          alignment: Alignment.centerLeft,
          child: Text('YOUR PREDICTION', style: TextStyle(color: IPLColors.textMuted,
              fontSize: 10, fontWeight: FontWeight.w600, letterSpacing: 1)),
        ),
        const SizedBox(height: 10),
        // Team picker row
        Row(children: [
          _PickButton(match.teamAShort, match.teamA, selectedTeam == match.teamAShort,
              () => onSelectTeam(match.teamAShort), logoUrl: match.teamALogo),
          const SizedBox(width: 10),
          _PickButton(match.teamBShort, match.teamB, selectedTeam == match.teamBShort,
              () => onSelectTeam(match.teamBShort), logoUrl: match.teamBLogo),
        ]),

        const SizedBox(height: 14),

        // Bid amount + manual entry + quick picks
        Row(children: [
          // Tappable amount field
          GestureDetector(
            onTap: () => _showCoinInput(context, bidSlider, maxBid, onBidChanged),
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
              decoration: BoxDecoration(
                color: IPLColors.accent.withOpacity(0.1),
                borderRadius: BorderRadius.circular(8),
                border: Border.all(color: IPLColors.accent.withOpacity(0.25)),
              ),
              child: Row(mainAxisSize: MainAxisSize.min, children: [
                const CoinIcon(size: 14),
                const SizedBox(width: 5),
                Text('${bidSlider.toInt()}',
                    style: const TextStyle(color: IPLColors.accent,
                        fontSize: 18, fontWeight: FontWeight.w800)),
                const SizedBox(width: 6),
                const Icon(Icons.edit, color: IPLColors.accent, size: 12),
              ]),
            ),
          ),
          const Spacer(),
          _QuickBidChip('25%', (maxBid * 0.25).roundToDouble().clamp(10.0, maxBid), onBidChanged),
          const SizedBox(width: 4),
          _QuickBidChip('50%', (maxBid * 0.5).roundToDouble().clamp(10.0, maxBid), onBidChanged),
          const SizedBox(width: 4),
          _QuickBidChip('MAX', maxBid, onBidChanged),
        ]),
        SliderTheme(
          data: SliderThemeData(
            trackHeight: 4,
            activeTrackColor: IPLColors.accent,
            inactiveTrackColor: IPLColors.border,
            thumbColor: IPLColors.accent,
            thumbShape: const RoundSliderThumbShape(enabledThumbRadius: 7),
            overlayColor: IPLColors.accent.withOpacity(0.12),
            overlayShape: const RoundSliderOverlayShape(overlayRadius: 14),
          ),
          child: Slider(
            value: bidSlider, min: 10, max: maxBid, divisions: divisions,
            onChanged: onBidChanged,
          ),
        ),
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 8),
          child: Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
            const Text('10', style: TextStyle(color: IPLColors.textMuted, fontSize: 10)),
            Text('Balance: ${maxBid.toInt()}',
                style: const TextStyle(color: IPLColors.textMuted, fontSize: 10)),
          ]),
        ),

        const SizedBox(height: 10),

        // Submit button
        SizedBox(
          width: double.infinity,
          child: ElevatedButton(
            onPressed: selectedTeam == null || loading ? null : onSubmit,
            style: ElevatedButton.styleFrom(
              padding: const EdgeInsets.symmetric(vertical: 14),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
            ),
            child: loading
                ? const SizedBox(height: 18, width: 18,
                    child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                : Text((match.userPoll == null || match.userPoll!.isRefunded)
                    ? 'Place Prediction  ·  ${bidSlider.toInt()} coins'
                    : 'Update  ·  ${bidSlider.toInt()} coins',
                    style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w700)),
          ),
        ),

        // Cancel bid button (only shown when user has an existing poll)
        if (match.userPoll != null && onCancel != null) ...[
          const SizedBox(height: 8),
          SizedBox(
            width: double.infinity,
            child: OutlinedButton(
              onPressed: cancelLoading ? null : onCancel,
              style: OutlinedButton.styleFrom(
                padding: const EdgeInsets.symmetric(vertical: 12),
                side: const BorderSide(color: IPLColors.red),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
              ),
              child: cancelLoading
                  ? const SizedBox(height: 18, width: 18,
                      child: CircularProgressIndicator(color: IPLColors.red, strokeWidth: 2))
                  : const Text('Cancel Bid',
                      style: TextStyle(color: IPLColors.red, fontSize: 14, fontWeight: FontWeight.w700)),
            ),
          ),
        ],
      ]),
    );
  }

  static void _showCoinInput(BuildContext context, double current, double max, ValueChanged<double> onChanged) {
    final controller = TextEditingController(text: current.toInt().toString());
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor: IPLColors.cardDark,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
        title: const Text('Enter Bid Amount', style: TextStyle(color: Colors.white, fontSize: 16)),
        content: TextField(
          controller: controller,
          keyboardType: TextInputType.number,
          autofocus: true,
          style: const TextStyle(color: Colors.white, fontSize: 20, fontWeight: FontWeight.w700),
          decoration: InputDecoration(
            prefixIcon: const Padding(
              padding: EdgeInsets.only(left: 12, right: 4),
              child: CoinIcon(size: 20),
            ),
            prefixIconConstraints: const BoxConstraints(minWidth: 36),
            hintText: '10 - ${max.toInt()}',
            hintStyle: const TextStyle(color: IPLColors.textMuted, fontSize: 14),
            filled: true,
            fillColor: IPLColors.deepNavy,
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(10),
              borderSide: const BorderSide(color: IPLColors.border),
            ),
            focusedBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(10),
              borderSide: const BorderSide(color: IPLColors.accent, width: 1.5),
            ),
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text('Cancel', style: TextStyle(color: IPLColors.textMuted)),
          ),
          ElevatedButton(
            onPressed: () {
              final val = int.tryParse(controller.text) ?? 0;
              final clamped = val.toDouble().clamp(10.0, max);
              onChanged(clamped);
              Navigator.pop(ctx);
            },
            child: const Text('Done'),
          ),
        ],
      ),
    );
  }
}

class _ResultCard extends StatelessWidget {
  final UserPollModel poll;
  final MatchModel match;
  const _ResultCard({required this.poll, required this.match});

  @override
  Widget build(BuildContext context) {
    final isWon = poll.isWon;
    final isRefunded = poll.isRefunded;
    final color = isWon ? Colors.greenAccent
        : isRefunded ? IPLColors.accent
        : IPLColors.red;
    final icon = isWon ? Icons.emoji_events
        : isRefunded ? Icons.replay
        : Icons.sentiment_dissatisfied;
    final title = isWon ? 'You Won!'
        : isRefunded ? 'Bid Refunded'
        : 'Better luck next time';
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(24),
      decoration: BoxDecoration(
        color: color.withOpacity(0.08),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: color.withOpacity(0.3)),
      ),
      child: Column(children: [
        Icon(icon, color: color, size: 40),
        const SizedBox(height: 10),
        Text(title,
            style: TextStyle(color: color, fontSize: 18, fontWeight: FontWeight.w700)),
        if (!isRefunded) ...[
          const SizedBox(height: 4),
          Row(mainAxisAlignment: MainAxisAlignment.center, children: [
            Text('Picked: ${poll.selectedTeam}  ·  Bid: ',
                style: const TextStyle(color: IPLColors.textSecondary)),
            const CoinIcon(size: 13),
            Text(' ${poll.bidAmount}',
                style: const TextStyle(color: IPLColors.textSecondary)),
          ]),
        ],
        if (isWon) ...[
          const SizedBox(height: 10),
          Row(mainAxisAlignment: MainAxisAlignment.center, children: [
            const CoinIcon(size: 20),
            const SizedBox(width: 4),
            Text('+${poll.coinsEarned}',
                style: const TextStyle(color: Colors.greenAccent,
                    fontSize: 22, fontWeight: FontWeight.w800)),
          ]),
        ],
      ]),
    );
  }
}

class _SectionTitle extends StatelessWidget {
  final String title;
  const _SectionTitle(this.title);

  @override
  Widget build(BuildContext context) => Align(
    alignment: Alignment.centerLeft,
    child: Text(title,
        style: const TextStyle(
            fontSize: 12, fontWeight: FontWeight.w700,
            color: IPLColors.textMuted, letterSpacing: 1)),
  );
}

// ─────────────────────────────────────────────────────────────
// lib/screens/my_polls_screen.dart

class MyPollsScreen extends ConsumerWidget {
  const MyPollsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final pollsAsync = ref.watch(myPollsProvider);
    final user = ref.watch(authProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Row(mainAxisSize: MainAxisSize.min, children: [
          Icon(Icons.poll_rounded, size: 22, color: IPLColors.accent),
          SizedBox(width: 8),
          Text('My Predictions'),
        ]),
        actions: [
          GestureDetector(
            onTap: () => context.go('/wallet'),
            child: Container(
              margin: const EdgeInsets.only(right: 14),
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
              decoration: BoxDecoration(
                color: IPLColors.cardDark,
                borderRadius: BorderRadius.circular(20),
                border: Border.all(color: IPLColors.border),
              ),
              child: Row(children: [
                const CoinIcon(size: 16),
                const SizedBox(width: 4),
                Text('${user?.coinBalance ?? 0}',
                    style: const TextStyle(
                        color: Colors.white, fontWeight: FontWeight.w700, fontSize: 13)),
              ]),
            ),
          ),
        ],
      ),
      body: RefreshIndicator(
        color: IPLColors.accent,
        onRefresh: () => ref.refresh(myPollsProvider.future),
        child: pollsAsync.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (e, _) => Center(child: Text(ApiService.humanError(e))),
          data: (polls) {
            if (polls.isEmpty) {
              return const Center(child: Text('No predictions yet. Place your first one!',
                  style: TextStyle(color: IPLColors.textMuted)));
            }
            return ListView.builder(
              padding: const EdgeInsets.all(14),
              itemCount: polls.length,
              itemBuilder: (ctx, i) => GestureDetector(
                onTap: () => context.push('/match/${polls[i].matchId}'),
                child: _PollTile(poll: polls[i]),
              ),
            );
          },
        ),
      ),
    );
  }
}

class _PollTile extends StatelessWidget {
  final PollModel poll;
  const _PollTile({required this.poll});

  @override
  Widget build(BuildContext context) {
    final color = poll.isWon ? Colors.greenAccent
        : poll.isLost ? IPLColors.red
        : IPLColors.accent;

    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: IPLColors.cardDark,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: IPLColors.border.withOpacity(0.3)),
      ),
      child: Row(children: [
        Container(
          width: 42, height: 42,
          decoration: BoxDecoration(
            color: color.withOpacity(0.1), shape: BoxShape.circle),
          child: Text(
            poll.match != null ? '#${poll.match!.matchNumber}' : '#${poll.matchId}',
            style: TextStyle(color: color, fontSize: 13, fontWeight: FontWeight.w800),
          ),
          alignment: Alignment.center,
        ),
        const SizedBox(width: 12),
        Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          if (poll.match != null)
            Row(children: [
              TeamLogo(shortCode: poll.match!.teamAShort, logoUrl: poll.match!.teamALogo, size: 20),
              const SizedBox(width: 6),
              Text(poll.match!.teamAShort, style: const TextStyle(
                  color: Colors.white, fontWeight: FontWeight.w700, fontSize: 13)),
              const Text('  vs  ', style: TextStyle(
                  color: IPLColors.textMuted, fontSize: 11, fontWeight: FontWeight.w500)),
              Text(poll.match!.teamBShort, style: const TextStyle(
                  color: Colors.white, fontWeight: FontWeight.w700, fontSize: 13)),
              const SizedBox(width: 6),
              TeamLogo(shortCode: poll.match!.teamBShort, logoUrl: poll.match!.teamBLogo, size: 20),
            ])
          else
            Text('Match #${poll.matchId}',
                style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w600,
                    fontSize: 13)),
          const SizedBox(height: 3),
          if (poll.status == 'refunded')
            const Text('Cancelled',
                style: TextStyle(color: IPLColors.textMuted, fontSize: 11))
          else
            Row(children: [
              const Text('Picked: ', style: TextStyle(color: IPLColors.textMuted, fontSize: 11)),
              TeamLogo(shortCode: poll.selectedTeam,
                  logoUrl: poll.match != null
                      ? (poll.selectedTeam == poll.match!.teamAShort
                          ? poll.match!.teamALogo : poll.match!.teamBLogo)
                      : null,
                  size: 16),
              const SizedBox(width: 4),
              Text('${poll.selectedTeam}  ·  Bid: ',
                  style: const TextStyle(color: IPLColors.textMuted, fontSize: 11)),
              const CoinIcon(size: 11),
              Text(' ${poll.bidAmount}',
                  style: const TextStyle(color: IPLColors.textMuted, fontSize: 11)),
            ]),
        ])),
        Column(crossAxisAlignment: CrossAxisAlignment.end, children: [
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
            decoration: BoxDecoration(
              color: color.withOpacity(0.12),
              borderRadius: BorderRadius.circular(4)),
            child: Text(poll.status.toUpperCase(),
                style: TextStyle(color: color, fontSize: 9,
                    fontWeight: FontWeight.w700, letterSpacing: 0.5)),
          ),
          if (poll.isWon) ...[
            const SizedBox(height: 6),
            Row(mainAxisSize: MainAxisSize.min, children: [
              const CoinIcon(size: 12),
              const SizedBox(width: 2),
              Text('+${poll.coinsEarned}',
                  style: const TextStyle(color: Colors.greenAccent,
                      fontWeight: FontWeight.w700, fontSize: 13)),
            ]),
          ],
        ]),
      ]),
    );
  }

  IconData get _statusIcon => poll.isWon ? Icons.check_circle
      : poll.isLost ? Icons.cancel
      : Icons.hourglass_empty;
}

// ─────────────────────────────────────────────────────────────
// lib/screens/wallet_screen.dart

class WalletScreen extends ConsumerWidget {
  const WalletScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final balanceAsync = ref.watch(walletBalanceProvider);
    final txnsAsync    = ref.watch(transactionsProvider);
    final pollsAsync   = ref.watch(myPollsProvider);

    final isLoading = balanceAsync.isLoading || txnsAsync.isLoading;
    final hasError = balanceAsync.hasError || txnsAsync.hasError;
    final errorMsg = balanceAsync.hasError
        ? ApiService.humanError(balanceAsync.error!)
        : txnsAsync.hasError
            ? ApiService.humanError(txnsAsync.error!)
            : '';

    return Scaffold(
      appBar: AppBar(title: const Row(mainAxisSize: MainAxisSize.min, children: [
        Icon(Icons.wallet_rounded, size: 22, color: IPLColors.accent),
        SizedBox(width: 8),
        Text('Wallet'),
      ])),
      body: IPLBackground(child: isLoading
          ? const Center(child: CircularProgressIndicator())
          : hasError
              ? Center(child: Text(errorMsg, style: const TextStyle(color: IPLColors.textMuted)))
              : Column(
                  children: [
                    // Fixed balance card
                    if (balanceAsync.hasValue)
                      Padding(
                        padding: const EdgeInsets.fromLTRB(16, 16, 16, 0),
                        child: Container(
                          padding: const EdgeInsets.all(24),
                          decoration: BoxDecoration(
                            gradient: const LinearGradient(
                              begin: Alignment.topLeft,
                              end: Alignment.bottomRight,
                              colors: [IPLColors.navy, IPLColors.darkNavy]),
                            borderRadius: BorderRadius.circular(14),
                            border: Border.all(color: IPLColors.accent.withOpacity(0.2)),
                          ),
                          child: Column(children: [
                            const Text('COIN BALANCE', style: TextStyle(
                                color: IPLColors.textMuted, fontSize: 10,
                                fontWeight: FontWeight.w600, letterSpacing: 1)),
                            const SizedBox(height: 10),
                            Row(mainAxisAlignment: MainAxisAlignment.center, children: [
                              const CoinIcon(size: 32),
                              const SizedBox(width: 10),
                              Text('${balanceAsync.value!['balance']}',
                                  style: const TextStyle(fontSize: 38,
                                      fontWeight: FontWeight.w800, color: Colors.white)),
                            ]),
                            const SizedBox(height: 20),
                            Row(mainAxisAlignment: MainAxisAlignment.spaceAround, children: [
                              _WalletStat('Total Won', '${balanceAsync.value!['total_won']}', Colors.greenAccent),
                              Container(width: 1, height: 30, color: IPLColors.border),
                              _WalletStat('Pending', '${pollsAsync.whenOrNull(data: (polls) => polls.where((p) => p.isPending).fold<int>(0, (sum, p) => sum + p.bidAmount)) ?? 0}', IPLColors.accent),
                            ]),
                          ]),
                        ),
                      ),

                    // Transaction header
                    const Padding(
                      padding: EdgeInsets.fromLTRB(16, 24, 16, 12),
                      child: Align(
                        alignment: Alignment.centerLeft,
                        child: Text('TRANSACTION HISTORY',
                            style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700,
                                color: IPLColors.textMuted, letterSpacing: 1)),
                      ),
                    ),

                    // Scrollable transactions
                    Expanded(
                      child: RefreshIndicator(
                        color: IPLColors.accent,
                        onRefresh: () async {
                          ref.refresh(walletBalanceProvider);
                          ref.refresh(transactionsProvider);
                          ref.invalidate(myPollsProvider);
                        },
                        child: txnsAsync.hasValue && txnsAsync.value!.isNotEmpty
                            ? ListView.builder(
                                padding: const EdgeInsets.symmetric(horizontal: 16),
                                itemCount: txnsAsync.value!.length,
                                itemBuilder: (_, i) => _TxnTile(txn: txnsAsync.value![i]),
                              )
                            : ListView(
                                children: const [
                                  SizedBox(height: 60),
                                  Center(child: Text('No transactions yet',
                                      style: TextStyle(color: IPLColors.textMuted, fontSize: 13))),
                                ],
                              ),
                      ),
                    ),
                  ],
                )),
    );
  }
}

class _WalletStat extends StatelessWidget {
  final String label, value;
  final Color color;
  const _WalletStat(this.label, this.value, this.color);

  @override
  Widget build(BuildContext context) => Column(children: [
    Text(value, style: TextStyle(color: color,
        fontSize: 18, fontWeight: FontWeight.w800)),
    const SizedBox(height: 2),
    Text(label, style: const TextStyle(color: IPLColors.textMuted, fontSize: 10,
        fontWeight: FontWeight.w500)),
  ]);
}

class _TxnTile extends StatelessWidget {
  final TransactionModel txn;
  const _TxnTile({required this.txn});

  @override
  Widget build(BuildContext context) {
    final isCredit = txn.isCredit;
    final color    = isCredit ? Colors.greenAccent : IPLColors.red;

    return Container(
      margin: const EdgeInsets.only(bottom: 6),
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(
        color: IPLColors.cardDark,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: IPLColors.border.withOpacity(0.2)),
      ),
      child: Row(children: [
        Container(
          width: 32, height: 32,
          decoration: BoxDecoration(
            color: color.withOpacity(0.1),
            borderRadius: BorderRadius.circular(8),
          ),
          child: Icon(isCredit ? Icons.arrow_downward : Icons.arrow_upward,
              color: color, size: 16),
        ),
        const SizedBox(width: 12),
        Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text(txn.description ?? txn.type,
              maxLines: 1, overflow: TextOverflow.ellipsis,
              style: const TextStyle(color: Colors.white, fontSize: 13,
                  fontWeight: FontWeight.w500)),
          Text(DateFormat('d MMM yyyy, h:mm a').format(txn.createdAt.toLocal()),
              style: const TextStyle(color: IPLColors.textMuted, fontSize: 11)),
        ])),
        Row(mainAxisSize: MainAxisSize.min, children: [
          const CoinIcon(size: 14),
          const SizedBox(width: 3),
          Text('${isCredit ? '+' : ''}${txn.amount}',
              style: TextStyle(color: color,
                  fontWeight: FontWeight.w700, fontSize: 15)),
        ]),
      ]),
    );
  }
}

// ─────────────────────────────────────────────────────────────
// lib/screens/leaderboard_screen.dart

class LeaderboardScreen extends ConsumerStatefulWidget {
  const LeaderboardScreen({super.key});

  @override
  ConsumerState<LeaderboardScreen> createState() => _LeaderboardScreenState();
}

class _LeaderboardScreenState extends ConsumerState<LeaderboardScreen>
    with SingleTickerProviderStateMixin {
  late final TabController _tabController;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    _tabController.addListener(() => setState(() {}));
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final rankAsync    = ref.watch(myRankProvider);
    final currentUser  = ref.watch(authProvider);
    final isCoinsTab   = _tabController.index == 0;

    final boardAsync = isCoinsTab
        ? ref.watch(leaderboardProvider)
        : ref.watch(winsLeaderboardProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Leaderboard'),
        bottom: TabBar(
          controller: _tabController,
          indicatorColor: IPLColors.accent,
          labelColor: IPLColors.accent,
          unselectedLabelColor: IPLColors.textMuted,
          tabs: const [
            Tab(text: 'Coins'),
            Tab(text: 'Wins'),
          ],
        ),
      ),
      body: RefreshIndicator(
        color: IPLColors.accent,
        onRefresh: () async {
          ref.refresh(leaderboardProvider);
          ref.refresh(winsLeaderboardProvider);
          ref.refresh(myRankProvider);
        },
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            // My rank card
            rankAsync.when(
              loading: () => const SizedBox(),
              error: (_, __) => const SizedBox(),
              data: (data) => Container(
                margin: const EdgeInsets.only(bottom: 20),
                padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
                decoration: BoxDecoration(
                  gradient: const LinearGradient(
                    colors: [IPLColors.navy, IPLColors.cardDark]),
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: IPLColors.accent.withOpacity(0.3)),
                ),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                      const Text('YOUR RANK', style: TextStyle(
                          color: IPLColors.textMuted, fontSize: 10,
                          fontWeight: FontWeight.w600, letterSpacing: 1)),
                      const SizedBox(height: 4),
                      Text('#${isCoinsTab ? data['rank'] : data['wins_rank']}',
                          style: const TextStyle(color: Colors.white,
                              fontSize: 28, fontWeight: FontWeight.w800)),
                    ]),
                    Row(children: [
                      if (isCoinsTab) ...[
                        const CoinIcon(size: 18),
                        const SizedBox(width: 6),
                        Text('${data['coin_balance']}',
                            style: const TextStyle(color: Colors.white,
                                fontWeight: FontWeight.w700, fontSize: 16)),
                      ] else ...[
                        const Icon(Icons.emoji_events, color: Colors.amber, size: 18),
                        const SizedBox(width: 6),
                        Text('${data['total_wins']} wins',
                            style: const TextStyle(color: Colors.white,
                                fontWeight: FontWeight.w700, fontSize: 16)),
                      ],
                    ]),
                  ],
                ),
              ),
            ),

            boardAsync.when(
              loading: () => const Center(child: CircularProgressIndicator()),
              error: (e, _) => Text(ApiService.humanError(e)),
              data: (entries) => Column(
                children: entries.map((e) => _LeaderRow(
                  entry: e,
                  isMe: e.id == currentUser?.id,
                  showWins: !isCoinsTab,
                )).toList(),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _LeaderRow extends StatelessWidget {
  final LeaderboardEntry entry;
  final bool isMe;
  final bool showWins;
  const _LeaderRow({required this.entry, required this.isMe, this.showWins = false});

  @override
  Widget build(BuildContext context) {
    final rankColor = entry.rank == 1 ? const Color(0xFFFFD700)
        : entry.rank == 2 ? const Color(0xFFC0C0C0)
        : entry.rank == 3 ? const Color(0xFFCD7F32)
        : IPLColors.textMuted;

    return Container(
      margin: const EdgeInsets.only(bottom: 6),
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(
        color: isMe ? IPLColors.accent.withOpacity(0.08) : IPLColors.cardDark,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(
            color: isMe ? IPLColors.accent.withOpacity(0.3) : IPLColors.border.withOpacity(0.3)),
      ),
      child: Row(children: [
        SizedBox(width: 32,
            child: Text('#${entry.rank}',
                style: TextStyle(color: rankColor,
                    fontWeight: FontWeight.w800, fontSize: 14))),
        const SizedBox(width: 8),
        CircleAvatar(radius: 16, backgroundColor: IPLColors.navy,
            child: Text(entry.name[0].toUpperCase(),
                style: const TextStyle(color: Colors.white, fontSize: 12,
                    fontWeight: FontWeight.w600))),
        const SizedBox(width: 10),
        Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text(entry.name + (isMe ? ' (You)' : ''),
              style: TextStyle(
                  color: isMe ? IPLColors.accent : Colors.white,
                  fontWeight: FontWeight.w600, fontSize: 13)),
          Text(entry.mobileMasked,
              style: const TextStyle(color: IPLColors.textMuted, fontSize: 11)),
        ])),
        if (showWins)
          Row(children: [
            const Icon(Icons.emoji_events, color: Colors.amber, size: 14),
            const SizedBox(width: 4),
            Text('${entry.totalWins}',
                style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w700)),
          ])
        else
          Row(children: [
            const CoinIcon(size: 14),
            const SizedBox(width: 4),
            Text('${entry.coinBalance}',
                style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w700)),
          ]),
      ]),
    );
  }
}

// ─────────────────────────────────────────────────────────────
// lib/screens/profile_screen.dart

class ProfileScreen extends ConsumerWidget {
  const ProfileScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final user       = ref.watch(authProvider);
    final profileAsync = ref.watch(profileProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Row(mainAxisSize: MainAxisSize.min, children: [
          Icon(Icons.person_rounded, size: 22, color: IPLColors.accent),
          SizedBox(width: 8),
          Text('Profile'),
        ]),
        actions: [
          GestureDetector(
            onTap: () => context.go('/wallet'),
            child: Container(
              margin: const EdgeInsets.only(right: 14),
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
              decoration: BoxDecoration(
                color: IPLColors.cardDark,
                borderRadius: BorderRadius.circular(20),
                border: Border.all(color: IPLColors.border),
              ),
              child: Row(children: [
                const CoinIcon(size: 16),
                const SizedBox(width: 4),
                Text('${user?.coinBalance ?? 0}',
                    style: const TextStyle(
                        color: Colors.white, fontWeight: FontWeight.w700, fontSize: 13)),
              ]),
            ),
          ),
        ],
      ),
      body: IPLBackground(child: profileAsync.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error:   (e, _) => Center(child: Text(ApiService.humanError(e))),
        data: (data) {
          final stats = data['stats'] as Map<String, dynamic>;
          return ListView(
            padding: const EdgeInsets.all(20),
            children: [
              // Avatar
              Center(child: Container(
                width: 80, height: 80,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  gradient: const LinearGradient(
                    colors: [IPLColors.navy, IPLColors.accent]),
                ),
                child: Center(child: Text(
                  (user?.name ?? 'U')[0].toUpperCase(),
                  style: const TextStyle(fontSize: 32, color: Colors.white,
                      fontWeight: FontWeight.w800),
                )),
              )),
              const SizedBox(height: 14),
              Center(child: Text(user?.name ?? '',
                  style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w700,
                      color: Colors.white))),
              const SizedBox(height: 4),
              Center(child: Text(user?.mobile ?? '',
                  style: const TextStyle(color: IPLColors.textMuted, fontSize: 13))),
              const SizedBox(height: 28),

              // Stats grid
              GridView.count(
                crossAxisCount: 2, shrinkWrap: true,
                physics: const NeverScrollableScrollPhysics(),
                crossAxisSpacing: 10, mainAxisSpacing: 10,
                childAspectRatio: 1.6,
                children: [
                  _StatCard('Total Polls', '${stats['total_polls']}',
                      Icons.poll_rounded, IPLColors.accent,
                      onTap: () => context.go('/my-polls')),
                  _StatCard('Won', '${stats['won']}',
                      Icons.emoji_events, Colors.greenAccent),
                  _StatCard('Lost', '${stats['lost']}',
                      Icons.cancel, IPLColors.red),
                  _StatCard('Win Rate', '${stats['win_rate']}%',
                      Icons.percent, IPLColors.accentLight),
                ],
              ),
              const SizedBox(height: 28),

              // Change password
              OutlinedButton.icon(
                onPressed: () => context.push('/change-password', extra: false),
                icon: const Icon(Icons.lock_reset),
                label: const Text('Change Password'),
              ),
              const SizedBox(height: 10),

              // Logout
              OutlinedButton.icon(
                onPressed: () async {
                  await ref.read(authProvider.notifier).logout();
                  if (context.mounted) context.go('/login');
                },
                icon: const Icon(Icons.logout, color: IPLColors.red),
                label: const Text('Logout', style: TextStyle(color: IPLColors.red)),
                style: OutlinedButton.styleFrom(
                    side: const BorderSide(color: IPLColors.red)),
              ),
            ],
          );
        },
      )),
    );
  }
}

class _StatCard extends StatelessWidget {
  final String label, value;
  final IconData icon;
  final Color color;
  final VoidCallback? onTap;
  const _StatCard(this.label, this.value, this.icon, this.color, {this.onTap});

  @override
  Widget build(BuildContext context) => GestureDetector(
    onTap: onTap,
    child: Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
      decoration: BoxDecoration(
        color: IPLColors.cardDark,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: IPLColors.border.withOpacity(0.3)),
      ),
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(icon, color: color, size: 22),
          const SizedBox(height: 6),
          Text(value, style: TextStyle(color: color,
              fontSize: 18, fontWeight: FontWeight.w800)),
          const SizedBox(height: 2),
          Text(label, style: const TextStyle(color: IPLColors.textMuted, fontSize: 10)),
        ],
      ),
    ),
  );
}

// ─────────────────────────────────────────────────────────────
// lib/screens/login_screen.dart

class LoginScreen extends ConsumerStatefulWidget {
  const LoginScreen({super.key});

  @override
  ConsumerState<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends ConsumerState<LoginScreen> {
  final _mobileController   = TextEditingController();
  final _passwordController = TextEditingController();
  bool _loading = false;
  bool _obscure = true;

  @override
  void dispose() {
    _mobileController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  Future<void> _login() async {
    final mobile   = _mobileController.text.trim();
    final password = _passwordController.text;

    if (mobile.isEmpty || password.isEmpty) {
      _showError('Please enter mobile number and password.');
      return;
    }

    setState(() => _loading = true);
    try {
      final result = await ref.read(authProvider.notifier).login(mobile, password);
      if (!mounted) return;
      if (result.mustChangePassword) {
        context.go('/change-password', extra: true);
      } else {
        context.go('/home');
      }
    } catch (e) {
      if (!mounted) return;
      _showError(ApiService.humanError(e));
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  void _showError(String msg) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(msg), backgroundColor: Colors.red),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: IPLBackground(
        child: SafeArea(
          child: Center(
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(32),
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  ClipRRect(
                    borderRadius: BorderRadius.circular(24),
                    child: Image.asset('assets/icons/app_icon.png', width: 100, height: 100),
                  ),
                  const SizedBox(height: 16),
                  const Text('IPL POLL 2026',
                      style: TextStyle(fontSize: 24, fontWeight: FontWeight.w800,
                          color: Colors.white, letterSpacing: 1)),
                  const SizedBox(height: 6),
                  const Text('Login to place your predictions',
                      style: TextStyle(color: IPLColors.textMuted, fontSize: 13)),
                  const SizedBox(height: 40),
                  TextField(
                    controller: _mobileController,
                    keyboardType: TextInputType.phone,
                    decoration: const InputDecoration(
                      labelText: 'Mobile Number',
                      prefixIcon: Icon(Icons.phone, color: IPLColors.textMuted),
                    ),
                    style: const TextStyle(color: Colors.white),
                  ),
                  const SizedBox(height: 14),
                  TextField(
                    controller: _passwordController,
                    obscureText: _obscure,
                    decoration: InputDecoration(
                      labelText: 'Password',
                      prefixIcon: const Icon(Icons.lock, color: IPLColors.textMuted),
                      suffixIcon: IconButton(
                        icon: Icon(_obscure ? Icons.visibility_off : Icons.visibility,
                            color: IPLColors.textMuted),
                        onPressed: () => setState(() => _obscure = !_obscure),
                      ),
                    ),
                    style: const TextStyle(color: Colors.white),
                    onSubmitted: (_) => _login(),
                  ),
                  const SizedBox(height: 28),
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton(
                      onPressed: _loading ? null : _login,
                      child: _loading
                          ? const SizedBox(height: 20, width: 20,
                              child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                          : const Text('Login'),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}

// ─────────────────────────────────────────────────────────────
// lib/screens/change_password_screen.dart

class ChangePasswordScreen extends ConsumerStatefulWidget {
  final bool isForced;
  const ChangePasswordScreen({super.key, this.isForced = false});

  @override
  ConsumerState<ChangePasswordScreen> createState() => _ChangePasswordScreenState();
}

class _ChangePasswordScreenState extends ConsumerState<ChangePasswordScreen> {
  final _oldController = TextEditingController();
  final _newController = TextEditingController();
  final _confirmController = TextEditingController();
  bool _loading = false;

  @override
  void dispose() {
    _oldController.dispose();
    _newController.dispose();
    _confirmController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    final oldPw = _oldController.text;
    final newPw = _newController.text;
    final confirm = _confirmController.text;

    if (oldPw.isEmpty || newPw.isEmpty || confirm.isEmpty) {
      _showError('Please fill all fields.');
      return;
    }

    if (newPw != confirm) {
      _showError('New passwords do not match.');
      return;
    }

    if (newPw.length < 6) {
      _showError('Password must be at least 6 characters.');
      return;
    }

    setState(() => _loading = true);
    try {
      await ref.read(authProvider.notifier).changePassword(oldPw, newPw);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Password changed successfully.'),
            backgroundColor: Colors.green),
      );
      context.go('/home');
    } catch (e) {
      if (!mounted) return;
      _showError(ApiService.humanError(e));
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  void _showError(String msg) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(msg), backgroundColor: Colors.red),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: widget.isForced
          ? null
          : AppBar(title: const Text('Change Password')),
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(32),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                if (widget.isForced) ...[
                  const Icon(Icons.lock_reset, size: 56, color: IPLColors.accent),
                  const SizedBox(height: 12),
                  const Text('Change Your Password',
                      style: TextStyle(fontSize: 22, fontWeight: FontWeight.w700,
                          color: Colors.white)),
                  const SizedBox(height: 8),
                  const Text('You must change your password before continuing.',
                      textAlign: TextAlign.center,
                      style: TextStyle(color: IPLColors.textMuted)),
                  const SizedBox(height: 32),
                ],
                TextField(
                  controller: _oldController,
                  obscureText: true,
                  decoration: const InputDecoration(
                    labelText: 'Current Password',
                    prefixIcon: Icon(Icons.lock_outline, color: IPLColors.textMuted),
                  ),
                  style: const TextStyle(color: Colors.white),
                ),
                const SizedBox(height: 14),
                TextField(
                  controller: _newController,
                  obscureText: true,
                  decoration: const InputDecoration(
                    labelText: 'New Password',
                    prefixIcon: Icon(Icons.lock, color: IPLColors.textMuted),
                  ),
                  style: const TextStyle(color: Colors.white),
                ),
                const SizedBox(height: 14),
                TextField(
                  controller: _confirmController,
                  obscureText: true,
                  decoration: const InputDecoration(
                    labelText: 'Confirm New Password',
                    prefixIcon: Icon(Icons.lock, color: IPLColors.textMuted),
                  ),
                  style: const TextStyle(color: Colors.white),
                  onSubmitted: (_) => _submit(),
                ),
                const SizedBox(height: 28),
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton(
                    onPressed: _loading ? null : _submit,
                    child: _loading
                        ? const SizedBox(height: 20, width: 20,
                            child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                        : const Text('Change Password'),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

// ─────────────────────────────────────────────────────────────
// lib/screens/match_biddings_screen.dart

class MatchBiddingsScreen extends ConsumerWidget {
  final int matchId;
  const MatchBiddingsScreen({super.key, required this.matchId});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final pollsAsync = ref.watch(matchPollsProvider(matchId));
    final detailAsync = ref.watch(matchDetailProvider(matchId));

    return Scaffold(
      body: IPLBackground(
        child: Column(children: [
          // ── Header ──
          detailAsync.when(
            loading: () => SizedBox(
              height: MediaQuery.of(context).padding.top + 56,
              child: const Center(child: CircularProgressIndicator()),
            ),
            error: (_, __) => const SizedBox(),
            data: (data) {
              final match = MatchModel.fromJson(data['match']);
              final stats = data['stats'] as Map<String, dynamic>;
              final totalPolls = (stats['total_polls'] as num).toInt();
              return Container(
                width: double.infinity,
                padding: EdgeInsets.only(
                  top: MediaQuery.of(context).padding.top + 8,
                  bottom: 14, left: 16, right: 16,
                ),
                decoration: const BoxDecoration(
                  gradient: LinearGradient(
                    begin: Alignment.topCenter,
                    end: Alignment.bottomCenter,
                    colors: [Color(0xFF003366), IPLColors.darkNavy],
                  ),
                  borderRadius: BorderRadius.only(
                    bottomLeft: Radius.circular(24),
                    bottomRight: Radius.circular(24),
                  ),
                ),
                child: Column(children: [
                  // Top bar: back + title
                  Row(children: [
                    GestureDetector(
                      onTap: () => Navigator.of(context).pop(),
                      child: Container(
                        padding: const EdgeInsets.all(6),
                        decoration: BoxDecoration(
                          color: Colors.white.withOpacity(0.08),
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: const Icon(Icons.arrow_back, color: Colors.white, size: 18),
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Text(
                        'Match ${match.matchNumber}: ${match.teamAShort} vs ${match.teamBShort}',
                        style: const TextStyle(color: Colors.white,
                            fontSize: 15, fontWeight: FontWeight.w700, letterSpacing: 0.5),
                      ),
                    ),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                      decoration: BoxDecoration(
                        color: IPLColors.accent.withOpacity(0.15),
                        borderRadius: BorderRadius.circular(6),
                      ),
                      child: Text('$totalPolls votes',
                          style: const TextStyle(color: IPLColors.accent,
                              fontSize: 10, fontWeight: FontWeight.w700)),
                    ),
                  ]),
                  const SizedBox(height: 14),
                  // Teams row
                  Row(children: [
                    Expanded(child: Column(children: [
                      TeamLogo(shortCode: match.teamAShort, logoUrl: match.teamALogo, size: 40),
                      const SizedBox(height: 4),
                      Text(match.teamAShort, style: const TextStyle(
                          color: Colors.white, fontSize: 14, fontWeight: FontWeight.w800)),
                      if (match.scoreA != null)
                        Text(match.scoreA!, style: const TextStyle(
                            color: Colors.white, fontSize: 12, fontWeight: FontWeight.w600)),
                    ])),
                    Column(children: [
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                        decoration: BoxDecoration(
                          color: Colors.white.withOpacity(0.06),
                          borderRadius: BorderRadius.circular(6),
                        ),
                        child: const Text('VS', style: TextStyle(fontSize: 11,
                            fontWeight: FontWeight.w800, color: IPLColors.textMuted,
                            letterSpacing: 2)),
                      ),
                      const SizedBox(height: 4),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                        decoration: BoxDecoration(
                          color: match.isCompleted
                              ? Colors.green.withOpacity(0.15)
                              : IPLColors.accent.withOpacity(0.15),
                          borderRadius: BorderRadius.circular(4),
                        ),
                        child: Text(match.status.toUpperCase(),
                            style: TextStyle(
                                color: match.isCompleted ? Colors.green : IPLColors.accent,
                                fontSize: 9, fontWeight: FontWeight.w700)),
                      ),
                    ]),
                    Expanded(child: Column(children: [
                      TeamLogo(shortCode: match.teamBShort, logoUrl: match.teamBLogo, size: 40),
                      const SizedBox(height: 4),
                      Text(match.teamBShort, style: const TextStyle(
                          color: Colors.white, fontSize: 14, fontWeight: FontWeight.w800)),
                      if (match.scoreB != null)
                        Text(match.scoreB!, style: const TextStyle(
                            color: Colors.white, fontSize: 12, fontWeight: FontWeight.w600)),
                    ])),
                  ]),
                  // (User's prediction hidden from this screen)
                ]),
              );
            },
          ),

          // ── Biddings List ──
          Expanded(
            child: RefreshIndicator(
              color: IPLColors.accent,
              onRefresh: () => ref.refresh(matchPollsProvider(matchId).future),
              child: pollsAsync.when(
                loading: () => const Center(child: CircularProgressIndicator()),
                error: (e, _) => Center(child: Text(ApiService.humanError(e),
                    style: const TextStyle(color: IPLColors.textMuted))),
                data: (data) {
                  final polls = data['polls'] as List? ?? [];
                  if (polls.isEmpty) {
                    return const Center(child: Text('No predictions yet.',
                        style: TextStyle(color: IPLColors.textMuted)));
                  }
                  // Build logo map from match detail
                  final matchData = detailAsync.valueOrNull;
                  Map<String, String?> teamLogos = {};
                  if (matchData != null) {
                    final m = MatchModel.fromJson(matchData['match']);
                    teamLogos[m.teamAShort] = m.teamALogo;
                    teamLogos[m.teamBShort] = m.teamBLogo;
                  }
                  return ListView.builder(
                    padding: const EdgeInsets.all(14),
                    itemCount: polls.length,
                    itemBuilder: (ctx, i) {
                      final poll = polls[i] as Map<String, dynamic>;
                      return _BiddingTile(poll: poll, teamLogos: teamLogos);
                    },
                  );
                },
              ),
            ),
          ),
        ]),
      ),
    );
  }
}

class _BiddingTile extends StatelessWidget {
  final Map<String, dynamic> poll;
  final Map<String, String?> teamLogos;
  const _BiddingTile({required this.poll, this.teamLogos = const {}});

  @override
  Widget build(BuildContext context) {
    final status = (poll['status'] as String?) ?? 'pending';
    final isWon = status == 'won';
    final isLost = status == 'lost';
    final color = isWon ? Colors.greenAccent
        : isLost ? IPLColors.red
        : IPLColors.accent;
    final userName = poll['user_name'] as String? ?? 'User';

    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: IPLColors.cardDark,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: IPLColors.border.withOpacity(0.3)),
      ),
      child: Row(children: [
        CircleAvatar(
          radius: 20,
          backgroundColor: IPLColors.navy,
          child: Text(userName[0].toUpperCase(),
              style: const TextStyle(color: Colors.white,
                  fontSize: 14, fontWeight: FontWeight.w700)),
        ),
        const SizedBox(width: 12),
        Expanded(child: Text(userName,
            style: const TextStyle(color: Colors.white,
                fontWeight: FontWeight.w600, fontSize: 13))),
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
          decoration: BoxDecoration(
            color: color.withOpacity(0.12),
            borderRadius: BorderRadius.circular(4)),
          child: Text(status.toUpperCase(),
              style: TextStyle(color: color, fontSize: 9,
                  fontWeight: FontWeight.w700, letterSpacing: 0.5)),
        ),
      ]),
    );
  }
}

// ─── Reusable Team Logo Widget ───────────────────────────────

class CoinIcon extends StatelessWidget {
  final double size;
  const CoinIcon({super.key, this.size = 16});

  @override
  Widget build(BuildContext context) {
    return Image.asset('assets/icons/coin.png', width: size, height: size);
  }
}

class TeamLogo extends StatelessWidget {
  final String shortCode;
  final String? logoUrl;
  final double size;

  const TeamLogo({
    super.key,
    required this.shortCode,
    this.logoUrl,
    this.size = 56,
  });

  @override
  Widget build(BuildContext context) {
    final color = Color(AppConstants.teamColor(shortCode));
    final hasLogo = logoUrl != null && logoUrl!.isNotEmpty;

    if (hasLogo) {
      return SizedBox(
        width: size,
        height: size,
        child: CachedNetworkImage(
          imageUrl: logoUrl!,
          fit: BoxFit.contain,
          placeholder: (_, __) => _fallbackContainer(color),
          errorWidget: (_, __, ___) => _fallbackContainer(color),
        ),
      );
    }

    return _fallbackContainer(color);
  }

  Widget _fallbackContainer(Color color) => Container(
    width: size,
    height: size,
    decoration: BoxDecoration(
      color: color.withOpacity(0.15),
      shape: BoxShape.circle,
      border: Border.all(color: color.withOpacity(0.4)),
    ),
    alignment: Alignment.center,
    child: Text(
      shortCode,
      style: TextStyle(
        color: color,
        fontWeight: FontWeight.w800,
        fontSize: size * 0.24,
      ),
    ),
  );
}