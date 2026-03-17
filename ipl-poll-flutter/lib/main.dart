// lib/main.dart

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'core/constants.dart';
import 'providers/providers.dart';
import 'screens/login_screen.dart';
import 'screens/change_password_screen.dart';
import 'screens/home_screen.dart';
import 'screens/match_detail_screen.dart';
import 'screens/my_polls_screen.dart';
import 'screens/wallet_screen.dart';
import 'screens/profile_screen.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  SystemChrome.setEnabledSystemUIMode(SystemUiMode.immersiveSticky);
  runApp(const ProviderScope(child: IPLPollApp()));
}

class IPLPollApp extends ConsumerWidget {
  const IPLPollApp({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    return MaterialApp.router(
      title: AppConstants.appName,
      debugShowCheckedModeBanner: false,
      theme: AppTheme.iplTheme(),
      routerConfig: _router(ref),
    );
  }
}

GoRouter _router(WidgetRef ref) => GoRouter(
      initialLocation: '/splash',
      routes: [
        GoRoute(
          path: '/splash',
          builder: (_, __) => const SplashScreen(),
        ),
        GoRoute(
          path: '/login',
          builder: (_, __) => const LoginScreen(),
        ),
        GoRoute(
          path: '/change-password',
          builder: (ctx, state) {
            final isForced = state.extra as bool? ?? false;
            return ChangePasswordScreen(isForced: isForced);
          },
        ),
        ShellRoute(
          builder: (ctx, state, child) => MainShell(child: child),
          routes: [
            GoRoute(path: '/home',        builder: (_, __) => const HomeScreen()),
            GoRoute(path: '/my-polls',    builder: (_, __) => const MyPollsScreen()),
            GoRoute(path: '/wallet',      builder: (_, __) => const WalletScreen()),
            GoRoute(path: '/profile',     builder: (_, __) => const ProfileScreen()),
          ],
        ),
        GoRoute(
          path: '/match/:id',
          builder: (ctx, state) {
            final id = int.parse(state.pathParameters['id']!);
            return MatchDetailScreen(matchId: id);
          },
        ),
      ],
    );

// ─── IPL Color Palette ────────────────────────────────────────

class IPLColors {
  IPLColors._();
  // Primary backgrounds
  static const navy        = Color(0xFF002D72);  // IPL deep blue
  static const darkNavy    = Color(0xFF001D4A);  // Darker variant
  static const deepNavy    = Color(0xFF001233);  // Deepest bg

  // Card / surface
  static const cardDark    = Color(0xFF0A2A4A);  // Card on dark bg
  static const cardLight   = Color(0xFF103456);  // Lighter card

  // Accent
  static const accent      = Color(0xFF00AEEF);  // IPL sky blue
  static const accentLight = Color(0xFF4FC3F7);  // Light blue
  static const red         = Color(0xFFE8344E);  // IPL red

  // Text
  static const textPrimary   = Colors.white;
  static const textSecondary = Color(0xFFB0C4DE);  // Light steel blue
  static const textMuted     = Color(0xFF6889A8);

  // Borders
  static const border      = Color(0xFF1A4066);
  static const borderLight = Color(0xFF2A5580);
}

// ─── Splash Screen ────────────────────────────────────────────

class SplashScreen extends ConsumerStatefulWidget {
  const SplashScreen({super.key});

