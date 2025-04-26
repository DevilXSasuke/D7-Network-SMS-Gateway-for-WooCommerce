📦 D7 Network SMS Gateway for WooCommerce
D7 Network SMS Gateway is a WordPress plugin that enables SMS notifications for WooCommerce stores using the D7 Networks API.
It provides per-order status control, customizable SMS templates for customers and admins, and full logging of all sent messages.

✨ Features
🔒 Secure Access Token authentication (hidden input)
🛒 Send SMS on WooCommerce order status changes
📄 Different SMS templates for Customers and Admins per order status
✅ Enable/Disable SMS per status individually
🔔 Admin and Customer notifications separately
🔎 SMS Logs (phone, recipient, message, status, timestamp)
🧪 Test SMS feature for quick testing
📋 Automatic database table creation on activation
🚫 Safe fallback for old database rows (no warnings)
📈 Supports all standard WooCommerce order statuses
🌐 Compatible with latest WordPress and WooCommerce

🔧 Installation
Upload the plugin to your WordPress /wp-content/plugins/ directory.
Activate the plugin from WordPress Admin → Plugins.
Go to D7 SMS Settings menu.
Enter your Access Token and Sender ID.
Customize your notification settings and templates.

🛠 Requirements
WordPress 5.0+
WooCommerce 4.0+
D7 Networks account (with API token)

📋 Placeholder Variables Available in Templates
You can use these placeholders in your SMS templates:
{site_title}
{order_id}
{order_status}
{order_total}
{billing_name}
{shipping_name}
{shipping_method}
{additional_notes}
{order_date}

🚀 Future Improvements (coming soon)
SMS retry on failure
Export logs to CSV
Resend SMS button from logs
Multilingual (Arabic / English templates)

📄 License
Released under the MIT License.
