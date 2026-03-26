<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
</head>
<body style="font-family: Arial, sans-serif; background-color: #f7f7f7; margin: 0; padding: 20px;">
    <div style="max-width: 480px; margin: 0 auto; background: #ffffff; border-radius: 8px; padding: 40px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <h1 style="text-align: center; color: #1a1a1a; font-size: 24px; margin-bottom: 8px;">RYDEEN</h1>
        <p style="text-align: center; color: #666; font-size: 14px; margin-bottom: 32px;">Dealer Portal</p>

        <p style="color: #333; font-size: 16px;">Hi {{ $dealer->first_name }},</p>

        <p style="color: #333; font-size: 16px;">Your dealer account has been approved. You now have full access to the Rydeen Dealer Portal where you can browse our catalog, place orders, and access dealer resources.</p>

        <div style="text-align: center; margin: 32px 0;">
            <a href="{{ route('dealer.login') }}" style="display: inline-block; background: #FFD200; color: #000000; padding: 14px 32px; text-decoration: none; border-radius: 6px; font-size: 16px; font-weight: bold;">Log In to Your Account</a>
        </div>

        <p style="color: #666; font-size: 14px; margin-top: 24px;">
            If you have any questions, please contact us at {{ config('rydeen.admin_order_email') }}.
        </p>

        <p style="color: #999; font-size: 12px; text-align: center; margin-top: 32px;">
            &mdash; Rydeen
        </p>
    </div>
</body>
</html>