  @override
  ConsumerState<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends ConsumerState<SplashScreen> {
  @override
  void initState() {
    super.initState();
    _init();
  }

  Future<void> _init() async {
    await Future.delayed(const Duration(milliseconds: 800));
    if (!mounted) return;

    final restored = await ref.read(authProvider.notifier).tryRestoreSession();
    if (!mounted) return;

    if (restored) {
      final user = ref.read(authProvider);
      if (user?.mustChangePassword == true) {
        context.go('/change-password', extra: true);
      } else {
        context.go('/home');
      }
    } else {
      context.go('/login');
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: IPLBackground(
        child: Center(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              ClipRRect(
                borderRadius: BorderRadius.circular(24),
                child: Image.asset('assets/icons/app_icon.png', width: 100, height: 100),
              ),
              const SizedBox(height: 20),
              const Text('IPL POLL 2026',
                  style: TextStyle(fontSize: 26, fontWeight: FontWeight.w800,
                      color: Colors.white, letterSpacing: 1.5)),
              const SizedBox(height: 6),
              const Text('Predict & Win',
                  style: TextStyle(fontSize: 14, color: IPLColors.textSecondary,
                      letterSpacing: 0.5)),
              const SizedBox(height: 40),
              const SizedBox(
                width: 28, height: 28,
                child: CircularProgressIndicator(
                    color: IPLColors.accent, strokeWidth: 2.5),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

// ─── Bottom Navigation Shell ──────────────────────────────────

class MainShell extends StatelessWidget {
  final Widget child;
  const MainShell({super.key, required this.child});

  static const _tabs = [
    _Tab('/home',        Icons.sports_cricket,        'Matches'),
    _Tab('/my-polls',    Icons.how_to_vote,            'My Polls'),
    _Tab('/wallet',      Icons.account_balance_wallet, 'Wallet'),
    _Tab('/profile',     Icons.person,                 'Profile'),
  ];

  @override
  Widget build(BuildContext context) {
    final location = GoRouterState.of(context).uri.toString();
    final currentIndex = _tabs.indexWhere((t) => location.startsWith(t.path));

    final idx = currentIndex < 0 ? 0 : currentIndex;

    return Scaffold(
      body: child,
      bottomNavigationBar: Padding(
        padding: const EdgeInsets.fromLTRB(14, 0, 14, 10),
        child: Container(
          height: 68,
          decoration: BoxDecoration(
            color: IPLColors.cardDark.withOpacity(0.85),
            borderRadius: BorderRadius.circular(28),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withOpacity(0.4),
                blurRadius: 24,
                offset: const Offset(0, 8),
              ),
            ],
            border: Border.all(color: Colors.white.withOpacity(0.08)),
          ),
          child: ClipRRect(
            borderRadius: BorderRadius.circular(28),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceAround,
              children: List.generate(_tabs.length, (i) {
                final selected = idx == i;
                final tab = _tabs[i];
                return GestureDetector(
                  onTap: () => context.go(tab.path),
                  behavior: HitTestBehavior.opaque,
                  child: AnimatedContainer(
                    duration: const Duration(milliseconds: 250),
                    curve: Curves.easeOut,
                    padding: EdgeInsets.symmetric(
                      horizontal: selected ? 20 : 12,
                      vertical: 8,
                    ),
                    decoration: BoxDecoration(
                      borderRadius: BorderRadius.circular(20),
                      color: selected
                          ? IPLColors.accent.withOpacity(0.15)
                          : Colors.transparent,
                      border: selected
                          ? Border.all(color: IPLColors.accent.withOpacity(0.2))
                          : null,
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(tab.icon, size: 20,
                            color: selected ? IPLColors.accent : IPLColors.textMuted),
                        if (selected) ...[
                          const SizedBox(width: 8),
                          Text(tab.label,
                              style: const TextStyle(
                                fontSize: 11,
                                fontWeight: FontWeight.w700,
                                color: IPLColors.accent,
                              )),
                        ],
                      ],
                    ),
                  ),
                );
              }),
            ),
          ),
        ),
      ),
    );
  }
}

class _Tab {
  final String path;
  final IconData icon;
  final String label;
  const _Tab(this.path, this.icon, this.label);
}

// ─── App Theme ────────────────────────────────────────────────

class AppTheme {
  static ThemeData iplTheme() {
    return ThemeData(
      brightness: Brightness.dark,
      scaffoldBackgroundColor: IPLColors.deepNavy,
      fontFamily: 'Roboto',
      colorScheme: const ColorScheme.dark(
        primary:     IPLColors.accent,
        secondary:   IPLColors.red,
        surface:     IPLColors.cardDark,
        onPrimary:   Colors.white,
        onSecondary: Colors.white,
        onSurface:   Colors.white,
      ),
      appBarTheme: const AppBarTheme(
        backgroundColor: IPLColors.darkNavy,
        foregroundColor: Colors.white,
        elevation: 0,
        centerTitle: false,
        titleTextStyle: TextStyle(
          fontSize: 18,
          fontWeight: FontWeight.w700,
          color: Colors.white,
          letterSpacing: 0.3,
        ),
        systemOverlayStyle: SystemUiOverlayStyle(
          statusBarColor: Colors.transparent,
          statusBarIconBrightness: Brightness.light,
        ),
      ),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          backgroundColor: IPLColors.accent,
          foregroundColor: Colors.white,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
          padding: const EdgeInsets.symmetric(vertical: 14),
          textStyle: const TextStyle(fontSize: 15, fontWeight: FontWeight.w700,
              letterSpacing: 0.3),
          elevation: 0,
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          foregroundColor: IPLColors.textSecondary,
          side: const BorderSide(color: IPLColors.border),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
          padding: const EdgeInsets.symmetric(vertical: 14),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: IPLColors.cardDark,
        labelStyle: const TextStyle(color: IPLColors.textMuted),
        hintStyle: const TextStyle(color: IPLColors.textMuted),
        border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(10), borderSide: BorderSide.none),
        enabledBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(10),
            borderSide: const BorderSide(color: IPLColors.border)),
        focusedBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(10),
            borderSide: const BorderSide(color: IPLColors.accent, width: 1.5)),
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
      ),
      cardTheme: CardThemeData(
        color: IPLColors.cardDark,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        elevation: 0,
      ),
      dividerColor: IPLColors.border,
      bottomNavigationBarTheme: const BottomNavigationBarThemeData(
        backgroundColor: IPLColors.deepNavy,
        selectedItemColor: IPLColors.accent,
        unselectedItemColor: IPLColors.textMuted,
      ),
      tabBarTheme: const TabBarThemeData(
        labelColor: Colors.white,
        unselectedLabelColor: IPLColors.textMuted,
        indicatorColor: IPLColors.accent,
        labelStyle: TextStyle(fontSize: 13, fontWeight: FontWeight.w600),
        unselectedLabelStyle: TextStyle(fontSize: 13, fontWeight: FontWeight.w500),
      ),
      progressIndicatorTheme: const ProgressIndicatorThemeData(
        color: IPLColors.accent,
      ),
      sliderTheme: const SliderThemeData(
        activeTrackColor: IPLColors.accent,
        inactiveTrackColor: IPLColors.border,
        thumbColor: IPLColors.accent,
      ),
      snackBarTheme: SnackBarThemeData(
        backgroundColor: IPLColors.cardDark,
        contentTextStyle: const TextStyle(color: Colors.white),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
        behavior: SnackBarBehavior.floating,
      ),
    );
  }
}

