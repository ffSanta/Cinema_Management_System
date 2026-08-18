import 'package:flutter/material.dart';

import 'movies_screen.dart';
import 'booking_history_screen.dart';
import 'profile_screen.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  int _index = 0;

  // key ไว้สั่งโหลดประวัติการจองใหม่ตอนสลับเข้าแท็บ (IndexedStack ไม่ re-init เอง)
  final _bookingKey = GlobalKey<BookingHistoryScreenState>();

  late final List<Widget> _pages = [
    const MoviesScreen(),
    BookingHistoryScreen(key: _bookingKey),
    const ProfileScreen(),
  ];

  void _onSelect(int i) {
    setState(() => _index = i);
    // เข้าแท็บ "การจอง" → โหลดประวัติล่าสุดเสมอ (เห็นการจองที่เพิ่งทำ)
    if (i == 1) _bookingKey.currentState?.reload();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: IndexedStack(index: _index, children: _pages),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _index,
        onDestinationSelected: _onSelect,
        destinations: const [
          NavigationDestination(
              icon: Icon(Icons.movie_outlined),
              selectedIcon: Icon(Icons.movie),
              label: 'ภาพยนตร์'),
          NavigationDestination(
              icon: Icon(Icons.confirmation_number_outlined),
              selectedIcon: Icon(Icons.confirmation_number),
              label: 'การจอง'),
          NavigationDestination(
              icon: Icon(Icons.person_outline),
              selectedIcon: Icon(Icons.person),
              label: 'โปรไฟล์'),
        ],
      ),
    );
  }
}
