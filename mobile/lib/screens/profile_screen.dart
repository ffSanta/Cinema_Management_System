import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../services/api_client.dart';
import '../services/auth_service.dart';
import '../state/auth_provider.dart';

class ProfileScreen extends StatelessWidget {
  const ProfileScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    final user = auth.user;

    return Scaffold(
      appBar: AppBar(title: const Text('โปรไฟล์ของฉัน')),
      body: user == null
          ? const Center(child: CircularProgressIndicator())
          : ListView(
              padding: const EdgeInsets.all(20),
              children: [
                const SizedBox(height: 8),
                Center(
                  child: CircleAvatar(
                    radius: 44,
                    backgroundColor: const Color(0xFFe94560),
                    child: Text(
                      user.name.isNotEmpty ? user.name[0].toUpperCase() : '?',
                      style: const TextStyle(
                          fontSize: 36, fontWeight: FontWeight.bold),
                    ),
                  ),
                ),
                const SizedBox(height: 12),
                Center(
                  child: Text(user.name,
                      style: const TextStyle(
                          fontSize: 22, fontWeight: FontWeight.bold)),
                ),
                Center(
                  child: Text(user.email,
                      style: const TextStyle(color: Colors.white60)),
                ),
                const SizedBox(height: 6),
                Center(
                  child: Chip(
                    label: Text(user.role),
                    visualDensity: VisualDensity.compact,
                  ),
                ),
                const SizedBox(height: 28),
                FilledButton.icon(
                  onPressed: () => Navigator.of(context).push(MaterialPageRoute(
                      builder: (_) => const EditProfileScreen())),
                  icon: const Icon(Icons.edit),
                  label: const Text('แก้ไขโปรไฟล์'),
                ),
                const SizedBox(height: 12),
                OutlinedButton.icon(
                  onPressed: () => _confirmLogout(context),
                  style:
                      OutlinedButton.styleFrom(foregroundColor: Colors.redAccent),
                  icon: const Icon(Icons.logout),
                  label: const Text('ออกจากระบบ'),
                ),
              ],
            ),
    );
  }

  Future<void> _confirmLogout(BuildContext context) async {
    final auth = context.read<AuthProvider>();
    final ok = await showDialog<bool>(
      context: context,
      builder: (_) => AlertDialog(
        title: const Text('ออกจากระบบ'),
        content: const Text('ต้องการออกจากระบบใช่หรือไม่?'),
        actions: [
          TextButton(
              onPressed: () => Navigator.pop(context, false),
              child: const Text('ยกเลิก')),
          FilledButton(
              onPressed: () => Navigator.pop(context, true),
              child: const Text('ออกจากระบบ')),
        ],
      ),
    );
    if (ok == true) await auth.logout();
  }
}

/// หน้าแก้ไขโปรไฟล์ (ชื่อ/อีเมล + เปลี่ยนรหัสผ่านถ้าต้องการ)
class EditProfileScreen extends StatefulWidget {
  const EditProfileScreen({super.key});

  @override
  State<EditProfileScreen> createState() => _EditProfileScreenState();
}

class _EditProfileScreenState extends State<EditProfileScreen> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _name;
  late final TextEditingController _email;
  final _password = TextEditingController();
  final _confirm = TextEditingController();
  bool _loading = false;

  @override
  void initState() {
    super.initState();
    final user = context.read<AuthProvider>().user;
    _name = TextEditingController(text: user?.name ?? '');
    _email = TextEditingController(text: user?.email ?? '');
  }

  @override
  void dispose() {
    _name.dispose();
    _email.dispose();
    _password.dispose();
    _confirm.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    final messenger = ScaffoldMessenger.of(context);
    final navigator = Navigator.of(context);
    final auth = context.read<AuthProvider>();
    final authService = context.read<AuthService>();
    setState(() => _loading = true);
    try {
      final updated = await authService.updateProfile(
        name: _name.text.trim(),
        email: _email.text.trim(),
        password: _password.text.isEmpty ? null : _password.text,
        passwordConfirmation: _confirm.text,
      );
      auth.updateUser(updated);
      messenger.showSnackBar(
          const SnackBar(content: Text('อัปเดตโปรไฟล์เรียบร้อยแล้ว')));
      navigator.pop();
    } on ApiException catch (e) {
      final msg = e.errors.isNotEmpty ? e.errors.values.first.first : e.message;
      messenger.showSnackBar(SnackBar(content: Text(msg)));
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('แก้ไขโปรไฟล์')),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              TextFormField(
                controller: _name,
                decoration: const InputDecoration(
                    labelText: 'ชื่อ', border: OutlineInputBorder()),
                validator: (v) =>
                    (v == null || v.trim().isEmpty) ? 'กรุณากรอกชื่อ' : null,
              ),
              const SizedBox(height: 16),
              TextFormField(
                controller: _email,
                keyboardType: TextInputType.emailAddress,
                decoration: const InputDecoration(
                    labelText: 'อีเมล', border: OutlineInputBorder()),
                validator: (v) =>
                    (v == null || v.trim().isEmpty) ? 'กรุณากรอกอีเมล' : null,
              ),
              const SizedBox(height: 24),
              const Text('เปลี่ยนรหัสผ่าน (เว้นว่างถ้าไม่เปลี่ยน)',
                  style: TextStyle(color: Colors.white60, fontSize: 13)),
              const SizedBox(height: 8),
              TextFormField(
                controller: _password,
                obscureText: true,
                decoration: const InputDecoration(
                    labelText: 'รหัสผ่านใหม่', border: OutlineInputBorder()),
                validator: (v) => (v != null && v.isNotEmpty && v.length < 8)
                    ? 'รหัสผ่านอย่างน้อย 8 ตัวอักษร'
                    : null,
              ),
              const SizedBox(height: 16),
              TextFormField(
                controller: _confirm,
                obscureText: true,
                decoration: const InputDecoration(
                    labelText: 'ยืนยันรหัสผ่านใหม่',
                    border: OutlineInputBorder()),
                validator: (v) =>
                    (_password.text.isNotEmpty && v != _password.text)
                        ? 'ยืนยันรหัสผ่านไม่ตรงกัน'
                        : null,
              ),
              const SizedBox(height: 24),
              FilledButton(
                onPressed: _loading ? null : _submit,
                style: FilledButton.styleFrom(
                    padding: const EdgeInsets.symmetric(vertical: 14)),
                child: _loading
                    ? const SizedBox(
                        height: 20,
                        width: 20,
                        child: CircularProgressIndicator(strokeWidth: 2))
                    : const Text('บันทึก'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
