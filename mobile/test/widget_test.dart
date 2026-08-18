// Smoke test พื้นฐาน
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  testWidgets('renders a MaterialApp', (WidgetTester tester) async {
    await tester.pumpWidget(
      const MaterialApp(home: Scaffold(body: Text('Cinema'))),
    );
    expect(find.text('Cinema'), findsOneWidget);
  });
}
