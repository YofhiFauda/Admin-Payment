<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Email - FinanceOps</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f1f5f9; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background-color: #f1f5f9; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="max-width: 520px; margin: 0 auto;">

                    {{-- Logo Header --}}
                    <tr>
                        <td align="center" style="padding-bottom: 32px;">
                            <table cellpadding="0" cellspacing="0" role="presentation">
                                <tr>
                                    <td style="width: 40px; height: 40px; background: linear-gradient(135deg, #6366f1, #9333ea); border-radius: 12px; text-align: center; vertical-align: middle;">
                                        <span style="color: #ffffff; font-size: 18px; font-weight: 900;">F</span>
                                    </td>
                                    <td style="padding-left: 12px;">
                                        <span style="font-size: 22px; font-weight: 800; color: #1e293b; letter-spacing: -0.5px;">FinanceOps</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Main Card --}}
                    <tr>
                        <td style="background-color: #ffffff; border-radius: 24px; padding: 40px 32px; box-shadow: 0 1px 3px rgba(0,0,0,0.05), 0 10px 30px rgba(0,0,0,0.04);">

                            {{-- Icon --}}
                            <div style="text-align: center; margin-bottom: 24px;">
                                <div style="display: inline-block; width: 56px; height: 56px; background: linear-gradient(135deg, #eef2ff, #e0e7ff); border-radius: 16px; line-height: 56px; text-align: center;">
                                    <span style="font-size: 28px;">✉️</span>
                                </div>
                            </div>

                            {{-- Greeting --}}
                            <h1 style="margin: 0 0 8px; font-size: 24px; font-weight: 900; color: #0f172a; text-align: center; letter-spacing: -0.5px;">
                                Selamat Datang, {{ $user->name }}! 🎉
                            </h1>
                            <p style="margin: 0 0 28px; font-size: 14px; color: #64748b; text-align: center; line-height: 1.6;">
                                Akun FinanceOps Anda telah berhasil dibuat. Silakan verifikasi email Anda untuk mulai menggunakan sistem.
                            </p>

                            {{-- Account Info Box --}}
                            <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 16px; padding: 20px 24px; margin-bottom: 28px;">
                                <p style="margin: 0 0 12px; font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px;">Informasi Akun</p>
                                <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                    <tr>
                                        <td style="padding: 6px 0; font-size: 13px; color: #94a3b8; font-weight: 600;">Email</td>
                                        <td style="padding: 6px 0; font-size: 13px; color: #1e293b; font-weight: 700; text-align: right;">{{ $user->email }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 6px 0; font-size: 13px; color: #94a3b8; font-weight: 600;">Password</td>
                                        <td style="padding: 6px 0; font-size: 13px; color: #1e293b; font-weight: 700; text-align: right; font-family: monospace;">{{ $plainPassword }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 6px 0; font-size: 13px; color: #94a3b8; font-weight: 600;">Role</td>
                                        <td style="padding: 6px 0; font-size: 13px; color: #1e293b; font-weight: 700; text-align: right;">{{ ucfirst($user->role) }}</td>
                                    </tr>
                                </table>
                            </div>

                            {{-- CTA Button --}}
                            <div style="text-align: center; margin-bottom: 24px;">
                                <a href="{{ $verifyUrl }}"
                                   style="display: inline-block; padding: 14px 40px; background: linear-gradient(135deg, #6366f1, #9333ea); color: #ffffff; font-size: 14px; font-weight: 800; text-decoration: none; border-radius: 14px; box-shadow: 0 4px 14px rgba(99,102,241,0.3); letter-spacing: 0.3px;">
                                    ✓ Verifikasi Email Saya
                                </a>
                            </div>

                            {{-- Expiry Note --}}
                            <p style="margin: 0 0 20px; font-size: 12px; color: #94a3b8; text-align: center; font-weight: 600;">
                                Link verifikasi berlaku selama 72 jam.
                            </p>

                            {{-- Divider --}}
                            <div style="border-top: 1px solid #e2e8f0; margin: 24px 0;"></div>

                            {{-- Fallback URL --}}
                            <p style="margin: 0; font-size: 11px; color: #94a3b8; line-height: 1.6;">
                                Jika tombol di atas tidak berfungsi, salin dan tempel URL berikut ke browser Anda:<br>
                                <a href="{{ $verifyUrl }}" style="color: #6366f1; word-break: break-all; font-size: 11px;">{{ $verifyUrl }}</a>
                            </p>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="padding-top: 28px; text-align: center;">
                            <p style="margin: 0; font-size: 12px; color: #94a3b8; font-weight: 600;">
                                &copy; 2026 FinanceOps Inc. — Semua hak dilindungi.
                            </p>
                            <p style="margin: 6px 0 0; font-size: 11px; color: #cbd5e1;">
                                Email ini dikirim secara otomatis, mohon jangan dibalas.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