// ─── IPL Decorative Background ────────────────────────────────

class IPLBackground extends StatelessWidget {
  final Widget child;
  final bool showTopSpiral;
  final bool showBottomSpiral;
  final bool showGlow;

  const IPLBackground({
    super.key,
    required this.child,
    this.showTopSpiral = true,
    this.showBottomSpiral = true,
    this.showGlow = true,
  });

  @override
  Widget build(BuildContext context) {
    return Stack(
      children: [
        // Base gradient
        Positioned.fill(
          child: Container(
            decoration: const BoxDecoration(
              gradient: LinearGradient(
                begin: Alignment.topCenter,
                end: Alignment.bottomCenter,
                colors: [IPLColors.darkNavy, IPLColors.deepNavy],
              ),
            ),
          ),
        ),

        // Top-right radial glow
        if (showGlow)
          Positioned(
            top: -60,
            right: -60,
            child: Container(
              width: 250,
              height: 250,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                gradient: RadialGradient(
                  colors: [
                    IPLColors.accent.withOpacity(0.07),
                    IPLColors.accent.withOpacity(0.0),
                  ],
                ),
              ),
            ),
          ),

        // Bottom-left radial glow
        if (showGlow)
          Positioned(
            bottom: -40,
            left: -40,
            child: Container(
              width: 200,
              height: 200,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                gradient: RadialGradient(
                  colors: [
                    IPLColors.accent.withOpacity(0.05),
                    IPLColors.accent.withOpacity(0.0),
                  ],
                ),
              ),
            ),
          ),

        // Top spiral decoration
        if (showTopSpiral)
          Positioned(
            top: -20,
            right: -40,
            child: Opacity(
              opacity: 0.06,
              child: Image.asset('assets/images/spiral_top.png',
                  width: 280),
            ),
          ),

        // Bottom spiral decoration
        if (showBottomSpiral)
          Positioned(
            bottom: -30,
            left: -50,
            child: Opacity(
              opacity: 0.06,
              child: Image.asset('assets/images/spiral_bottom.png',
                  width: 320),
            ),
          ),

        // Content
        Positioned.fill(child: child),
      ],
    );
  }
}